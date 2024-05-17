<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;

use GatherContent\Importer\Views\View;

class Database extends Base implements Type {

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'text',
		'text_rich',
		'text_plain',
		'choice_radio',
	);

	protected $type_id      = 'wp-type-database';
	protected $post_options = [];

	protected $tableColumnData = [];

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( array $post_options ) {
		$this->tableColumnData = $post_options;

		$tableNames = array_keys($this->tableColumnData);
		$this->post_options = array_combine($tableNames, $tableNames);
		$this->option_label = __( 'Database', 'gathercontent-import' );
	}

	private function getAllTableColOptions()
	{
		$allOpts = [];

		foreach ($this->tableColumnData as $tableName => $columns) {
			$allOpts = array_merge($allOpts, $this->getTableColOptions($tableName));
		}

		return $allOpts;
	}

	private function getTableColOptions(string $tableName)
	{
		$optionStrings = [];

		foreach ($this->tableColumnData[$tableName] as $column) {
			/**
			 * Returns the required underscore template string to be true when
			 * this column and table are selected. We need to check for both as
			 * multiple tables can use the same column name
			 */
			$isColumnAndTableTemplateJs = "data.field_value == '{$tableName}.{$column}'";

			/**
			 * template engine needs to know if this should be set selected
			 */
			$selectedJs = "
				<# if ($isColumnAndTableTemplateJs) { #> selected='selected' <# } #>
			";

			/**
			 * Template engine always set visible if this is the selected column
			 */
			$defaultVisibleJs = "
				style='display: <# if ($isColumnAndTableTemplateJs) { #>block<# } else { #>none<# } #>'
			";

			$optionData = "data-tablename='$tableName' data-columnname='$column'";

			$optionStrings[] = "<option $selectedJs $defaultVisibleJs $optionData value='{$column}'>{$column}</option>";
		}

		return $optionStrings;
	}

	public function underscore_options( $array ) {
		foreach ( $array as $value => $label ) {
			$this->underscore_option( $value, $label );
		}
	}

	public function underscore_option( $value, $label ) {
		$fieldValueJs = "
			<# if ( '" . $value . "' === data.field_value.split('.')[0] ) { #>selected='selected'<# } #>
		";

		echo '<option '.$fieldValueJs.' value="' . $value . '">' . $label . '</option>';
	}

	private function tableSelectChangedJavascript(): string
	{
		return <<<EOT
/** this runs when the table select is changed */
const selectElement = this
const value = selectElement.value

// get the selected options text
const text = selectElement.options[selectElement.selectedIndex].text

// get the column selector sibling element
const tableSelect = this.parentElement.querySelector('.cw-column-selector')
tableSelect.value = ''

// hide any option whose data-tablename is not this text
tableSelect.querySelectorAll('option').forEach(opt => {
	const optTableName = opt.getAttribute('data-tablename')
	opt.style.display = optTableName === text ? 'block' : 'none'
})

// set this value as the first portion of the hidden element's value
const hidden = this.parentElement.querySelector('.hidden-database-table-name')
let hiddenVal = hidden.value
if(!hiddenVal.includes('.')){
	hiddenVal = '.'
}
const parts = hiddenVal.split('.')
parts.splice(0, 1, value)
hidden.value = parts.join('.')
EOT;
	}

	private function columSelectChangedJavascript(): string
	{
		return <<<EOT
/** this runs when the column selector is changed */
const selectElement = this
const value = selectElement.value

// set this value as the second portion of the hidden element's value
const hidden = this.parentElement.querySelector('.hidden-database-table-name')
let hiddenVal = hidden.value
if(!hiddenVal.includes('.')){
	hiddenVal = '.'
}
const parts = hiddenVal.split('.')
parts.splice(1, 1, value)
hidden.value = parts.join('.')
EOT;
	}

	public function underscore_template( View $view ) {
		/**
		 * TODO gavin - surely this cannot be the correct way of dynamic js on
		 * wordpress pages? Fix this when you suss out how it's meant to be done
		 */
		$tableSelectChangedJavascript = $this->tableSelectChangedJavascript();
		$columnSelectChangedJavascript = $this->columSelectChangedJavascript();

		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
			<div class="wp-type-database-dropdown-container">
				<select
					class="gc-select2 wp-type-value-select <?php $this->e_type_id(); ?>"
					onchange="<?= $tableSelectChangedJavascript ?>"
					name=""
				>
					<?php $this->underscore_options( $this->post_options ); ?>
					<?php $this->underscore_empty_option( __( 'Do Not Import', 'gathercontent-import' ) ); ?>
				</select>

				<select
					class="cw-column-selector"
					onchange="<?= $columnSelectChangedJavascript ?>"
					name=""
				>
					<option value="">Select a column</option>
					<?= implode('\r\n', $this->getAllTableColOptions()) ?>
				</select>

				<!--		//TODO gavin - this is the actual input that is passed on form submit -->
				<input
					class="hidden-database-table-name"
					type="hidden"
					name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]"
				<# if ( data.field_value ) { #>value="{{ data.field_value }}"<# } #>
				/>
			</div>
		<# } #>
		<?php
	}

}
