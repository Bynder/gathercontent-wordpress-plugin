<h2 class="gc_logo"><span>GatherContent</span></h2>

<div class="gc_container">
	<div class="gc_container-header">
		<h2><?php $this->_e( 'Importing files' ) ?></h2>
	</div>
	<div id="gc_media">
		<div class="alert alert-success">
			<?php $this->_e( '<strong>Heads up!</strong> This process can take a while, it depends on how many files you have attached to your pages. Just think how much time you\'re saving.' ) ?>
		</div>
		<label><?php $this->_e( 'Page:' ) ?> <span id="gc_page_title" title="<?php echo $original_title ?>"><?php echo $page_title ?></span><img src="<?php echo $this->plugin_url ?>img/ajax-loader-grey.gif" alt="" /></label>
		<div id="current_page" class="progress">
			<div class="bar" style="width:0%"></div>
		</div>
		<label><?php $this->_e( 'Overall Progress' ) ?></label>
		<div id="overall_files" class="progress">
			<div class="bar" style="width:0%"></div>
		</div>
		<div class="gc_center">
			<a href="<?php $this->url( 'pages_import' ) ?>" class="gc_blue_link"><?php $this->_e( 'Cancel' ) ?></a>
		</div>
		<?php wp_nonce_field( $this->base_name ) ?>
	</div>
</div>
<script type="text/javascript">
var redirect_url = '<?php $this->url( 'finished' ) ?>';
</script>
