<?php
/**
 * The Modal Window, including sidebar and content area.
 * Add menu items to ".navigation-bar nav ul"
 * Add content to ".gc-bb-modal-main article"
 */
// echo "<# console.log( 'data', data ); #>";
?>
<div class="gc-bb-modal">
	<button type="button" class="button-link media-modal-close gc-bb-modal-close">
		<span class="media-modal-icon">
			<span class="screen-reader-text">
				<?php echo __( 'Close', 'gathercontent-import' ); ?>
			</span>
		</span>
	</button>

	<div class="gc-bb-modal-content <# if ( data.navItems.length ) { #>has-nav-tabs<# } #>">

		<div class="media-frame-menu">
			<div class="media-menu">
				<?php foreach ( $this->get( 'nav' ) as $url => $text ) { ?>
					<a class="media-menu-item" href="<?php echo esc_url( $url ); ?>">
						<?php echo $text; ?>
					</a>
				<?php } ?>
			</div>
		</div>

		<div class="media-frame-title gc-bb-modal-title">
			<h1>
				<img width="220px" height="39px" src="<?php echo GATHERCONTENT_URL; ?>images/logo.svg" alt="GatherContent" />
			</h1>
		</div>

		<# if ( data.navItems.length ) { #>
		<div class="media-frame-router gc-bb-modal-nav-tabs">
			<div class="media-router">
				<# _.each( data.navItems, function( nav ) { #>
					<a class="media-menu-item <# if ( ! nav.hidden ) { #>active<# } #>" data-id="{{ nav.id }}" href="#">{{ nav.label }}</a>
				<# }); #>
			</div>
		</div>
		<# } #>

		<div class="media-frame-content gc-bb-modal-main">
			<div class="tablenav top">
				<div class="tablenav-pages one-page">
					<span class="displaying-num"><span class="gc-item-count">{{ data.count }}</span> <?php _e( 'items', 'gathercontent-import' ); ?></span>
				</div>
				<br class="clear">
			</div>
			<table id="gc-modal-{{ data.currID }}" class="gc-modal-tabs widefat striped gc-table">
				<thead>
					<tr>
						<td id="cb" class="gc-field-th manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'gathercontent-import' ); ?></label>
							<input <# if ( data.checked ) { #>checked="checked"<# } #> id="cb-select-all-1" type="checkbox">
						</td>
						<th class="gc-field-th"><?php _e( 'Status', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'Item', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'Updated', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'Template Mapping', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'WordPress Title', 'gathercontent-import' ); ?></th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td>
							<span class="spinner is-active" style="margin: 0 4px 0 0;"></span>
						</td>
						<td>
							<span><?php _e( 'Checking for items...', 'gathercontent-import' ); ?></span>
						</td>
					</tr>
				</tbody>

				<tfoot>
					<tr>
						<td class="gc-field-th manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'gathercontent-import' ); ?></label>
							<input <# if ( data.checked ) { #>checked="checked"<# } #> id="cb-select-all-1" type="checkbox">
						</td>
						<th class="gc-field-th"><?php _e( 'Status', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'Item', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'Updated', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'Template Mapping', 'gathercontent-import' ); ?></th>
						<th class="gc-field-th"><?php _e( 'WordPress Title', 'gathercontent-import' ); ?></th>
					</tr>
				</tfoot>
			</table>
		</div>

		<div class="media-frame-toolbar">
			<div class="media-toolbar">
				<div class="media-toolbar-primary search-form">
					<# if ( data.btns.length ) { _.each( data.btns, function( btn ) { #>
					<button id="gc-btn-{{ btn.id }}" class="button media-button button-<# if ( btn.primary ) { #>primary<# } else { #>secondary<# } #> button-large">
						{{ btn.label }}
					</button>
					<# }); } #>
				</div>
			</div>
		</div>

		<div class="gc-cloak"></div>
		<div id="gc-related-data"></div>

	</div>
</div>
<?php
	// echo "<# console.log( 'data', data ); #>";
