<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\General;
use GatherContent\Importer\API;
use GatherContent\Importer\Admin\Mapping\Base as UI_Base;
use GatherContent\Importer\Admin\Enqueue;

class Single_Enqueue extends Enqueue {}

class Single extends UI_Base {

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected $api = null;

	/**
	 * GatherContent\Importer\Post_Types\Template_Mappings instance
	 *
	 * @var GatherContent\Importer\Post_Types\Template_Mappings
	 */
	protected $mappings = null;

	/**
	 * GatherContent\Importer\Admin\Enqueue instance
	 *
	 * @var GatherContent\Importer\Admin\Enqueue
	 */
	protected $enqueue = null;

	/**
	 * A flag to check if this is an ajax request.
	 *
	 * @var boolean
	 */
	protected $doing_ajax = false;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api      API object
	 * @param $mappings Template_Mappings object
	 */
	public function __construct( API $api, Template_Mappings $mappings ) {
		$this->api        = $api;
		$this->mappings   = $mappings;
		$this->enqueue    = new Single_Enqueue;
		$this->doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	protected function script_id() {
		return 'gathercontent-single';
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		// add_meta_box( 'submitdiv', __('Save'), 'attachment_submit_meta_box', null, 'side', 'core' );
		$this->post_types = $this->mappings->get_mapping_post_types();

		global $pagenow;
		if (
			$pagenow
			&& ! empty( $this->post_types )
			&& 'post.php' === $pagenow
			) {
			add_action( 'admin_enqueue_scripts', array( $this, 'ui' ) );
		}

		// Handle quick-edit/bulk-edit ajax-post-saving
		add_action( 'save_post', array( $this, 'set_gc_status' ), 10, 2 );
	}

	/**
	 * The Bulk Edit page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function ui_page() {
		$screen = get_current_screen();

		if (
			'post' !== $screen->base
			|| ! $screen->post_type
			// || ! isset( $this->post_types[ $screen->post_type ] )
		) {
			return;
		}

		if ( ! isset( $this->post_types[ $screen->post_type ] ) ) {
			return;
		}

		$this->enqueue->admin_enqueue_style();
		$this->enqueue->admin_enqueue_script();

		add_meta_box( 'gc-manage', 'GatherContent', array( $this, 'meta_box' ), $screen, 'side', 'high' );
		// add_meta_box( 'submitdiv', __( 'Publish' ), 'post_submit_meta_box', null, 'side', 'core', $publish_callback_args );
	}

	public function meta_box( $post, $box ) {
		?>
		<?php wp_nonce_field( __CLASS__, 'gc-edit-nonce' ); ?>

		<div class="misc-pub-section misc-pub-post-status">
			<span class="dashicons dashicons-post-status"></span>

			<?php esc_html_e( 'Remote Status:', 'gathercontent-importer' ); ?>
			<strong>Pending Review</strong>
			<a href="#post_status" class="edit-post-status hide-if-no-js"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit status</span></a>
		</div>

		<div class="misc-pub-section curtime misc-pub-curtime">
			<span id="timestamp">
			Last Updated: <b>Jun 24, 2016 @ 06:13</b></span>
			<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit date and time</span></a>
		</div>

		<div class="misc-pub-section misc-pub-post-status">
			<span class="dashicons dashicons-media-document"></span>

			<?php esc_html_e( 'Template:', 'gathercontent-importer' ); ?>
			<strong>Blah</strong>
			<a href="#post_status" class="edit-post-status hide-if-no-js"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit status</span></a>
		</div>

		<div class="misc-pub-section misc-pub-post-status">
			<span class="dashicons dashicons-edit"></span>
			<a href="#">Edit in GatherContent</a>
		</div>

		<!-- <fieldset class="inline-edit-col-right inline-edit-gc-status">
			<div class="inline-edit-col column-gathercontent">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'GatherContent Status', 'gathercontent-importer' ); ?></span>
					<span class="gc-status-select2"><span class="spinner"></span></span>
				</label>
			</div>
		</fieldset> -->

		<div class="gc-major-publishing-actions">
			<div class="gc-publishing-action">
				<button id="gc-sync-modal" type="button" class="button gc-button-primary alignright"><?php esc_html_e( 'GatherContent Sync', 'gathercontent-importer' ); ?></button>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	// public function set_gc_status( $post_id, $post ) {
	// 	if (
	// 		wp_is_post_autosave( $post )
	// 		|| wp_is_post_revision( $post )
	// 		|| ! $this->_post_val( 'gc-edit-nonce' )
	// 		|| ! wp_verify_nonce( $this->_post_val( 'gc-edit-nonce' ), __CLASS__ )
	// 		|| ! ( $status_id = $this->_post_val( 'gc_status' ) )
	// 		|| ! ( $item_id = absint( \GatherContent\Importer\get_post_item_id( $post_id ) ) )
	// 		|| ! ( $mapping_id = absint( \GatherContent\Importer\get_post_mapping_id( $post_id ) ) )
	// 		|| ! ( $item = $this->api->get_item( $item_id ) )
	// 		|| ( isset( $item->status->data->id ) && $status_id == $item->status->data->id )
	// 	) {
	// 		return;
	// 	}

	// 	if ( isset( $item->status->data->id ) && $status_id == $item->status->data->id ) {
	// 		return;
	// 	}

	// 	$this->api->set_item_status( $item_id, $status_id );
	// }

	/**
	 * Gets the underscore templates array.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	protected function get_underscore_templates() {
		return array(
			// 'tmpl-gc-post-column-row' => array(),
			'tmpl-gc-status-select2' => array(),
			// 'tmpl-gc-select2-item' => array(),
			// 'tmpl-gc-modal-window' => array(),
			// 'tmpl-gc-modal-item' => array(),
		);
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
			'_post' => \GatherContent\Importer\get_post_for_js( get_the_id() ),
			'_statuses' => array(
				'starting' => __( 'Starting Sync', 'gathercontent-importer' ),
				'syncing'  => __( 'Syncing', 'gathercontent-importer' ),
				'complete' => __( 'Sync Complete', 'gathercontent-importer' ),
				'failed'   => __( 'Sync Failed', 'gathercontent-importer' ),
			),
		);
	}

}
