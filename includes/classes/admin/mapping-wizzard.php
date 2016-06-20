<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Settings\Setting;
use GatherContent\Importer\Settings\Form_Section;
use GatherContent\Importer\Post_Types\Template_Mappings;

/**
 * Class for the template mappings creation wizzard.
 */
class Mapping_Wizzard extends Base {

	public $parent_page_slug;
	public $parent_url;
	public $project_items = array();
	public $stored_values = null;
	public $menu_priority = 11; // Puts "New Mapping" after "Template Mappings" CPT menu.

	/**
	 * Template_Mappings
	 *
	 * @var Template_Mappings
	 */
	public $mappings;

	/**
	 * Mapping\Template_Mapper
	 *
	 * @var Mapping\Template_Mapper
	 */
	public $template_mapper;

	/**
	 * Mapping\Items_Sync
	 *
	 * @var Mapping\Items_Sync
	 */
	public $items_sync;

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
	 */
	public function __construct( Admin $parent ) {
		$this->option_page_slug = $parent->option_page_slug . '-add-new-template';
		$this->option_name      = $parent->option_name . '_add_new_template';
		$this->option_group     = $parent->option_group . '_add_new_template';
		$this->parent_page_slug = $parent->option_page_slug;
		$this->parent_url       = $parent->url;
		$this->settings         = new Setting( $parent->option_name, $parent->default_options );
		$this->mappings         = new Template_Mappings( $parent->option_page_slug );

		if ( $this->_get_val( 'project' ) ) {
			$this->step = 1;

			if ( $this->_get_val( 'template' ) ) {
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
			apply_filters( 'gathercontent_settings_view_capability', 'publish_pages' ),
			$this->option_page_slug,
			array( $this, 'admin_page' )
		);

		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_style' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_script' ) );
	}

	public function admin_page() {
		$this->register_notices();

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

	public function register_notices() {
		if ( get_option( 'gc-api-updated' ) ) {
			$this->add_settings_error( $this->option_name, 'gc-api-connection-reset', __( 'We refreshed the data from the GatherContent API.', 'gathercontent-import' ), 'updated' );
			delete_option( 'gc-api-updated' );
		}

		if ( $this->_get_val( 'updated' ) &&  $this->_get_val( 'project' ) &&  $this->_get_val( 'template' ) ) {

			if ( $this->_get_val( 'sync-items' ) ) {
				return $this->add_settings_error( $this->option_name, 'gc-mapping-updated', __( 'Item Sync complete!', 'gathercontent-import' ), 'updated' );
			}

			$label = 1 === absint( $this->_get_val( 'updated' ) ) ? 'item_updated' : 'item_saved';
			$this->add_settings_error( $this->option_name, 'gc-mapping-updated', $this->mappings->args->labels->{$label}, 'updated' );
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
	 * Step one of the mapping wizzard, pick a project from list of accounts.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
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
			array( $this, 'select_project_fields_ui' )
		);
	}

	public function select_project_fields_ui( $field ) {
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

			$tabs[] = array(
				'id' => $account->id,
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

	/**
	 * Step two of the mapping wizzard, pick a template in the chosen project.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function select_template() {
		$project = $this->api()->get( 'projects/' . $this->_get_val( 'project' ) );

		$section = new Form_Section(
			'select_template',
			__( 'Next, select a template to map.', 'gathercontent-import' ),
			$this->project_name_and_edit_link( $project ),
			$this->option_page_slug
		);

		$section->add_field(
			'template',
			'',
			array( $this, 'select_template_fields_ui' )
		);

	}

	public function select_template_fields_ui( $field ) {
		$field_id   = $field->param( 'id' );
		$project_id = $this->_get_val( 'project' );
		$options    = array();

		$value = '';
		if ( $templates = $this->api()->get( 'templates?project_id=' . $project_id ) ) {

			foreach ( $templates as $template ) {
				$desc = esc_attr( $template->description );

				if ( $items = $this->get_project_items_list( $project_id, $template->id ) ) {
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

	/**
	 * Step three, the final step of the mapping wizzard. Create a template-mapping.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function map_template() {
		$template   = $this->api()->get( 'templates/' . esc_attr( $this->_get_val( 'template' ) ) );
		$project    = $this->api()->get( 'projects/' . esc_attr( $this->_get_val( 'project' ) ) );
		$mapping_id = absint( $this->_get_val( 'mapping' ) );
		$mapping_id = $mapping_id && get_post( $mapping_id ) ? $mapping_id : false;
		$sync_items = $mapping_id && $this->_get_val( 'sync-items' );
		$notes      = '';

		if ( ! $sync_items && $mapping_id ) {
			$notes .= $this->view( 'existing-mapping-notice', array(
				'name' => $this->mappings->args->labels->singular_name,
				'id'   => $mapping_id,
			), false );
		}

		if ( ! isset( $project->id, $template->id ) ) {
			$notes = $this->view( 'no-mapping-or-template-available', array(), false ) . $notes;
		}

		$title = isset( $template->name )
			? $template->name
			: __( 'Unknown Template', 'gathercontent-import' );

		if ( $sync_items ) {
			$title_prefix = __( 'Sync Items for: %s', 'gathercontent-import' );
		} elseif ( $mapping_id ) {
			$title_prefix = __( 'Edit Mapping for: %s', 'gathercontent-import' );
		} else {
			$title_prefix = __( 'Create Mapping for: %s', 'gathercontent-import' );
		}
		$title = sprintf( $title_prefix, $title );

		$desc = '';
		if ( $template && isset( $template->description ) ) {
			$desc .= '<h4 class="description">' . esc_attr( $template->description ) . '</h4>';
		}

		$desc .= $this->project_name_and_edit_link( $project );

		$section = new Form_Section(
			'select_template',
			$notes . $title,
			$desc,
			$this->option_page_slug
		);

		if ( ! $sync_items ) {
			$this->template_mapper = new Mapping\Template_Mapper( array(
				'mapping_id'  => $mapping_id,
				'template'    => $template,
				'project'     => $project,
				'statuses'    => $this->api()->get( 'projects/' . esc_attr( $this->_get_val( 'project' ) ) .'/statuses' ),
				'option_name' => $this->option_name,
			) );

			$callback = isset( $project->id, $template->id )
				? array( $this->template_mapper, 'ui' )
				: '__return_empty_string';

			$section->add_field( 'mapping', '', $callback );

		} else {

			$edit_link = sprintf(
				'<a href="%s">%s</a>',
				get_edit_post_link( $mapping_id ),
				$this->mappings->args->labels->edit_item
			);

			$this->items_sync = new Mapping\Items_Sync( array(
				'mapping_id'        => $mapping_id,
				'template'          => $template,
				'project'           => $project,
				'items'             => $this->filter_items_by_template( $project->id, $template->id ),
				'edit_mapping_link' => $edit_link,
			) );

			$section->add_field( 'mapping', '', array( $this->items_sync, 'ui' ) );
		}
	}

	/**
	 * Like inception, checks how many levels deep we are in the Template Mapping process,
	 * and performs the appropriate action/redirect.
	 *
	 * Does not actually save fields, but simply uses the field values to determine where
	 * we are in the process (wizzard).
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
			// Send the user to the template-picker.
			$this->redirect_to_template_picker( $project );
		}

		// Ok, we have a template.
		$template = esc_attr( $options['template'] );

		if ( ! isset( $options['create_mapping'] ) ) {
			// Send the user to the mapping-creator.
			$this->redirect_to_mapping_creation( $project, $template );
		}

		// Ok, we have all we need. Let's attempt to create a new mapping post.
		$this->create_new_mapping_post_and_redirect( $project, $template, $options );
	}

	/**
	 * Redirects to the template-picker page of the wizzard.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $project GC Project ID.
	 *
	 * @return void
	 */
	protected function redirect_to_template_picker( $project ) {
		wp_safe_redirect( esc_url_raw( add_query_arg( 'project', $project, $this->url ) ) );
		exit;
	}

	/**
	 * Redirects to the (final) mapping-creator page of the wizzard.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $project  GC Project ID.
	 * @param  int $template GC Template ID.
	 *
	 * @return void
	 */
	protected function redirect_to_mapping_creation( $project, $template ) {

		// Let's check if we already have a mapped template.
		$exists = $this->mappings->get_by_project_template( $project, $template );

		$args = compact( 'project', 'template' );
		if ( $exists->have_posts() ) {
			// Yep, we found one.
			$args['mapping'] = $exists->posts[0];
			$args['settings-updated'] = 1;
		}

		// Now redirect to the template mapping.
		wp_safe_redirect( esc_url_raw( add_query_arg( $args, $this->url ) ) );
		exit;
	}

	/**
	 * Creates/Saves a mapping post after submission and redirects back
	 * to mapping-creator page to edit new mapping.
	 *
	 * @since  3.0.0
	 *
	 * @param  int   $project  GC Project ID.
	 * @param  int   $template GC Template ID.
	 * @param  array $options Array of options/values submitted.
	 *
	 * @return void
	 */
	protected function create_new_mapping_post_and_redirect( $project, $template, $options ) {
		if ( ! wp_verify_nonce( $options['create_mapping'], md5( $project . $template ) ) ) {

			// Let check_admin_referer handle the fail.
			check_admin_referer( 'fail', 'fail' );
		}

		$post_id = $this->create_new_mapping_post( $options );

		if ( is_wp_error( $post_id ) ) {
			wp_die( $post_id->get_error_message(), __( 'Failed creating mapping!', 'gathercontent-import' ) );
		}

		$edit_url = get_edit_post_link( $post_id, 'raw' );

		$status = isset( $options['existing_mapping_id'] ) && $options['existing_mapping_id'] ? 1 : 2;

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'updated' => $status ), $edit_url ) ) );
		exit;
	}

	/**
	 * A URL for flushing the cached connection to GC's API
	 *
	 * @since  3.0.0
	 *
	 * @return string URL for flushing cache.
	 */
	public function refresh_connection_link() {
		$args = array(
			'redirect_url' => false,
			'flush_url' => add_query_arg( array( 'flush_cache' => 1, 'redirect' => 1 ) ),
		);

		if ( $this->_get_val( 'flush_cache' ) && $this->_get_val( 'redirect' ) ) {
			update_option( 'gc-api-updated', 1 );
			$args['redirect_url'] = remove_query_arg( 'flush_cache', remove_query_arg( 'redirect' ) );
		}

		return $this->view( 'refresh-connection-button', $args, false );
	}

	public function project_name_and_edit_link( $project ) {
		$project_name = '';

		if ( $project->name ) {
			$url = $this->get_setting( 'platform_url' ) . 'templates/' . $project->id;
			$project_name = '<p class="gc-project-name description">' . sprintf( _x( 'Project: %s', 'GatherContent project name', 'gathercontent-import' ), $project->name ) . ' | <a href="'. esc_url( $url ) .'" target="_blank">'. __( 'edit project templates', 'gathercontent-import' ) .'</a></p>';
		}

		return $project_name;
	}

	public function get_project_items_list( $project_id, $template_id, $class = 'gc-radio-desc' ) {
		$items = $this->filter_items_by_template( $project_id, $template_id );

		$list = '';
		if ( ! empty( $items ) ) {
			$list = $this->view( 'gc-items-list', array(
				'class'        => $class,
				'platform_url' => $this->get_setting( 'platform_url' ),
				'items'        => array_slice( $items, 0, 5 ),
			), false );
		}

		return $list;
	}

	public function filter_items_by_template( $project_id, $template_id ) {
		$items = $this->get_project_items( $project_id );

		$tmpl_items = is_array( $items )
			? wp_list_filter( $items, array( 'template_id' => $template_id ) )
			: array();

		return $tmpl_items;
	}

	public function get_project_items( $project_id ) {
		if ( isset( $this->project_items[ $project_id ] ) ) {
			return $this->project_items[ $project_id ];
		}

		$this->project_items[ $project_id ] = $this->api()->get( 'items?project_id=' . $project_id );

		return $this->project_items[ $project_id ];
	}

	/**
	 * Create or update a template mapping post using the saved options/values.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $options Array of options from mapping UI.
	 *
	 * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
	 */
	protected function create_new_mapping_post( $options ) {
		$post_args = $mapping_args = array();

		$mapping_args = \GatherContent\Importer\array_map_recursive( 'sanitize_text_field', $options );

		unset( $options['create_mapping'] );
		unset( $options['title'] );
		unset( $options['project'] );
		unset( $options['template'] );

		if ( isset( $options['existing_mapping_id'] ) ) {
			$post_args['ID'] = absint( $options['existing_mapping_id'] );
			unset( $options['existing_mapping_id'] );
		}

		$mapping_args['content'] = $options;

		return $this->mappings->create_mapping( $mapping_args, $post_args, $post_args, 1 );
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
			|| ! $this->_get_val( 'project' )
			|| ! $this->_get_val( 'template' )
		) {
			return;
		}

		$mapping_id = absint( $this->_get_val( 'mapping' ) );

		$exists = $this->mappings->get_by_project_template(
			sanitize_text_field( $this->_get_val( 'project' ) ),
			sanitize_text_field( $this->_get_val( 'template' ) )
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

}
