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

/**
 * Wrapper for WP_Query that gets the assocated post for a GatherContent Item Id.
 *
 * @since  3.0.0
 *
 * @param  int   $item_id GatherContent Item Id.
 * @param  array $args    Optional array of WP_Query args.
 *
 * @return mixed          WP_Post if an associated post is found.
 */
function get_post_by_item_id( $item_id, $args = array() ) {
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

/**
 * Wrapper for get_post_meta that gets the associated GatherContent item ID, if it exists.
 *
 * @since  3.0.0
 *
 * @param  int  $post_id The ID of the post to check.
 *
 * @return mixed         Result of get_post_meta.
 */
function get_post_item_id( $post_id ) {
	return get_post_meta( $post_id, '_gc_mapped_item_id', 1 );
}

/**
 * Wrapper for update_post_meta that saves the associated GatherContent item ID to the post's meta.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to store the item ID against.
 * @param  int $item_id The item id to store against the post.
 *
 * @return mixed         Result of update_post_meta.
 */
function update_post_item_id( $post_id, $item_id ) {
	return update_post_meta( $post_id, '_gc_mapped_item_id', $item_id );
}

/**
 * Wrapper for get_post_meta that gets the associated GatherContent item meta, if it exists.
 *
 * @since  3.0.0
 *
 * @param  int  $post_id The ID of the post to check.
 *
 * @return mixed         Result of get_post_meta.
 */
function get_post_item_meta( $post_id ) {
	return get_post_meta( $post_id, '_gc_mapped_meta', 1 );
}

/**
 * Wrapper for update_post_meta that saves the associated GatherContent item meta to the post's meta.
 *
 * @since  3.0.0
 *
 * @param  int   $post_id The ID of the post to update.
 * @param  mixed $meta    The item meta to store against the post.
 *
 * @return mixed          Result of update_post_meta.
 */
function update_post_item_meta( $post_id, $meta ) {
	return update_post_meta( $post_id, '_gc_mapped_meta', $meta );
}

/**
 * Wrapper for get_post_meta that gets the associated GatherContent mapping post ID, if it exists.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to check.
 *
 * @return mixed Result of get_post_meta.
 */
function get_post_mapping_id( $post_id ) {
	return get_post_meta( $post_id, '_gc_mapping_id', 1 );
}

/**
 * Wrapper for update_post_meta that saves the associated GatherContent mapping post ID to the post's meta.
 *
 * @since  3.0.0
 *
 * @param  int $mapping_post_id The ID of the mapping post.
 *
 * @return mixed Result of update_post_meta.
 */
function update_post_mapping_id( $post_id, $mapping_post_id ) {
	return update_post_meta( $post_id, '_gc_mapping_id', $mapping_post_id );
}

/**
 * Get a an array of data from a WP_Post object to be used as a backbone model.
 *
 * @since  3.0.0
 *
 * @param  mixed  $post WP_Post or post ID.
 *
 * @return array        JS post array.
 */
function get_post_for_js( $post ) {
	$post = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post ) {
		return false;
	}

	$post_id = $post->ID;

	$js_post = array_change_key_case( (array) $post );

	$js_post['item']        = absint( \GatherContent\Importer\get_post_item_id( $post_id ) );
	$js_post['mapping']     = absint( \GatherContent\Importer\get_post_mapping_id( $post_id ) );

	$js_post['mappingLink'] = $js_post['mapping'] ? get_edit_post_link( $js_post['mapping'] ) : '';
	$js_post['status']      = (object) array();
	$js_post['itemName']    = __( 'N/A', 'gathercontent-importer' );

	if ( $js_post['item'] ) {
		$item = General::get_instance()->api->only_cached()->get_item( $js_post['item'] );

		if ( isset( $item->name ) ) {
			$js_post['itemName'] = $item->name;
		}

		$js_post['status'] = isset( $item->status->data )
			? $item->status->data
			: (object) array();
	}

	return $js_post;
}

/**
 * A button for flushing the cached connection to GC's API.
 *
 * @since  3.0.0
 *
 * @return string URL for flushing cache.
 */
function refresh_connection_link() {
	$args = array(
		'redirect_url' => false,
		'flush_url' => add_query_arg( array( 'flush_cache' => 1, 'redirect' => 1 ) ),
	);

	if ( isset( $_GET['flush_cache'], $_GET['redirect'] ) ) {
		update_option( 'gc-api-updated', 1, false );
		$args['redirect_url'] = remove_query_arg( 'flush_cache', remove_query_arg( 'redirect' ) );
	}

	$view = new Views\View( 'refresh-connection-button', $args );

	return $view->load( false );
}

/**
 * Determine if current user can view GC settings.
 *
 * @since  3.0.0
 *
 * @return bool Whether current user can view GC settings.
 */
function user_allowed() {
	return current_user_can( \GatherContent\Importer\view_capability() );
}

/**
 * Capability for user to be able to view GC settings.
 *
 * @since  3.0.0
 *
 * @return string Capability
 */
function view_capability() {
	return apply_filters( 'gathercontent_settings_view_capability', 'publish_pages' );
}
