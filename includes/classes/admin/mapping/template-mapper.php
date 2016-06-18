<?php
namespace GatherContent\Importer\Admin\Mapping;

/**
 * Class for managing/creating template mappings.
 */
class Template_Mapper extends Base {

	protected $option_name = '';
	protected $statuses = array();

	/**
	 * Field_Types\Types
	 *
	 * @var Field_Types\Types
	 */
	public $field_types;

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->statuses = $args['statuses'];
		$this->option_name = $args['option_name'];
	}

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	protected function script_id() {
		return 'gathercontent-mapping';
	}

	/**
	 * The mapping page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function ui_page() {

		// Output the markup for the JS to build on.
		echo '<div id="mapping-tabs"><span class="gc-loader spinner is-active"></span></div>';

		if ( $this->mapping_id ) {

			echo '<div class="sync-items-descriptions">
			<p class="description"><a href="'. esc_url( add_query_arg( 'sync-items', 1 ) ) .'"><span class="dashicons dashicons-randomize"> </span>' . __( 'Sync Template Items with GatherContent', 'domain' ) . '</a></p>
			</div>';

			$this->view( 'input', array(
				'type'    => 'hidden',
				'id'      => 'gc-existing-id',
				'name'    => $this->option_name .'[existing_mapping_id]',
				'value'   => $this->mapping_id,
			) );
		}

		$project_id  = esc_attr( $this->project->id );
		$template_id = esc_attr( $this->template->id );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-create-map',
			'name'    => $this->option_name .'[create_mapping]',
			'value'   => wp_create_nonce( md5( $project_id . $template_id ) ),
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-project-id',
			'name'    => $this->option_name .'[title]',
			'value'   => esc_attr( isset( $this->template->name ) ? $this->template->name : __( 'Mapped Template', 'gathercontent-import' ) ),
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-project-id',
			'name'    => $this->option_name .'[project]',
			'value'   => $project_id,
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-template-id',
			'name'    => $this->option_name .'[template]',
			'value'   => $template_id,
		) );

		$this->field_types = $this->initiate_mapped_field_types();
	}

	/**
	 * Get the localizable data array.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of localizable data
	 */
	protected function get_localize_data() {
		return array(
			'_tabs'      => $this->get_tabs(),
			'_meta_keys' => $this->custom_field_keys(),
		);
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	protected function get_underscore_templates() {
		return array(
			'tmpl-gc-tabs-wrapper' => array(),
			'tmpl-gc-tab-wrapper' => array(),
			'tmpl-gc-mapping-tab-row' => array(
				'option_base' => $this->option_name,
				'post_types'  => $this->post_types(),
			),
			'tmpl-gc-mapping-defaults-tab' => array(
				'post_author_label'   => $this->post_column_label( 'post_author' ),
				'post_status_options' => $this->get_default_field_options( 'post_status' ),
				'post_status_label'   => $this->post_column_label( 'post_status' ),
				'post_type_label'     => $this->post_column_label( 'post_type' ),
				'post_type_options'   => $this->get_default_field_options( 'post_type' ),
				'gc_status_options'   => $this->statuses,
				'option_base'         => $this->option_name,
			),
		);
	}

	/**
	 * Initiates the mapped field types. By default, post fields, taxonomies, and meta fields.
	 * If WP-SEO is installed, that field type will be iniitated as well.
	 *
	 * @since  3.0.0
	 *
	 * @return Field_Types\Types object
	 */
	protected function initiate_mapped_field_types() {
		$core_field_types = array(
			new Field_Types\Post( $this->post_options() ),
			new Field_Types\Taxonomy( $this->post_types() ),
			new Field_Types\Meta(),
		);

		if ( defined( 'WPSEO_VERSION' ) ) {
			$core_field_types[] = new Field_Types\WPSEO( $this->post_types() );
		}

		return ( new Field_Types\Types( $core_field_types ) )->register();
	}

	/**
	 * Get's the GC tabs and adds a default tab for universal settings.
	 *
	 * @since  3.0.0
	 *
	 * @return array  Array of tabs.
	 */
	protected function get_tabs() {
		$tabs = array();
		foreach ( $this->template->config as $tab ) {

			$rows = array();
			foreach ( $tab->elements as $element ) {

				if ( $this->get_value( $element->name ) ) {
					$val = $this->get_value( $element->name );
					$element->field_type = isset( $val['type'] ) ? $val['type'] : '';
					$element->field_value = isset( $val['value'] ) ? $val['value'] : '';
				}

				$rows[] = $element;
			}

			$tab_array = array(
				'id'     => $tab->name,
				'label'  => $tab->label,
				'hidden' => ! empty( $tabs ),
				'rows'   => $rows,
			);

			$tabs[] = $tab_array;
		}

		$default_tab = array(
			'id'          => 'mapping-defaults',
			'label'       => __( 'Mapping Defaults', 'gathercontent-import' ),
			'hidden'      => true,
			'navClasses'  => 'alignright',
			'viewId'      => 'defaultTab',
			'rows'        => $this->post_options(),
			'post_author' => $this->get_value( 'post_author', 'absint', 1 ),
			'post_status' => $this->get_value( 'post_status', 'esc_attr', 'draft' ),
			'post_type'   => $this->get_value( 'post_type', 'esc_attr', 'post' ),
			'gc_status'   => $this->get_value( 'gc_status', 'esc_attr' ),
		);

		$default_tab[ 'select2:post_author:' . $default_tab['post_author'] ] = $this->get_default_field_options( 'post_author' );

		$tabs[] = $default_tab;

		return $tabs;
	}

}
