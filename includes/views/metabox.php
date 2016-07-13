<?php wp_nonce_field( GATHERCONTENT_SLUG, 'gc-edit-nonce' ); ?>

<div id="gc-related-data" data-id="<?php $this->output( 'post_id' ); ?>" data-item="<?php $this->output( 'item_id' ); ?>" data-mapping="<?php $this->output( 'mapping_id' ); ?>" class="no-js">
	<?php if ( $this->get( 'mapping_id' ) ) : ?>
		<p><span class="spinner is-active"></span>  <?php esc_html_e( 'Loading...', 'gathercontent-importer' ); ?></p>
	<?php else: ?>
		<p><?php esc_html_e( 'This post does not have an associated GatherContent item.', 'gathercontent-importer' ); ?></p>
		<?php if ( $this->get( 'message' ) ) : ?>
		<p><?php $this->output( 'message' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
