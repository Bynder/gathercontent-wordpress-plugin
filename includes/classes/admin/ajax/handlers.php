<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\General;
use GatherContent\Importer\Utils;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;

class Handlers extends Plugin_Base {

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	public $api;

	/**
	 * Sync_Items instance
	 *
	 * @var Sync_Items
	 */
	public $sync_items;

	/**
	 * Sync_Bulk instance
	 *
	 * @var Sync_Bulk
	 */
	public $sync_bulk;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api      API object
	 */
	public function __construct( API $api ) {
		$this->api = $api;
		$this->sync_items = new Sync_Items();
		$this->sync_bulk = new Sync_Bulk();
	}

	public function init_hooks() {
		add_action( 'wp_ajax_gc_get_option_data', array( $this, 'gc_get_option_data_cb' ) );
		add_action( 'wp_ajax_gc_sync_items', array( $this->sync_items, 'gc_sync_items_cb' ) );
		add_action( 'wp_ajax_gc_pull_items', array( $this->sync_bulk, 'gc_pull_items_cb' ) );
		add_action( 'wp_ajax_gc_push_items', array( $this->sync_bulk, 'gc_push_items_cb' ) );
		add_action( 'wp_ajax_gc_get_posts', array( $this, 'gc_get_posts_cb' ) );
		add_action( 'wp_ajax_gc_get_post_statuses', array( $this, 'gc_get_post_statuses_cb' ) );
		add_action( 'wp_ajax_set_gc_status', array( $this, 'set_gc_status_cb' ) );
		add_action( 'wp_ajax_gc_fetch_js_post', array( $this, 'gc_fetch_js_post_cb' ) );
		add_action( 'wp_ajax_gc_wp_filter_mappings', array( $this, 'gc_wp_filter_mappings_cb' ) );
		add_action( 'wp_ajax_gc_save_mapping_id', array( $this, 'gc_save_mapping_id_cb' ) );
	}

	public function gc_get_option_data_cb() {
		if ( ! $this->_get_val( 'q' ) || ! $this->_get_val( 'column' ) ) {
			wp_send_json_error();
		}

		$search_term = sanitize_text_field( trim( $this->_get_val( 'q' ) ) );

		if ( ! $search_term ) {
			wp_send_json_error();
		}

		$method = $this->_get_val( 'column' );

		switch ( $method ) {
			case 'post_author':
				if ( $results = $this->$method( $search_term ) ) {
					wp_send_json( $results );
				}
				break;
		}

		wp_send_json_error();
	}

	public function gc_get_posts_cb() {
		$posts = $this->_post_val( 'posts' );
		if ( empty( $posts ) || ! is_array( $posts ) ) {
			wp_send_json_error();
		}

		$post_updates = array();

		foreach ( $posts as $key => $post ) {
			if ( empty( $post['id'] ) ) {
				continue;
			}

			$post = wp_parse_args( $post, array(
				'id' => 0,
				'item' => 0,
			) );

			$status = (object) array();
			if ( $post['item'] ) {
				$item = $this->api->uncached()->get_item( $post['item'] );

				if ( isset( $item->status->data ) ) {
					$status = $item->status->data;
				}
			}

			$post_updates[ $post['id'] ] = array(
				'id'       => $post['id'],
				'status'   => $status,
				'itemName' => isset( $item->name ) ? $item->name : __( 'N/A', 'gathercontent-importer' ),
				'updated' => isset( $item->updated_at )
					? Utils::relative_date( $item->updated_at->date )
					: __( '&mdash;', 'gathercontent-importer' ),
				'current' => \GatherContent\Importer\post_is_current( $post['id'], $item ),
			);
		}

		wp_send_json_success( apply_filters( 'gc_prepare_js_update_data_for_posts', $post_updates ) );
	}

	public function gc_get_post_statuses_cb() {
		$postId = $this->_post_val( 'postId' );
		if ( empty( $postId ) || ! ( $post = get_post( $postId ) ) ) {
			wp_send_json_error( compact( 'postId' ) );
		}

		$item_id = absint( \GatherContent\Importer\get_post_item_id( $postId ) );
		$mapping_id = absint( \GatherContent\Importer\get_post_mapping_id( $postId ) );

		if (
			empty( $item_id )
			|| empty( $mapping_id )
			|| ! ( $mapping = Mapping_Post::get( $mapping_id ) )
			|| ! ( $project = $mapping->get_project() )
			|| ! ( $statuses = $this->api->get_project_statuses( $project ) )
		) {
			wp_send_json_error( compact( 'postId' ) );
		}

		wp_send_json_success( compact( 'postId', 'statuses' ) );
	}

	public function set_gc_status_cb() {
		$post_data = $this->_post_val( 'post' );
		$status = absint( $this->_post_val( 'status' ) );
		$nonce = $this->_post_val( 'nonce' );

		if ( empty( $post_data ) || empty( $status ) || ! $this->verify_nonce( $nonce ) ) {
			wp_send_json_error();
		}

		$item_id = isset( $post_data['item'] ) ? absint( $post_data['item'] ) : 0;

		if ( ! $item_id ) {
			wp_send_json_error();
		}

		if ( $this->api->set_item_status( $item_id, $status ) ) {
			wp_send_json_success( compact( 'status' ) );
		}

		wp_send_json_error();
	}

	public function gc_fetch_js_post_cb() {
		if ( $post_id = $this->_get_val( 'id' ) ) {
			wp_send_json( \GatherContent\Importer\prepare_post_for_js(
				absint( $post_id ),
				'force' === $this->_get_val( 'flush_cache' )
			) );
		}
	}

	public function gc_save_mapping_id_cb() {
		$post_data = $this->_post_val( 'post' );

		if ( empty( $post_data['id'] ) || empty( $post_data['mapping'] ) || ! $this->verify_nonce( $this->_post_val( 'nonce' ) ) ) {
			wp_send_json_error();
		}

		try {
			$mapping = Mapping_Post::get( absint( $post_data['mapping'] ), true );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

		if ( \GatherContent\Importer\update_post_mapping_id( absint( $post_data['id'] ), $mapping->ID ) ) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	public function gc_wp_filter_mappings_cb() {
		$post_data = $this->_post_val( 'post' );
		$property = $this->_post_val( 'property' );

		if ( empty( $post_data['id'] ) || empty( $property ) || ! $this->verify_nonce( $this->_post_val( 'nonce' ) ) ) {
			wp_send_json_error();
		}

		$mappings = General::get_instance()->admin->mapping_wizzard->mappings;
		$objects = array();

		switch ( $property ) {
			case 'mapping':
				if ( ! isset( $post_data['project'], $post_data['projects'] ) ) {
					wp_send_json_error( esc_html__( 'Missing required project id.', 'gathercontent-importer' ) );
				}

				$mapping_ids = array();
				foreach ( $post_data['projects'] as $project ) {
					$mapping_ids = array_merge( $mapping_ids, $project['mappings'] );
				}

				$objects = $mappings->get_project_mappings(
					absint( $post_data['project'] ),
					array_unique( $mapping_ids )
				);
				break;

			case 'project':
				if ( ! isset( $post_data['account'], $post_data['accounts'] ) ) {
					wp_send_json_error( esc_html__( 'Missing required account id.', 'gathercontent-importer' ) );
				}

				$mapping_ids = array();
				foreach ( $post_data['accounts'] as $account ) {
					$mapping_ids = array_merge( $mapping_ids, $account['mappings'] );
				}

				$objects = $mappings->get_account_projects_with_mappings(
					absint( $post_data['account'] ),
					array_unique( $mapping_ids )
				);

				break;

			case 'account':
			default:
				$objects = $mappings->get_accounts_with_mappings();
				break;
		}

		if ( is_wp_error( $objects ) ) {
			wp_send_json_error( $objects->get_error_message() );
		}

		if ( ! empty( $objects ) ) {
			wp_send_json_success( array_values( $objects ) );
		}

		wp_send_json_error();
	}

	/*
	 * Non-callback methods.
	 */

	protected function post_author( $search_term ) {
		if ( ! \GatherContent\Importer\user_allowed() ) {
			wp_send_json_error();
		}

		$users = get_users( array(
			'search' => '*' . $search_term . '*',
			'number' => 30,
		) );

		$users = array_map( function( $user ) {
			return array(
				'text' => $user->user_login,
				'id'   => $user->ID,
			);
		}, $users );

		return array( 'results' => $users );
	}

	public function verify_nonce( $nonce ) {
		return wp_verify_nonce( $nonce, GATHERCONTENT_SLUG );
	}

}
