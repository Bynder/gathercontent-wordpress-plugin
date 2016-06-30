<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\API;

class Push extends Base {

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( API $api ) {
		parent::__construct( $api, new Async_Push_Action() );
	}

	public function init_hooks() {
		add_action( 'wp_async_gc_push_items', array( $this, 'push_items' ) );
	}

	public function push_items( $mapping ) {
		// @todo Use mapping to map WP data to GC data
		throw new Exception( '@todo' );

		try {

			$result = $this->push_item( $post_id );

			// Then trigger the next async request
			do_action( 'gc_push_items', $mapping );

		} catch ( Exception $e ) {
			$result = new WP_Error( 'gc_push_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	public function maybe_push_item( $post_id ) {
		try {
			return $this->push_item( $post_id );
		} catch ( Exception $e ) {
			return new WP_Error( 'gc_push_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}
	}

	protected function push_item( $post_id ) {
		$post = $this->get_post( $post_id );

		$item_id = $this->get_post_item_id( $post->ID );

		$item = $this->get_item( $item_id );

		$mapping = $this->get_item_mapping( $item );

		$data = $this->map_wp_data_to_gc_data( $mapping->posts[0], $post, $item );

		$item = $this->api->save_item( $item_id, $data );
	}
}
