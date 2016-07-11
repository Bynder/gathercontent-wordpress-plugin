<?php wp_nonce_field( GATHERCONTENT_SLUG, 'gc-edit-nonce' ); ?>
<span id="gc-related-data" data-id="<?php $this->output( 'post_id' ); ?>" data-item="<?php $this->output( 'item_id' ); ?>" data-mapping="<?php $this->output( 'mapping_id' ); ?>"></span>

<?php if ( $this->get( 'mapping_id' ) ) : ?>
	<p><span class="spinner is-active"></span></p>

	<div class="misc-pub-section hidden misc-pub-post-status">
		<span class="dashicons dashicons-post-status"></span>
		<?php esc_html_e( 'Remote Status:', 'gathercontent-importer' ); ?>
		<strong>Pending Review</strong>
		<a href="#post_status" class="edit-post-status hide-if-no-js"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit status</span></a>
	</div>

	<div class="misc-pub-section hidden curtime misc-pub-curtime">
		<span id="timestamp">
		Last Updated: <b>Jun 24, 2016 @ 06:13</b></span>
		<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit date and time</span></a>
	</div>

	<div class="misc-pub-section hidden misc-pub-post-status">
		<span class="dashicons dashicons-media-document"></span>

		<?php esc_html_e( 'Template:', 'gathercontent-importer' ); ?>
		<strong>Blah</strong>
		<a href="#post_status" class="edit-post-status hide-if-no-js"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">Edit status</span></a>
	</div>

	<div class="misc-pub-section hidden misc-pub-post-status">
		<span class="dashicons dashicons-edit"></span>
		<a href="#">Edit in GatherContent</a>
	</div>
<?php else: ?>
	<div class="misc-pub-section">
		<p><?php esc_html_e( 'This post does not have an associated GatherContent item.', 'gathercontent-importer' ); ?></p>
		<?php if ( $this->get( 'message' ) ) : ?>
		<p><?php $this->output( 'message' ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="gc-major-publishing-actions">
	<div class="gc-publishing-action">
		<button id="gc-sync-modal" type="button" class="button gc-button-primary alignright" <?php if ( ! $this->get( 'mapping_id' ) ) : ?>disabled="disabled"<?php endif; ?>><?php esc_html_e( 'GatherContent Sync', 'gathercontent-importer' ); ?></button>
	</div>
	<div class="clear"></div>
</div>
