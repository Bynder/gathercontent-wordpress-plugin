<table class="widefat">
	<tbody>
		<tr>
			<td>
				<div>
					<span class="gc-mapping-setting gc-import-as">
						<label><?php _e( 'Import as:', 'gathercontent-import' ); ?> </label>
						<select name="<?php $this->output( 'option_base' ); ?>[post_type]" class="dropdown-menu">
							<?php foreach ( $this->get( 'post_types' ) as $type ) : ?>
							<option <?php selected( $this->get_from( 'values', 'post_type' ), $type->name ); ?> value="<?php echo $type->name; ?>"><?php echo $type->label; ?></option>
							<?php endforeach; ?>
						</select>
					</span>
					</span>
					<?php foreach ( $this->get( 'post_types' ) as $type ) : ?>
						<?php foreach ( $type->taxonomies as $tax ) : ?>
							<span class="gc-mapping-setting gc-category">
								<label><?php echo $tax->label; ?></label>
								<select name="<?php $this->output( 'option_base' ); ?>[<?php echo $tax->name; ?>]" class="dropdown-menu">
									<?php foreach ( $tax->terms as $term ) : ?>
									<option <?php selected( $this->get_from( 'values', $tax->name ), $term->term_id ); ?> value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
									<?php endforeach; ?>
								</select>

							</span>
						<?php endforeach; ?>
					<?php endforeach; ?>
					<span class="gc-mapping-setting gc-state">
						<label>Status </label>
						<select name="<?php $this->output( 'option_base' ); ?>[post_status]" class="dropdown-menu">
							<option <?php selected( $this->get_from( 'values', 'post_status' ), 'publish' ); ?> value="publish">Published</option>
							<option <?php selected( $this->get_from( 'values', 'post_status' ), 'draft' ); ?> value="draft">Draft</option>
							<option <?php selected( $this->get_from( 'values', 'post_status' ), 'pending' ); ?> value="pending">Pending</option>
						</select>
					</span>
				</div>

			</td>
		</tr>
		<?php foreach ( $this->get( 'destination_post_options' ) as $value => $option ) : if ( ! $value ) { continue; } ?>
		<tr>
			<td>
				<strong><?php echo $option; ?></strong>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
