<?php
namespace GatherContent\Importer\Admin\Mapping;

/**
 * Class for managing syncing template items.
 */
class Items_Sync extends Base {

	protected $items = array();
	protected $edit_mapping_link = '';

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->items             = $args['items'];
		$this->edit_mapping_link = $args['edit_mapping_link'];

		add_filter( 'gc_template_args_for_admin-page', array( $this, 'replace_page_buttons' ) );
	}

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	protected function script_id() {
		return 'gathercontent-sync';
	}

	/**
	 * The sync page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function ui_page() {

		// Output the markup for the JS to build on.
		echo '<div id="sync-tabs"><span class="gc-loader spinner is-active"></span></div>';

		print( '<xmp>$this->items: '. print_r( $this->items, true ) .'</xmp>' );
		echo '<p class="description">';
		echo $this->edit_mapping_link;
		echo '</p>';

	}

	/**
	 * Get the localizable data array.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of localizable data
	 */
	protected function get_localize_data() {
		return array(
			// '_tabs'      => $this->get_tabs(),
			// '_meta_keys' => $this->custom_field_keys(),
		);
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	protected function get_underscore_templates() {
		return array(
			// 'tmpl-gc-mapping-tab-row' => array(
			// 	'option_base' => $this->option_name,
			// 	'post_types'  => $this->post_types(),
			// ),
		);
	}

	public function replace_page_buttons( $args ) {

		$args['go_back_url'] = '';
		$args['submit_button_text'] = __( 'Sync Items', 'gathercontent-import' );

		return $args;
	}

}
