<?php
namespace GatherContent\Importer\Admin\Mapping;

/**
 * Class for managing syncing template items.
 */
class Items_Sync extends Base {

	/**
	 * Template_Mappings
	 *
	 * @var Template_Mappings
	 */
	public $mappings;

	protected $items = array();

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->mappings = $args['mappings'];
		$this->items    = $args['items'];
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
		?>
		<input type="hidden" name="mapping_id" id="gc-input-mapping_id" value="<?php echo $this->mapping_id; ?>"/>
		<?php foreach ( $_GET as $key => $value ) : if ( 'mapping' === $key ) { continue; } ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" id="gc-input-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<?php endforeach; ?>
		<div id="sync-tabs"><span class="gc-loader spinner is-active"></span></div>
		<p class="description">
			<a href="<?php echo get_edit_post_link( $this->mapping_id ); ?>"><?php echo $this->mappings->args->labels->edit_item; ?></a>
		</p>
		<?php
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
			'_items'  => array_values( $this->items ),
			'percent' => $this->mappings->get_pull_percent( $this->mapping_id ),
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
			'tmpl-gc-items-sync' => array(),
			'tmpl-gc-item' => array(),
			'tmpl-gc-items-sync-progress' => array(),
		);
	}

}
