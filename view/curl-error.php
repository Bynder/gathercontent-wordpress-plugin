<h2 class="gc_logo"><span>GatherContent</span></h2>

<div class="gc_container">
	<div class="gc_container-header">
		<h2><?php $this->_e( 'cURL not found' ) ?></h2>
	</div>
	<div class="curl_error">
		<p><?php $this->_e( 'cURL was not found on your server. GatherContent requires cURL in order to connect to our services API. Please contact your host/webmaster to request they enable cURL.' ) ?></p>
		<p><?php printf( $this->__( 'If you have cURL installed and are seeing this error, please contact %ssupport@gathercontent.com%s' ), '<a href="mailto:support@gathercontent.com">', '</a>' ) ?></p>
	</div>
</div>
