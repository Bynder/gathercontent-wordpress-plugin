<table class="widefat">
	<tbody>
		<?php foreach ( $this->get( 'elements' ) as $element ) : ?>
		<tr>
			<td>
				<strong><?php echo $element->label; ?></strong>
			</td>
			<td>
				<select name="<?php $this->output( 'option_base' ); ?>[mapping][type][<?php echo $element->name; ?>]">
					<option value=""><?php _e( 'Unused', 'gathercontent-import' ); ?></option>
					<option value="post"><?php _e( 'Post Data', 'gathercontent-import' ); ?></option>
					<option value="meta"><?php _e( 'Metadata', 'gathercontent-import' ); ?></option>
					<option value="taxonomy"><?php _e( 'Taxonomy/Terms', 'gathercontent-import' ); ?></option>
				</select>
			</td>
			<td>
				<select name="<?php $this->output( 'option_base' ); ?>[mapping][<?php echo $element->name; ?>]">
					<?php foreach ( $this->get( 'destination_post_options' ) as $col => $label ) : ?>
					<option <?php selected( $this->get_from( 'values', $element->name ), $col ); ?> value="<?php echo $col; ?>"><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
