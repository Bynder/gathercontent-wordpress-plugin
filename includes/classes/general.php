<?php
namespace GatherContent\Importer;

class General extends Base {

	protected static $single_instance = null;

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	public $api;

	/**
	 * GatherContent\Importer\Admin instance
	 *
	 * @var GatherContent\Importer\Admin
	 */
	public $admin;

	/**
	 * GatherContent\Importer\Select2_Ajax_Handler instance
	 *
	 * @var GatherContent\Importer\Select2_Ajax_Handler
	 */
	public $ajax_handler;

	/**
	 * Creates or returns an instance of this class.
	 * @since  3.0.0
	 * @return General A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	protected function __construct() {
		parent::__construct( $_GET, $_POST );

		$this->api = new API( _wp_http_get_object() );
		$this->admin = new Admin\Admin( $this->api );
		$this->ajax_handler = new Select2_Ajax_Handler;
	}

	public function init_hooks() {
		$this->admin->init_hooks();
		$this->ajax_handler->init_hooks();
	}

}

