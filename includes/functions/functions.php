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

