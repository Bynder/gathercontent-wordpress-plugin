<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Base as Plugin_Base;

abstract class Enqueue extends Plugin_Base {

	public function admin_enqueue_style() {
		\GatherContent\Importer\enqueue_style( 'select2', 'vendor/select2-4.0.3/select2', array(), '4.0.3' );
		\GatherContent\Importer\enqueue_style( 'gathercontent', 'gathercontent-importer' );
	}

	public function admin_enqueue_script() {
		\GatherContent\Importer\enqueue_script( 'select2', 'vendor/select2-4.0.3/select2', array( 'jquery' ), '4.0.3' );
		\GatherContent\Importer\enqueue_script( 'gathercontent', 'gathercontent', array( 'select2', 'wp-backbone' ) );

		// Localize in footer so that 'gathercontent_localized_data' filter is more useful.
		add_action( 'admin_footer', array( $this, 'script_localize' ), 1 );
	}

	public function script_localize() {
		wp_localize_script( 'gathercontent', 'GatherContent', apply_filters( 'gathercontent_localized_data', array(
			'debug'     => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			'queryargs' => $_GET,
		) ) );
	}
}
