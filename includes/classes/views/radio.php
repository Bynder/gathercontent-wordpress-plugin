<?php
namespace GatherContent\Importer\Views;

class Radio extends Form_Element {

	protected $default_attributes = array(
		'id'      => '',
		'name'    => '',
		'value'   => '',
		'desc'    => '',
		'options' => array(),
	);

	protected function element() {
		$value = '';
		$attributes = $this->attributes();
		$options = (array) $attributes['options'];
		// unset( $attributes['options'] );

		$value = $attributes['value'];
		$value = is_array( $value ) ? $value : array();
		// unset( $attributes['value'] );

		// $atts_string = '';
		// foreach ( $attributes as $attr => $attr_value ) {
		// 	if ( 'value' === $attr ) {
		// 		$value = $attr_value;
		// 		continue;
		// 	}
		// 	$atts_string .= ' ' . $attr . '="'. $attr_value .'"';
		// }

		$content = '<ul>';
		$index = 0;
		foreach ( $options as $option_val => $option_label ) {
			$index++;
			$content .= '<li>';
			$content .= new Input( array(
				'type'     => 'radio',
				'class'    => 'radio-select',
				'id'       => $attributes['id'] . '-' . $index,
				'name'     => $attributes['name'],
				'value'    => $option_val,
				'selected' => in_array( $option_val, $value, 1 ) ? 'selected' : '',
			) );

			$desc = '';
			if ( is_array( $option_label ) ) {
				$desc = isset( $option_label['desc'] ) ? $option_label['desc'] : '';
				$option_label = isset( $option_label['label'] ) ? $option_label['label'] : '';
			}
			$content .= ' <label title="'. esc_attr( $option_val ) . '" for="'. $attributes['id'] . '-' . $index .'">' . $option_label . '</label>';
			$content .= $desc ? '<p class="description gc-radio-desc">'. $desc .'</p>' : '';
			$content .= '</li>';
		}
		$content .= '</ul>';

		return $content;
	}

}
