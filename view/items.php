<h2 class="gc_logo"><span>GatherContent</span></h2>
<?php
if ( $this->error != '' ) {
	echo '<p class="gc_error">' . $this->error . '</p>';
}
?>
<div class="gc_container gc_wide" id="gc_itemlist_container">
	<div class="gc_container-header">
		<h2><?php $this->_e( 'Choose items to import' ) ?><a href="<?php $this->url( 'login' ) ?>" class="gc_blue_link gc_right"><?php $this->_e( 'Choose account' ) ?></a></h2>
	</div>
	<form action="<?php $this->url( 'items' ) ?>" method="post" id="gc_importer_step_items">
		<div class="gc_main_content">
			<div class="gc_search_items gc_cf">
				<div class="gc_left">
					<?php echo $projects_dropdown ?>
				</div>
				<div class="gc_right">
					<?php echo $state_dropdown ?>
					<input type="text" name="search" id="gc_live_filter" placeholder="<?php echo esc_attr( $this->__( 'Search...' ) ) ?>" />
					<?php $item_count > 0 && $this->get_submit_button( $this->__( 'Configure selected items' ) ) ?>
				</div>
			</div>
			<table class="gc_items gc_itemlist" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th></th>
						<th class="gc_th_item_name"><?php echo $this->__( 'Items' ); ?></th>
						<th><input type="checkbox" id="toggle_all" /></th>
					</tr>
				</thead>
				<tbody>
					<?php echo $item_settings ?>
				</tbody>
			</table>
		</div>
		<div class="gc_subfooter gc_cf">
			<div class="gc_right">
				<?php $item_count > 0 && $this->get_submit_button( $this->__( 'Configure selected items' ) ) ?>
			</div>
		</div>
		<?php wp_nonce_field( $this->base_name ) ?>
	</form>
</div>
