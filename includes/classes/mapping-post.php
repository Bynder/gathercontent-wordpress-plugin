<?php
namespace GatherContent\Importer;
use GatherContent\Importer\Post_Types\Template_Mappings;
use WP_Post;

class Mapping_Post_Exception extends \Exception {
	protected $data;

	public function __construct( $message, $code, $data = null ) {
		parent::__construct( $message, $code );
		if ( null !== $data ) {
			$this->data = $data;
		}
	}

	public function get_data() {
		return $this->data;
	}
}

class Mapping_Post extends Base {

	/**
	 * Array of Mapping_Post objects
	 *
	 * @var Mapping_Post[]
	 */
	protected static $instances;

	/**
	 * WP_Post object
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Array of mapping data or false.
	 *
	 * @var array|false
	 */
	protected $data = false;

	/**
	 * Get a Mapping_Post object instance by Post ID/object
	 *
	 * @since  3.0.0
	 *
	 * @param  WP_Post|int $post WP_Post object or ID
	 *
	 * @return Mapping_Post|false Will return false if $post is not found or not a template-mapping post.
	 */
	public static function get( $post, $throw_error = false ) {
		if ( $post instanceof Mapping_Post ) {
			return $post;
		}

		try {

			$post = self::get_post( $post );

			if ( ! isset( self::$instances[ $post->ID ] ) ) {
				self::$instances[ $post->ID ] = new self( $post );
			}

			return self::$instances[ $post->ID ];

		} catch( \Exception $e ) {
			if ( $throw_error ) {
				throw $e;
			}
			return false;
		}
	}

	protected static function get_post( $post ) {
		$post = $post instanceof WP_Post ? $post : get_post( $post );

		if ( ! $post ) {
			throw new Mapping_Post_Exception( __CLASS__ .' expects a WP_Post object or post ID.', __LINE__, $post );
		}

		if ( $post->post_type !== Template_Mappings::SLUG ) {
			throw new Mapping_Post_Exception( __CLASS__ .' expects a '. Template_Mappings::SLUG .' object.', __LINE__, $post );
		}

		return $post;
	}

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $post
	 */
	protected function __construct( WP_Post $post ) {
		$this->post = $post;
		$this->init_data( $post );
	}

	protected function init_data( $post ) {
		if ( ! isset( $post->post_content ) || empty( $post->post_content ) ) {
			return;
		}

		$json = json_decode( $post->post_content, 1 );

		if ( is_array( $json ) ) {
			$this->data = $json;

			if ( isset( $this->data['mapping'] ) && is_array( $this->data['mapping'] ) ) {
				$_mapping = $this->data['mapping'];
				unset( $this->data['mapping'] );
				$this->data += $_mapping;
			}
		}
	}

	public function data( $arg = null, $sub_arg = null ) {
		if ( null === $arg ) {
			return $this->data;
		}

		if ( ! isset( $this->data[ $arg ] ) ) {
			return false;
		}

		$destination = $this->data[ $arg ];

		if ( isset( $destination['type'] ) ) {
			// Trim qualifiers (wpseo, acf, cmb2, etc)
			$type = explode( '--', $destination['type'] );
			$destination['type'] = $type[0];
		}

		if ( $sub_arg ) {
			return is_array( $destination ) && isset( $destination[ $sub_arg ] ) ? $destination[ $sub_arg ] : false;
		}

		return $destination;
	}

	public function get_wp_status_for_item( $item ) {
		$status_id = isset( $item->custom_state_id ) ? $item->custom_state_id : $item;
		if ( $gc_status = $this->data( 'gc_status', $status_id ) ) {
			if ( ! empty( $gc_status['wp'] ) ) {
				return sanitize_text_field( $gc_status['wp'] );
			}
		}
		return false;
	}

	public function get_item_new_status( $item ) {
		$status_id = isset( $item->custom_state_id ) ? $item->custom_state_id : $item;
		if ( $gc_status = $this->data( 'gc_status', $status_id ) ) {
			if ( ! empty( $gc_status['after'] ) ) {
				return absint( $gc_status['after'] );
			}
		}
		return false;
	}

	public function update_meta( $key, $value ) {
		return update_post_meta( $this->post->ID, $key, $value );
	}

	protected function get_meta( $key ) {
		return get_post_meta( $this->post->ID, $key, 1 );
	}

	protected function delete_meta( $key ) {
		return delete_post_meta( $this->post->ID, $key );
	}

	public function get_template() {
		return $this->get_meta( '_gc_template' );
	}

	public function get_project() {
		return $this->get_meta( '_gc_project' );
	}

	public function get_account_id() {
		return $this->get_meta( '_gc_account_id' );
	}

	public function get_account_slug() {
		return $this->get_meta( '_gc_account' );
	}

	public function get_items_to_pull() {
		return $this->get_items_to_sync();
	}

	public function update_items_to_pull( $items ) {
		return $this->update_items_to_sync( $items );
	}

	public function get_pull_percent() {
		return $this->get_sync_percent();
	}

	public function get_items_to_push() {
		return $this->get_items_to_sync( 'push' );
	}

	public function update_items_to_push( $items ) {
		return $this->update_items_to_sync( $items, 'push' );
	}

	public function get_push_percent() {
		return $this->get_sync_percent( 'push' );
	}

	public function get_items_to_sync( $direction = 'pull'  ) {
		$items = $this->get_meta( "_gc_{$direction}_items" );
		return is_array( $items ) ? $items : array();
	}

	public function update_items_to_sync( $items, $direction = 'pull' ) {
		if ( empty( $items ) || empty( $items['pending'] ) ) {
			return $this->delete_meta( "_gc_{$direction}_items" );
		}

		return $this->update_meta( "_gc_{$direction}_items", $items );
	}

	public function get_sync_percent( $direction = 'pull'  ) {
		$percent = 1;

		$items = $this->get_items_to_sync( $direction );

		if ( ! empty( $items ) ) {

			if ( empty( $items['pending'] ) ) {
				$this->delete_meta( "_gc_{$direction}_items" );
			} else {

				$pending_count = count( $items['pending'] );
				$done_count = ! empty( $items['complete'] ) ? count( $items['complete'] ) : 0;

				$percent = $done_count / ( $pending_count + $done_count );
			}
		}

		return round( $percent * 100 );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $property
	 * @throws Mapping_Post_Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $property ) {

		switch ( $property ) {
			case 'post':
			case 'data':
				return $this->{$property}();

			default:
				// Check post object for property
				// In general, we'll avoid using same-named properties,
				// so the post object properties are always available.
				if ( isset( $this->post->{$property} ) ) {
					return $this->post->{$property};
				}
				throw new Mapping_Post_Exception( 'Invalid ' . __CLASS__ . ' property: ' . $property );
		}
	}

	/**
	 * Magic isset checker for our object.
	 *
	 * @param string $property
	 * @return bool
	 */
	public function __isset( $property ) {
		// Check post object for property
		// In general, we'll avoid using same-named properties,
		// so the post object properties are always available.
		return isset( $this->post->{$property} );
	}

}
