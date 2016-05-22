<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\API;
use GatherContent\Importer\Settings\Setting;

abstract class Base extends Plugin_Base {

	public $option_page_slug = '';
	public $option_name      = '';
	public $option_group     = '';
	public $url              = '';
	public $step             = 0;
	public $menu_priority    = 9;

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected static $api = null;

	/**
	 * GatherContent\Importer\Settings instance
	 *
	 * @var GatherContent\Importer\Settings
	 */
	protected $settings = null;

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
	public function __construct() {
		parent::__construct();

		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->url = admin_url( 'admin.php?page='. $this->option_page_slug );
		$this->logo = '<img width="220px" height="39px" src="'. GATHERCONTENT_URL . 'images/logo.svg" alt="GatherContent" />';
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		if ( did_action( 'admin_menu' ) ) {
			$this->admin_menu();
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), $this->menu_priority );
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
	abstract public function admin_menu();

	abstract public function admin_page();

	/**
	 * Initializes the plugin's setting, and settings sections/Fields.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function initialize_settings_sections() {
		register_setting(
			$this->option_group,
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	public function sanitize_settings( $options ) {
		self::$api->flush_cache();
		return $this->settings()->sanitize_settings( $options );
	}

	public function admin_enqueue_style() {
		wp_enqueue_style( 'gathercontent', GATHERCONTENT_URL . "assets/css/gathercontent-importer.{$this->suffix}css", array(), GATHERCONTENT_VERSION );
	}

	public function admin_enqueue_script() {
		wp_enqueue_script( 'gathercontent', GATHERCONTENT_URL . "assets/js/gathercontent-importer{$this->suffix}.js", array( 'jquery' ), GATHERCONTENT_VERSION, 1 );
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
		return $this->step;
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
			$this->settings = new Setting( $this->option_name, $this->default_options );
		}

		return $this->settings;
	}

	protected function api() {
		if ( null === self::$api ) {
			throw new \Exception( 'Must set the API object with '. get_class( $this ) .'::set_api( $api ).' );
		}

		return self::$api;
	}

	protected function set_api( API $api ) {
		return self::$api = $api;
	}

}
