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
	protected $post_options = array();

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

	private function getAllTableColOptions($currentTableName = null)
	{
		$allOpts = [];

		foreach ($this->tableColumnData as $tableName => $columns) {
			$allOpts = array_merge($allOpts, $this->getTableColOptions($tableName, $currentTableName === $tableName));
		}

		return $allOpts;
	}

	private function getTableColOptions(string $tableName, bool $shouldShow)
	{
		$optionStrings = [];

		foreach ($this->tableColumnData[$tableName] as $column) {
			$optionStrings[] = "<option <# if ('" . $column . "' == data.field_subvalue) { #> selected='selected' <# } #> style='display: " . ($shouldShow ? 'block' : 'none') . " data-tablename='$tableName' value='{$column}'>{$column}</option>";
		}

		return $optionStrings;
	}

	public function underscore_template( View $view ) {

		$subValueName = $view->get( 'option_base' ) . '[mapping][{{ data.name }}][sub-value]';

		//TODO Gavin this cannot be the done way of adding on-page js. Do it properly.
		$mainSelectOnChangeJavascript = <<<EOT
/** @type {HTMLSelectElement} selectElement */
const selectElement = this
const value = selectElement.value

// get the selected options text
const text = selectElement.options[selectElement.selectedIndex].text

// get the sub-selector and clear any selection
const subSelectElement = this.parentElement.querySelector('.cw-column-selector')
subSelectElement.value = ''

// hide any option whose data-tablename is not this text
subSelectElement.querySelectorAll('option').forEach(opt => {
	const optTableName = opt.getAttribute('data-tablename')

	opt.style.display = optTableName === text ? 'block' : 'none'
})
EOT;

		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
			<select onchange="<?= $mainSelectOnChangeJavascript ?>" class="gc-select2 wp-type-value-select <?php $this->e_type_id(); ?>" name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
				<?php $this->underscore_options( $this->post_options ); ?>
				<?php $this->underscore_empty_option( __( 'Do Not Import', 'gathercontent-import' ) ); ?>
			</select>
<!--		//TODO Gavin how do we pass the selection down on submit?-->
			<select name="<?= $subValueName ?>" class="cw-column-selector">
				<option value="">Select a column</option>
				<?= implode('\r\n', $this->getAllTableColOptions()) ?>
			</select>
		<# } #>
		<?php
	}

}
