<th scope="row" class="check-column">
	<label class="screen-reader-text" for="cb-select-{{ data.id }}"><?php _e( 'Select Another Item', 'gatdercontent-import' ); ?></label>
	<input id="cb-select-{{ data.id }}" type="checkbox" <# if ( data.checked ) { #>checked="checked"<# } #> name="import[]" value="{{ data.id }}" <# if ( data.disabled ) { #>disabled="disabled"<# } #>>
</th>
<td class="gc-modal-item-wp-post-title">
	{{ data.post_title }}
</td>
<td>
	{{ data.itemName }}
</td>
<td class="gc-status-column">
	<span class="gc-status-color" style="background-color:{{ data.status.color }};"></span>
	{{ data.status.name }}
</td>
<td class="gc-status-column">
	<a href="https://zao.gathercontent.com/item/{{ data.id }}" target="_blank"><?php _ex( 'ID:', 'GatherContent Item Id', 'gathercontent-import' ); ?> {{ data.id }}</a>
</td>
<td>
	<# if ( data.mappingLink ) { #>
	<a href="{{ data.mappingLink }}">
		<# if ( data.mappingStatus ) { #>
		{{ data.mappingStatus }}
		<# } else { #>
		<?php _ex( 'ID:', 'GatherContent Mapping Id', 'gathercontent-import' ); ?> {{ data.mapping }}
		<# } #>
	</a>
	<# } else { #>
	<?php _ex( 'ID:', 'GatherContent Mapping Id', 'gathercontent-import' ); ?> {{ data.mapping }}
	<# } #>
</td>
