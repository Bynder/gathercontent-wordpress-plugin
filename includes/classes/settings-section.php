<?php
namespace GatherContent\Importer;

class Settings_Section extends Base {

	protected $page;
	protected $id;
	protected $field;
	protected static $sections;

	public function __construct( $id, $title, $callback, $page ) {
		$this->page = $page;

		$section = compact( 'id', 'title', 'callback', 'is_current' );
		$section = apply_filters( "gathercontent_importer_section_{$id}", $section, $this );

		$this->id = $section['id'];
		$this->title = $section['title'];
		$this->callback = $section['callback'];

		self::$sections[ $this->id ] = $this;
	}

	public function get_section( $is_current ) {
		$class = 'gc-section-'. $this->id . ( $is_current ? '' : ' hidden' );
		$html  = '<div class="gc-setting-section '. $class .'">';

			if ( $this->title ) {
				$html .= "<h2>{$this->title}</h2>\n";
			}

			if ( $this->callback ) {
				$html .= $this->do_desc_callback();
			}

			$html .= '<table class="form-table">';
			$html .= $this->do_fields();
			$html .= '</table>';

		$html .= '</div>';

		return $html;
	}

	public function do_desc_callback() {
		ob_start();
		call_user_func( $this->callback, $this );
		return ob_get_clean();
	}

	public function do_fields() {
		ob_start();
		do_settings_fields( $this->page, $this->id );
		return ob_get_clean();
	}

	public function add_field( $id, $title, $callback, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'label_for' => $id,
			'class' => $id . '-row',
		) );

		$field = compact( 'id', 'title', 'callback', 'args' );
		$field = apply_filters( "gathercontent_importer_field_{$this->id}_{$id}", $field, $this );

		add_settings_field(
			$field['id'],
			$field['title'],
			function( $args ) use ( $field ) {
				$this->field = $field;
				$field['callback']( $this );
			},
			$this->page,
			$this->id,
			$field['args']
		);

	}

	public function do_param( $key ) {
		echo $this->param( $key );
	}

	public function param( $key ) {
		return isset( $this->field[ $key ] ) ? $this->field[ $key ] : null;
	}

	public static function get_sections( $step ) {

		$html = '';
		$step_count = 0;

		foreach ( self::$sections as $section ) {
			$html .= $section->get_section( ++$step_count === $step );
		}

		return $html;
	}

}


