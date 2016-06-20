<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;

class Sync_Items extends Plugin_Base {

	public function callback() {
		if ( ! $this->_post_val( 'data' ) ) {
			wp_send_json_error();
		}

		if ( 'check' === $this->_post_val( 'data' ) ) {
			// For now, fake it to work on the UI
			$percent = absint( $this->_post_val( 'percent' ) ) + 12;
			wp_send_json_success( array( 'percent' => $percent ) );
		}


		// Parse the URL query string of the fields array.
		parse_str( $this->_post_val( 'data' ), $fields );

		if ( ! wp_verify_nonce( $fields['_wpnonce'], $fields['option_page'] . '-options' ) ) {
			wp_send_json_error();
		}

		// $this->do_item( absint( $fields['post_id'] ), $fields['import'] );

		// error_log( '$fields: '. print_r( $fields, true ) );
		wp_send_json_success( $fields );
	}

	public function do_item( $post_id, $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			wp_send_json_error();
		}


		$mapping = $this->get_mapping( $post_id );

		if ( ! $mapping ) {
			wp_send_json_error();
		}

		$post = array_shift( $items );

		if ( ! empty( $items ) ) {
			update_post_meta( $post_id, '_gc_sync_items', array_map( 'sanitize_text_field', $items ) );
		}



		// @todo figure out how to calculate percentage complete.



		update_post_meta( $post_id, '_gc_sync_percent', 0.1 );
	}

	public function get_mapping( $post_id ) {
		$mapping = false;

		if ( $post_id && ( $json = get_post_field( 'post_content', $post_id ) ) ) {

			$json = json_decode( $json, 1 );

			if ( is_array( $json ) ) {
				$mapping = $json;

				if ( isset( $mapping['mapping'] ) && is_array( $mapping['mapping'] ) ) {
					$_mapping = $mapping['mapping'];
					unset( $mapping['mapping'] );
					$mapping += $_mapping;
				}
			}
		}

		return $mapping;
	}

}

/*

[20-Jun-2016 04:51:55 UTC] $fields: Array
(
    [option_page] => gathercontent_importer_settings_add_new_template
    [action] => update
    [_wpnonce] => 7fda24b448
    [_wp_http_referer] => /gathercontent/wp-admin/admin.php?page=gathercontent-import-add-new-template&project=73849&template=347939&mapping=33&sync-items=1
    [post_id] => 33
    [import] => Array
        (
            [0] => 2862508
        )

)

$fields: Array
(
    [option_page] => gathercontent_importer_settings_add_new_template
    [action] => update
    [_wpnonce] => 7fda24b448
    [_wp_http_referer] => /gathercontent/wp-admin/admin.php?page=gathercontent-import-add-new-template&project=73849&template=347939&mapping=33&sync-items=1
    [import] => Array
        (
            [0] => 2817575
        )

)

 */
