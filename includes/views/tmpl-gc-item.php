<th scope="row" class="check-column">
	<label class="screen-reader-text" for="cb-select-{{ data.id }}"><?php _e( 'Select Another Item', 'gatdercontent-import' ); ?></label>
	<input id="cb-select-{{ data.id }}" type="checkbox" <# if ( data.checked ) { #>checked="checked"<# } #> name="import[]" value="{{ data.id }}" <# if ( data.disabled ) { #>disabled="disabled"<# } #>>
</th>
<td class="gc-status-column">
	<span class="gc-status-color <# if ( '#ffffff' === data.status.color ) { #> gc-status-color-white<# } #>" style="background-color:{{ data.status.color }};"></span>
	{{ data.status.name }}
</td>
<td>
	<a href="<?php $this->output( 'url' ); ?>item/{{ data.id }}" target="_blank">{{ data.itemName }}</a>
</td>
<td>
	{{ data.updated }}
</td>
<td>
	<# if ( data.mappingLink ) { #>
	<a href="{{ data.mappingLink }}">
		<# if ( data.mappingStatus ) { #>
		{{ data.mappingStatus }}
		<# } else { #>
		{{ data.mappingName }}
		<# } #>
	</a>
	<# } else { #>
	{{ data.mappingName }}
	<# } #>
</td>
<td class="gc-item-wp-post-title">
	<# if ( data.editLink ) { #><a href="{{ data.editLink }}"><# } #>
	{{{ data.post_title }}}
	<# if ( data.editLink ) { #></a><# } #>
</td>
<?php
	// echo "<# console.log( 'data', data ); #>";
