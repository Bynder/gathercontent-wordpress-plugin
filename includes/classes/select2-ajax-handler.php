<?php
namespace GatherContent\Importer;

class Select2_Ajax_Handler extends Base {

	public function init_hooks() {
		add_action( 'wp_ajax_gc_get_option_data', array( new Select2_Ajax_Handler, 'callback' ) );
	}

	public function callback() {
		if ( ! $this->get_val( 'q' ) || ! $this->get_val( 'column' ) ) {
			wp_send_json_error();
		}

		$search_term = sanitize_text_field( trim( $this->get_val( 'q' ) ) );

		if ( ! $search_term ) {
			wp_send_json_error();
		}

		$method = $this->get_val( 'column' );

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
