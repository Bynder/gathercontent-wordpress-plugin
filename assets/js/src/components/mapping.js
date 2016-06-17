window.GatherContent = window.GatherContent || {};

( function( window, document, $, gc, undefined ) {
	'use strict';

	gc.mapping = gc.mapping || {};
	var app = gc.mapping;

	app.models      = { base : require( './models/base.js' ) };
	app.collections = { base : require( './collections/base.js' ) };
	app.views       = { base : require( './views/base.js' ) };

	/*
	 * Tab Row setup
	 */

	app.models.tabRow = require( './models/tab-row.js' )( app );
	app.collections.tabRows = require( './collections/tab-rows.js' )( {
		collectionBase : app.collections.base,
		model : app.models.tabRow
	} );
	app.views.tabRow = require( './views/tab-row.js' )( app, gc._meta_keys );

	/*
	 * Tab setup
	 */

	app.models.tab = require( './models/tab.js' )( {
		modelBase : app.models.base,
		rowCollection : app.collections.tabRows
	} );
	app.collections.tabs = require( './collections/tabs.js' )( {
		collectionBase : app.collections.base,
		model : app.models.tab
	} );
	app.views.tab = require( './views/tab.js' )( {
		viewBase : app.views.base,
		rowView : app.views.tabRow
	} );

	app.views.tabLink = require( './views/tab-link.js' )( {
		viewBase : app.views.base
	} );

	app.views.defaultTab = require( './views/default-tab.js' )( {
		viewTab : app.views.tab,
	} );

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
