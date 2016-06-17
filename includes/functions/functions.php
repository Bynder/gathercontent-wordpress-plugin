<?php
namespace GatherContent\Importer;

/**
 * Utility function for doing array_map recursively.
 *
 * @since  3.0.0
 *
 * @param  callable $callback Callable function
 * @param  array    $array    Array to recurse
 *
 * @return array              Updated array.
 */
function array_map_recursive( $callback, $array ) {
	foreach ( $array as $key => $value) {
		if ( is_array( $array[ $key ] ) ) {
			$array[ $key ] = array_map_recursive( $callback, $array[ $key ] );
		}
		else {
			$array[ $key ] = call_user_func( $callback, $array[ $key ] );
		}
	}
	return $array;
}


/**
 * Style enqueue helper w/ GC defaults.
 *
 * @since  3.0.0
 *
 * @param string           $handle   Name of the stylesheet. Should be unique.
 * @param string           $filename Path (w/o extension/suffix) to CSS file in /assets/css/
 * @param array            $deps     Optional. An array of registered stylesheet handles this stylesheet
 *                                   depends on. Default empty array.
 * @param string|bool|null $ver      Optional. String specifying stylesheet version number, if it has one,
 *                                   which is added to the URL
 *
 * @return void
 */
function enqueue_style( $handle, $filename, $deps = [], $ver = GATHERCONTENT_VERSION ) {
	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_enqueue_style( $handle, GATHERCONTENT_URL . "assets/css/{$filename}.{$suffix}css", $deps, $ver );
}

/**
 * Script enqueue helper w/ GC defaults.
 *
 * @since  3.0.0
 *
 * @param string           $handle   Name of the script. Should be unique.
 * @param string           $filename Path (w/o extension/suffix) to JS file in /assets/js/
 * @param array            $deps     Optional. An array of registered script handles this script
 *                                   depends on. Default empty array.
 * @param string|bool|null $ver      Optional. String specifying script version number, if it has one,
 *                                   which is added to the URL
 *
 * @return void
 */
function enqueue_script( $handle, $filename, $deps = [], $ver = GATHERCONTENT_VERSION ) {
	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_enqueue_script( $handle, GATHERCONTENT_URL . "assets/js/{$filename}{$suffix}.js", $deps, $ver, 1 );
}
