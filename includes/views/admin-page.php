<div class="wrap">
	<h2><?php echo get_admin_page_title(); ?></h2>
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( $this->get( 'option_group' ) );
		$this->output( 'settings_sections' );
		submit_button();
		?>
	</form>

</div>
