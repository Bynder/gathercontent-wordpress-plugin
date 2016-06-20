<th scope="row" class="check-column">
	<label class="screen-reader-text" for="cb-select-{{ data.id }}"><?php _e( 'Select Another Item', 'gatdercontent-import' ); ?></label>
	<input id="cb-select-{{ data.id }}" type="checkbox" <# if ( data.checked ) { #>checked="checked"<# } #> name="import[]" value="{{ data.id }}">
</th>
<td>
	{{ data.name }}
</td>
<td class="gc-status-column">
	<span class="gc-status-color" style="background-color:{{ data.status.data.color }};"></span>
	{{ data.status.data.name }}
</td>
<td class="gc-status-column">
	<a href="https://zao.gathercontent.com/item/{{ data.id }}" target="_blank"><?php _ex( 'ID:', 'GatherContent Item Id', 'gathercontent-import' ); ?> {{ data.id }}</a>
</td>
