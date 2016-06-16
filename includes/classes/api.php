<?php
namespace GatherContent\Importer;

class API extends Base {

	protected $base_url = 'https://api.gathercontent.com/';
	protected $user = '';
	protected $api_key = '';
	public $flush = false;

	/**
	 * WP_Http instance
	 *
	 * @var WP_Http
	 */
	protected $http;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( \WP_Http $http ) {
		parent::__construct();

		$this->http = $http;
	}

	public function set_user( $email ) {
		$this->user = $email;
	}

	public function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * GET request helper which assumes caching, and assumes a data parameter in response.
	 *
	 * @since  3.0.0
	 *
	 * @see    API::request_cache() For additional information
	 *
	 * @param  string $endpoint   GatherContent API endpoint to retrieve.
	 * @param  array  $args       Optional. Request arguments. Default empty array.
	 * @return mixed             The response.
	 */
	public function get( $endpoint, $args = array() ) {
		$data = $this->request_cache( $endpoint, DAY_IN_SECONDS, $args, 'GET' );
		if ( isset( $data->data ) ) {
			return $data->data;
		}

		return false;
	}

	/**
	 * Retrieve and cache the HTTP request.
	 *
	 * @since  3.0.0
	 *
	 * @see    API::request() For additional information
	 *
	 * @param  string $endpoint   GatherContent API endpoint to retrieve.
	 * @param  string $expiration The expiration time. Defaults to an hour.
	 * @param  array  $args       Optional. Request arguments. Default empty array.
	 * @param  array  $method     Optional. Request method, defaults to 'GET'.
	 * @return array             The response.
	 */
	public function request_cache( $endpoint, $expiration = HOUR_IN_SECONDS, $args = array(), $method = 'GET' ) {
		$trans_key = 'gctr-' . md5( serialize( compact( 'endpoint', 'args', 'method' ) ) );
		$response = get_transient( $trans_key );

		if ( ! $response || $this->_get_val( 'flush_cache' ) || $this->flush ) {

			$response = $this->request( $endpoint, $args, $method );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			set_transient( $trans_key, $response, $expiration );

			$keys = get_option( 'gathercontent_transients' );
			$keys = is_array( $keys ) ? $keys : array();
			$keys[ $endpoint ][] = $trans_key;
			update_option( 'gathercontent_transients', $keys, false );

			$this->flush = false;
		}

		return $response;
	}

	/**
	 * Retrieve the raw response from the HTTP request.
	 *
	 * Request method defaults for helper functions:
	 *  - Default 'GET'  for wp_remote_get()
	 *  - Default 'POST' for wp_remote_post()
	 *  - Default 'HEAD' for wp_remote_head()
	 *
	 * @since  3.0.0
	 *
	 * @see    WP_Http::request() For additional information on default arguments.
	 *
	 * @param  string $endpoint GatherContent API endpoint to retrieve.
	 * @param  array  $args     Optional. Request arguments. Default empty array.
	 * @param  array  $method   Optional. Request method, defaults to 'GET'.
	 * @return array           The response.
	 */
	public function request( $endpoint, $args = array(), $method = 'GET' ) {
		$method = strtolower( $method );
		$uri = $this->base_url . $endpoint;
		$args = $this->request_args( $args );

		error_log( '$uri: '. print_r( $uri, true ) );
		error_log( '$args: '. print_r( $args, true ) );
		$response = $this->http->{$method}( $uri, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code     = $response['response']['code'];
		$success  = $code >= 200 && $code < 300;

		return $success ? json_decode( wp_remote_retrieve_body( $response ) ) : $response;
	}

	public function request_args( $args ) {
		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->user . ':' . $this->api_key ),
			'Accept'        => 'application/vnd.gathercontent.v0.5+json',
		);

		$args['headers'] = isset( $args['headers'] )
			? wp_parse_args( $args['headers'], $headers )
			: $headers;

		return $args;
	}

	public function flush_cache( $endpoint = '' ) {
		$deleted = false;
		$keys = get_option( 'gathercontent_transients' );
		$keys = is_array( $keys ) ? $keys : array();

		if ( $endpoint ) {
			if ( isset( $keys[ $endpoint ] ) ) {
				foreach ( $keys[ $endpoint ] as $transient ) {
					delete_transient( $transient );
				}

				unset( $keys[ $endpoint ] );
				$deleted = true;
			}
		} else {
			foreach ( $keys as $endpoint => $transients ) {
				foreach ( $transients as $transient ) {
					delete_transient( $transient );
				}
			}

			$keys = array();
			$deleted = true;
		}

		update_option( 'gathercontent_transients', $keys, false );

		return $deleted;
	}

}
