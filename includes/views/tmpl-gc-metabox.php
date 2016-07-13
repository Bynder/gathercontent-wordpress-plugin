<div class="misc-pub-section gc-item-name">
	<span class="dashicons dashicons-edit"></span> <?php echo esc_html_x( 'Item:', 'GatherContent item name', 'gathercontent-importer' ); ?> <a href="<?php $this->output( 'url' ); ?>item/{{ data.item }}" target="_blank">{{ data.itemName }}</a>
</div>

<div class="misc-pub-section curtime misc-pub-curtime">
	<span id="timestamp"> <?php echo esc_html_x( 'Last Updated:', 'GatherContent updated date', 'gathercontent-importer' ); ?> <b>{{ data.updated }}</b></span>
</div>

<div class="misc-pub-section misc-pub-gc-mapping">
	<span class="dashicons dashicons-media-document"></span>

	<?php esc_html_e( 'Mapping Template:', 'gathercontent-importer' ); ?>
	<strong>
	<# if ( data.mappingLink ) { #>
	<a href="{{ data.mappingLink }}">
		<# if ( data.mappingStatus ) { #>
		{{ data.mappingStatus }}
		<# } else { #>
		{{ data.mappingName }}
		<# } #>
	</a>
	<# } else { #>
	{{ data.mappingName }}
	<# } #>
	</strong>
</div>

<div class="gc-major-publishing-actions">
	<div class="gc-publishing-action">
		<?php // $this->output( 'refresh_link' ); ?>
		<span class="spinner"></span>
		<button id="gc-push" type="button" class="button gc-button-primary alignright" <# if ( ! data.mapping ) { #>disabled="disabled"<# } #>><?php esc_html_e( 'Push', 'gathercontent-importer' ); ?></button>
		<button id="gc-pull" type="button" class="button gc-button-primary alignright" <# if ( ! data.mapping ) { #>disabled="disabled"<# } #>><?php esc_html_e( 'Pull', 'gathercontent-importer' ); ?></button>
	</div>
	<div class="clear"></div>
</div>

<?php
	echo "<# console.log( 'data', data ); #>";
