<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
use WP_Error;

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
	 * Array of field types completed.
	 *
	 * @var array
	 */
	protected $done = array();

	/**
	 * A json-encoded reference to the original Item config object,
	 * before transformation for the update.
	 *
	 * @var string
	 */
	protected $config = array();

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
			// wp_die( '<xmp>'. __LINE__ .') create_item $item_id: '. print_r( $this->api->create_item( 73849, 347939, 'HEYO!' ), true ) .'</xmp>' );
			wp_die( '<xmp>maybe_push_item $result: '. print_r( $this->maybe_push_item( 138 ), true ) .'</xmp>' );
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

		$config_update = $this->map_wp_data_to_gc_data();

		// No updated data, so bail.
		if ( empty( $config_update ) ) {
			throw new Exception( sprintf( __( 'No update data found for that post ID: %d', 'gathercontent-import' ), $this->post->ID ), __LINE__, array(
				'post_id'    => $this->post->ID,
				'mapping_id' => $this->mapping->ID,
				'item_id'    => $this->item->id,
			) );
		}

		// If we found updates, do the update.
		return $this->maybe_do_item_update( $config_update );
	}

	public function maybe_do_item_update( $update ) {
		if ( isset( $_GET['test_push'] ) ) {
			echo '<xmp>To update: '. print_r( $update, true ) .'</xmp>';
		}

		// Get our initial config reference.
		$config = json_decode( $this->config );

		// And update it with the new values.
		foreach ( $update as $index => $tab ) {
			foreach ( $tab->elements as $element_index => $element ) {
				$config[ $index ]->elements[ $element_index ] = $element;
			}
		}

		// And finally, update the item in GC.
		$result = $this->api->save_item( $this->item->id, $config );

		if ( $result && ! is_wp_error( $result ) ) {

			// If item update was successful, re-fetch it from the API...
			$this->item = $this->api->uncached()->get_item( $this->item->id );

			// and update the meta.
			\GatherContent\Importer\update_post_item_meta( $this->post->ID, array(
				'created_at' => $this->item->created_at->date,
				'updated_at' => $this->item->updated_at->date,
			) );
		}

		return $result;
	}

	protected function set_item( $item_id ) {
		// Let's create an item, if it doesn't exist yet.
		// It will have the correct template applied to it, and the config object will exist.
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
			throw new Exception( sprintf( __( 'No item found or created for that post ID: %d', 'gathercontent-import' ), $this->post->ID ), __LINE__, array(
				'post_id'    => $this->post->ID,
				'mapping_id' => $this->mapping->ID,
			) );
		}

		$item = parent::set_item( $item_id );

		\GatherContent\Importer\update_post_item_meta( $item_id, array(
			'created_at' => $item->created_at->date,
			'updated_at' => $item->updated_at->date,
		) );

		// Clone the config, to be used as a reference later.
		// Needs to happen before map_wp_data_to_gc_data is called.
		$this->config = wp_json_encode( $item->config );

		return $item;
	}

	protected function map_wp_data_to_gc_data() {
		$config = $this->loop_item_elements_and_map();
		return apply_filters( 'gc_update_gc_config_data', $config, $this );
	}

	public function loop_item_elements_and_map() {
		if ( empty( $this->item->config ) ) {
			return false;
		}

		foreach ( $this->item->config as $index => $tab ) {
			if ( ! isset( $tab->elements ) || ! $tab->elements ) {
				continue;
			}

			foreach ( $tab->elements as $element_index => $this->element ) {
				if ( ! empty( $this->element->value ) ) {
					$this->element->value = self::remove_zero_width( $this->element->value );
				}

				$source = $this->mapping->data( $this->element->name );
				$source_type = isset( $source['type'] ) ? $source['type'] : '';
				$source_key = isset( $source['value'] ) ? $source['value'] : '';

				if ( $source_type ) {
					if ( ! isset( $this->done[ $source_type ] ) ) {
						$this->done[ $source_type ] = array();
					}

					if ( ! isset( $this->done[ $source_type ][ $source_key ] ) ) {
						$this->done[ $source_type ][ $source_key ] = array();
					}

					$this->done[ $source_type ][ $source_key ][ $index .':'. $element_index ] = (array) $this->element;
				}

				if (
					! $source
					|| ! isset( $source['type'], $source['value'] )
					|| ! $this->set_values_from_wp( $source_type, $source_key )
				) {
					unset( $this->item->config[ $index ]->elements[ $element_index ] );
				}

			}

			if ( empty( $this->item->config[ $index ]->elements ) ) {
				unset( $this->item->config[ $index ] );
			}
		}

		$this->remove_unknowns();

		return $this->item->config;
	}

	/**
	 * Loops the $done array and looks for duplicates (unknowns) and removes them.
	 *
	 * @todo Fix this. Probably need a reverse mapping UI for each item push, or something.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function remove_unknowns() {
		foreach ( $this->done as $source_type => $keys ) {
			foreach ( $keys as $source_key => $values ) {
				if ( count( $values ) < 2 ) {
					// We're good to go!
					continue;
				}

				// @todo fix this.
				// UH OH, this means we've encountered some appendable field types which
				// have more than one GC value mapping to them. We don't have a reliable
				// way of parsing those bits back to the individual GC fields, So we have
				// to simply remove them from the update.

				foreach ( $values as $key => $value ) {
					$keys = explode( ':', $key );

					unset( $this->item->config[ $keys[0] ]->elements[ $keys[1] ] );

					if ( empty( $this->item->config[ $keys[0] ]->elements ) ) {
						unset( $this->item->config[ $keys[0] ] );
					}

				}
			}
		}
	}

	protected function set_values_from_wp( $source_type, $source_key ) {
		$updated = false;

		switch ( $source_type ) {
			case 'wp-type-post':
				$updated = $this->set_post_field_value( $source_key );
				break;

			case 'wp-type-taxonomy':
				$updated = $this->set_taxonomy_field_value( $source_key );
				break;

			case 'wp-type-meta':
				$updated = $this->set_meta_field_value( $source_key );
				break;

			// @todo determine if GC can accept file updates.
			// case 'wp-type-media':
			// 	$updated = $this->get_media_field_value( $source_key );
			// 	break;
		}

		return $updated;
	}

	protected function set_post_field_value( $post_column ) {
		$updated = false;
		$el_value = $this->element->value;
		$value = ! empty( $this->post->{$post_column} ) ? self::remove_zero_width( $this->post->{$post_column} ) : false;
		$value = apply_filters( "gc_get_{$post_column}", $value, $this );

		// Make element value match the WP versions formatting, to see if they are equal.
		switch ( $post_column ) {
			case 'post_title':
				$el_value = wp_kses_post( $this->get_element_value() );
				break;
			case 'post_content':
			case 'post_excerpt':
				$el_value = wp_kses_post( $this->get_element_value() );
				$value = $this->convert_media_to_shortcodes( $value );
				break;
		}

		if ( $value != $el_value ) {
			$this->element->value = $value;
			$updated = true;
		}

		return $updated;
	}

	protected function set_taxonomy_field_value( $taxonomy ) {
		$updated = false;
		$terms = get_the_terms( $this->post, $taxonomy );
		$term_names = ! is_wp_error( $terms ) && ! empty( $terms )
			? wp_list_pluck( $terms, 'name' )
			: array();

		switch ( $this->element->type ) {

			case 'text':
				$item_vals = array_map( 'trim', explode( ',', $this->element->value ) );

				$diff = array_diff( $term_names, $item_vals );
				if ( empty( $diff ) ) {
					$diff = array_diff( $item_vals, $term_names );
				}

				if ( ! empty( $diff ) ) {
					$this->element->value = ! empty( $term_names ) ? implode( ', ', $term_names ) : '';
					$updated = true;
				}
				break;

			case 'choice_checkbox':
			case 'choice_radio':
				$updated = $this->update_element_selected_options( function( $option ) use ( $term_names ) {
					return in_array( $option, $term_names );
				} );

				// @todo Probably can't create options via the API, but we'll leave this for the future, in case you can.
				// $option_names = wp_list_pluck( $this->element->options, 'label' );
				// $new_terms = array_diff( $term_names, $option_names );
				// foreach ( $new_terms as $new_term ) {
				// 	$this->element->options[] = (object) array(
				// 		'label' => $new_term,
				// 		'selected' => true,
				// 	)
				// }
				break;

		}

		return $updated;
	}

	protected function set_meta_field_value( $meta_key ) {
		$updated = false;
		$meta_value = get_post_meta( $this->post->ID, $meta_key, 1 );;

		switch ( $this->element->type ) {

			case 'text':
				if ( $meta_value != $this->element->value ) {
					$this->element->value = $meta_value;
					$updated = true;
				}
				break;

			case 'choice_radio':
				$updated = $this->update_element_selected_options( function( $option ) use ( $meta_value ) {
					return $meta_value == $option;
				} );
				break;

			case 'choice_checkbox':
				if ( empty( $meta_value ) ) {
					$meta_value = array();
				} else {
					$meta_value = is_array( $meta_value ) ? $meta_value : array( $meta_value );
				}

				$updated = $this->update_element_selected_options( function( $option ) use ( $meta_value ) {
					return in_array( $option, $meta_value );
				} );
				break;

		}

		return $updated;
	}

	/**
	 * Uses $callback to determine if each option value should be selected,
	 *
	 * @since  3.0.0
	 *
	 * @param  callable $callback Closure
	 *
	 * @return bool            	Whether the options were updated or not.
	 */
	public function update_element_selected_options( $callback ) {
		$pre_options = json_encode( $this->element->options );

		foreach ( $this->element->options as $key => $option ) {
			if ( $callback( self::remove_zero_width( $option->label ) ) ) {
				$this->element->options[ $key ]->selected = true;
			} else {
				$this->element->options[ $key ]->selected = false;
			}
		}

		$post_options = json_encode( $this->element->options );

		// Check if the values have been updated.
		return $pre_options != $post_options;
	}
}
