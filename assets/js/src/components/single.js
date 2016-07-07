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

	// app.models.post = require( './models/post.js' )( gc );
	// app.collections.posts = require( './collections/posts.js' )( app );
	// app.views.postRow = require( './views/post-row.js' )( app, gc );
	// app.views.statusSelect2 = require( './views/status-select2.js' )( app );
	// app.views.postRows = require( './views/post-rows.js' )( app, gc, $ );

	app.init = function() {
		console.warn('single init');
		// Kick it off.
		// app.singleView = new app.views.postRows( {
		// 	collection : new app.collections.posts( [ gc._post ] )
		// } );
	};

	$( app.init );

} )( window, document, jQuery, window.GatherContent );
