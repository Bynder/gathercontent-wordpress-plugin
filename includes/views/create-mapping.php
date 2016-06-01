<div class="gc-template-tab-group">

	<h5 class="nav-tab-wrapper gc-nav-tab-wrapper">
	<?php foreach ( $this->get( 'tabs' ) as $tab ) : ?>
		<a href="#<?php echo esc_attr( $tab->nav_item['value'] ); ?>" class="<?php echo esc_attr( $tab->nav_item['class'] ); ?>"><?php echo $tab->nav_item['lable']; ?></a>
	<?php endforeach; ?>
	</h5>

	<div>
		<span class="gc-mapping-setting gc-import-as">
			<label>Import as </label>
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

	<?php foreach ( $this->get( 'tabs' ) as $tab ) : ?>
		<fieldset class="<?php echo $tab->tab_class; ?>" id="<?php echo esc_attr( $tab->name ); ?>">
		<legend class="screen-reader-text"><?php echo $tab->label; ?></legend>
		<table class="widefat">
			<tbody>
				<?php foreach ( $tab->elements as $element ) : ?>
				<tr>
					<td>
						<strong><?php echo $element->label; ?></strong>
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
		</fieldset>
	<?php endforeach; ?>
	<?php if ( $this->get( 'edit_link' ) ) : ?>
		<?php printf( __( '<strong>Note:</strong> You are editing <a href="%s">an existing %s</a>.', 'gathercontent-import' ), esc_url( $this->get( 'edit_link' ) ), $this->get( 'mapping_template_label' ) ); ?>
	<?php endif; ?>
