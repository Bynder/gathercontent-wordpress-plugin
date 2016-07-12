module.exports = function( app, $, gc ) {
	return app.views.base.extend({
		initial : gc._initial,
		el : '#mapping-tabs',

		template : wp.template( 'gc-tabs-wrapper' ),

		events : {
			'click .nav-tab'      : 'tabClick',
			'click .nav-tab-link' : 'triggerClick'
		},

		initialize: function() {
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this, 'render', this.render );
			this.listenTo( this, 'saveEnabled', this.enableSave );
			this.listenTo( this, 'saveDisabled', this.disableSave );

			if ( this.initial ) {

				// Listen for initialization
				this.listenTo( this.collection, 'change', this.maybeInitMapping );

				// 'initMapping' only fires when an un-saved mapping is first 'modified'.
				// It enables saving, viewing tabs, etc.
				this.listenTo( this, 'initMapping', this.initMapping );
			}

			this.defaultTab = this.collection.getById( 'mapping-defaults' );
			this.render();
		},

		maybeInitMapping: function( model ) {
			if ( 'post_type' in model.changed ) {
				this.trigger( 'initMapping' );
			}
		},

		initMapping: function() {
			this.initial = false;

			this.stopListening( this.collection, 'change', this.maybeInitMapping );
			this.stopListening( this, 'initMapping', this.initMapping );

			this.defaultTab.set( 'initial', this.initial );
			this.render();

			if ( gc._pointers.select_tab_how_to ) {
				this.pointer( '.gc-nav-tab-wrapper-bb', 'select_tab_how_to' );
				this.pointer( '#gc-status-mappings', 'map_status_how_to' );
			}

			this.trigger( 'saveEnabled' );
		},

		triggerClick: function( evt ) {
			evt.preventDefault();

			this.$( '.nav-tab[href="' + $( evt.target ).attr( 'href' ) + '"]' ).trigger( 'click' );
		},

		tabClick: function( evt ) {
			evt.preventDefault();
			this.setTab( $( evt.target ).attr( 'href' ).substring(1) );
			this.render();
		},

		setTab: function( id ) {
			this.$el.attr( 'class', id );
			this.collection.invoke( 'set', { 'hidden': true } );
			this.collection.getById( id ).set( 'hidden', false );
		},

		render: function() {
			this.$( '.gc-select2' ).each( function() {
				$( this ).select2( 'destroy' );
			} );

			this.$el.html( this.template() );

			// Add tab links
			this.renderNav();

			// Add tab content
			this.renderTabs();

			if ( this.initial ) {
				this.renderInitial();
			}

			return this;
		},

		renderNav: function() {
			var toAppend;

			if ( this.initial ) {
				this.setTab( this.defaultTab.get( 'id' ) );
				toAppend = (new app.views.tabLink({ model: this.defaultTab })).render().el;

			} else {
				toAppend = this.getRenderedModels( app.views.tabLink );
			}

			this.$el.find( '.nav-tab-wrapper' ).append( toAppend );
		},

		renderTabs: function() {
			var frag = document.createDocumentFragment();
			if ( this.initial ) {

				this.defaultTab.set( 'initial', this.initial );
				var view = new app.views.defaultTab({ model: this.defaultTab });
				frag.appendChild( view.render().el );

			} else {

				this.collection.each( function( model ) {
					var viewid = 'mapping-defaults' === model.get( 'id' ) ? 'defaultTab' : 'tab';
					var view = new app.views[ viewid ]({ model: model });

					frag.appendChild( view.render().el );
				});
			}

			this.$el.find( '.gc-template-tab-group' ).append( frag );
		},

		renderInitial: function() {
			// Show the "select post-type" pointer.
			this.pointer( '[data-column="post_type"]', 'select_type', {
				dismissable : false,
				position: {
					edge: 'bottom',
					align: 'left'
				}
			} );

			this.trigger( 'saveDisabled' );
		},

		enableSave: function() {
			// Enable save button.
			$( '.submit .button-primary' ).prop( 'disabled', false );
		},

		disableSave: function() {
			// Disable save button.
			$( '.submit .button-primary' ).prop( 'disabled', true );
		},

		pointer: function( selector, key, args ) {
			args = args || {};
			var defaults = {
				content: gc._pointers[ key ]
			};

			if ( false !== args.dismissable ) {
				defaults.close = function() {
					$.post( window.ajaxurl, {
						pointer: 'gc_' + key,
						action: 'dismiss-wp-pointer'
					} );
				};
			}

			if ( args.position ) {
				defaults.position = args.position;
			}

			this.$( selector ).pointer( defaults ).pointer( 'open' );
		}

	});
};
