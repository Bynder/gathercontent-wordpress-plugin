<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
use WP_Error;

class Exception extends \Exception {
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

abstract class Base extends Plugin_Base {

	/**
	 * Sync direction. 'push', or 'pull'.
	 *
	 * @var string
	 */
	protected $direction = '';

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected $api = null;

	/**
	 * Async_Base instance
	 *
	 * @var Async_Base
	 */
	protected $async = null;

	/**
	 * GatherContent item object.
	 *
	 * @var null|object
	 */
	protected $item = null;

	/**
	 * GatherContent item element object.
	 *
	 * @var null|object
	 */
	protected $element = null;

	/**
	 * Mapping post object
	 *
	 * @var null|Mapping_Post
	 */
	protected $mapping = null;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( API $api, Async_Base $async ) {
		$this->api   = $api;
		$this->async = $async;
	}

	abstract public function init_hooks();
	abstract protected function do_item( $id );

	public function sync_items( $mapping ) {
		$result = $this->_sync_items( $mapping );
		error_log( '_sync_items $result: '. print_r( $result, true ) );
		return $result;
	}

	protected function _sync_items( $mapping ) {
		try {
			$this->mapping = Mapping_Post::get( $mapping, true );

			$this->check_mapping_data();
			$ids = $this->get_items_to_sync( $this->direction );

		} catch ( Exception $e ) {
			return new WP_Error( "gc_{$this->direction}_items_fail_" . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		$id = array_shift( $ids['pending'] );

		if ( get_option( "gc_{$this->direction}_item_{$id}" ) ) {
			return new WP_Error( "gc_{$this->direction}_item_in_progress", sprintf( __( 'Currently in progress: %d', 'gathercontent-import' ), $id ) );
		}

		try {
			update_option( "gc_{$this->direction}_item_{$id}", time(), false );
			$result = $this->do_item( $id );
		} catch ( Exception $e ) {
			$result = new WP_Error( "gc_{$this->direction}_item_fail_" . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		$ids['complete'] = isset( $ids['complete'] ) ? $ids['complete'] : array();
		$ids['complete'][] = $id;

		$this->mapping->update_items_to_sync( $ids, $this->direction );
		delete_option( "gc_{$this->direction}_item_{$id}" );

		// If we have more items
		if ( ! empty( $ids['pending'] ) ) {
			// Then trigger the next async request
			do_action( "gc_{$this->direction}_items", $this->mapping );
		}

		return $result;
	}

	protected function get_post( $post_id ) {
		$post = $post_id instanceof WP_Post ? $post_id : get_post( $post_id );
		if ( ! $post ) {
			throw new Exception( sprintf( __( 'No post object by that id: %d', 'gathercontent-import' ), $post_id ), __LINE__ );
		}

		return $post;
	}

	protected function set_item( $item_id ) {
		// if ( isset( $_GET['test_push'] ) ) {
		// 	$this->item = $this->api->get_item( $item_id );
		// } else {
			$this->item = $this->api->uncached()->get_item( $item_id );
		// }


		if ( ! isset( $this->item->id ) ) {
			// @todo maybe check if error was temporary.
			throw new Exception( sprintf( __( 'GatherContent could not get an item for that item id: %d', 'gathercontent-import' ), $item_id ), __LINE__, $this->item );
		}

		return $this->item;
	}

	protected function get_items_to_sync() {
		$items = $this->mapping->get_items_to_sync( $this->direction );

		if ( empty( $items['pending'] ) ) {
			throw new Exception( sprintf( __( 'No items to pull for: %s', 'gathercontent-import' ), $this->mapping->ID ), __LINE__ );
		}

		return $items;
	}

	protected function check_mapping_data() {
		$mapping_data = $this->mapping->data();
		if ( empty( $mapping_data ) ) {
			// @todo maybe check if error was temporary.
			throw new Exception( sprintf( __( 'No mapping data found for: %s', 'gathercontent-import' ), $this->mapping->ID ), __LINE__ );
		}
	}

	protected function get_element_value() {
		$val = $this->get_value_for_element( $this->element );
		return apply_filters( 'gc_get_element_value', $val, $this->element, $this->item );
	}

	protected function get_value_for_element( $element ) {
		$val = false;

		switch ( $element->type ) {
			case 'text':
				$val = $element->value;
				$val = trim( str_replace( "\xE2\x80\x8B", '', $val ) );
				if ( ! $element->plain_text ) {
					$val = preg_replace_callback(
						'#\<p\>(.+?)\<\/p\>#s',
						function ( $matches ) {
							return '<p>'. str_replace( array(
								"\n    ",
								"\r\n    ",
								"\r    ",
								"\n",
								"\r\n",
								"\r",
							), '', $matches[1] ) .'</p>';
						},
						$val
					);
					$val = str_replace( '</ul><', "</ul>\n<", $val );
					$val = preg_replace( '/<\/p>\s*<p>/m', "</p>\n<p>", $val );
					$val = preg_replace( '/<\/p>\s*</m', "</p>\n<", $val );
					$val = preg_replace( '/<p>\s*<\/p>/m','<p>&nbsp;</p>',$val );
					$val = str_replace( array('<ul><li', '</li><li>', '</li></ul>'), array("<ul>\n\t<li", "</li>\n\t<li>", "</li>\n</ul>"), $val );

					$val = preg_replace( '/<mark[^>]*>/i', '', $val );
					$val = preg_replace( '/<\/mark>/i', '', $val );

					// Replace encoded ampersands in html entities.
					// http://regexr.com/3dpcf
					$val = preg_replace_callback( '~(&amp;)(?:[a-z,A-Z,0-9]+|#\d+|#x[0-9a-f]+);~', function( $matches ) {
						return str_replace( '&amp;', '&', $matches[0] );
					}, $val );

				}
				$val = wp_kses_post( $val );
				break;

			case 'choice_radio':
				$val = '';
				foreach ( $element->options as $idx => $option ) {
					if ( $option->selected ) {
						if ( isset( $option->value ) ) {
							$val = sanitize_text_field( $option->value );
						}
						else {
							$val = sanitize_text_field( $option->label );
						}
					}
				}
				break;

			case 'choice_checkbox':
				$val = array();
				foreach ( $element->options as $option ) {
					if ( $option->selected ) {
						$val = sanitize_text_field( $option->label );
					}
				}
				break;

			case 'files':
				if ( is_array( $this->item->files ) && isset( $this->item->files[ $element->name ] ) ) {
					$val = $this->item->files[ $element->name ];
				}
				break;

			default:
				if ( isset( $element->value ) ) {
					$val = sanitize_text_field( $option->label );
				}
				break;
		}

		return $val;
	}

	protected function set_element_value() {
		$this->element->value = $this->get_element_value();
	}

	protected function get_append_types() {
		return array( 'post_content', 'post_title', 'post_excerpt' );
	}

	protected function type_can_append( $field ) {
		$can_append = in_array( $field, $this->get_append_types(), 1 );

		return apply_filters( "gc_can_append_{$field}", $can_append, $this->element, $this->item );
	}

	/**
	 * Removes faulty "zero width space", which seems to come through the GC API.
	 * @link http://stackoverflow.com/questions/11305797/remove-zero-width-space-characters-from-a-javascript-string
	 * U+200B zero width space
	 * U+200C zero width non-joiner Unicode code point
	 * U+200D zero width joiner Unicode code point
	 * U+FEFF zero width no-break space Unicode code point
	 */
	public static function remove_zero_width( $string ) {
		return preg_replace( '/[\x{200B}-\x{200D}]/u', '', $string );
	}

}
