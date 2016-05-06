<?php
namespace GatherContent\Importer;

abstract class Base {

	protected static $_get  = null;
	protected static $_post = null;

	/**
	 * Creates an instance of this class.
	 * Superglobals are passed as class arguments to keep the object clean of globals.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $_get       Array of $_GET variables
	 * @param array  $_post       Array of $_POST variables
	 */
	protected function __construct( array $_get = null, array $_post = null ) {
		if ( is_array( $_get ) ) {
			self::$_get  = $_get;
		}

		if ( is_array( $_post ) ) {
			self::$_post = $_post;
		}

		if ( null === self::$_get || null === self::$_post ) {
			throw new Exception( __CLASS__ . ' expects the $_GET and $_POST variables as arguments' );
		}
	}

	/**
	 * Get the value from the query array.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $key Key to check/retrieve
	 *
	 * @return mixed        Query value if it exists.
	 */
	protected function get_val( $key ) {
		return isset( self::$_get[ $key ] ) ? self::$_get[ $key ] : null;
	}

	/**
	 * Check if the query array has a key.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $key Key to check.
	 *
	 * @return boolean      Whether query key exists in query.
	 */
	protected function get_has( $key ) {
		return isset( self::$_get[ $key ] );
	}

	/**
	 * Get the value from the $_POST array.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $key Key to check/retrieve
	 *
	 * @return mixed        Query value if it exists.
	 */
	protected function post_val( $key ) {
		return isset( self::$_post[ $key ] ) ? self::$_post[ $key ] : null;
	}

	/**
	 * Check if the query array has a key.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $key Key to check.
	 *
	 * @return boolean      Whether query key exists in query.
	 */
	protected function post_has( $key ) {
		return isset( self::$_post[ $key ] );
	}

	/**
	 * Outputs a view
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function view( $template, array $args = array(), $echo = true ) {
		switch ( $template ) {
			case 'input':
				echo new Views\Input( $args );
				break;

			default:
				echo new View( $template, $args );
				break;
		}
	}

}
