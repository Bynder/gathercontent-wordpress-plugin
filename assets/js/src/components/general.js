window.GatherContent = window.GatherContent || {};

( function( window, document, $, gc, undefined ) {
	'use strict';

	gc.general = gc.general || {};
	var app = gc.general;

	// Initiate base objects.
	require( './initiate-objects.js' )( app );

	/*
	 * Post Row setup
	 */

	app.models.post = require( './models/post.js' )( app );
	app.collections.posts = require( './collections/posts.js' )( app );
	app.views.postRow = require( './views/post-row.js' )( app, gc );
	app.views.statusSelect2 = require( './views/status-select2.js' )( app );
	app.views.postRows = require( './views/post-rows.js' )( app, gc, $ );

	app.getPosts = function() {
		var posts = [];
		$( '.gc-status-column' ).each( function() {
			posts.push( $( this ).data() );
		} );
		return posts;
	};

	app.monkeyPatchQuickEdit = function( cb ) {
		// we create a copy of the WP inline edit post function
		var edit = window.inlineEditPost.edit;

		// and then we overwrite the function with our own code
		window.inlineEditPost.edit = function() {
			// "call" the original WP edit function
			// we don't want to leave WordPress hanging
			edit.apply( this, arguments );

			// now we take care of our business
			cb.apply( this, arguments );
		};
	};

	app.ajaxResponse = function( response ) {
		if ( response.success ) {
			// Kick it off.
			app.generalView = new app.views.postRows( {
				collection : new app.collections.posts( response.data )
			} );

			app.monkeyPatchQuickEdit( function() {
				app.generalView.trigger( 'quickEdit', arguments, this );
			} );
		}
	};

	app.init = function() {
		$( document.body ).addClass( 'gathercontent-admin-select2' );

		$.post( window.ajaxurl, {
			action      : 'gc_get_items',
			posts       : app.getPosts(),
			flush_cache : !! gc.queryargs.flush_cache
		}, app.ajaxResponse );
	};

	$( app.init );

} )( window, document, jQuery, window.GatherContent );
