window.GatherContent = window.GatherContent || {};

( function( window, document, $, undefined ) {
	'use strict';

	this.sync = this.sync || {};
	var app = this.sync;
	var log = this.log;

	log( this );

	app.init = function() {
		log( 'warn', 'GC Sync init' );
	};

	$( app.init );

} ).call( window.GatherContent, window, document, jQuery );
