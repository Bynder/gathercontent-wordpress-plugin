<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\API;
use GatherContent\Importer\Views\View;
use GatherContent\Importer\Settings\Setting;
use GatherContent\Importer\Settings\Form_Section;
use GatherContent\Importer\Post_Types\Template_Mappings;
use WP_Query;

/**
 * Class for managing/creating template mappings.
 *
 * @todo Make taxonomies easier to apply (a term picker per taxonomy, ideally using WP functionality for applying terms)
 * @todo Look at SearchWP's functionality for mapping meta/terms
 */
class Manage_Templates extends Base {

	public $parent_page_slug;
	public $items = array();
	public $menu_priority = 11; // Puts "New Mapping" after "Template Mappings" CPT menu.

	/**
	 * Template_Mappings
	 *
	 * @var Template_Mappings
	 */
	public $mappings;

	/**
	 * Default option value (if none is set)
	 *
	 * @var array
	 */
	public $default_options = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( Admin $parent ) {
		$this->option_page_slug = $parent->option_page_slug . '-add-new-template';
		$this->option_name      = $parent->option_name . '_add_new_template';
		$this->option_group     = $parent->option_group . '_add_new_template';
		$this->parent_page_slug = $parent->option_page_slug;
		$this->settings         = new Setting( $parent->option_name, $parent->default_options );
		$this->mappings         = new Template_Mappings( $parent->option_page_slug );

		if ( $this->get_val( 'project' ) ) {
			$this->step = 1;

			if ( $this->get_val( 'template' ) ) {
				$this->step = 2;
			}
		}

		parent::__construct();

		$this->handle_redirects();
	}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	function admin_menu() {
		$page = add_submenu_page(
			$this->parent_page_slug,
			$this->logo,
			__( 'New Mapping', 'gathercontent-import' ),
			'publish_pages',
			$this->option_page_slug,
			array( $this, 'admin_page' )
		);

		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_style' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_script' ) );
	}

	public function admin_page() {
		$this->view( 'admin-page', array(
			'logo'               => $this->logo,
			'option_group'       => $this->option_group,
			'settings_sections'  => Form_Section::get_sections( $this->option_page_slug ),
			'submit_button_text' => 2 === $this->step
				? __( 'Save Mapping', 'gathercontent-import' )
				: __( 'Next Step', 'gathercontent-import' ),
		) );
	}

	/**
	 * Initializes the plugin's setting, and settings sections/Fields.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	function initialize_settings_sections() {

		switch ( $this->step ) {
			case 1:
				$this->select_template();
				break;

			case 2:
				$this->map_template();
				break;

			default:
				if ( ! $this->step ) {
					$this->select_project();
				}
				break;
		}

		parent::initialize_settings_sections();
	}

	/**
	 * Like inception, checks how many levels deep we are in the Template Mapping process,
	 * and performs the appropriate action/redirect.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $options Array of options
	 *
	 * @return void
	 */
	public function sanitize_settings( $options ) {
		if ( ! isset( $options['project'] ) ) {
			// Hmmm, this should never happen, but if so, we def. don't want to save.
			return false;
		}

		// Ok, we have a project.
		$project = esc_attr( $options['project'] );

		if ( ! isset( $options['template'] ) ) {
			// we need to send the user to the template picker.
			wp_safe_redirect( esc_url_raw( add_query_arg( 'project', $project, $this->url ) ) );
			exit;
		}

		// Ok, we have a template.
		$template = esc_attr( $options['template'] );

		if ( ! isset( $options['create_mapping'] ) ) {

			// Ok, we we're we've selected a template, let's create a mapping.

			$args = compact( 'project', 'template' );

			// Let's check if we already have a mapped template.
			$exists = $this->mappings->get_by_project_template( $project, $template );

			if ( $exists->have_posts() ) {
				// Yep, we found one.
				$args['mapping'] = $exists->posts[0];
			}

			// Now redirect to the template mapping.
			wp_safe_redirect( esc_url_raw( add_query_arg( $args, $this->url ) ) );
			exit;
		}

		// Ok, we have all we need. Let's attempt to create a new mapping post.

		$nonce = md5( $project . $template );
		if ( ! wp_verify_nonce( $options['create_mapping'], $nonce ) ) {
			// Let check_admin_referer handle the fail.
			check_admin_referer( 'fail', 'fail' );
		}

		$post_id = $this->create_new_mapping_post( $options );

		if ( is_wp_error( $post_id ) ) {
			wp_die( $post_id->get_error_message(), __( 'Failed creating mapping!', 'gathercontent-import' ) );
		}

		wp_safe_redirect( esc_url_raw( get_edit_post_link( $post_id, 'raw' ) ) );
		exit;
	}

	public function select_project() {
		$section = new Form_Section(
			'select_project',
			__( 'First, choose a project.', 'gathercontent-import' ),
			'',
			$this->option_page_slug
		);

		$section->add_field(
			'project',
			'',
			function( $field ) {
				$field_id = $field->param( 'id' );

				if ( $accounts = $this->api()->get( 'accounts' ) ) {
					foreach ( $accounts as $account ) {
						echo '<p class="gc-account-name description">';
						printf( __( 'Account: %s', 'gathercontent-import' ), isset( $account->name ) ? $account->name : '' );
						echo '</p>';

						// echo '<xmp>$account: '. print_r( $account, true ) .'</xmp>';

						if ( isset( $account->id ) ) {
							$options = array();

							if ( $projects = $this->api()->get( 'projects?account_id=' . $account->id ) ) {
								foreach ( $projects as $project ) {
									# code...
									$options[ esc_attr( $project->id ) ] = esc_attr( $project->name );
								}
							}

							// echo '<xmp>$projects: '. print_r( $projects, true ) .'</xmp>';
							$this->view( 'radio', array(
								'id'      => $field_id . '-' . $account->id,
								'name'    => $this->option_name .'['. $field_id .']',
								'value'   => '',
								'options' => $options,
							) );
						}

					}
				}

			}
		);

	}

	public function select_template() {
		$project = $this->api()->get( 'projects/' . $this->get_val( 'project' ) );

		$section = new Form_Section(
			'select_template',
			__( 'Next, select a template to map.', 'gathercontent-import' ),
			$this->project_name( $project ),
			$this->option_page_slug
		);

		$section->add_field(
			'template',
			'',
			function( $field ) {
				$field_id   = $field->param( 'id' );
				$project_id = $this->get_val( 'project' );
				$options    = array();

				if ( $templates = $this->api()->get( 'templates?project_id=' . $project_id ) ) {

					foreach ( $templates as $template ) {
						$desc = esc_attr( $template->description );

						if ( $items = $this->get_filtered_items_list( $project_id, $template->id ) ) {
							$desc .= '</p>' . $items . '<p>';
						}

						$options[ esc_attr( $template->id ) ] = array(
							'label' => esc_attr( $template->name ),
							'desc' => $desc,
						);
					}
				}

				$this->view( 'radio', array(
					'id'      => $field_id,
					'name'    => $this->option_name .'['. $field_id .']',
					// 'value'   => $val,
					'value'   => '',
					'options' => $options,
				) );

				$this->view( 'input', array(
					'type'    => 'hidden',
					'id'      => 'gc-project-id',
					'name'    => $this->option_name .'[project]',
					'value'   => $project_id,
				) );

			}
		);

	}

	public function map_template() {

		$template     = $this->api()->get( 'templates/' . $this->get_val( 'template' ) );
		$project      = $this->api()->get( 'projects/' . $this->get_val( 'project' ) );

		$title = $template && isset( $template->name )
			? $template->name
			: __( 'Map Template', 'gathercontent-import' );

		$desc = '';
		if ( $template && isset( $template->description ) ) {
			$desc .= '<h4 class="description">' . esc_attr( $template->description ) . '</h4>';
		}

		$desc .= $this->project_name( $project );

		$section = new Form_Section(
			'select_template',
			$title,
			$desc,
			$this->option_page_slug
		);

		$section->add_field(
			'template',
			'',
			function( $field ) use( $template ) {
				$project_id  = esc_attr( $this->get_val( 'project' ) );
				$template_id = esc_attr( $this->get_val( 'template' ) );

				$hidden = '';
				foreach ( $template->config as $index => $tab ) {
					$tab->nav_item = array(
						'value' => $tab->name,
						'class' => 'nav-tab '. ( '' === $hidden ? 'nav-tab-active' : '' ),
						'lable' => $tab->label,
					);

					$tab->tab_class = 'gc-template-tab ' . $hidden;
					$hidden = 'hidden';

					$template->config[ $index ] = $tab;
				}

				$existing_id = absint( $this->get_val( 'mapping' ) );
				$edit_link = $existing_id ? get_edit_post_link( $existing_id ) : '';

				$this->view( 'create-mapping', array(
					'destination_post_options' => $this->post_destinations( true ),
					'option_base'              => $this->option_name,
					'post_types'               => $this->post_types(),
					'tabs'                     => $template->config,
					'edit_link'                => $edit_link,
					'mapping_template_label'   => $this->mappings->args->labels->singular_name,
				) );

				if ( $edit_link ) {
					$this->view( 'input', array(
						'type'    => 'hidden',
						'id'      => 'gc-existing-id',
						'name'    => $this->option_name .'[existing_mapping_id]',
						'value'   => $existing_id,
					) );
				}

				$this->view( 'input', array(
					'type'    => 'hidden',
					'id'      => 'gc-create-map',
					'name'    => $this->option_name .'[create_mapping]',
					'value'   => wp_create_nonce( md5( $project_id . $template_id ) ),
				) );

				$title = isset( $template->name ) ? ( $template->name ) : __( 'Map Template', 'gathercontent-import' );

				$this->view( 'input', array(
					'type'    => 'hidden',
					'id'      => 'gc-project-id',
					'name'    => $this->option_name .'[title]',
					'value'   => esc_attr( $title ),
				) );

				$this->view( 'input', array(
					'type'    => 'hidden',
					'id'      => 'gc-project-id',
					'name'    => $this->option_name .'[project]',
					'value'   => $project_id,
				) );

				$this->view( 'input', array(
					'type'    => 'hidden',
					'id'      => 'gc-template-id',
					'name'    => $this->option_name .'[template]',
					'value'   => $template_id,
				) );

			}
		);

	}

	public function project_name( $project ) {
		$project_name = '';

		if ( $project->name ) {
			$url = $this->get_setting( 'platform_url' ) . 'templates/' . $project->id;
			$project_name = '<p class="gc-project-name description">' . sprintf( __( 'Project: %s', 'gathercontent-import' ), $project->name ) . ' | <a href="'. esc_url( $url ) .'" target="_blank">'. __( 'edit project templates', 'gathercontent-import' ) .'</a></p>';
		}

		return $project_name;
	}

	public function get_filtered_items_list( $project_id, $template_id, $class = 'gc-radio-desc' ) {
		$items = $this->filter_items_by_template( $project_id, $template_id );

		$list = '';
		if ( ! empty( $items ) ) {
			$view = new View( 'gc-items-list', array(
				'class'        => $class,
				'platform_url' => $this->get_setting( 'platform_url' ),
				'items'        => $items,
			) );
			$list = $view->load( false );
		}

		return $list;
	}

	public function filter_items_by_template( $project_id, $template_id ) {
		$items = $this->items( $project_id );

		$tmpl_items = is_array( $items )
			? wp_list_filter( $items, array( 'template_id' => $template_id ) )
			: array();

		return $tmpl_items;
	}

	public function items( $project_id ) {
		if ( isset( $this->items[ $project_id ] ) ) {
			return $this->items[ $project_id ];
		}

		$this->items[ $project_id ] = $this->api()->get( 'items?project_id=' . $project_id );

		return $this->items[ $project_id ];
	}

	public function post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $index => $type ) {
			$type->taxonomies = array();
			foreach ( get_object_taxonomies( $type->name, 'objects' ) as $tax ) {
				if ( 'post_format' === $tax->name ) {
					continue;
				}

				$tax->terms = array();
				// Get all terms for this taxonomy
				// @todo NOT SCALABLE
				foreach ( get_terms( $tax->name, array( 'hide_empty' => false, ) ) as $term ) {
					$tax->terms[] = $term;
				}

				$type->taxonomies[] = $tax;
			}

			$post_types[ $index ] = $type;
		}

		return $post_types;
	}

	public function post_destinations( $html = true ) {
		$options = array(
			'' => __( 'Unused', 'gathercontent-import' ),
		);

		$destination_options = '<option value="">'. $options[''] .'</option>';

		foreach ( $this->get_wp_post_columns() as $col ) {
			if ( $label = $this->post_column_label( $col ) ) {
				$destination_options .= '<option value="'. $col .'">'. $label .'</option>';
				$options[ $col ] = $label;
			}
		}

		return $html ? $destination_options : $options;
	}

	public function post_column_label( $col ) {
		switch ( $col ) {
			case 'ID':
			case 'to_ping':
			case 'pinged':
			case 'post_mime_type':
			case 'comment_count':
			case 'post_content_filtered':
			case 'guid':
				return false;
			case 'post_author':
				return __( 'Author', 'gathercontent-import' );
			case 'post_date':
				return __( 'Post Date', 'gathercontent-import' );
				return 'post_date';
			case 'post_date_gmt':
				return __( 'Post Date (GMT)', 'gathercontent-import' );
			case 'post_content':
				return __( 'Post Content', 'gathercontent-import' );
			case 'post_title':
				return __( 'Post Title', 'gathercontent-import' );
			case 'post_excerpt':
				return __( 'Post Excerpt', 'gathercontent-import' );
			case 'post_status':
				return __( 'Post Status', 'gathercontent-import' );
			case 'comment_status':
				return __( 'Comment Status', 'gathercontent-import' );
			case 'ping_status':
				return __( 'Ping Status', 'gathercontent-import' );
			case 'post_password':
				return __( 'Post Password', 'gathercontent-import' );
			case 'post_name':
				return __( 'Post Name (Slug)', 'gathercontent-import' );
			case 'post_modified':
				return __( 'Post Modified Date', 'gathercontent-import' );
			case 'post_modified_gmt':
				return __( 'Post Modified Date (GMT)', 'gathercontent-import' );
			case 'post_parent':
				return __( 'Post Parent', 'gathercontent-import' );
			case 'menu_order':
				return __( 'Menu Order', 'gathercontent-import' );
			case 'post_type':
				return __( 'Post Type', 'gathercontent-import' );
			default:
				return $col;
		}
	}


	public function get_wp_post_columns() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'posts';
		return $wpdb->get_col( "DESC " . $table_name, 0 );
	}

	protected function create_new_mapping_post( $options ) {
		$project  = sanitize_text_field( $options['project'] );
		$template = sanitize_text_field( $options['template'] );
		$title    = sanitize_text_field( $options['title'] );

		$existing_id = 0;
		if ( isset( $options['existing_mapping_id'] ) ) {
			$existing_id = absint( $options['existing_mapping_id'] );
			unset( $options['existing_mapping_id'] );
		}

		unset( $options['create_mapping'] );
		unset( $options['title'] );
		unset( $options['project'] );
		unset( $options['template'] );

		$post_args = array(
			'post_content' => wp_json_encode( $options ),
			'post_status'  => 'publish',
			'post_title'   => $title,
		);

		if ( $existing_id ) {
			$post_args['ID'] = $existing_id;
		}

		return $this->mappings->create_mapping( $project, $template, $post_args, 1 );
	}

	/**
	 * Determine if any conditions are met to cause us to redirect.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function handle_redirects() {
		$this->maybe_redirect_to_create_new_mapping();
		$this->maybe_redirect_to_edit_mapping_template();
	}

	/**
	 * Determine if we should redirect to new-mapping settings page
	 * when trying to create a new Template Mapping.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function maybe_redirect_to_create_new_mapping() {
		global $pagenow;

		if ( 'post-new.php' === $pagenow && $this->get_val_equals( 'post_type', $this->mappings->slug ) ) {
			wp_safe_redirect( $this->url );
			exit;
		}
	}

	/**
	 * Determine if we should redirect to a defined mapping template to edit.
	 * (based on template/project id)
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function maybe_redirect_to_edit_mapping_template() {
		if (
			! $this->get_val_equals( 'page', $this->option_page_slug )
			|| ! $this->get_val( 'project' )
			|| ! $this->get_val( 'template' )
		) {
			return;
		}

		$mapping_id = absint( $this->get_val( 'mapping' ) );

		$exists = $this->mappings->get_by_project_template(
			sanitize_text_field( $this->get_val( 'project' ) ),
			sanitize_text_field( $this->get_val( 'template' ) )
		);

		$redirect_id = $exists->have_posts() ? $exists->posts[0] : false;

		// If not mapping id is found to match project/template, get rid of mapping query arg.
		if ( ! $redirect_id && $mapping_id ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( 'mapping' ) ) );
			exit;
		}

		// Determine if 'mapping' query arg is correct.
		$redirect_id = $redirect_id && $mapping_id !== $redirect_id ? $redirect_id : false;

		if ( ! $redirect_id ) {
			return;
		}

		// Ok, we found a mapping ID, so add that as a query string and redirect.
		$args['mapping'] = $redirect_id;

		wp_safe_redirect( esc_url_raw( add_query_arg( $args ) ) );
		exit;
	}

	public function get_mapping_id_from_query_args() {
		$exists = $this->mappings->get_by_project_template(
			sanitize_text_field( $this->get_val( 'project' ) ),
			sanitize_text_field( $this->get_val( 'template' ) )
		);

		return $exists->have_posts() ? $exists->posts[0] : false;
	}

}
