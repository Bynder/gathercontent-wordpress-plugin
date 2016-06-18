<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Views\View;

abstract class Base extends Plugin_Base implements Type {
	protected $type_id = '';
	protected $option_label = '';

	public function type_id() {
		return $this->type_id;
	}

	public function e_type_id() {
		echo $this->type_id;
	}

	public function option_underscore_template( View $view ) {
		?>
		<option <# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>selected="selected"<# } #> value="<?php $this->e_type_id(); ?>"><?php echo $this->option_label; ?></option>
		<?php
	}

	abstract function underscore_template( View $view );

	public function underscore_options( $array ) {
		foreach ( $array as $value => $label ) {
			$this->underscore_option( $value, $label );
		}
	}

	public function underscore_option( $value, $label ) {
		echo '<option <# if ( "'. $value .'" === data.field_value ) { #>selected="selected"<# } #> value="'. $value .'">'. $label .'</option>';
	}

	public function underscore_empty_option( $label ) {
		$this->underscore_option( '', $label );
	}

}
