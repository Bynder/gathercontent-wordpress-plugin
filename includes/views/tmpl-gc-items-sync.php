<div id="gc-tablenav" class="tablenav top"></div>
<legend class="screen-reader-text"><?php _e( 'Import Items', 'gathercontent-import' ); ?></legend>
<table class="widefat striped gc-table">
	<thead>
		<tr>
			<td id="cb" class="gc-field-th manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'gathercontent-import' ); ?></label>
				<input <# if ( data.checked ) { #>checked="checked"<# } #> id="cb-select-all-1" type="checkbox">
			</td>
			<?php echo new self( 'tmpl-gc-items-table-header' ); ?>
		</tr>
	</thead>
	<tbody>
		<tr><td colspan="6"><span class="gc-loader spinner is-active"></span></td></tr>
	</tbody>
	<tfoot>
		<tr>
			<td class="gc-field-th manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'gathercontent-import' ); ?></label>
				<input <# if ( data.checked ) { #>checked="checked"<# } #> id="cb-select-all-1" type="checkbox">
			</td>
			<?php echo new self( 'tmpl-gc-items-table-header' ); ?>
		</tr>
	</tfoot>
</table>
<?php
	// echo "<# console.log( 'data', data ); #>";
