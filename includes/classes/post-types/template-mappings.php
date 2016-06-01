<?php
namespace GatherContent\Importer\Post_Types;
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
		add_filter( 'get_edit_post_link', array( $this, 'modify_mapping_post_edit_link' ), 10, 2 );
	}

	public function output_mapping_data( $post ) {
		if ( $this->slug === $post->post_type ) {
			echo '<p class="postbox" style="padding: 1em;background: #f5f5f5;margin: -4px 0 0">';
			echo '<strong>' . __( 'Project ID:', 'gathercontent-import' ) . '</strong> '. get_post_meta( get_the_id(), 'project', 1 );
			echo ',&nbsp;';
			echo '<strong>' . __( 'Template ID:', 'gathercontent-import' ) . '</strong> '. get_post_meta( get_the_id(), 'template', 1 );
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

	public function create_mapping( $project_id, $template_id, $postarr, $wp_error = false ) {
		$postarr['post_type']              = $this->slug;
		$postarr['meta_input']['project']  = $project_id;
		$postarr['meta_input']['template'] = $template_id;

		return wp_insert_post( $postarr, 1 );
	}

	public function get_mapping( $args = array() ) {
		$args['post_type'] = $this->slug;

		return new WP_Query( $args );
	}

	public function get_by_project( $project_id, $args = array() ) {
		$meta_query = array(
			array(
				'key'   => 'project',
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
				'key'   => 'template',
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
		if ( isset( $_GET['gc_standard_edit_links'] ) ) {
			return $link;
		}

		$post_type = '';

		if ( isset( $post->ID ) ) {
			$post_id = $post->ID;
			$post_type = $post->post_type;
		} elseif ( is_numeric( $post ) ) {
			$post_id = $post;
			$post_type = get_post_type( $post_id );
		}

		if ( $this->slug === $post_type ) {

			$project_id = get_post_meta( $post_id, 'project', 1 );
			$template_id = get_post_meta( $post_id, 'template', 1 );

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

}
