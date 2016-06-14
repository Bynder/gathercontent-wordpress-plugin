<div class="gc-template-tab-group">

	<h5 class="nav-tab-wrapper gc-nav-tab-wrapper">
	<?php
	$active = 'nav-tab-active';
	foreach ( $this->get( 'tabs' ) as $tab_id => $tab ) : ?>
		<a href="#<?php echo esc_attr( $tab_id ); ?>" class="nav-tab <?php echo $active; ?> <?php echo isset( $tab['class'] ) ? $tab['class'] : ''; ?>"><?php echo $tab['label']; ?></a>
	<?php
	$active = '';
	endforeach; ?>
	</h5>

	<?php $this->output( 'before_tabs_wrapper' ); ?>

	<?php
	$hidden = '';
	foreach ( $this->get( 'tabs' ) as $tab_id => $tab ) : ?>
		<fieldset class="gc-template-tab <?php echo $hidden; ?>" id="<?php echo esc_attr( $tab_id ); ?>">
		<legend class="screen-reader-text"><?php echo $tab['label']; ?></legend>
		<?php echo $tab['content']; ?>
		</fieldset>
	<?php
	$hidden = 'hidden';
	endforeach; ?>

	<?php $this->output( 'after_tabs_wrapper' ); ?>

</div>
