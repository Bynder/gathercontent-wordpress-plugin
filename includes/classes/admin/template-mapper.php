<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Base as Plugin_Base;

/**
 * Class for managing/creating template mappings.
 */
class Template_Mapper extends Plugin_Base {

	protected $mapping_id;
	protected $template;
	protected $project;
	protected $option_name;

	/**
	 * Field_Types\Types
	 *
	 * @var Field_Types\Types
	 */
	public $field_types;

	public function __construct( array $args ) {
		$this->mapping_id  = $args['mapping_id'];
		$this->template    = $args['template'];
		$this->project     = $args['project'];
		$this->option_name = $args['option_name'];
	}

	/**
	 * The mapping page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function mapping_ui() {

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

		// Hook in the underscores templates
		add_action( 'admin_footer', array( $this, 'footer_mapping_js_templates' ) );
		add_filter( 'gathercontent_localized_data', array( $this, 'localize_data' ) );

		wp_enqueue_script( 'gathercontent-mapping', GATHERCONTENT_URL . "assets/js/gathercontent-mapping{$this->suffix}.js", array( 'gathercontent' ), GATHERCONTENT_VERSION, 1 );

		$this->field_types = $this->initiate_mapped_field_types();
	}

	public function localize_data( $data ) {
		$data['_tabs']      = $this->get_tabs();
		$data['_meta_keys'] = $this->custom_field_keys();

		return $data;
	}

	/**
	 * Output the underscore templates in the footer.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function footer_mapping_js_templates() {
		$templates = array(
			'tmpl-gc-tabs-wrapper' => array(),
			'tmpl-gc-tab-wrapper' => array(),
			'tmpl-gc-mapping-tab-row' => array(
				'option_base' => $this->option_name,
				'post_types'  => $this->post_types(),
			),
			'tmpl-gc-mapping-defaults-tab' => array(
				'default_fields'      => $this->get_default_fields(),
				'post_author_label'   => $this->post_column_label( 'post_author' ),
				'post_author_options' => $this->get_default_field_options( 'post_author' ),
				'post_status_options' => $this->get_default_field_options( 'post_status' ),
				'post_status_label'   => $this->post_column_label( 'post_status' ),
				'post_type_label'     => __( 'Post Type', 'gathercontent-import' ),
				'post_type_options'   => $this->get_default_field_options( 'post_type' ),
				'option_base'         => $this->option_name,
			),
		);
		foreach ( $templates as $template_id => $view_args ) {
			echo '<script type="text/html" id="'. $template_id .'">';
			$this->view( $template_id, $view_args );
			echo '</script>';
		}
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

		$tabs[] = array(
			'id'          => 'mapping-defaults',
			'label'       => __( 'Mapping Defaults', 'gathercontent-import' ),
			'hidden'      => true,
			'navClasses'  => 'alignright',
			'viewId'      => 'defaultTab',
			'rows'        => $this->post_options(),
			'post_author' => $this->get_value( 'post_author', 'esc_attr', 1 ),
			'post_status' => $this->get_value( 'post_status', 'esc_attr', 'draft' ),
			'post_type'   => $this->get_value( 'post_type', 'esc_attr', 'post' ),
		);

		return $tabs;
	}

	/**
	 * Get's the default <select> options for a Post column.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $col Post column.
	 *
	 * @return array        Array of <select> options.
	 */
	protected function get_default_field_options( $col ) {
		$select_options = array();

		switch ( $col ) {
			case 'post_author':
				$value = 1;
				$user = $this->get_value( 'post_author' )
					? get_user_by( 'id', absint( $this->get_value( 'post_author' ) ) )
					: wp_get_current_user();
				$select_options[ $value ] = $user->user_login;
				break;
			case 'post_status':
				$select_options = array(
					'publish' => __( 'Published', 'gathercontent-import' ),
					'draft'   => __( 'Draft', 'gathercontent-import' ),
					'pending' => __( 'Pending', 'gathercontent-import' ),
					'private' => __( 'Private', 'gathercontent-import' ),
				);
				break;
			case 'post_type':
				foreach ( $this->post_types() as $type ) {
					$select_options[ $type->name ] = $type->labels->singular_name;
				}
				break;
		}

		return $select_options;
	}

	/**
	 * Gets the columns from the posts table for the <select> option.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of <select> options.
	 */
	protected function post_options() {
		static $options = null;

		if ( null !== $options ) {
			return $options;
		}

		global $wpdb;

		$options = array();
		$table_name = $wpdb->prefix . 'posts';
		$post_columns = $wpdb->get_col( "DESC " . $table_name, 0 );

		foreach ( $post_columns as $col ) {
			if ( ! $this->post_column_option_is_blacklisted( $col) ) {
				$options[ $col ] = $this->post_column_label( $col );
			}
		}

		return $options;
	}

	/**
	 * Gets a list of unique keys from the postmeta table. Value is cached for a day.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of keys to be used in a backbone collection.
	 */
	protected function custom_field_keys() {
		global $wpdb;

		$meta_keys = get_transient( 'gathercontent_importer_custom_field_keys' );

		if ( ! $meta_keys || isset( $_GET['delete-trans'] ) ) {
			// retrieve custom field keys to include in the Custom Fields weight table select
			$meta_keys = $wpdb->get_col( $wpdb->prepare( "
				SELECT meta_key
				FROM $wpdb->postmeta
				WHERE meta_key NOT LIKE %s
				GROUP BY meta_key
			",
				'_oembed_%'
			) );

			set_transient( 'gathercontent_importer_custom_field_keys', $meta_keys, DAY_IN_SECONDS );
		}

		// allow devs to filter this list
		$meta_keys = array_unique( apply_filters( 'gathercontent_importer_custom_field_keys', $meta_keys ) );

		// sort the keys alphabetically
		if ( $meta_keys ) {
			natcasesort( $meta_keys );
		} else {
			$meta_keys = array();
		}

		/**
		 * Fields which should not be shown in the UI for meta-keys.
		 *
		 * @var array
		 */
		$meta_keys_blacklist = apply_filters( 'gathercontent_importer_custom_field_keys_blacklist', array(
			'_wp_attachment_image_alt' => 1,
			'_wp_attachment_metadata' => 1,
			'_wp_attached_file' => 1,
			'_edit_lock' => 1,
			'_edit_last' => 1,
			'_wp_page_template' => 1,
			'_gc_project' => 1,
			'_gc_template' => 1,
		) );

		$keys = array();
		foreach ( array_values( $meta_keys ) as $column ) {
			if ( ! isset( $meta_keys_blacklist[ $column ] ) )  {
				$keys[] = array( 'value' => $column );
			}
		}

		return $keys;
	}

	/**
	 * Gets the universal/default field rows for the "Mapping Defaults" tab.
	 *
	 * @since  3.0.0
	 *
	 * @return array  Array of field rows.
	 */
	public function get_default_fields() {
		$new_options = array();

		foreach ( array( 'post_author', 'post_status', 'post_type' ) as $col ) {
			$new_options[] = array(
				'column'  => $col,
				'label'   => $this->post_column_label( $col ),
				'options' => $this->get_default_field_options( $col ),
			);
		}

		return $new_options;
	}

	/**
	 * Only allow a certain set of post-table columns to be mappable .
	 *
	 * @since  3.0.0
	 *
	 * @param  string $col Post table column
	 *
	 * @return bool        Whether column passed the blacklist check.
	 */
	protected function post_column_option_is_blacklisted( $col ) {
		return in_array( $col, array(
			'ID',
			'to_ping',
			'pinged',
			'post_mime_type',
			'comment_count',
			'post_content_filtered',
			'guid',
			'post_type',
			'post_type',
		) );
	}

	/**
	 * Maps the post-table's column names to a human-readable value.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $col Post table column
	 *
	 * @return string       Human readable value if we have one.
	 */
	protected function post_column_label( $col ) {
		switch ( $col ) {
			case 'ID':
				return __( 'Author', 'gathercontent-import' );
			case 'post_author':
				return __( 'Author', 'gathercontent-import' );
			case 'post_date':
				return __( 'Post Date', 'gathercontent-import' );
				return 'post_date';
			case 'post_date_gmt':
				return __( 'Post Date (GMT)', 'gathercontent-import' );
			case 'post_content':
				return __( 'Post Content', 'gathercontent-import' );
			case 'post_title':
				return __( 'Post Title', 'gathercontent-import' );
			case 'post_excerpt':
				return __( 'Post Excerpt', 'gathercontent-import' );
			case 'post_status':
				return __( 'Post Status', 'gathercontent-import' );
			case 'comment_status':
				return __( 'Comment Status', 'gathercontent-import' );
			case 'ping_status':
				return __( 'Ping Status', 'gathercontent-import' );
			case 'post_password':
				return __( 'Post Password', 'gathercontent-import' );
			case 'post_name':
				return __( 'Post Name (Slug)', 'gathercontent-import' );
			case 'post_modified':
				return __( 'Post Modified Date', 'gathercontent-import' );
			case 'post_modified_gmt':
				return __( 'Post Modified Date (GMT)', 'gathercontent-import' );
			case 'post_parent':
				return __( 'Post Parent', 'gathercontent-import' );
			case 'menu_order':
				return __( 'Menu Order', 'gathercontent-import' );
			case 'post_type':
				return __( 'Post Type', 'gathercontent-import' );
			default:
				return $col;
		}
	}

	/**
	 * Get all post-types and related taxonomies.
	 *
	 * @since  3.0.0
	 *
	 * @return array  Array of post-types w/ thier taxonomies.
	 */
	protected function post_types() {
		static $post_types = null;

		if ( null !== $post_types ) {
			return $post_types;
		}

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $index => $type ) {
			$type->taxonomies = array();
			foreach ( get_object_taxonomies( $type->name, 'objects' ) as $tax ) {
				if ( 'post_format' === $tax->name ) {
					// continue;
					$tax->label = __( 'Post Formats', 'gathercontent-import' );
				}

				$type->taxonomies[] = $tax;
			}

			$post_types[ $index ] = $type;
		}

		return $post_types;
	}

	/**
	 * Get a specific value from the array of values stored to the template-mapping post.
	 *
	 * @since  3.0.0
	 *
	 * @param  string   $key      Array key to check
	 * @param  callable $callback Callback to send data through.
	 * @param  mixed    $default  Default value if value doesn't exist.
	 *
	 * @return mixed              Value of field.
	 */
	protected function get_value( $key, $callback = null, $default = null ) {
		static $values = null;

		if ( null === $values ) {
			$values = $this->stored_values();
		}

		$value = isset( $values[ $key ] ) ? $values[ $key ] : $default;

		return $callback && $value ? $callback( $value ) : $value;
	}

	/**
	 * Get the stored mapping values from the template-mapping post's content field.
	 *
	 * @since  3.0.0
	 *
	 * @return array  Array of values.
	 */
	protected function stored_values() {
		$values = array();

		if ( $this->mapping_id && ( $json = get_post_field( 'post_content', $this->mapping_id ) ) ) {

			$json = json_decode( $json, 1 );

			if ( is_array( $json ) ) {
				$values = $json;

				if ( isset( $values['mapping'] ) && is_array( $values['mapping'] ) ) {
					$mapping = $values['mapping'];
					unset( $values['mapping'] );
					$values += $mapping;
				}
			}
		}

		$stored_values = $values;

		return $stored_values;
	}

}
