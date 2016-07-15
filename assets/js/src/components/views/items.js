module.exports = function( app, $, gc ) {
	var percent = gc.percent;
	var thisView;
	var masterCheckSelector = '.gc-field-th.check-column input';

	return app.views.base.extend({
		el : '#sync-tabs',
		template : wp.template( 'gc-items-sync' ),
		progressTemplate : wp.template( 'gc-items-sync-progress' ),
		spinnerRow : '<tr><td colspan="6"><span class="gc-loader spinner is-active"></span></td></tr>',
		$wrap : $( '.gc-admin-wrap' ),
		timeoutID : null,
		ajax : null,

		events : function() {
			var evts = {
				'click .gc-cancel-sync' : 'clickCancelSync'
			};
			evts[ 'change '+ masterCheckSelector ] = 'checkAll';

			return evts;
		},

		initialize: function() {
			thisView = this;

			this.setupAjax();

			this.listenTo( this.ajax, 'response', this.ajaxResponse );
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this.collection, 'enabledChange', this.checkEnableButton );
			this.listenTo( this.collection, 'notAllChecked', this.allCheckedStatus );
			this.listenTo( this, 'render', this.render );

			this.$wrap.on( 'submit', 'form', this.submit.bind( this ) );

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

		checkEnableButton: function( syncEnabled ) {
			this.buttonStatus( syncEnabled );
		},

		buttonStatus: function( enable ) {
			this.$wrap.find( '.button-primary' ).prop( 'disabled', ! enable );
		},

		allCheckedStatus: function( checked ) {
			this.$wrap.find( masterCheckSelector ).prop( 'checked', checked );
		},

		checkAll: function( evt ) {
			this.collection.trigger( 'checkAll', $( evt.target ).is( ':checked' ) );
		},

		clickCancelSync: function( evt ) {
			evt.preventDefault();
			this.cancelSync();
		},

		doSpinner: function() {
			this.$el.find( 'tbody' ).html( this.spinnerRow );
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
				if ( response.data ) {
					window.alert( response.data );
				}
				this.cancelSync();
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

		initRender: function() {
			// If sync is going, show that status.
			if ( percent > 0 && percent < 100 ) {
				this.startSync( 'check' );
			} else {
				this.$el.html( this.template({ checked: this.collection.allChecked }) );
				this.render();
			}
		},

		render: function() {
			// Not syncing, so remove wrap-class
			this.$wrap.removeClass( 'gc-sync-progress' );

			// Re-render and replace table rows.
			this.$el.find( 'tbody' ).html( this.getRenderedModels( app.views.item ) );

			// Make sync button enabled/disabled
			this.buttonStatus( this.collection.syncEnabled );

			// Make check-all inputs checked/unchecked
			this.allCheckedStatus( this.collection.allChecked );

			return this;
		},
	});
};
