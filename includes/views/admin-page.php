<div class="wrap">
	<h2><?php $this->output( 'logo' ); ?></h2>
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( $this->get( 'option_group' ) );
		$this->output( 'settings_sections' );
		submit_button( $this->get( 'submit_button_text' ) );
		?>
	</form>

</div>
