window.GatherContent = window.GatherContent || {};

( function( window, document, $, gc, undefined ) {
	'use strict';

	gc.sync = gc.sync || {};
	var app = gc.sync;

	// Initiate base objects.
	require( './initiate-objects.js' )( app );

	/*
	 * Item setup
	 */

	app.models.item = require( './models/item.js' )( app );
	app.collections.items = require( './collections/items.js' )( app );
	app.views.item = require( './views/item.js' )( app );
	app.views.items = require( './views/items.js' )( app, $, gc.percent );

	app.init = function() {
		// Kick it off.
		app.syncView = new app.views.items( {
			collection : new app.collections.items( gc._items )
		} );
	};

	$( app.init );

} )( window, document, jQuery, window.GatherContent );
