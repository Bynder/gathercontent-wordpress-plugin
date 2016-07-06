<span class="gc-status-column" data-id="<?php $this->output( 'post_id' ); ?>" data-item="<?php $this->output( 'item_id' ); ?>" data-mapping="<?php $this->output( 'mapping_id' ); ?>">
<?php if ( $this->get( 'status_name' ) ) : ?>
	<div class="gc-item-status">
		<span class="gc-status-color" style="background-color:<?php $this->output( 'status_color' ); ?>;"></span>
		<?php $this->output( 'status_name' ); ?>
	</div>
<?php else: ?>
	&mdash;
<?php endif; ?>
</span>