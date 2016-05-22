/*! GatherContent Importer - v3.0.0
 * http://www.gathercontent.com
 * Copyright (c) 2016; * Licensed GPL-2.0+ */
window.GatherContent = window.GatherContent || {};

( function( window, document, $, app, undefined ) {
	'use strict';

	app.cache = function() {
		app.$ = {};
		app.$.tabNav = $( '.gc-nav-tab-wrapper .nav-tab' );
		app.$.tabs = $( '.gc-template-tab' );
	};

	app.init = function() {
		app.cache();
		$( document.body )
			.on( 'click', '.gc-nav-tab-wrapper .nav-tab', app.changeTabs )
			.on( 'click', '.gc-reveal-items', app.maybeReveal );
		// put all your jQuery goodness in here.
	};

	app.changeTabs = function( evt ) {
		var $this = $( this );
		evt.preventDefault();

		app.$.tabNav.removeClass( 'nav-tab-active' );
		$( this ).addClass( 'nav-tab-active' );
		app.$.tabs.addClass( 'hidden' );
		$( document.getElementById( $( this ).attr( 'href' ).substring(1) ) ).removeClass( 'hidden' );
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
