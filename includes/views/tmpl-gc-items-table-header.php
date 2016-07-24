<th scope="col" class="gc-field-th sortable <# if ( 'status' === data.sortKey ) { #>{{ data.sortDirection }}<# } #>">
	<a href="#" data-id="status">
		<span><?php _e( 'Status', 'gathercontent-import' ); ?></span>
		<# if ( 'status' === data.sortKey ) { #>
		<span class="sorting-indicator"></span>
		<# } #>
	</a>
</th>
<th scope="col" class="gc-field-th sortable <# if ( 'itemName' === data.sortKey ) { #>{{ data.sortDirection }}<# } #>">
	<a href="#" data-id="itemName">
		<span><?php _e( 'Item', 'gathercontent-import' ); ?></span>
		<# if ( 'itemName' === data.sortKey ) { #>
		<span class="sorting-indicator"></span>
		<# } #>
	</a>
</th>
<th scope="col" class="gc-field-th sortable <# if ( 'updated_at' === data.sortKey ) { #>{{ data.sortDirection }}<# } #>">
	<a href="#" data-id="updated_at">
		<span><?php _e( 'Updated', 'gathercontent-import' ); ?></span>
		<# if ( 'updated_at' === data.sortKey ) { #>
		<span class="sorting-indicator"></span>
		<# } #>
	</a>
</th>
<th scope="col" class="gc-field-th sortable <# if ( 'mapping' === data.sortKey ) { #>{{ data.sortDirection }}<# } #>">
	<a href="#" data-id="mapping">
		<span><?php _e( 'Template Mapping', 'gathercontent-import' ); ?></span
		<# if ( 'mapping' === data.sortKey ) { #>
		><span class="sorting-indicator"></span>
		<# } #>
	</a>
</th>
<th scope="col" class="gc-field-th sortable <# if ( 'post_title' === data.sortKey ) { #>{{ data.sortDirection }}<# } #>">
	<a href="#" data-id="post_title">
		<span><?php _e( 'WordPress Title', 'gathercontent-import' ); ?></span
		<# if ( 'post_title' === data.sortKey ) { #>
		><span class="sorting-indicator"></span>
		<# } #>
	</a>
</th>
