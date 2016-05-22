<?php
namespace GatherContent\Importer;

class Admin extends Base {

	public $option_page_slug = GATHERCONTENT_SLUG;
	public $option_name      = 'gathercontent_importer';
	public $option_group     = 'gathercontent_importer_settings';
	public $url              = '';

	/**
	 * API instance
	 *
	 * @var API
	 */
	protected $api;

	/**
	 * GatherContent\Importer\Settings instance
	 *
	 * @var GatherContent\Importer\Settings
	 */
	public $settings = null;

	/**
	 * Default option value (if none is set)
	 *
	 * @var array
	 */
	public $default_options = array(
		'account_owner' => '',
		'platform_url'  => '',
		'api_key'       => '',
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( API $api ) {
		parent::__construct();

		$this->api = $api;
		$this->url = admin_url( '?page='. $this->option_page_slug );
		$this->logo = '<img width="220px" height="39px" src="'. GATHERCONTENT_URL . 'images/logo.svg" alt="GatherContent" />';

		if (
			$this->get_setting( 'account_owner_email' )
			&& $this->get_setting( 'platform_url' )
			&& $this->get_setting( 'api_key' )
			&& 'gathercontent-import' === $this->get_val( 'page' )
		) {

			$review = absint( $this->get_val( 'review' ) );
			if ( $review && 2 !== $review ) {
				return;
			}

			$this->api->set_user( $this->get_setting( 'account_owner_email' ) );
			$this->api->set_api_key( $this->get_setting( 'api_key' ) );

			if ( 2 === $review ) {
				$this->api->flush = true;
			}

			$user = $this->api->request_cache( 'me', DAY_IN_SECONDS );

			if ( ! isset( $user->data ) ) {

				if ( 2 === $review ) {
					return add_settings_error( $this->option_name, 'gc-api-connect-fail', __( 'We had trouble connecting to the GatherContent API. Please check your settings.', 'gathercontent-import' ), 'error' );
				}

				wp_redirect( esc_url_raw( add_query_arg( array( 'step' => 1, 'review' => 2 ), $this->url ) ) );
			}

			if ( $this->which_step() < 2 ) {
				wp_redirect( esc_url_raw( add_query_arg( 'step', 2, $this->url ) ) );
				exit;
			}

		}
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( did_action( 'admin_menu' ) ) {
			$this->admin_menu();
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

		$this->initialize_settings_sections();
	}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	function admin_menu() {
		$hook_suffix = add_menu_page(
			$this->logo,
			'GatherContent',
			'publish_pages',
			$this->option_page_slug,
			array( $this, 'admin_page' ),
			GATHERCONTENT_URL . 'images/menu-logo.svg'
		);

		// add_action( 'admin_print_scripts-' . $hook_suffix, array($this, 'admin_print_scripts') );
		add_action( 'admin_print_styles-' . $hook_suffix, array($this, 'admin_print_styles') );
	}

	public function admin_page() {

		if ( $key = $this->should_migrate() ) {

			// @todo implement migration wizzard.
			$this->view( 'migrate-from-old', array(
				'settings'       => get_option( $key . '_saved_settings' ),
				'media_files'    => get_option( $key . '_media_files' ),
				'selected_items' => get_option( $key . '_selected_items' ),
				'project_id'     => get_option( $key . '_project_id' ),
				'api_key'        => get_option( $key . '_api_key' ),
				'api_url'        => get_option( $key . '_api_url' ),
				'version'        => get_option( 'gathercontent_version' ),
			) );

		} else {
			$this->view( 'admin-page', array(
				'logo'              => $this->logo,
				'option_group'      => $this->option_group,
				'settings_sections' => Settings\Form_Section::get_sections( $this->which_step() ),
			) );
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

		$this->step_one_settings();
		$this->steps_complete_settings();

		register_setting(
			$this->option_group,
			$this->option_name,
			array( $this->settings(), 'sanitize_settings' )
		);
	}

	public function admin_print_styles() {
		wp_enqueue_style( 'gathercontent', GATHERCONTENT_URL . 'assets/css/gathercontent-importer.css', array(), GATHERCONTENT_VERSION );
	}

	public function step_one_settings() {

		$section = new Settings\Form_Section(
			'step_1',
			__( 'API Credentials', 'gathercontent-import' ),
			function() {
				echo '<p>' . sprintf( __( 'Enter you GatherContent API credentials. Instructions for getting your API key can be found <a href="%s" target="_blank">here</a>.', 'gathercontent-import' ), 'https://gathercontent.com/developers/authentication/' ) . '</p>';
			},
			$this->option_page_slug
		);

		$section->add_field(
			'account_owner_email',
			__( 'Account Owner Email Address', 'gathercontent-import' ),
			function( $field ) {
				$id = $field->param( 'id' );

				$this->view( 'input', array(
					'id' => $id,
					'name' => $this->option_name .'['. $id .']',
					'value' => esc_attr( $this->get_setting( $id ) ),
				) );

			}
		);

		$section->add_field(
			'platform_url',
			__( 'Platform URL', 'gathercontent-import' ),
			function( $field ) {
				$id = $field->param( 'id' );

				$this->view( 'input', array(
					'id' => $id,
					'name' => $this->option_name .'['. $id .']',
					'value' => esc_attr( $this->get_setting( $id ) ),
					'placeholder' => 'https://your-account.gathercontent.com/',
				) );

			}
		);

		$section->add_field(
			'api_key',
			__( 'API Key', 'gathercontent-import' ),
			function( $field ) {
				$id = $field->param( 'id' );

				$this->view( 'input', array(
					'id' => $id,
					'name' => $this->option_name .'['. $id .']',
					'value' => esc_attr( $this->get_setting( $id ) ),
					'placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
					'desc' => '<a href="https://gathercontent.com/developers/authentication/" target="_blank">'. __( 'How to get your API key', 'gathercontent-import' ) . '</a>',
				) );

			}
		);
	}

	public function steps_complete_settings() {

		$section = new Settings\Form_Section(
			'steps_complete',
			'',
			function() {
				$user = $this->api->request_cache( 'me', DAY_IN_SECONDS );
				if ( isset( $user->data ) ) {
					if ( isset( $user->data->first_name ) ) {
						$this->view( 'user-profile', (array) $user->data );
					}

					echo '<xmp>$user: '. print_r( $user, true ) .'</xmp>';
				}
			},
			$this->option_page_slug
		);

		// @todo implement next steps
	}

	/**
	 * Determine if settings need to be migrated from previous version.
	 *
	 * Since previous version used `plugin_basename( __FILE__ )` to determine
	 * the option prefix, we have to check a couple possible variations.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed Settings key prefix, if old settings are found.
	 */
	public function should_migrate() {
		$prefixes = array(
			'gathercontent-import', // from wordpress.org/plugins/gathercontent-import
			'wordpress-plugin', // from github.com/gathercontent/wordpress-plugin

			// 'gathercontent-import-old', // local copy
		);

		foreach ( $prefixes as $prefix ) {
			if ( get_option( $prefix . '_api_key' ) && get_option( $prefix . '_api_url' ) ) {
				return $prefix;
			}
		}

		return false;
	}

	/**
	 * Determine which step user is on.
	 *
	 * @todo  This should be determined which options they have filled out, and redirect user to step.
	 *
	 * @since  3.0.0
	 *
	 * @return int  Step number.
	 */
	public function which_step() {
		return $this->get_has( 'step' ) ? absint( $this->get_val( 'step' ) ) : 0;
	}

	/**
	 * Get option value.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $key Key from options array to retrieve.
	 *
	 * @return mixed       Value for option.
	 */
	public function get_setting( $key ) {
		return $this->settings()->get( $key );
	}

	/**
	 * Gets the Settings object
	 *
	 * @since  3.0.0
	 *
	 * @return Settings
	 */
	public function settings() {
		if ( null === $this->settings ) {
			$this->settings = new Settings\Setting( $this->option_name, $this->default_options );
		}

		return $this->settings;
	}

}
