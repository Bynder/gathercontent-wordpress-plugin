<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
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
	 * This JS post array.
	 *
	 * @var array
	 */
	protected $post = array();

	/**
	 * This post's post-type label.
	 *
	 * @var string
	 */
	protected $post_type_label = '';

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api      API object
	 * @param $mappings Template_Mappings object
	 */
	public function __construct( API $api, Template_Mappings $mappings ) {
		$this->api      = $api;
		$this->mappings = $mappings;
		$this->enqueue  = new Single_Enqueue;
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

		if ( 'post' !== $screen->base || ! $screen->post_type ) {
			return;
		}

		// Do not show GC metabox if there is no mapping for this post-type, or if this is a new post.
		if ( ! isset( $this->post_types[ $screen->post_type ] ) || ! $this->_get_val( 'post' ) ) {
			return;
		}

		$this->enqueue->admin_enqueue_style();
		$this->enqueue->admin_enqueue_script();
		add_meta_box( 'gc-manage', 'GatherContent <span class="dashicons dashicons-randomize"></span>', array( $this, 'meta_box' ), $screen, 'side', 'high' );
	}

	public function meta_box( $post, $box ) {
		$object = get_post_type_object( $post->post_type );
		$this->post_type_label = isset( $object->labels->singular_name ) ? $object->labels->singular_name : $object->name;

		$this->post = \GatherContent\Importer\prepare_post_for_js( $post );

		$this->view( 'metabox', array(
			'post_id'    => $this->post['id'],
			'item_id'    => $this->post['item'],
			'mapping_id' => $this->post['mapping'],
			'label'      => $this->post_type_label,
		) );
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
			'tmpl-gc-metabox' => array(
				'url'   => General::get_instance()->admin->platform_url(),
				'label' => $this->post_type_label,
				// 'refresh_link' => \GatherContent\Importer\refresh_connection_link(),
			),
			'tmpl-gc-metabox-statuses' => array(),
			'tmpl-gc-mapping-metabox' => array(
				'label' => $this->post_type_label,
			),
			'tmpl-gc-status-select2' => array(),
			'tmpl-gc-select2-item' => array(),
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
			'_post' => $this->post,
			'_statuses' => array(
				'starting' => __( 'Starting Sync', 'gathercontent-importer' ),
				'syncing'  => __( 'Syncing', 'gathercontent-importer' ),
				'complete' => __( 'Sync Complete', 'gathercontent-importer' ),
				'failed'   => __( 'Sync Failed', 'gathercontent-importer' ),
			),
			'_sure' => array(
				'push_no_item' => sprintf( __( 'Push this %s to GatherContent?', 'gathercontent-importer' ), $this->post_type_label ),
				'push' => sprintf( __( 'Are you sure you want to push this %s to GatherContent? Any unsaved changes in GatherContent will be overwritten.', 'gathercontent-importer' ), $this->post_type_label ),
				'pull'  => sprintf( __( 'Are you sure you want to pull this %s from GatherContent? Any local changes will be overwritten.', 'gathercontent-importer' ), $this->post_type_label ),
			),
			'_errors' => array(
				'unknown' => __( 'There was an unknown error', 'gathercontent-importer' ),
			),
			'_step_labels' => array(
				'accounts' => esc_html__( 'Select an account:', 'gathercontent-importer' ),
				'projects' => esc_html__( 'Select a project:', 'gathercontent-importer' ),
				'mappings' => sprintf( esc_html_x( 'Select a %s:', 'Select a template mapping', 'gathercontent-importer' ), $this->mappings->args->labels->singular_name ),
			),
			'_edit_nonce' => wp_create_nonce( General::get_instance()->admin->mapping_wizzard->option_group . '-options' ),
		);
	}

}
