<?php
namespace GatherContent\Importer;

/**
 * Default setup routine
 *
 * @since  3.0.0
 *
 * @uses add_action()
 * @uses do_action()
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init', $n( 'i18n' ) );

	// We only need to do our work in the admin.
	add_action( 'admin_init', $n( 'init' ) );

	do_action( 'gathercontent_loaded' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @since  3.0.0
 *
 * @uses do_action()
 *
 * @return void
 */
function init() {
	do_action( 'gathercontent_init' );
}

/**
 * Activate the plugin
 *
 * @since  3.0.0
 *
 * @uses init()
 * @uses flush_rewrite_rules()
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	init();
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @since  3.0.0
 *
 * @return void
 */
function deactivate() {
}

// Activation/Deactivation
register_activation_hook( GATHERCONTENT_PLUGIN, '\GatherContent\Importer\activate' );
register_deactivation_hook( GATHERCONTENT_PLUGIN, '\GatherContent\Importer\deactivate' );

// Bootstrap
setup();
