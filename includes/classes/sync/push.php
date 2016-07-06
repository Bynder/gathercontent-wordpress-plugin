<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;

class Push extends Base {

	/**
	 * Sync direction.
	 *
	 * @var string
	 */
	protected $direction = 'push';

	/**
	 * Post object to push.
	 *
	 * @var int
	 */
	protected $post = null;

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
		add_action( 'wp_async_gc_push_items', array( $this, 'sync_items' ) );

		if ( isset( $_GET['test_push'] ) ) {

			wp_die( '<xmp>maybe_push_item: '. print_r( $this->maybe_push_item( 38 ), true ) .'</xmp>' );
		}

	}

	public function maybe_push_item( $post_id ) {
		try {
			$post = $this->get_post( $post_id );
			$mapping_id = \GatherContent\Importer\get_post_mapping_id( $post->ID );
			$this->mapping = Mapping_Post::get( $mapping_id, true );
			$result = $this->do_item( $post->ID );
		} catch ( Exception $e ) {
			$result = new WP_Error( 'gc_push_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	protected function do_item( $id ) {
		$this->post = $this->get_post( $id );

		$this->check_mapping_data( $this->mapping );

		$this->set_item( \GatherContent\Importer\get_post_item_id( $this->post->ID ) );
		wp_die( '<xmp>'. __LINE__ .') $this->item: '. print_r( $this->item, true ) .'</xmp>' );

		$config = $this->map_wp_data_to_gc_data( $post_data );

		return $post_id;
	}

	protected function set_item( $item_id ) {
		if ( isset( $_GET['test_push'] ) ) {
			echo '<xmp>$item_id: '. print_r( $item_id, true ) .'</xmp>';
		}

		// Let's create an item, if it doesn't exist.
		if ( ! $item_id ) {
			$item_id = $this->api->create_item(
				$this->mapping->get_project(),
				$this->mapping->get_template(),
				$this->post->post_title
			);

			if ( $item_id ) {
				// Let's map the new item back to the post.
				\GatherContent\Importer\update_post_item_id( $this->post->ID, $item_id );
			}
		}

		if ( ! $item_id ) {
			// @todo maybe check if error was temporary and try again?
			throw new Exception( sprintf( __( 'No item found or created for that post ID:', 'gathercontent-import' ), $this->post->ID ), __LINE__, array(
				'post_id'    => $this->post->ID,
				'mapping_id' => $this->mapping->ID,
			) );
		}

		$item = parent::set_item( $item_id );

		\GatherContent\Importer\update_post_item_meta( $item_id, array(
			'created_at' => $item->created_at,
			'updated_at' => $item->updated_at,
		) );

		return $item;
	}

	// protected function do_item( $post_id ) {
	// 	$post = $this->get_post( $post_id );

	// 	$data = $this->map_wp_data_to_gc_data( $mapping->posts[0], $post, $item );

	// 	$item = $this->api->save_item( $item_id, $data );
	// }

	protected function map_wp_data_to_gc_data( WP_Post $mapping, WP_Post $post, $item ) {
		$config = $this->item->config;

		// @todo Use mapping to map WP data to GC data
		throw new Exception( '@todo' );


		return $config;
	}

}
