<td>
	<# if ( ( data.limit && data.limit_type ) || data.microcopy ) { #>
	<a href="#" class="gc-reveal-items dashicons-before dashicons-arrow-<# if ( data.expanded ) { #>down<# } else { #>right<# } #>"><strong>{{ data.label }}</strong></a>
	<ul class="gc-reveal-items-list <# if ( ! data.expanded ) { #>hidden<# } #>">
		<# if ( data.limit && data.limit_type ) { #>
		<li><strong><?php _e( 'Limit: ', 'gathercontent-import' ); ?></strong>{{ data.limit }} {{ data.limit_type }} </li>
		<# } #>
		<# if ( data.microcopy ) { #>
		<li>{{ data.microcopy }}</li>
		<# } #>
	</ul>
	<# } else { #>
	<strong>{{ data.label }}</strong>
	<# } #>
</td>
<td>
	<select class="wp-type-select" name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][type]">
		<option <# if ( '' === data.field_type ) { #>selected="selected"<# } #> value=""><?php _e( 'Unused', 'gathercontent-import' ); ?></option>
		<option <# if ( 'post' === data.field_type ) { #>selected="selected"<# } #> value="post"><?php _e( 'Post Data', 'gathercontent-import' ); ?></option>
		<option <# if ( 'taxonomy' === data.field_type ) { #>selected="selected"<# } #> value="taxonomy"><?php _e( 'Taxonomy/Terms', 'gathercontent-import' ); ?></option>
		<option <# if ( 'meta' === data.field_type ) { #>selected="selected"<# } #> value="meta"><?php _e( 'Metadata', 'gathercontent-import' ); ?></option>
	</select>

	<?php foreach ( $this->get( 'post_types' ) as $type ) : ?>
	<# if ( 'taxonomy' === data.field_type && '<?php echo $type->name; ?>' === data.post_type ) { #>
		<select class="wp-type-value-select wp-type-taxonomy wp-taxonomy-<?php echo $type->name; ?>-type" name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
			<?php if ( empty( $type->taxonomies ) ) : ?>
				<option selected="selected" value=""><?php _e( 'N/A', 'gathercontent-import' ); ?></option>
			<?php else: ?>
			<?php foreach ( $type->taxonomies as $taxonomy ) : ?>
				<option <# if ( '<?php echo $taxonomy->name; ?>' === data.field_value ) { #>selected="selected"<# } #> value="<?php echo $taxonomy->name; ?>"><?php echo $taxonomy->label; ?></option>
			<?php endforeach; ?>
			<?php endif; ?>
		</select>
	<# } #>
	<?php endforeach; ?>
	<# if ( 'post' === data.field_type ) { #>
		<select class="gc-select2 wp-type-value-select wp-type-post" name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
			<?php foreach ( $this->get( 'post_options' ) as $col => $label ) : ?>
			<option <# if ( '<?php echo $col; ?>' === data.field_value ) { #>selected="selected"<# } #> value="<?php echo $col; ?>"><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>
	<# } #>
	<# if ( 'meta' === data.field_type ) { #>
		<select class="gc-select2 wp-type-value-select wp-type-meta" name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
			<?php foreach ( $this->get( 'meta_options' ) as $key ) : ?>
			<option <# if ( '<?php echo $key; ?>' === data.field_value ) { #>selected="selected"<# } #> value="<?php echo $key; ?>"><?php echo $key; ?></option>
			<?php endforeach; ?>
		</select>
	<# } #>
</td>
