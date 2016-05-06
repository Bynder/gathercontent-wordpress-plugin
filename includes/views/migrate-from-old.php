<div class="wrap">
	<h2><?php echo get_admin_page_title(); ?></h2>
	<h3><?php _e( 'You need to migrate your settings from the previous version.', 'gathercontent-import' ); ?></h3>
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		echo '<xmp>$this->args: '. print_r( $this->args, true ) .'</xmp>';
		submit_button();
		?>
	</form>

</div>
