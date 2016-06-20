<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;

class Select2 extends Plugin_Base {

	public function callback() {
		if ( ! $this->_get_val( 'q' ) || ! $this->_get_val( 'column' ) ) {
			wp_send_json_error();
		}

		$search_term = sanitize_text_field( trim( $this->_get_val( 'q' ) ) );

		if ( ! $search_term ) {
			wp_send_json_error();
		}

		$method = $this->_get_val( 'column' );

		switch ( $method ) {
			case 'post_author':
				if ( $results = $this->$method( $search_term ) ) {
					wp_send_json( $results );
				}
				break;
		}

		wp_send_json_error();
	}

	public function post_author( $search_term ) {
		if ( ! apply_filters( 'gathercontent_settings_view_capability', 'publish_pages' ) ) {
			wp_send_json_error();
		}

		$users = get_users( array(
			'search' => '*' . $search_term . '*',
			'number' => 30,
		) );

		$users = array_map( function( $user ) {
			return array(
				'text' => $user->user_login,
				'id'   => $user->ID,
			);
		}, $users );

		return array( 'results' => $users );
	}

}
