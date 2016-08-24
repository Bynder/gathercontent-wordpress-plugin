<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;

use GatherContent\Importer\Settings\Form_Section;
use GatherContent\Importer\Admin\Admin;

/**
 * GatherContent Plugin Debug
 *
 * @since 3.0.0.8
 */
class Debug extends Base {

	/**
	 * A flag to check if we are in debug mode.
	 *
	 * @var boolean
	 */
	protected static $debug_mode = false;

	/**
	 * GatherContent\Importer\Admin\Admin instance.
	 *
	 * @var GatherContent\Importer\Admin\Admin
	 */
	protected $admin;

	/**
	 * The GC log file name.
	 *
	 * @var string
	 */
	protected static $log_file = 'gathercontent-debug.log';

	/**
	 * The path to the GC log file.
	 *
	 * @var string
	 */
	protected static $log_path = '';

	/**
	 * Constructor. Sets the asset_suffix var.
	 *
	 * @since 3.0.0.8
	 */
	public function __construct( Admin $admin ) {
		$this->admin = $admin;

		self::$log_path = WP_CONTENT_DIR . '/' . self::$log_file;

		if ( $debug_mode_enabled = get_option( 'gathercontent_debug_mode' ) ) {
			if ( time() > $debug_mode_enabled ) {
				delete_option( 'gathercontent_debug_mode' );
			} else {
				self::$debug_mode = true;
			}
		}
	}

	public function init_hooks() {
		if ( is_admin() && isset( $_GET['gathercontent_debug_mode'] ) ) {
			$enabled = self::toggle_debug_mode( $_GET['gathercontent_debug_mode'] );
			unset( $_GET['gathercontent_debug_mode'] );
			add_action( 'all_admin_notices', array( $this, $enabled ? 'debug_enabled_notice' : 'debug_disabled_notice' ) );
		}

		if ( ! self::$debug_mode ) {
			return;
		}

		add_filter( "sanitize_option_{$this->admin->option_name}", array( $this, 'do_debug_options_actions' ), 5 );

		add_action( 'admin_init', array( $this, 'add_debug_fields' ), 50 );
		add_action( 'gathercontent_do_debug_actions', array( $this, 'do_debug_actions' ), 50 );


		add_action( 'gc_sync_items_result', array( $this, 'log_sync_results' ), 10, 2 );
	}

	public function log_sync_results( $maybe_error, $sync ) {
		self::debug_log( $maybe_error );
	}

	public function debug_disabled_notice() {
		$msg = esc_html__( 'GatherContent Debug Mode: Disabled', 'gathercontent-import' );
		echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
	}

	public function debug_enabled_notice() {
		$msg = esc_html__( 'GatherContent Debug Mode: Enabled', 'gathercontent-import' );
		echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
	}

	public function add_debug_fields() {
		$section = new Form_Section(
			'debug',
			__( 'Debug Mode', 'gathercontent-import' ),
			sprintf( __( 'Debug file location: %s', 'gathercontent-import' ), '<code>wp-content/'. self::$log_file .'</code>' ),
			Admin::SLUG
		);

		$section->add_field(
			'review_stuck_status',
			__( 'Review stuck sync statuses?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'delete_stuck_status',
			__( 'Delete stuck sync statuses?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'view_gc_log_file',
			__( 'View contents of the GatherContent debug log file?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

		$section->add_field(
			'delete_gc_log_file',
			__( 'Delete GatherContent debug log file?', 'gathercontent-import' ),
			array( $this, 'debug_checkbox_field_cb' )
		);

	}

	public function debug_checkbox_field_cb( $field ) {
		$id = $field->param( 'id' );

		$this->view( 'input', array(
			'id' => $id,
			'name' => $this->admin->option_name .'[debug]['. $id .']',
			'type' => 'checkbox',
			'class' => '',
			'value' => 1,
		) );
	}

	public function do_debug_options_actions( $settings ) {
		if ( ! isset( $settings['debug'] ) || empty( $settings['debug'] ) ) {
			return $settings;
		}

		$debug = wp_parse_args( $settings['debug'], array(
			'review_stuck_status' => false,
			'delete_stuck_status' => false,
			'view_gc_log_file'    => false,
			'delete_gc_log_file'  => false,
		) );

		$back_button = isset( $_SERVER['HTTP_REFERER'] ) ? '<p><a href="'. $_SERVER['HTTP_REFERER'] . '">'. __( 'Go Back', 'gathercontent-import' ) .'</a></p>' : '';

		if ( $debug['review_stuck_status'] || $debug['delete_stuck_status']  ) {
				global $wpdb;

				$sql = "SELECT `option_name` FROM `$wpdb->options` WHERE `option_name` LIKE ('gc_pull_item_%') OR `option_name` LIKE ('gc_push_item_%');";
				$options = $wpdb->get_results( $sql );

				if ( ! empty( $options ) ) {
					foreach ( $options as $key => $option ) {
						$options[ $key ] = array(
							'name' => $option->option_name,
							'value' => get_option( $option->option_name ),
						);
					}
				} else {
					wp_die( __( 'There are no stuck statuses.', 'gathercontent-import' ) . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
				}

				if ( $debug['delete_stuck_status'] ) {
					foreach ( $options as $key => $option ) {
						$options[ $key ]['deleted'] = delete_option( $option['name'] );
					}
				}

				wp_die( '<xmp>'. __LINE__ .') $options: '. print_r( $options, true ) .'</xmp>' . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );

		} elseif ( $debug['delete_gc_log_file'] ) {
			if ( unlink( self::$log_path ) ) {
				wp_die( __( 'GatherContent log file deleted.', 'gathercontent-import' ) . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
			} else {
				wp_die( __( 'Failed to delete GatherContent log file.' . $back_button, 'gathercontent-import' ), __( 'Debug Mode', 'gathercontent-import' ) );
			}
		} elseif ( $debug['view_gc_log_file'] ) {
			$log_contents = file_get_contents( self::$log_path );

			if ( ! $log_contents ) {
				wp_die( __( 'GatherContent log file is empty.', 'gathercontent-import' ) . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
			}

			die( '<html><body>'. $back_button .'<pre><textarea style="width:100%;height:100%;min-height:1000px;font-size:14px;font-family:monospace;padding:.5em;">'. print_r( $log_contents, true ) .'</textarea></pre></body></html>' );

		} else {
			wp_die( '<xmp>'. __LINE__ .') $debug: '. print_r( $debug, true ) .'</xmp>' . $back_button, __( 'Debug Mode', 'gathercontent-import' ) );
		}
	}

	/**
	 * Check if SCRIPT_DEBUG is enabled.
	 *
	 * @return string
	 */
	public static function debug_mode() {
		return self::$debug_mode;
	}

	/**
	 * Enable/disable the Debug Mode.
	 *
	 * @since  3.0.0.8
	 *
	 * @param  bool  $debug_enabled Enable/Disable
	 *
	 * @return bool                 Whether it has been enabled.
	 */
	public static function toggle_debug_mode( $debug_enabled ) {
		$changed = false;
		if ( ! $debug_enabled ) {
			delete_option( 'gathercontent_debug_mode' );
			$changed = ! empty( self::$debug_mode );
		} elseif ( date( 'm-d-Y' ) === $debug_enabled ) {
			update_option( 'gathercontent_debug_mode', time() + DAY_IN_SECONDS );
			$changed = empty( self::$debug_mode );
		}

		if ( $changed ) {
			$status = $debug_enabled
				? esc_html__( 'Enabled', 'gathercontent-import' )
				: esc_html__( 'Disabled', 'gathercontent-import' );

			self::_debug_log( sprintf( esc_html__( 'GatherContent Debug Mode: %s', 'gathercontent-import' ), $status ) );
		}

		return self::$debug_mode = !! $debug_enabled;
	}

	/**
	 * Write a message to the log if debug enabled.
	 *
	 * @since  3.0.0.8
	 *
	 * @param  string  $message Message to write to log file.
	 *
	 * @return void
	 */
	public static function debug_log( $message = '' ) {
		if ( self::$debug_mode ) {
			self::_debug_log( $message );
		}
	}

	/**
	 * Write a message to the log.
	 *
	 * @since  3.0.0.8
	 *
	 * @param  string  $message Message to write to log file.
	 *
	 * @return void
	 */
	protected static function _debug_log( $message = '' ) {
		error_log( date('Y-m-d H:i:s') .': '. print_r( $message, 1 ) ."\r\n", 3, self::$log_path );
	}

}
