<?php
namespace GatherContent\Importer;
use DateTime;
use DateTimeZone;

class Utils extends Base {

	/**
	 * The .min suffix if SCRIPT_DEBUG is disabled.
	 *
	 * @var string
	 */
	protected static $js_suffix = '';

	public function __construct() {
		self::$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Magic getter for our object, to make protected properties accessible.
	 * @param string $field
	 * @return mixed
	 */
	public static function js_suffix() {
		return self::$js_suffix;
	}

	/**
	 * Determines if $date_check is within allowance of $date_compare.
	 *
	 * @since  3.0.0
	 *
	 * @param  mixed  $date_check   Date to check
	 * @param  mixed  $date_compare Date to compare with
	 * @param  integer $allowance   Allowed tolerance.
	 *
	 * @return bool                 Whether $date_check is current with $date_compare.
	 */
	public static function date_current_with( $date_check, $date_compare, $allowance = 0 ) {
		$date_compare = strtotime( $date_compare );
		$date_check   = strtotime( $date_check );
		$difference   = $date_compare - $date_check;

		return $difference < $allowance;
	}

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
	static function array_map_recursive( $callback, $array ) {
		foreach ( $array as $key => $value) {
			if ( is_array( $array[ $key ] ) ) {
				$array[ $key ] = self::array_map_recursive( $callback, $array[ $key ] );
			}
			else {
				$array[ $key ] = call_user_func( $callback, $array[ $key ] );
			}
		}
		return $array;
	}

	/**
	 * Convert a UTC date to human readable date using the WP timezone.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $utc_date UTC date
	 *
	 * @return string           Human readable relative date.
	 */
	public static function relative_date( $utc_date ) {
		static $tzstring = null;

		// Get the WP timezone string.
		if ( null === $tzstring ) {
			$current_offset = get_option( 'gmt_offset' );
			$tzstring       = get_option( 'timezone_string' );

			// Remove old Etc mappings. Fallback to gmt_offset.
			if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
				$tzstring = '';
			}

			if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
				if ( 0 == $current_offset ) {
					$tzstring = 'UTC+0';
				} elseif ( $current_offset < 0 ) {
					$tzstring = 'UTC' . $current_offset;
				} else {
					$tzstring = 'UTC+' . $current_offset;
				}
			}
		}

		$date = new DateTime( $utc_date, new DateTimeZone( 'UTC' ) );
		$date->setTimeZone( new DateTimeZone( $tzstring ) );

		$time = $date->getTimestamp();
		$currtime = time();
		$time_diff = $currtime - $time;

		if ( $time_diff >= 0 && $time_diff < DAY_IN_SECONDS ) {
			$date = sprintf( __( '%s ago' ), human_time_diff( $time ) );
		} else {
			$date = mysql2date( __( 'Y/m/d' ), $time );
		}

		return $date;
	}
}
