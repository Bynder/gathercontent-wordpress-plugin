<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;

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

	protected function map_wp_data_to_gc_data( WP_Post $mapping, WP_Post $post, $item ) {
		$data = $item->config;

		// @todo Use mapping to map WP data to GC data
		throw new Exception( '@todo' );


		return $data;
	}

	protected function get_post( $post_id ) {
		$post = $post_id instanceof WP_Post ? $post_id : get_post( $post_id );
		if ( ! $post ) {
			throw new Exception( sprintf( __( 'No post object by that id: %d', 'gathercontent-import' ), $post_id ), __LINE__ );
		}

		return $post;
	}

	protected function get_post_item_id( $post_id ) {
		$item_id = \GatherContent\Importer\get_post_item_id( $post_id );

		if ( ! $item_id ) {
			throw new Exception( sprintf( __( 'No GatherContent item id for that id: %d', 'gathercontent-import' ), $post_id ), __LINE__ );
		}

		return $item_id;
	}

	protected function set_item( $item_id ) {
		// $this->item = $this->api->get_item( $item_id );
		$this->item = $this->api->uncached()->get_item( $item_id );

		if ( ! isset( $this->item->id ) ) {
			// @todo maybe check if error was temporary.
			throw new Exception( sprintf( __( 'GatherContent could not get an item for that item id: %d', 'gathercontent-import' ), $item_id ), __LINE__, $this->item );
		}

		return $this->item;
	}

	protected function get_item_mapping( $item ) {
		$mapping = Template_Mappings::get_by_project_template( $item->project_id, $item->template_id );

		if ( ! $mapping->have_posts() ) {
			throw new Exception( sprintf( __( 'Could not find an existing project (%s) template (%s) mapping for this item: %d', 'gathercontent-import' ), $item->project_id, $item->template_id, $item->id ), __LINE__, $item );
		}

		$this->mapping = Mapping_Post::get( $mapping, true );

		return $this->mapping;
	}

	protected function get_items_to_pull() {
		$items = $this->mapping->get_items_to_pull();

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

	protected function set_element_value() {
		$val = false;

		switch ( $this->element->type ) {
			case 'text':
				$val = $this->element->value;
				$val = trim( str_replace( "\xE2\x80\x8B", '', $val ) );
				if ( ! $this->element->plain_text ) {
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
				}
				$val = wp_kses_post( $val );
				break;

			case 'choice_radio':
				$val = '';
				foreach ( $this->element->options as $idx => $option ) {
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
				foreach ( $this->element->options as $option ) {
					if ( $option->selected ) {
						$val = sanitize_text_field( $option->label );
					}
				}
				break;

			case 'files':
				if ( is_array( $this->item->files ) && isset( $this->item->files[ $this->element->name ] ) ) {
					$val = $this->item->files[ $this->element->name ];
				}
				break;

			default:
				if ( isset( $this->element->value ) ) {
					$val = sanitize_text_field( $option->label );
				}
				break;
		}

		$this->element->value = apply_filters( 'gc_get_element_value', $val, $this->element, $this->item );
	}

}
