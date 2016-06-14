window.GatherContent = window.GatherContent || {};

( function( window, document, $, app, undefined ) {
	'use strict';

	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};

	var log = function() {
		log.history = log.history || [];
		log.history.push( arguments );
		if ( app.debug && window.console && window.console.log ) {
			window.console.log( Array.prototype.slice.call(arguments) );
		}
	};

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
	app.views.tabRow = require( './views/tab-row.js' )( {
		viewBase : app.views.base
	} );

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

		$( document.body )
			.on( 'click', '.gc-reveal-items', app.maybeReveal );

		// Kick it off.
		app.mappingView = new app.views.tabs({
			collection : new app.collections.tabs( app._tabs )
		});
	};

	app.maybeReveal = function( evt ) {
		var $this = $( this );
		evt.preventDefault();

		if ( $this.hasClass( 'dashicons-arrow-right' ) ) {
			$this.removeClass( 'dashicons-arrow-right' ).addClass( 'dashicons-arrow-down' );
			$this.next().removeClass( 'hidden' );
		} else {
			$this.removeClass( 'dashicons-arrow-down' ).addClass( 'dashicons-arrow-right' );
			$this.next().addClass( 'hidden' );
		}
	};

	$( app.init );

} )( window, document, jQuery, window.GatherContent );
