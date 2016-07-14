<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\API;
use GatherContent\Importer\General;
use GatherContent\Importer\Settings\Setting;
use GatherContent\Importer\Settings\Form_Section;

class Admin extends Base {

	public $option_name  = General::OPTION_NAME;
	public $option_group = 'gathercontent_importer_settings';
	public $mapping_wizzard;

	/**
	 * Default option value (if none is set)
	 *
	 * @var array
	 */
	public $default_options = array(
		'account_email'     => '',
		'platform_url_slug' => '',
		'api_key'           => '',
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( API $api ) {
		global $pagenow;
		parent::set_api( $api );
		parent::__construct();
		if (
			$this->get_setting( 'account_email' )
			&& $this->get_setting( 'platform_url_slug' )
			&& $this->get_setting( 'api_key' )
		) {
			$this->step = 1;
			$this->api()->set_user( $this->get_setting( 'account_email' ) );
			$this->api()->set_api_key( $this->get_setting( 'api_key' ) );

			// Get 'me'. If that fails, try again w/o cached response, to flush "fail" response cache.
			if ( ! defined( 'DOING_AJAX' ) && ! $this->api()->get_me() && ! $this->api()->get_me( 1 ) ) {

				if ( 'admin.php' === $pagenow && self::SLUG === $this->_get_val( 'page' ) ) {

					$response = $this->api()->get_last_response();

					$message = __( 'We had trouble connecting to the GatherContent API. Please check your settings.', 'gathercontent-import' );

					if ( is_wp_error( $response ) ) {
						$message .= '</p><p>' . sprintf( esc_html__( 'The error received: %s', 'gathercontent-import' ), $response->get_error_message() );
					}

					$this->add_settings_error( $this->option_name, 'gc-api-connect-fail', $message, 'error' );
				}

				$this->step = 0;
			}

		}

		if ( $this->step > 0 ) {
			$this->mapping_wizzard = new Mapping_Wizzard( $this );
		}

	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		parent::init_hooks();

		if ( $this->mapping_wizzard ) {
			$this->mapping_wizzard->init_hooks();
		}
	}

	public function sanitize_settings( $settings ) {
		$settings = parent::sanitize_settings( $settings );

		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $value ) {

			switch ( $key ) {
				case 'account_email':
					$value = is_email( $value ) ? sanitize_text_field( $value ) : '';
					break;
				default:
					$value = is_scalar( $value ) ? sanitize_text_field( $value ) : '';
					break;
			}


			$settings[ $key ] = $value;
		}

		if ( isset( $settings['account_owner_email'] ) ) {
			unset( $settings['account_owner_email'] );
		}

		return $settings;
	}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	function admin_menu() {
		$page = add_menu_page(
			$this->logo,
			'GatherContent',
			\GatherContent\Importer\view_capability(),
			self::SLUG,
			array( $this, 'admin_page' ),
			GATHERCONTENT_URL . 'images/menu-logo.svg'
		);

		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_style' ) );
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
				'settings_sections' => Form_Section::get_sections( self::SLUG ),
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
		if ( $this->step > 0 ) {
			$this->api_setup_complete();
		}

		$this->api_setup_settings();

		parent::initialize_settings_sections();
	}

	public function api_setup_settings() {

		$section = new Form_Section(
			'step_1',
			__( 'API Credentials', 'gathercontent-import' ),
			function() {
				echo '<p>' . sprintf( __( 'Enter you GatherContent API credentials. Instructions for getting your API key can be found <a href="%s" target="_blank">here</a>.', 'gathercontent-import' ), 'https://gathercontent.com/developers/authentication/' ) . '</p>';
			},
			self::SLUG
		);

		$section->add_field(
			'account_email',
			__( 'GatherContent Email Address', 'gathercontent-import' ),
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
			'platform_url_slug',
			__( 'Platform URL', 'gathercontent-import' ),
			function( $field ) {
				$id = $field->param( 'id' );

				echo '<div class="platform-url-wrap">';

				echo '<div class="platform-url-help gc-domain-prefix">https://</div>';

				$this->view( 'input', array(
					'id' => $id,
					'name' => $this->option_name .'['. $id .']',
					'value' => esc_attr( $this->get_setting( $id ) ),
					'placeholder' => 'your-account',
				) );

				echo '<div class="platform-url-help gc-domain">.gathercontent.com</div>';

				echo '</div>';
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

	public function api_setup_complete() {
		$section = new Form_Section(
			'steps_complete',
			'',
			function() {

				if ( $user = $this->api()->get_me() ) {
					if ( isset( $user->first_name ) ) {

						$data = (array) $user;

						$data['message'] = esc_html__( "You've successfully connected to the GatherContent API", 'gathercontent-import' );

						$data['avatar'] = ! empty( $data['avatar'] )
							? 'https://gathercontent-production-avatars.s3-us-west-2.amazonaws.com/' . $data['avatar']
							: 'https://app.gathercontent.com/assets/img/avatar.png';

						if ( $this->set_my_account() ) {

							$data['message'] .= ' '. sprintf( esc_html__( "and the %s account.", 'gathercontent-import' ), '<a href="'. esc_url( $this->platform_url() ) .'" target="_blank">'. esc_html( $this->account->name ) .'</a>' );
						}

						$this->view( 'user-profile', $data );
					}
				}
			},
			self::SLUG
		);
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

}
