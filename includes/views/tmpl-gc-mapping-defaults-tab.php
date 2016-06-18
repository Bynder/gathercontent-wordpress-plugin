<tr id="default-mapping-post_author">
	<td>
		<strong><?php $this->output( 'post_author_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select gc-select2" data-url="<?php echo esc_url( admin_url( 'admin-ajax.php?action=gc_get_option_data' ) ); ?>" data-column="post_author" name="<?php $this->output( 'option_base' ); ?>[post_author]">
			<# if ( data.post_author && data[ 'select2:post_author:' + data.post_author ] ) { #>
				<option selected="selected" value="{{ data.post_author }}">{{ data['select2:post_author:' + data.post_author] }}</option>
			<# } #>
		</select>
	</td>
</tr>
<tr id="default-mapping-post_status">
	<td>
		<strong><?php $this->output( 'post_status_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select" data-column="post_status" name="<?php $this->output( 'option_base' ); ?>[post_status]">
			<?php foreach ( $this->get( 'post_status_options' ) as $option_val => $option_label ) : ?>
				<option <# if ( '<?php echo $option_val; ?>' === data.post_status ) { #>selected="selected"<# } #> value="<?php echo $option_val; ?>"><?php echo $option_label; ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
<tr id="default-mapping-post_type">
	<td>
		<strong><?php $this->output( 'post_type_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select" data-column="post_type" name="<?php $this->output( 'option_base' ); ?>[post_type]">
			<?php foreach ( $this->get( 'post_type_options' ) as $option_val => $option_label ) : ?>
				<option <# if ( '<?php echo $option_val; ?>' === data.post_type ) { #>selected="selected"<# } #> value="<?php echo $option_val; ?>"><?php echo $option_label; ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
<tr id="default-mapping-gc_status">
	<td>
		<a title="<?php _ex( 'Click to show additional details', 'About the GatherContent object', 'gathercontent-import' ); ?>" href="#" class="gc-reveal-items dashicons-before dashicons-arrow-<# if ( data.expanded ) { #>down<# } else { #>right<# } #>"><strong><?php _e( 'GatherContent Status', 'gathercontent-import' ); ?></strong></a>
		<ul class="gc-reveal-items-list <# if ( ! data.expanded ) { #>hidden<# } #>">
			<li><?php _e( 'Optionally change the GatherContent Status when content is imported.', 'gathercontent-import' ); ?></li>
		</ul>
	</td>
	<td>
		<select class="gc-default-mapping-select" data-column="gc_status" name="<?php $this->output( 'option_base' ); ?>[gc_status]">
			<?php foreach ( $this->get( 'gc_status_options' ) as $status ) : ?>
				<option <# if ( '<?php echo esc_attr( $status->id ); ?>' === data.gc_status ) { #>selected="selected"<# } #> value="<?php echo esc_attr( $status->id ); ?>"><?php echo esc_attr( $status->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
