<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Base as Plugin_Base;

/**
 * Class for managing syncing template items.
 */
class Items_Sync extends Plugin_Base {

	protected $mapping_id;
	protected $template;
	protected $project;

	/**
	 * Field_Types\Types
	 *
	 * @var Field_Types\Types
	 */
	public $field_types;

	public function __construct( array $args ) {
		$this->mapping_id        = $args['mapping_id'];
		$this->template          = $args['template'];
		$this->project           = $args['project'];
		$this->edit_mapping_link = $args['edit_mapping_link'];
	}

	/**
	 * The sync page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function sync_ui() {

		// Output the markup for the JS to build on.
		echo '<div id="sync-tabs"><span class="gc-loader spinner is-active"></span></div>';


		echo '<p class="description">';
		echo $this->edit_mapping_link;
		echo '</p>';


		// Hook in the underscores templates
		// add_action( 'admin_footer', array( $this, 'footer_sync_js_templates' ) );
		// add_filter( 'gathercontent_localized_data', array( $this, 'localize_data' ) );

		\GatherContent\Importer\enqueue_script( 'gathercontent-sync', 'gathercontent-sync', array( 'gathercontent' ) );

	}

}
