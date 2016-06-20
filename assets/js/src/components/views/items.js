module.exports = function( app, $, percent ) {
	var thisView;
	return app.views.base.extend({
		el : '#sync-tabs',
		template : wp.template( 'gc-items-sync' ),
		progressTemplate : wp.template( 'gc-items-sync-progress' ),
		spinnerRow : '<tr><td colspan="3"><span class="gc-loader spinner is-active"></span></td></tr>',

		$wrap : $( '.gc-admin-wrap' ),
		intervalID : null,
		hits : 0,
		time : 500,
		stopSync : false,

		events : {
			'change th.check-column input' : 'checkAll',
			'click .gc-cancel-sync' : 'clickCancelSync'
		},

		checkAll: function( evt ) {
			this.collection.trigger( 'checkAll', $( evt.target ).is( ':checked' ) );
		},

		clickCancelSync: function( evt ) {
			evt.preventDefault();
			this.cancelSync();
		},

		cancelSync: function( url ) {
			console.warn('cancelSync');
			percent = null;
			this.stopSync = true;
			this.hits = 0;
			this.time = 500;
			this.clearInterval();
			if ( url ) {
				window.location.href = url;
			} else {
				this.initRender();
			}
		},

		initialize: function() {
			thisView = this;
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this, 'render', this.render );

			this.$wrap.on( 'submit', 'form', this.submit.bind( this ) );

			this.initRender();
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
			this.stopSync = false;
			this.renderProgress( percent );
			this.ajaxPost( formData, percent );
		},

		ajaxPost: function( formData, completed ) {
			$.post(
				window.ajaxurl,
				{
					action: 'gc_sync_items',
					data: formData,
					percent: completed
				},
				this.ajaxResponse.bind( this )
			);
		},

		ajaxResponse: function( response ) {
			this.hits++;

			if ( this.stopSync ) {
				return;
			}

			if ( response.success ) {
				percent = response.data.percent || 1;

				if ( this.hits > 25 && this.time < 2000 ) {
					this.clearInterval();
					this.time = 2000;
				} else if ( this.hits > 50 && this.time < 5000 ) {
					this.clearInterval();
					this.time = 5000;
				}

				this.setInterval( this.checkProgress.bind( this ) );

				if ( percent > 99 ) {
					this.cancelSync( window.location.href + '&updated=1' );
				} else {
					this.renderProgressUpdate( percent );
				}
			}
		},

		setInterval: function( callback ) {
			this.intervalID = this.intervalID || window.setInterval( callback, this.time );
		},

		clearInterval: function() {
			window.clearInterval( this.intervalID );
			this.intervalID = null;
		},

		checkProgress: function() {
			console.log('checkProgress ' + this.hits +' ' + this.time);
			this.ajaxPost( 'check', percent );
		},

		renderProgressUpdate: function( percent ) {
			this.$( '.gc-progress-bar-partial' )
				.css({ width: percent + '%' })
				.find( 'span' ).text( percent + '%' );
		},

		renderProgress: function( percent ) {
			this.$wrap.addClass( 'sync-progress' );
			this.$wrap.find( '.button-primary' ).prop( 'disabled', true );
			this.$el.html( this.progressTemplate( { percent: percent } ) );
		},

		initRender: function() {
			if ( percent > 0 && percent < 100 ) {
				this.startSync( 'check' );
			} else {
				this.$wrap.removeClass( 'sync-progress' );
				this.$wrap.find( '.button-primary' ).prop( 'disabled', false );
				this.$el.html( this.template() );
				this.render();
			}
		},

		render: function() {

			var addedElements = this.getRenderedItems( app.views.item );
			this.$el.find( 'tbody' ).html( addedElements );

			return this;
		},
	});
};
