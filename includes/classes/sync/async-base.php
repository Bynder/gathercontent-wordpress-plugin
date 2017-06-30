<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\General;
use GatherContent\Importer\Debug;

require_once GATHERCONTENT_INC . 'vendor/wp-async-task/wp-async-task.php';

abstract class Async_Base extends \WP_Async_Task {

	/**
	 * Launch the real postback if we don't
	 * get an exception thrown by prepare_data().
	 *
	 * @uses func_get_args() To grab any arguments passed by the action
	 */
	public function launch() {
		$data = func_get_args();
		try {
			$data = $this->prepare_data( $data );
		} catch ( Exception $e ) {
			return;
		}

		$data['action'] = "wp_async_$this->action";
		$data['_nonce'] = $this->create_async_nonce();

		$this->_body_data = $data;

		// Do not wait for shutdown hook.
		$this->launch_on_shutdown();
	}

	/**
	 * Launch the request on the WordPress shutdown hook
	 *
	 * On VIP we got into data races due to the postback sometimes completing
	 * faster than the data could propogate to the database server cluster.
	 * This made WordPress get empty data sets from the database without
	 * failing. On their advice, we're moving the actual firing of the async
	 * postback to the shutdown hook. Supposedly that will ensure that the
	 * data at least has time to get into the object cache.
	 *
	 * @uses $_COOKIE        To send a cookie header for async postback
	 * @uses apply_filters()
	 * @uses admin_url()
	 * @uses wp_remote_post()
	 */
	public function launch_on_shutdown() {
		if ( empty( $this->_body_data ) ) {
			return;
		}

		$admin          = General::get_instance()->admin;
		$debug_requests = $admin->get_setting( 'log_importer_requests' );

		$request_args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
			'body'      => $this->_body_data,
			'headers'   => array(),
		);

		if ( \GatherContent\Importer\auth_enabled() ) {
			$username = $admin->get_setting( 'auth_username' );
			$password = $admin->get_setting( 'auth_pw' );

			// Attempt to add basic auth header.
			$request_args['headers']['Authorization'] = 'Basic ' . base64_encode( $username . ':' . $password );
		}


		if ( $debug_requests ) {
			unset( $request_args['timeout'] );
			$request_args['blocking'] = true;
			$request_args['sslverify'] = false;
		}

		$response = wp_remote_post( admin_url( 'admin-post.php' ), $request_args );

		if ( $debug_requests ) {
			Debug::debug_log( $request_args, 'async request args' );
			Debug::debug_log( array(
				'code'    => wp_remote_retrieve_response_code( $response ),
				'headers' => wp_remote_retrieve_headers( $response ),
				'body'    => wp_remote_retrieve_body( $response ),
			), 'async request response' );
		}
	}

	/**
	 * Verify the postback is valid, then fire any scheduled events.
	 *
	 * @uses $_POST['_nonce']
	 * @uses is_user_logged_in()
	 * @uses add_filter()
	 * @uses wp_die()
	 */
	public function handle_postback() {
		$data = $_POST;

		$data['ran_action'] = false;

		if ( isset( $_POST['_nonce'] ) && $this->verify_async_nonce( $_POST['_nonce'] ) ) {
			if ( ! is_user_logged_in() ) {
				$this->action = "nopriv_$this->action";
			}
			$this->run_action();
			$data['ran_action'] = true;
		}

		if ( ! General::get_instance()->admin->get_setting( 'log_importer_requests' ) ) {
			add_filter( 'wp_die_handler', function() { die(); } );
			wp_die();
		}

		die( wp_json_encode( $data ) );
	}

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @throws Exception If for any reason the request should not happen
	 *
	 * @param array $data An array of data sent to the hook
	 *
	 * @return array
	 */
	protected function prepare_data( $data ){
		return array( 'mapping_id' => isset( $data[0]->ID ) ? $data[0]->ID : 0 );
	}

	/**
	 * Run the async task action
	 */
	protected function run_action() {
		$mapping_id = absint( $_POST['mapping_id'] );
		if ( $mapping_id && ( $mapping_post = get_post( $mapping_id ) ) ) {
			do_action( "wp_async_$this->action", $mapping_post );
		}
	}
}
