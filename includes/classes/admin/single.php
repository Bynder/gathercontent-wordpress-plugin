<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
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

		if ( ! isset( $this->post_types[ $screen->post_type ] ) || ! $this->_get_val( 'post' ) ) {
			return;
		}


		// $accounts = $this->api->get_accounts();
		// wp_die( '<xmp>'. __LINE__ .') $accounts: '. print_r( $accounts, true ) .'</xmp>' );
		// $mapping_id = \GatherContent\Importer\get_post_mapping_id( absint( $this->_get_val( 'post' ) ) );

		// $mappings = $this->mappings->get_all();
		// wp_die( '<xmp>'. __LINE__ .') $mappings: '. print_r( $mappings, true ) .'</xmp>' );


		$this->enqueue->admin_enqueue_style();
		$this->enqueue->admin_enqueue_script();
		add_meta_box( 'gc-manage', 'GatherContent', array( $this, 'meta_box' ), $screen, 'side', 'high' );
	}

	public function meta_box( $post, $box ) {
		$post_id = $post->ID;
		$mapping_id = absint( \GatherContent\Importer\get_post_mapping_id( $post_id ) );
		$item_id = absint( \GatherContent\Importer\get_post_item_id( $post_id ) );

		$message = '';

		// if ( ! $mapping_id ) {
		// 	$accounts = $this->api()->get_accounts();

		// 	if ( ! $accounts ) {
		// 		$message = sprintf( __( 'We couldn\'t find any accounts associated with your GatherContent API credentials. Please <a href="%s">check your settings</a>.', 'gathercontent-import' ), admin_url( 'admin.php?page='. GATHERCONTENT_SLUG ) );
		// 	} else {
		// 		$message = esc_html__( 'To associtiate a mapping, please select an Account.', 'gathercontent-import' );
		// 	}
		// }

		$this->view( 'metabox', compact( 'post_id', 'item_id', 'mapping_id', 'message' ) );
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
