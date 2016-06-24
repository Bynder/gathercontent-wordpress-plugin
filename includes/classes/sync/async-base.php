<?php
namespace GatherContent\Importer\Sync;
require_once GATHERCONTENT_INC . 'vendor/wp-async-task/wp-async-task.php';

abstract class Async_Base extends \WP_Async_Task {

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
