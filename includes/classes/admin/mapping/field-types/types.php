<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;

use GatherContent\Importer\Base as Plugin_Base;

class Exception extends \Exception {}

class Types extends Plugin_Base {

	/**
	 * Array of Base (implements Type)
	 *
	 * @var Base[]
	 */
	protected $core_types = array();

	protected $sub_types = [];

	/**
	 * Array of Type
	 *
	 * @var Type[]
	 */
	protected $field_types = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( array $core_types, array $sub_types ) {
		$this->core_types = $core_types;
		$this->sub_types = $sub_types;
	}

	/**
	 * @since 3.0.0
	 */
	public function register() {
		$field_types = apply_filters( 'gathercontent_register_field_types_handlers', $this->core_types );

		foreach ( $field_types as $type ) {
			if ( ! ( $type instanceof Type ) ) {
				throw new Exception( 'Field type handler needs to be of type GatherContent\\Importer\\Admin\\Mapping\Field_Types\\Type' );
			}

			$this->field_types[ $type->type_id() ] = $type;
			//TODO Gavin this is where the dropdowns are populated
			//TODO gavin but where are the _Extra_ drop downs from?
			add_action( 'gathercontent_field_type_option_underscore_template', array( $type, 'option_underscore_template' ) );
			add_action( 'gathercontent_field_type_underscore_template', array( $type, 'underscore_template' ) );
		}

		return $this;
	}

	public function get_field_types() {
		return $this->field_types;
	}

}
