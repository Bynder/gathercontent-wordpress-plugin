module.exports = function( app, $, gc ) {
	var percent = gc.percent;
	var thisView;

	return app.views.base.extend({
		el               : '#sync-tabs',
		template         : wp.template( 'gc-items-sync' ),
		progressTemplate : wp.template( 'gc-items-sync-progress' ),
		$wrap            : $( '.gc-admin-wrap' ),
		timeoutID        : null,
		ajax             : null,
		tableNavView     : null,
		searchView       : null,

		events : {
			'click .gc-cancel-sync'       : 'clickCancelSync',
			'click .gc-field-th.sortable' : 'sortRowsByColumn',
			'change .gc-field-th.check-column input' : 'checkAll'
		},

		initialize: function() {
			thisView = this;

			this.setupAjax();

			this.listenTo( this.ajax, 'response', this.ajaxResponse );
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this.collection, 'enabledChange', this.checkEnableButton );
			this.listenTo( this.collection, 'notAllChecked', this.allCheckedStatus );
			this.listenTo( this.collection, 'search', this.searchQuery );
			this.listenTo( this.collection, 'change:checked', this.renderNav );

			this.listenTo( this, 'render', this.render );

			this.$wrap.on( 'submit', 'form', this.submit.bind( this ) );

			this.tableNavView = new app.views.tableNav( {
				collection : this.collection
			} );

			this.searchView = new app.views.tableSearch( {
				collection : this.collection
			} );

			this.initRender();
		},

		setupAjax: function() {
			var Ajax = require( './../models/ajax.js' )( app, {
				checkHits   : 0,
				time        : 500,
				nonce       : gc.el( '_wpnonce' ).value,
				id          : gc.el( 'gc-input-mapping_id' ).value,
				flush_cache : gc.queryargs.flush_cache ? 1 : 0
			} );

			this.ajax = new Ajax( {
				percent : percent
			} );
		},

		sortRowsByColumn: function( evt ) {
			evt.preventDefault();
			var collection = this.collection.current();

			var $this     = $( evt.currentTarget );
			var column    = $this.find( 'a' ).data( 'id' );
			var direction = false;

			if ( $this.hasClass( 'asc' ) ) {
				direction = 'desc';
			}

			if ( $this.hasClass( 'desc' ) ) {
				direction = 'asc';
			}

			if ( ! direction ) {
				direction = collection.sortDirection;
			}

			if ( 'asc' === direction ) {
				$this.addClass( 'desc' ).removeClass( 'asc' );
			} else {
				$this.addClass( 'asc' ).removeClass( 'desc' );
			}

			collection.trigger( 'sortByColumn', column, direction );
			this.initRender();
		},

		searchQuery: function() {
			this.initRender();
		},

		checkEnableButton: function( syncEnabled ) {
			this.buttonStatus( syncEnabled );
		},

		buttonStatus: function( enable ) {
			this.$wrap.find( '.button-primary' ).prop( 'disabled', ! enable );
		},

		allCheckedStatus: function( checked ) {
			this.$wrap.find( '.gc-field-th.check-column input' ).prop( 'checked', checked );
		},

		checkAll: function( evt ) {
			console.warn('click checkall', $( evt.target ).is( ':checked' ));
			this.collection.trigger( 'checkAll', $( evt.target ).is( ':checked' ) );
		},

		clickCancelSync: function( evt ) {
			evt.preventDefault();
			this.cancelSync();
		},

		doSpinner: function() {
			var html = this.blankRow( '<span class="gc-loader spinner is-active"></span>' );
			this.renderRows( html );
		},

		submit: function( evt ) {
			evt.preventDefault();
			this.startSync( this.$wrap.find( 'form' ).serialize() );
		},

		startSync: function( formData ) {
			this.doSpinner();
			this.ajax.reset().set( 'stopSync', false );
			this.renderProgress( 100 === window.parseInt( percent, 10 ) ? 0 : percent );
			this.doAjax( formData, percent );
		},

		cancelSync: function( url ) {
			percent = null;

			this.ajax.reset();
			this.clearInterval();

			if ( url ) {
				this.doAjax( 'cancel', 0, function() {
					window.location.href = url;
				} );
			} else {
				this.doAjax( 'cancel', 0, function(){} );
				this.initRender();
			}
		},

		doAjax: function( formData, completed, cb ) {
			cb = cb || this.ajaxSuccess.bind( this );
			this.ajax.send( formData, cb, completed );
		},

		ajaxSuccess: function( response ) {
			if ( this.ajax.get( 'stopSync' ) ) {
				return;
			}

			percent = response.data.percent || 1;
			var hits = this.checkHits();
			var time = this.ajax.get( 'time' );

			if ( hits > 25 && time < 2000 ) {
				this.clearInterval();
				this.ajax.set( 'time', 2000 );
			} else if ( hits > 50 && time < 5000 ) {
				this.clearInterval();
				this.ajax.set( 'time', 5000 );
			}

			this.setTimeout( this.checkProgress.bind( this ) );

			if ( percent > 99 ) {
				this.cancelSync( window.location.href + '&updated=1&flush_cache=1&redirect=1' );
			} else {
				this.renderProgressUpdate( percent );
			}
		},

		setTimeout: function( callback ) {
			this.timeoutID = window.setTimeout( callback, this.ajax.get( 'time' ) );
		},

		clearInterval: function() {
			window.clearTimeout( this.timeoutID );
			this.timeoutID = null;
		},

		checkProgress: function() {
			this.doAjax( 'check', percent );
		},

		checkHits: function() {
			return window.parseInt( this.ajax.get( 'checkHits' ), 10 );
		},

		ajaxResponse: function( response, formData ) {
			gc.log( 'warn', 'hits/interval/response: ' + this.checkHits() +'/' + this.ajax.get( 'time' ) +'/', response.success ? response.data : response );

			if ( 'check' === formData ) {
				this.ajax.set( 'checkHits', this.checkHits() + 1 );
			} else if ( response.data ) {
				this.ajax.set( 'checkHits', 0 );
			}

			if ( ! response.success ) {
				this.renderProgressUpdate( 0 );
				this.cancelSync();
				if ( response.data ) {
					return window.alert( response.data );
				}
			}
		},

		renderProgressUpdate: function( percent ) {
			this.$( '.gc-progress-bar-partial' )
				.css({ width: percent + '%' })
				.find( 'span' ).text( percent + '%' );
		},

		renderProgress: function( percent ) {
			this.$wrap.addClass( 'gc-sync-progress' );
			this.buttonStatus( false );
			this.$el.html( this.progressTemplate( { percent: percent } ) );
		},

		getRenderedRows: function() {
			var rows;

			if ( this.collection.current().length ) {
				rows = this.getRenderedModels( app.views.item, this.collection.current() ) ;
			} else {
				rows = this.blankRow( gc._text.no_items );
			}

			return rows;
		},

		blankRow: function( html ) {
			var cols = this.$( 'thead tr > *' ).length;
			return '<tr><td colspan="'+ cols +'">'+ html +'</td></tr>';
		},

		renderRows: function( html ) {
			this.$el.find( 'tbody' ).html( html || this.getRenderedRows() );
		},

		renderNav: function() {
			// Re-render table nav
			this.$el.find( '.tablenav.top' ).html( this.tableNavView.render().el );
		},

		initRender: function() {
			var collection = this.collection.current();
			// If sync is going, show that status.
			if ( percent > 0 && percent < 100 ) {
				this.startSync( 'check' );
			} else {
				this.$el.html( this.template( {
					checked       : collection.allChecked,
					sortKey       : collection.sortKey,
					sortDirection : collection.sortDirection,
				} ) );
				this.render();
			}
		},

		render: function() {
			var collection = this.collection.current();

			// Not syncing, so remove wrap-class
			this.$wrap.removeClass( 'gc-sync-progress' );

			// Re-render and replace table rows.
			this.renderRows();

			// Re-render table nav
			this.renderNav();

			// Make sync button enabled/disabled
			this.buttonStatus( collection.syncEnabled );

			// Make check-all inputs checked/unchecked
			this.allCheckedStatus( collection.allChecked );

			return this;
		},

	});
};
