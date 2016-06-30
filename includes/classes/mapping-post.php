<?php
namespace GatherContent\Importer;

class Mapping extends Base {

	/**
	 * WP_Post ID
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Array of mapping data or false.
	 *
	 * @var array|false
	 */
	protected $data = false;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $post
	 */
	public function __construct( \WP_Post $post ) {
		$this->post_id = $post->ID;
		$this->init_data( $post );
	}

	protected function init_data( $post ) {
		if ( ! isset( $post->post_content ) || empty( $post->post_content ) ) {
			return;
		}

		$json = json_decode( $post->post_content, 1 );

		if ( is_array( $json ) ) {
			$this->data = $json;

			if ( isset( $this->data['mapping'] ) && is_array( $this->data['mapping'] ) ) {
				$_mapping = $this->data['mapping'];
				unset( $this->data['mapping'] );
				$this->data += $_mapping;
			}
		}
	}

	public function data() {
		return $this->data;
	}

	public function get( $arg ) {
		if ( ! isset( $this->data[ $arg ] ) ) {
			return false;
		}

		$destination = $this->data[ $arg ];

		if ( isset( $destination['type'] ) ) {
			// Trim qualifiers (wpseo, acf, cmb2, etc)
			$type = explode( '--', $destination['type'] );
			$destination['type'] = $type[0];
		}

		return $destination;
	}

}
