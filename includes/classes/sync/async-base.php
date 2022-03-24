<?php
namespace GatherContent\Importer\Sync;

use GatherContent\Importer\General;
use GatherContent\Importer\Debug;

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
	 */
	public function launch_on_shutdown() {
		if ( empty( $this->_body_data ) || ! isset($this->_body_data['mapping_id'])) {
			return;
		}

		$mapping_post = get_post( $this->_body_data['mapping_id'] );
		do_action($this->_body_data['action'], $mapping_post);
	}

	/**
	 * Get the current request cookies.
	 * Not currently used, but left for posterity.
	 *
	 * @since  3.1.4
	 *
	 * @return array
	 */
	public static function get_cookies() {
		$cookies = array();
		foreach ( $_COOKIE as $name => $value ) {
			$cookies[] = "$name=" . urlencode( is_array( $value ) ? serialize( $value ) : $value );
		}

		return implode( '; ', $cookies );
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
			$data['ran_action'] = $this->run_action();
		}

		if ( ! General::get_instance()->admin->get_setting( 'log_importer_requests' ) ) {
			add_filter(
				'wp_die_handler',
				function() {
					die();
				}
			);
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
	protected function prepare_data( $data ) {
		return array( 'mapping_id' => isset( $data[0]->ID ) ? $data[0]->ID : 0 );
	}

	/**
	 * Run the async task action.
	 * We do this rather than changing $this->action so that nested calls work correctly.
	 *
	 * @return bool Whether the do_action was called.
	 */
	protected function run_action() {
		$action_name = $this->action;
		if ( ! is_user_logged_in() ) {
			$action_name = "nopriv_$action_name";
		}

		return $this->run_given_action( $action_name );
	}

	/**
	 * Run the given async task action
	 *
	 * @since  3.1.4
	 *
	 * @return bool Whether the do_action was called.
	 */
	protected function run_given_action( $action_name ) {
		$mapping_id = absint( $_POST['mapping_id'] );

		if ( $mapping_id && ( $mapping_post = get_post( $mapping_id ) ) ) {
			do_action( "wp_async_$action_name", $mapping_post );
			return true;
		}

		return false;
	}
}
