<hr>
<div class="gc-profile">
	<img src="https://gathercontent-production-avatars.s3-us-west-2.amazonaws.com/<?php $this->output( 'avatar' ); ?>" class="gc-avatar">
	<div>
		<h3 class="gc-hello"><?php printf( __( 'Hello %s!', 'gathercontent-import' ), $this->get( 'first_name' ) ); ?></h3>
		<div><?php _e( "You've successfully connected to the GatherContent API.", 'gathercontent-import' ); ?></div>
	</div>
</div>
<hr>
<p><?php printf( __( 'For more information: <a href="%s" target="_blank">https://gathercontent.com/how-it-works</a>.', 'gathercontent-import' ), 'https://gathercontent.com/how-it-works' ); ?></p>
