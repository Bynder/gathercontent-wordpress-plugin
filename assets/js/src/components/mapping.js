window.GatherContent = window.GatherContent || {};

( function( window, document, $, gc, undefined ) {
	'use strict';

	gc.mapping = gc.mapping || {};
	var app = gc.mapping;

	// Initiate base objects.
	require( './initiate-objects.js' )( app );

	/*
	 * Tab Row setup
	 */

	app.models.tabRow = require( './models/tab-row.js' )( app );
	app.collections.tabRows = require( './collections/tab-rows.js' )( app );
	app.views.tabRow = require( './views/tab-row.js' )( app, gc._meta_keys );

	/*
	 * Tab setup
	 */

	app.models.tab = require( './models/tab.js' )( app );
	app.collections.tabs = require( './collections/tabs.js' )( app );
	app.views.tab = require( './views/tab.js' )( app );

	app.views.tabLink = require( './views/tab-link.js' )( app );

	app.views.defaultTab = require( './views/default-tab.js' )( app );

	/*
	 * Overall view setup
	 */

	app.views.tabs = require( './views/tabs.js' )( app );

	app.init = function() {
		// Kick it off.
		app.mappingView = new app.views.tabs( {
			collection : new app.collections.tabs( gc._tabs )
		} );
	};

	$( app.init );

} )( window, document, jQuery, window.GatherContent );
