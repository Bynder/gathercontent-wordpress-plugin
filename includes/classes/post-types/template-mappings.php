<?php
namespace GatherContent\Importer\Post_Types;
use GatherContent\Importer\Mapping;
use WP_Query;

class Template_Mappings extends Base {
	public $slug = 'gc_templates';
	public $add_new_template;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( $parent_menu_slug ) {
		parent::__construct(
			array(
				'name'                  => _x( 'Template Mappings', 'post type general name', 'gathercontent-import' ),
				'singular_name'         => _x( 'Template Mapping', 'post type singular name', 'gathercontent-import' ),
				'add_new'               => _x( 'Add New', 'post', 'gathercontent-import' ),
				'add_new_item'          => __( 'Add New Template Mapping', 'gathercontent-import' ),
				'edit_item'             => __( 'Edit Template Mapping', 'gathercontent-import' ),
				'new_item'              => __( 'New Template Mapping', 'gathercontent-import' ),
				'view_item'             => __( 'View Template Mapping', 'gathercontent-import' ),
				'item_updated'          => __( 'Template Mapping updated', 'gathercontent-import' ),
				'item_saved'            => __( 'Template Mapping saved', 'gathercontent-import' ),
				'search_items'          => __( 'Search Template Mappings', 'gathercontent-import' ),
				'not_found'             => __( 'No template mappings found.', 'gathercontent-import' ),
				'not_found_in_trash'    => __( 'No template mappings found in Trash.', 'gathercontent-import' ),
				'all_items'             => __( 'Template Mappings', 'gathercontent-import' ),
				'archives'              => __( 'Template Mapping Archives', 'gathercontent-import' ),
				'insert_into_item'      => __( 'Insert into template mapping', 'gathercontent-import' ),
				'uploaded_to_this_item' => __( 'Uploaded to this template mapping', 'gathercontent-import' ),
				'filter_items_list'     => __( 'Filter template mappings list', 'gathercontent-import' ),
				'items_list_navigation' => __( 'Template Mappings list navigation', 'gathercontent-import' ),
				'items_list'            => __( 'Template Mappings list', 'gathercontent-import' ),
			),
			array(
				'show_ui'              => true,
				'show_in_menu'         => false,
				'show_in_menu'         => $parent_menu_slug,
				'supports'             => array( 'title' ),
				'rewrite'              => false,
			)
		);
	}

	public function register_post_type() {
		parent::register_post_type();

		add_action( 'edit_form_after_title', array( $this, 'output_mapping_data' ) );

		if ( ! isset( $_GET['gc_standard_edit_links'] ) ) {
			add_filter( 'get_edit_post_link', array( $this, 'modify_mapping_post_edit_link' ), 10, 2 );
		}

		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 2 );
	}

	/**
	 * removes quick edit from custom post type list
	 *
	 * @since  3.0.0
	 *
	 * @param array $actions An array of row action links. Defaults are
	 *                         'Edit', 'Quick Edit', 'Restore, 'Trash',
	 *                         'Delete Permanently', 'Preview', and 'View'.
	 * @param WP_Post $post  The post object.
	 *
	 * @return array         Modified $actions.
	 */
	function remove_quick_edit( $actions, $post ) {
		if ( $this->slug === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );

			$actions['sync-items'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				add_query_arg( 'sync-items', 1,  get_edit_post_link( $post->ID, 'raw' ) ),
				esc_attr( __( 'Import Items', 'gathercontent-import' ) ),
				__( 'Import Items', 'gathercontent-import' )
			);
		}

		return $actions;
	}

	public function output_mapping_data( $post ) {
		if ( $this->slug === $post->post_type ) {
			echo '<p class="postbox" style="padding: 1em;background: #f5f5f5;margin: -4px 0 0">';
			echo '<strong>' . __( 'Project ID:', 'gathercontent-import' ) . '</strong> '. get_post_meta( get_the_id(), '_gc_project', 1 );
			echo ',&nbsp;';
			echo '<strong>' . __( 'Template ID:', 'gathercontent-import' ) . '</strong> '. get_post_meta( get_the_id(), '_gc_template', 1 );
			echo '</p>';

			$content = $post->post_content;
			if ( defined( 'JSON_PRETTY_PRINT' ) ) {
				$pretty = json_encode( json_decode( $content ), JSON_PRETTY_PRINT );
				if ( $pretty && $pretty !== $content ) {
					$content = $pretty;
				}
			}

			echo '<pre><textarea name="content" id="content" rows="20" style="width:100%;">'. print_r( $content, true ) .'</textarea></pre>';
		}
	}

	public function create_mapping( $mapping_args, $postarr = array(), $wp_error = false ) {

		$mapping_args = wp_parse_args( $mapping_args, array(
			'title'    => '',
			'content'  => '',
			'project'  => null,
			'template' => null,
		) );

		$postarr = wp_parse_args( $postarr, array(
			'post_content' => wp_json_encode( $mapping_args['content'] ),
			'post_title'   => $mapping_args['title'],
			'post_status'  => 'publish',
			'post_type'    => $this->slug,
			'meta_input'   => array(
				'_gc_project'  => $mapping_args['project'],
				'_gc_template' => $mapping_args['template'],
			),
		) );

		return wp_insert_post( $postarr, $wp_error );
	}

	public function get_mapping( $args = array() ) {
		$args['post_type'] = $this->slug;

		return new WP_Query( $args );
	}

	public function get_by_project( $project_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_project',
				'value' => $project_id,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		return $this->get_mapping( $args );
	}

	public function get_by_project_template( $project_id, $template_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => '_gc_template',
				'value' => $template_id,
			),
		);

		$args['meta_query'] = isset( $args['meta_query'] )
			? $args['meta_query'] + $meta_query
			: $meta_query;

		$args = wp_parse_args( $args, array(
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		return $this->get_by_project( $project_id, $args );
	}

	public function modify_mapping_post_edit_link( $link, $post ) {
		$post_type = '';

		if ( isset( $post->ID ) ) {
			$post_id = $post->ID;
			$post_type = $post->post_type;
		} elseif ( is_numeric( $post ) ) {
			$post_id = $post;
			$post_type = get_post_type( $post_id );
		}

		if ( $this->slug === $post_type ) {

			$project_id = get_post_meta( $post_id, '_gc_project', 1 );
			$template_id = get_post_meta( $post_id, '_gc_template', 1 );

			if ( $project_id && $template_id ) {
				$link = admin_url( sprintf(
					'admin.php?page=gathercontent-import-add-new-template&project=%s&template=%s&mapping=%s',
					$project_id,
					$template_id,
					$post_id
				) );
			}

		}

		return $link;
	}

	public function get_mapping_template( $mapping_id ) {
		return get_post_meta( $post_id, '_gc_template', 1 );
	}

	public function get_mapping_project( $mapping_id ) {
		return get_post_meta( $post_id, '_gc_project', 1 );
	}

	public function get_items_to_pull( $mapping_id ) {
		$items = get_post_meta( $mapping_id, '_gc_sync_items', 1 );

		return is_array( $items ) ? $items : array();
	}

	public function update_items_to_sync( $mapping_id, $items = array() ) {
		if ( empty( $items ) || empty( $items['pending'] ) ) {
			return delete_post_meta( $mapping_id, '_gc_sync_items' );
		}

		return update_post_meta( $mapping_id, '_gc_sync_items', $items );
	}

	public function get_pull_percent( $mapping_id ) {
		$percent = 100;

		$items = $this->get_items_to_pull( $mapping_id );

		if ( ! empty( $items ) ) {

			if ( empty( $items['pending'] ) ) {
				delete_post_meta( $mapping_id, '_gc_sync_items' );
			} else {

				$pending = count( $items['pending'] );
				$done = ! empty( $items['complete'] ) ? count( $items['complete'] ) : 0;

				$percent = $done / ( $pending + $done );
			}
		}

		return $percent;
	}

	public function get_mapping_data( $post ) {
		$post = is_numeric( $post ) ? get_post( $post ) : $post;
		if ( isset( $post->mapping ) ) {
			return $post->mapping;
		}

		$post->mapping = new Mapping( $post );

		return $post->mapping->data();
	}

	public function is_mapping_post( $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		if ( $post && isset( $post->post_type ) && $post->post_type === $this->slug ) {
			return $post;
		}

		return false;
	}

}
