<tr id="default-mapping-post_author">
	<td>
		<strong><?php $this->output( 'post_author_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select gc-select2" data-column="post_author" name="<?php $this->output( 'option_base' ); ?>[post_author]">
			<?php foreach ( $this->get( 'post_author_options' ) as $option_val => $option_label ) : ?>
				<option <# if ( '<?php echo $option_val; ?>' === data.post_author ) { #>selected="selected"<# } #> value="<?php echo $option_val; ?>"><?php echo $option_label; ?></option>
			<?php endforeach; ?>
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
