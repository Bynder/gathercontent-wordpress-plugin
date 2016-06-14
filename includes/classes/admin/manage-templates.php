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
	public $parent_url;
	public $items = array();
	public $stored_values = null;
	public $post_types = null;
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
		$this->parent_url       = $parent->url;
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
		$this->handle_notices();

		$args = array(
			'logo'                => $this->logo,
			'option_group'        => $this->option_group,
			'settings_sections'   => Form_Section::get_sections( $this->option_page_slug ),
			'go_back_button_text' => __( 'Previous Step', 'gathercontent-import' ),
			'refresh_button'      => $this->refresh_connection_link(),
			'submit_button_text'  => 2 === $this->step
				? __( 'Save Mapping', 'gathercontent-import' )
				: __( 'Next Step', 'gathercontent-import' ),
		);


		switch ( $this->step ) {
			case 0:
				$args['go_back_button_text'] = __( 'Back to API setup', 'gathercontent-import' );
				$args['go_back_url'] = $this->parent_url;
				break;

			case 1:
				$args['go_back_url'] = remove_query_arg( 'project' );
				break;

			case 2:
				$args['go_back_url'] = remove_query_arg( 'template', remove_query_arg( 'mapping' ) );
				break;
		}

		$this->view( 'admin-page', $args );
	}

	public function handle_notices() {
		if ( get_option( 'gc-api-updated' ) ) {
			$this->add_settings_error( $this->option_name, 'gc-api-connection-reset', __( 'We refreshed the data from the GatherContent API.', 'gathercontent-import' ), 'updated' );
			delete_option( 'gc-api-updated' );
		}
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
				$args['settings-updated'] = 1;
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
			__( 'First, choose a project from an account.', 'gathercontent-import' ),
			'',
			$this->option_page_slug
		);

		$section->add_field(
			'project',
			'',
			array( $this, 'select_project_field_callback' )
		);
	}

	public function select_project_field_callback( $field ) {
		$field_id = $field->param( 'id' );
		$accounts = $this->api()->get( 'accounts' );

		if ( ! $accounts ) {
			return $this->add_settings_error( $this->option_name, 'gc-missing-accounts', sprintf( __( 'We couldn\'t find any accounts associated with your GatherContent API credentials. Please <a href="%s">check your settings</a>.', 'gathercontent-import' ), $this->parent_url ) );
		}

		$tabs = array();
		foreach ( $accounts as $account ) {
			if ( ! isset( $account->id ) ) {
				continue;
			}

			$options = array();
			$value = '';

			if ( $projects = $this->api()->get( 'projects?account_id=' . $account->id ) ) {
				foreach ( $projects as $project ) {
					$val = esc_attr( $project->id );
					$options[ $val ] = esc_attr( $project->name );
					if ( ! $value ) {
						$value = $val;
					}
				}
			}

			$tabs[ $account->id ] = array(
				'label' => sprintf( __( 'Account: %s', 'gathercontent-import' ), isset( $account->name ) ? $account->name : '' ),
				'content' => $this->view( 'radio', array(
					'id'      => $field_id . '-' . $account->id,
					'name'    => $this->option_name .'['. $field_id .']',
					'value'   => $value,
					'options' => $options,
				), false ),
			);
		}

		$this->view( 'tabs-wrapper', array(
			'tabs' => $tabs,
		) );

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

				$value = '';
				if ( $templates = $this->api()->get( 'templates?project_id=' . $project_id ) ) {

					foreach ( $templates as $template ) {
						$desc = esc_attr( $template->description );

						if ( $items = $this->get_filtered_items_list( $project_id, $template->id ) ) {
							$desc .= '</p>' . $items . '<p>';
						}

						$val = esc_attr( $template->id );

						$options[ $val ] = array(
							'label' => esc_attr( $template->name ),
							'desc' => $desc,
						);

						if ( ! $value ) {
							$value = $val;
						}

					}
				}

				$this->view( 'radio', array(
					'id'      => $field_id,
					'name'    => $this->option_name .'['. $field_id .']',
					'value'   => $value,
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

	public function existing_mapping_notice() {
		?>
		<div class="notice notice-info ">
			<p><?php printf( __( '<strong>NOTE:</strong> There can be only one %s per project template. You are editing an existing mapping (ID: %d).', 'gathercontent-import' ), $this->mappings->args->labels->singular_name, absint( $this->get_val( 'mapping' ) ) ); ?></p>
		</div>
		<?php
	}

	public function map_template() {

		$template    = $this->api()->get( 'templates/' . $this->get_val( 'template' ) );
		$project     = $this->api()->get( 'projects/' . $this->get_val( 'project' ) );
		$existing_id = absint( $this->get_val( 'mapping' ) );

		$existing_note = $existing_id ? $this->view( 'existing-mapping-notice', array(
			'name' => $this->mappings->args->labels->singular_name,
			'id'   => $existing_id,
		), false ) : '';

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
			$existing_note . $title,
			$desc,
			$this->option_page_slug
		);

		$section->add_field(
			'template',
			'',
			function( $field ) use( $template ) {
				$project_id  = esc_attr( $this->get_val( 'project' ) );
				$template_id   = esc_attr( $this->get_val( 'template' ) );

				$existing_id   = absint( $this->get_val( 'mapping' ) );
				$existing_id   = $existing_id && get_post( $existing_id ) ? $existing_id : false;
				$stored_values = $this->stored_values();

				echo '<div id="mapping-tabs"><span class="gc-loader spinner is-active"></span></div>';
				// $tabs = $template->config;
				// $tabs[] = (object) array(
				// 	'name' => 'mapping-defaults',
				// 	'label' => __( 'Mapping Defaults', 'gathercontent-import' ),
				// 	'class' => 'alignright',
				// 	'content' => $this->view( 'mapping-defaults-tab', array(
				// 		'option_base' => $this->option_name,
				// 		'values'      => $stored_values,
				// 		'destination_post_options' => $this->destination_post_options( $stored_values ),
				// 	), false ),
				// );

				$tabs = array();
				foreach ( $template->config as $tab ) {

					$rows = array();
					foreach ( $tab->elements as $element ) {

						if ( isset( $stored_values[ $element->name ] ) ) {
							$val = $stored_values[ $element->name ];
							$element->field_type = isset( $val['type'] ) ? $val['type'] : '';
							$element->field_value = isset( $val['value'] ) ? $val['value'] : '';
						}

						$rows[] = $element;
					}

					$tab_array = array(
						'id'     => $tab->name,
						'label'  => $tab->label,
						'hidden' => ! empty( $tabs ),
						'rows'   => $rows,
					);

					$tabs[] = $tab_array;
				}

				$tabs[] = array(
					'id'          => 'mapping-defaults',
					'label'       => __( 'Mapping Defaults', 'gathercontent-import' ),
					'hidden'      => true,
					'navClasses'  => 'alignright',
					'viewId'      => 'defaultTab',
					'rows'        => $this->destination_post_options(),
					'post_author' => isset( $stored_values['post_author'] ) ? esc_attr( $stored_values['post_author'] ) : 1,
					'post_status' => isset( $stored_values['post_status'] ) ? esc_attr( $stored_values['post_status'] ) : 'draft',
					'post_type'   => isset( $stored_values['post_type'] ) ? esc_attr( $stored_values['post_type'] ) : 'post',
				);

				// $tabs['mapping-defaults'] = array(
				// 	'label' => __( 'Mapping Defaults', 'gathercontent-import' ),
				// 	'class' => 'alignright',
				// 	'content' => $this->view( 'mapping-defaults-tab', array(
				// 		'option_base' => $this->option_name,
				// 		'values'      => $stored_values,
				// 		'destination_post_options' => $this->destination_post_options( $stored_values ),
				// 	), false ),
				// );


				add_action( 'admin_footer', array( $this, 'footer_mapping_js_templates' ) );

				wp_localize_script( 'gathercontent', 'GatherContent', array(
					'debug'         => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
					'_tabs'         => $tabs,
					// 'optionBase' => $this->option_name,
					'_values'       => $stored_values,
				) );

				// $tabs = array();
				// foreach ( $template->config as $tab ) {
				// 	$tabs[ $tab->name ] = array(
				// 		'label' => $tab->label,
				// 		'content' => $this->view( 'mapping-tab', array(
				// 			'elements'    => $tab->elements,
				// 			'option_base' => $this->option_name,
				// 			'values'      => $stored_values,
				// 			'destination_post_options' => $this->destination_post_options( $stored_values ),
				// 		), false ),
				// 	);
				// }

				// $tabs['mapping-defaults'] = array(
				// 	'label' => __( 'Mapping Defaults', 'gathercontent-import' ),
				// 	'class' => 'alignright',
				// 	'content' => $this->view( 'mapping-defaults-tab', array(
				// 		'option_base' => $this->option_name,
				// 		'values'      => $stored_values,
				// 		'destination_post_options' => $this->destination_post_options( $stored_values ),
				// 	), false ),
				// );

				// $this->view( 'tabs-wrapper', array(
				// 	'tabs' => $tabs,
				// 	'before_tabs_wrapper' => $this->view( 'mapping-before-tab', array(
				// 		'option_base' => $this->option_name,
				// 		'values'      => $stored_values,
				// 		'post_types'  => $this->post_types(),
				// 	), false ),
				// ) );

				if ( $existing_id ) {
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

				$title = isset( $template->name ) ? $template->name : __( 'Map Template', 'gathercontent-import' );

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

	public function footer_mapping_js_templates() {
		?>
		<script type="text/html" id="tmpl-gc-tabs-wrapper"><?php $this->view( 'tabs-wrapper-js' ); ?></script>
		<script type="text/html" id="tmpl-gc-tab-wrapper"><?php $this->view( 'tab-wrapper-js' ); ?></script>
		<script type="text/html" id="tmpl-gc-mapping-tab-row"><?php $this->view( 'mapping-tab-row-js', array(
			'option_base'  => $this->option_name,
			'post_types'   => $this->post_types(),
			'post_options' => $this->destination_post_options(),
			'meta_options' => $this->add_custom_field_options(),
		) ); ?></script>
		<script type="text/html" id="tmpl-gc-post-fields-mapping">
			<select class="wp-type-value-select wp-post-type" name="<?php echo $this->option_name; ?>[mapping][value][{{ data.name }}]">
				<?php foreach ( $this->destination_post_options() as $col => $label ) : ?>
				<option <# if ( data.isSelected === '<?php echo $col; ?>' ) { #>selected="selected"<# } #> value="<?php echo $col; ?>"><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</script>
		<script type="text/html" id="tmpl-gc-mapping-defaults-tab"><?php $this->view( 'mapping-defaults-tab-js', array(
			'default_fields'      => $this->get_default_fields(),
			'post_author_label'   => $this->post_column_label( 'post_author' ),
			'post_author_options' => $this->get_default_field_options( 'post_author' ),
			'post_status_options' => $this->get_default_field_options( 'post_status' ),
			'post_status_label'   => $this->post_column_label( 'post_status' ),
			'post_type_label'     => __( 'Post Type', 'gathercontent-import' ),
			'post_type_options'   => $this->get_default_field_options( 'post_type' ),

			'option_base'         => $this->option_name,
		) ); ?></script>
		<?php
	}

	public function refresh_connection_link() {
		$args = array(
			'redirect_url' => false,
			'flush_url' => add_query_arg( array( 'flush_cache' => 1, 'redirect' => 1 ) ),
		);

		if ( $this->get_val( 'flush_cache' ) && $this->get_val( 'redirect' ) ) {
			update_option( 'gc-api-updated', 1 );
			$args['redirect_url'] = remove_query_arg( 'flush_cache', remove_query_arg( 'redirect' ) );
		}

		return $this->view( 'refresh-connection-button', $args, false );
	}

	public function project_name( $project ) {
		$project_name = '';

		if ( $project->name ) {
			$url = $this->get_setting( 'platform_url' ) . 'templates/' . $project->id;
			$project_name = '<p class="gc-project-name description">' . sprintf( _x( 'Project: %s', 'GatherContent project name', 'gathercontent-import' ), $project->name ) . ' | <a href="'. esc_url( $url ) .'" target="_blank">'. __( 'edit project templates', 'gathercontent-import' ) .'</a></p>';
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
				'items'        => array_slice( $items, 0, 5 ),
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
		if ( null !== $this->post_types ) {
			return $this->post_types;
		}

		$this->post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $this->post_types as $index => $type ) {
			$type->taxonomies = array();
			foreach ( get_object_taxonomies( $type->name, 'objects' ) as $tax ) {
				if ( 'post_format' === $tax->name ) {
					// continue;
					$tax->label = __( 'Post Formats', 'gathercontent-import' );
				}

				$type->taxonomies[] = $tax;
			}

			$this->post_types[ $index ] = $type;
		}

		return $this->post_types;
	}

	public function get_default_fields() {

		$stored_values = $this->stored_values();

		$new_options = array();

		foreach ( array( 'post_author', 'post_status', 'post_type' ) as $col ) {
			$label = 'post_type' === $col ? __( 'Post Type', 'gathercontent-import' ) : $this->post_column_label( $col );

			$new_options[] = array(
				'column'  => $col,
				'label'   => $label,
				'options' => $this->get_default_field_options( $col, $stored_values ),
			);
		}

		return $new_options;
	}

	public function get_default_field_options( $col ) {
		$select_options = array();

		switch ( $col ) {
			case 'post_author':
				$value = 1;
				$user = get_user_by( 'id', $value );
				$user = isset( $user->user_login ) ? $user->user_login : $user;
				$select_options[ $value ] = $user;

				$value = 3;
				$user = get_user_by( 'id', $value );
				$user = isset( $user->user_login ) ? $user->user_login : $user;
				$select_options[ $value ] = $user;
				break;
			case 'post_status':
				$select_options = array(
					'publish' => __( 'Published', 'gathercontent-import' ),
					'draft'   => __( 'Draft', 'gathercontent-import' ),
					'pending' => __( 'Pending', 'gathercontent-import' ),
					'private' => __( 'Private', 'gathercontent-import' ),
				);
				break;
			case 'post_type':
				foreach ( $this->post_types() as $type ) {
					$select_options[ $type->name ] = $type->labels->singular_name;
				}
				break;
		}

		return $select_options;
	}


	public function destination_post_options() {
		$options = array(
			// '' => __( 'Do Not Import', 'gathercontent-import' ),
		);

		// $options['post'] = array(
		// 	'group_name' => __( 'Post Fields', 'gathercontent-import' ),
		// 	'options'    => array(),
		// );

		foreach ( $this->get_wp_post_columns() as $col ) {
			if ( $label = $this->post_column_label( $col ) ) {
				// $options['post']['options'][ $col ] = $label;
				$options[ $col ] = $label;
			}
		}

		$options[''] = __( 'Do Not Import', 'gathercontent-import' );
		// $type = isset( $stored_values['post_type'] ) ? $stored_values['post_type'] : 'post';

		// $taxonomies = get_object_taxonomies( $type, 'objects' );

		// if ( ! empty( $taxonomies ) ) {

		// 	$options['taxonomies'] = array(
		// 		'group_name' => __( 'Taxonomies', 'gathercontent-import' ),
		// 		'options'    => array(),
		// 	);

		// 	foreach ( $taxonomies as $taxonomy ) {
		// 		$options['taxonomies']['options'][ $taxonomy->name ] = $taxonomy->labels->singular_name;
		// 	}
		// }

		// $options = $this->add_custom_field_options( $options );

		return $options;
	}

	protected function add_custom_field_options() {
		global $wpdb;

		$meta_keys = get_transient( 'gathercontent_importer_custom_field_keys' );

		if ( ! $meta_keys ) {
			// retrieve custom field keys to include in the Custom Fields weight table select
			$meta_keys = $wpdb->get_col( $wpdb->prepare( "
				SELECT meta_key
				FROM $wpdb->postmeta
				WHERE meta_key NOT LIKE %s
				GROUP BY meta_key
			",
				'_oembed_%'
			) );

			set_transient( 'gathercontent_importer_custom_field_keys', $meta_keys, DAY_IN_SECONDS );
		}

		// allow devs to filter this list
		$meta_keys = array_unique( apply_filters( 'gathercontent_importer_custom_field_keys', $meta_keys ) );

		// sort the keys alphabetically
		if ( $meta_keys ) {
			natcasesort( $meta_keys );
		} else {
			$meta_keys = array();
		}

		return $meta_keys;
	}

	protected function stored_values() {
		if ( null !== $this->stored_values ) {
			return $this->stored_values;
		}

		$existing_id = absint( $this->get_val( 'mapping' ) );
		$existing_id = $existing_id && get_post( $existing_id ) ? $existing_id : false;

		$values = array();

		if ( $existing_id ) {
			$json = get_post_field( 'post_content', $existing_id );
			$json = json_decode( $json, 1 );
			if ( is_array( $json ) ) {
				$values = $json;

				if ( isset( $values['mapping'] ) && is_array( $values['mapping'] ) ) {
					$mapping = $values['mapping'];
					unset( $values['mapping'] );
					$values += $mapping;
				}
			}
		}

		$this->stored_values = $values;

		return $this->stored_values;
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
			case 'post_type':
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
			// case 'post_type':
			// 	return __( 'Post Type', 'gathercontent-import' );
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






function gc_ajax_author_search() {
	error_log( 'gc_ajax_author_search $_REQUEST: '. print_r( $_REQUEST, true ) );
	wp_send_json_error();
}
add_action( 'wp_ajax_gc_get_option_data_post_author', 'gc_ajax_author_search' );
