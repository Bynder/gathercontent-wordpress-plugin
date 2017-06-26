<?php
namespace GatherContent\Importer\Sync;
use GatherContent\Importer\General;

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
		if ( ! empty( $this->_body_data ) ) {
			$request_args = array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
				'body'      => $this->_body_data,
				'headers'   => array(),
			);

			if ( \GatherContent\Importer\auth_enabled() ) {
				$admin    = General::get_instance()->admin;
				$username = $admin->get_setting( 'auth_username' );
				$password = $admin->get_setting( 'auth_pw' );

				// Attempt to add basic auth header.
				$request_args['headers']['Authorization'] = 'Basic ' . base64_encode( $username . ':' . $password );
			}

			wp_remote_post( admin_url( 'admin-post.php' ), $request_args );
		}
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
