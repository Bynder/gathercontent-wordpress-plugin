<?php
namespace GatherContent\Importer;

class General {

	protected static $single_instance = null;

	/**
	 * GatherContent\Importer\Admin instance
	 *
	 * @var GatherContent\Importer\Admin
	 */
	public $admin;

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

	}

	public function init() {

	}

}

