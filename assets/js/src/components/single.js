window.GatherContent = window.GatherContent || {};

( function( window, document, $, gc, undefined ) {
	'use strict';

	gc.single = gc.single || {};
	var app = gc.single;

	// Initiate base objects.
	require( './initiate-objects.js' )( app );

	/*
	 * Posts
	 */

	app.models.post = require( './models/post.js' )( gc );
	app.views.statusSelect2 = require( './views/status-select2.js' )( app );
	app.views.metabox = require( './views/metabox.js' )( app, $, gc );

	app.init = function() {
		// Kick it off.
		app.metaboxView = new app.views.metabox( {
			model : new app.models.post( gc._post )
		} );
	};

	$( app.init );

} )( window, document, jQuery, window.GatherContent );
