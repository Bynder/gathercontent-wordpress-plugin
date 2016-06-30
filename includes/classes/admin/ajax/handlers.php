<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;
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
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api      API object
	 */
	public function __construct( API $api ) {
		$this->api = $api;
		$this->sync_items = new Sync_Items();
	}

	public function init_hooks() {
		add_action( 'wp_ajax_gc_get_option_data', array( $this, 'select2_field_data_callback' ) );
		add_action( 'wp_ajax_gc_sync_items', array( $this->sync_items, 'callback' ) );
		add_action( 'wp_ajax_gc_get_items', array( $this, 'get_items_callback' ) );
		add_action( 'wp_ajax_gc_get_post_statuses', array( $this, 'post_statuses_callback' ) );
	}

	public function select2_field_data_callback() {
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

	public function get_items_callback() {
		$posts = $this->_post_val( 'posts' );
		if ( empty( $posts ) || ! is_array( $posts ) ) {
			wp_send_json_error();
		}

		foreach ( $posts as $key => $post ) {
			if ( empty( $post['id'] ) ) {
				continue;
			}

			$post = wp_parse_args( $post, array(
				'id' => 0,
				'item' => 0,
				'mapping' => 0,
			) );

			if ( $post['item'] ) {
				$item = $this->api->uncached()->get_item( $post['item'] );

				if ( isset( $item->status->data ) ) {
					$post['status'] = $item->status->data;
				}
			}

			$posts[ $key ] = $post;
		}

		wp_send_json_success( $posts );
	}

	public function post_statuses_callback() {
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

	/*
	 * Non-callback methods.
	 */

	protected function post_author( $search_term ) {
		if ( ! apply_filters( 'gathercontent_settings_view_capability', 'publish_pages' ) ) {
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
}
