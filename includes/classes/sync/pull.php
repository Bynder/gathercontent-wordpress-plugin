<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\API;
use WP_Error;

/**
 * @todo  Add media importing.
 *
 *
 */
class Pull extends Base {

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( API $api, Template_Mappings $mappings ) {
		parent::__construct( $api, $mappings, new Async_Pull_Action() );
	}

	public function init_hooks() {
		add_action( 'wp_async_gc_pull_items', array( $this, 'pull_items' ) );

		if ( isset( $_GET['jtdebug'] ) ) {
			// do_action( 'wp_async_gc_pull_items', get_post( 33 ) );
			wp_die( '<xmp>maybe_pull_item: '. print_r( $this->maybe_pull_item( 33, 2861687 ), true ) .'</xmp>' );
		}

	}

	public function pull_items( $mapping ) {
		try {

			$mapping_data = $this->get_mapping_data( $mapping );
			$items = $this->get_items_to_pull( $mapping->ID );

		} catch ( Exception $e ) {
			$error = new WP_Error( 'gc_pull_items_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
			// @todo remove
			wp_die( '<xmp>$error: '. print_r( $error, true ) .'</xmp>' );
		}

		try {

			$item = array_shift( $items['pending'] );

			$result = $this->pull_item( $mapping, $item );

			$items['complete'] = isset( $items['complete'] ) ? $items['complete'] : array();
			$items['complete'][] = $item;

			$this->mappings->update_items_to_sync( $mapping->ID, $items );

			// If we have more items
			if ( ! empty( $items['pending'] ) ) {
				// Then trigger the next async request
				do_action( 'gc_pull_items', $mapping );
			}

		} catch ( Exception $e ) {
			$result = new WP_Error( 'gc_pull_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	public function maybe_pull_item( $mapping, $item_id ) {
		try {
			$result = $this->pull_item( $mapping, $item_id );
		} catch ( Exception $e ) {
			$result = new WP_Error( 'gc_pull_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	protected function pull_item( $mapping, $item_id ) {
		$mapping = $this->get_post( $mapping );

		if ( ! $mapping || ! isset( $mapping->ID ) ) {
			throw new Exception( sprintf( __( 'No mapping object found for: %s' ), print_r( $mapping, true ) ), __LINE__ );
		}

		$mapping_data = $this->get_mapping_data( $mapping );

		$item = $this->get_item( $item_id );

		$post_data = array();

		if ( $existing = \GatherContent\Importer\get_posts_by_item_id( $item_id ) ) {
			$post_data = (array) $existing;
		} else {
			$post_data['ID'] = 0;
		}

		$post_data = $this->map_gc_data_to_wp_data( $mapping, $item, $post_data );
		// wp_die( '<xmp>'. print_r( get_defined_vars(), true ) .'</xmp>' );

		$post_id = wp_insert_post( $post_data, 1 );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message(), __LINE__, $post_id->get_error_data() );
		}

		// Store item ID reference to post-meta.
		\GatherContent\Importer\update_post_item_id( $post_id, $item_id );

		if ( $gc_status = $mapping->mapping->get( 'gc_status' ) ) {
			// Update the GC item status.
			$this->api->set_item_status( $item_id, $gc_status );
		}

		return $post_id;
	}

	protected function map_gc_data_to_wp_data( \WP_Post $post, $item, $post_data = array() ) {
		$this->get_mapping_data( $post );
		$mapping = $post->mapping;

		foreach ( array( 'post_author', 'post_status', 'post_type' ) as $key ) {
			$post_data[ $key ] = $mapping->get( $key );
		}

		$files = $this->api->get_item_files( $item->id );
		// @todo disable cache for these requests
		// $files = $this->api->uncached()->get_item_files( $item->id );

		$item->files = array();
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$item->files[ $file->field ][] = $file;
			}
		}

		if ( isset( $item->config ) && $item->config ) {
			foreach ( $item->config as $tab ) {
				if ( isset( $tab->elements ) && $tab->elements ) {
					foreach ( $tab->elements as $element ) {
						$destination = $mapping->get( $element->name );
						if ( ! $destination  ) {
							break;
						}

						$element->value = $this->get_element_value( $element, $item );

						try {
							switch ( $destination['type'] ) {
								case 'wp-type-post':
									$field = $destination['value'];
									$value = $this->sanitize_field( $field, $element->value, $post_data['ID'] );
									$post_data[ $field ] = $value;
									break;

								case 'wp-type-taxonomy':
									$taxonomy = $destination['value'];
									$terms = $this->get_element_terms( $taxonomy, $element, $item );
									if ( ! empty( $terms ) ) {
										if ( 'category' === $taxonomy ) {
											$post_data['post_category'] = $terms;
										} else {
											$post_data['tax_input'][ $taxonomy ] = $terms;
										}
									}
									break;

								case 'wp-type-meta':
									$meta_key = $destination['value'];
									$post_data['meta_input'][ $meta_key ] = $this->sanitize_element_meta( $element, $item );
									break;
							}
						} catch ( Exception $e ) {

						}
					}
				}
			}
		}

		// wp_die( '<xmp>$post_data: '. print_r( $post_data, true ) .'</xmp>' );
		print( '<xmp>$post_data: '. print_r( $post_data, true ) .'</xmp>' );
		return $post_data;
	}

}
