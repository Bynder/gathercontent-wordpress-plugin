<?php
namespace GatherContent\Importer;
use WP_Query;

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

function get_posts_by_item_id( $item_id, $args = array() ) {
	$query = new WP_Query( wp_parse_args( $args, array(
		'post_type'      => 'any',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'   => '_gc_mapped_item_id',
				'value' => $item_id,
			),
		),
	) ) );

	return $query->have_posts() && $query->post ? $query->post : false;
}

function get_post_item_id( $post_id ) {
	return get_post_meta( $post_id, '_gc_mapped_item_id', 1 );
}

function update_post_item_id( $post_id, $item_id ) {
	return update_post_meta( $post_id, '_gc_mapped_item_id', $item_id );
}
