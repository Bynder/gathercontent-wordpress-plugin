<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Utils;

/**
 * A base class for enqueueing the GC resources and localizing the script data.
 *
 * @since  3.0.0
 */
abstract class Enqueue extends Plugin_Base {

	/**
	 * Enqueues the GC stylesheets.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_style() {
		\GatherContent\Importer\enqueue_style( 'select2', 'vendor/select2-4.0.3/select2', array(), '4.0.3' );
		\GatherContent\Importer\enqueue_style( 'gathercontent', 'gathercontent-importer' );
	}

	/**
	 * Enqueues the GC scripts, and hooks the localization to the footer.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_script() {
		\GatherContent\Importer\enqueue_script( 'select2', 'vendor/select2-4.0.3/select2', array( 'jquery' ), '4.0.3' );
		\GatherContent\Importer\enqueue_script( 'gathercontent', 'gathercontent', array( 'select2', 'wp-backbone' ) );

		// Localize in footer so that 'gathercontent_localized_data' filter is more useful.
		add_action( 'admin_footer', array( $this, 'script_localize' ), 1 );
	}

	/**
	 * Localizes the data for the GC scripts. Hooked to admin_footer in order to be run late,
	 * and for the gathercontent_localized_data filter to be easily hooked to.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function script_localize() {
		wp_localize_script( 'gathercontent', 'GatherContent', apply_filters( 'gathercontent_localized_data', array(
			'debug'       => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			// @codingStandardsIgnoreStart
			'queryargs'   => $_GET,
			// @codingStandardsIgnoreEnd
			'_type_names' => Utils::gc_field_type_name( 'all' ),
		) ) );
	}
}
