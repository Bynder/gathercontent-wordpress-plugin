<h2 class="gc_logo"><span>GatherContent</span></h2>
<?php
if ( $this->error != '' ){
	echo '<p class="gc_error">'.$this->error.'</p>';
}
?>
<div class="gc_container gc_wide">
	<div class="gc_overlay"></div>
	<div class="gc_container gc_modal gc_importing_modal">
		<h2><?php $this->_e( 'Importing items and text content...' ) ?></h2>
		<label><?php $this->_e( 'Item:' ) ?> <span id="gc_item_title"></span><img src="<?php echo $this->plugin_url ?>img/ajax-loader-grey.gif" alt="" /></label>
		<div id="current_item" class="progress">
			<div class="bar" style="width:0%"></div>
		</div>
	</div>
	<div class="gc_container gc_modal gc_repeating_modal">
		<h2><?php $this->_e( 'Repeating configuration...' ) ?></h2>
		<img src="<?php echo $this->plugin_url ?>img/ajax_loader_blue.gif" alt="" />
	</div>
	<div class="gc_container-header gc_cf">
		<h2><?php $this->_e( 'Choose items to import' ) ?><a href="<?php $this->url( 'login' ) ?>" class="gc_blue_link gc_right"><?php $this->_e( 'Choose account' ) ?></a></h2>
	</div>
	<form action="<?php $this->url( 'items_import' ) ?>" method="post" id="gc_importer_step_items_import">
		<div class="gc_main_content">
			<div class="gc_search_items gc_cf">
				<div class="gc_left">
					<a href="<?php $this->url( 'items' ) ?>" class="gc_option"><?php $this->_e( 'Select different items' ) ?></a>
				</div>
				<div class="gc_right">
					<?php $item_count > 0 && $this->get_submit_button( $this->__( 'Import selected items' ) ) ?>
				</div>
			</div>
			<table class="gc_items" id="gc_items" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th></th>
						<th class="gc_th_item_name"><?php echo $this->__( 'Items' ); ?></th>
						<th><input type="checkbox" id="toggle_all" checked="checked" /></th>
					</tr>
				</thead>
				<tbody>
					<?php echo $item_settings ?>
				</tbody>
			</table>
		</div>
		<div class="gc_subfooter gc_cf">
			<div class="gc_right">
				<?php $item_count > 0 && $this->get_submit_button( $this->__( 'Import selected items' ) ) ?>
			</div>
		</div>
		<?php wp_nonce_field( $this->base_name ) ?>
	</form>
</div>
<script type="text/javascript">
var redirect_url = <?php
echo json_encode(
	array(
		'media'        => $this->url( 'media', false ),
		'finished'     => $this->url( 'finished', false )
	)
);
?>;
var hierarchical_post_types =  <?php echo json_encode( $this->hierarchical ); ?>;
</script>
