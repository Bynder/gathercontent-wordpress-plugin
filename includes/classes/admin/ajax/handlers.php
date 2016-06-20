<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;

class Handlers extends Plugin_Base {

	/**
	 * Select2
	 *
	 * @var Select2
	 */
	public $select2;

	/**
	 * Sync_Items
	 *
	 * @var Sync_Items
	 */
	public $sync_items;

	public function __construct() {
		$this->select2 = new Select2;
		$this->sync_items = new Sync_Items;
	}

	public function init_hooks() {
		add_action( 'wp_ajax_gc_get_option_data', array( $this->select2, 'callback' ) );
		add_action( 'wp_ajax_gc_sync_items', array( $this->sync_items, 'callback' ) );
	}
}
