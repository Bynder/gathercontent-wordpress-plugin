<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Post_Types\Template_Mappings;

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

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $mappings Template_Mappings object
	 */
	public function __construct( Template_Mappings $mappings ) {
		$this->select2    = new Select2;
		$this->sync_items = new Sync_Items( $mappings );
	}

	public function init_hooks() {
		add_action( 'wp_ajax_gc_get_option_data', array( $this->select2, 'callback' ) );
		add_action( 'wp_ajax_gc_sync_items', array( $this->sync_items, 'callback' ) );
	}
}
