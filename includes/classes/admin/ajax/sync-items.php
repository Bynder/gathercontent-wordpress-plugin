<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\General;
use GatherContent\Importer\Post_Types\Template_Mappings;

class Sync_Items extends Plugin_Base {

	/**
	 * GatherContent\Importer\Post_Types\Template_Mappings instance
	 *
	 * @var GatherContent\Importer\Post_Types\Template_Mappings
	 */
	protected $mappings = null;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $mappings Template_Mappings object
	 */
	public function __construct( Template_Mappings $mappings ) {
		$this->mappings = $mappings;
	}

	public function callback() {
		$this->verify_request();

		$mapping = $this->get_mapping_post();

		$this->maybe_cancelling( $mapping->ID );

		$this->maybe_checking_status( $mapping->ID );

		$fields = $this->get_fields( $mapping->ID );

		$this->start_pull( $mapping, $fields );
	}

	protected function verify_request() {
		// Make sure we have the minimum data.
		if ( ! isset( $_REQUEST['data'], $_REQUEST['id'], $_REQUEST['nonce'] ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing required data.', 'gathercontent-import' ),
				__LINE__
			) );
		}

		// Get opt-group for nonce-verification
		$opt_group = General::get_instance()->admin->mapping_wizzard->option_group;

 		// No nonce, no pass.
		if ( ! wp_verify_nonce( $this->_post_val( 'nonce' ), $opt_group . '-options' ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing security nonce.', 'gathercontent-import' ),
				__LINE__
			) );
		}
	}

	protected function get_mapping_post() {
		if ( $mapping = get_post( absint( $this->_post_val( 'id' ) ) ) ) {
			return $mapping;
		}

		wp_send_json_error( sprintf(
			__( 'Error %d: Cannot find a mapping by that id: %d', 'gathercontent-import' ),
			__LINE__,
			absint( $this->_post_val( 'id' ) )
		) );
	}

	protected function maybe_cancelling( $mapping_id ) {
		if ( 'cancel' !== $this->_post_val( 'data' ) ) {
			return false;
		}

		error_log( 'delete meta and cancel' );
		$this->mappings->update_items_to_sync( $mapping_id, false );

		wp_send_json_success();
	}

	protected function maybe_checking_status( $mapping_id ) {
		if ( 'check' !== $this->_post_val( 'data' ) ) {
			return false;
		}

		$percent = round( $this->mappings->get_pull_percent( $mapping_id ) * 100 );
		error_log( '$percent: '. print_r( $percent, true ) );

		// $percent = absint( $this->_post_val( 'percent' ) ) + 12;
		wp_send_json_success( compact( 'percent' ) );
	}

	protected function get_fields( $mapping_id ) {
		$data = $this->_post_val( 'data' );

		if ( empty( $data ) || ! is_string( $data ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing form data.', 'gathercontent-import' ),
				__LINE__
			) );
		}

		// Parse the serialized fields string.
		parse_str( $data, $fields );

		if (
			! isset( $fields['import'], $fields['project'], $fields['template'] )
			|| empty( $fields['import'] ) || ! is_array( $fields['import'] )
			|| $this->mappings->get_mapping_project( $mapping_id ) != $fields['project']
			|| $this->mappings->get_mapping_template( $mapping_id ) != $fields['template']
		) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing required form data.', 'gathercontent-import' ),
				__LINE__
			) );
		}

		$fields['project']  = absint( $fields['project'] );
		$fields['template'] = absint( $fields['template'] );
		$fields['import']   = array_map( 'absint', $fields['import'] );

		return $fields;
	}

	protected function start_pull( $mapping, $fields ) {
		$count = count( $fields['import'] );

		// Start the sync and bump percent value.
		$this->mappings->update_items_to_sync( $mapping->ID, array( 'pending' => $fields['import'] ) );

		error_log( __METHOD__ .': '. print_r( $mapping->ID, true ) );

		do_action( 'gc_pull_items', $mapping );
		// error_log( 'start_pull $fields: '. print_r( $fields, true ) );
		// error_log( '$_REQUEST: '. print_r( $_REQUEST, true ) );

		$percent = 0.1;

		if ( 1 === $count ) {
			$percent = 50;
		} elseif ( $count < 5 ) {
			$percent = 20;
		}

		wp_send_json_success( compact( 'percent' ) );
	}

}
