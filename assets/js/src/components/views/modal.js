
 module.exports = function( app, gc, $ ) {
 	app.modalView = undefined;

 	var thisView;
	/**
	 * Taken from https://github.com/aut0poietic/wp-admin-modal-example
	 */
	return app.views.base.extend({
 		id: 'gc-bb-modal-dialog',
 		template : wp.template( 'gc-modal-window' ),

 		navSeparator: '<li class="separator">&nbsp;</li>',

 		backdrop: '<div class="gc-bb-modal-backdrop">&nbsp;</div>',
 		selected: [],
 		navItems: {},
 		btns: {},
 		currID: '',
 		currNav: false,
		timeoutID : null,
		ajax : null,

 		events: {
			'click .gc-bb-modal-close'               : 'closeModal',
			'click #btn-cancel'                      : 'closeModal',
			'click #gc-btn-ok'                       : 'saveModal',
			'click .gc-bb-modal-nav-tabs a'          : 'clickSelectTab',
			'change .gc-field-th.check-column input' : 'checkAll',
			'click #gc-btn-pull'                     : 'startPull',
			'click #gc-btn-push'                     : 'startPush'
 		},

		/**
 		 * Instantiates the Template object and triggers load.
 		 */
 		initialize: function () {
 			thisView = this;

 			_.bindAll( this, 'render', 'preserveFocus', 'closeModal', 'saveModal' );

 			this.setupAjax();

 			this.navItems = new app.collections.navItems( gc._nav_items );
 			this.btns = new app.collections.base( gc._modal_btns );
 			this.currNav = this.navItems.getActive();

 			this.listenTo( this.navItems, 'render', this.render );
 			this.listenTo( this.collection, 'render', this.render );
 			this.listenTo( this.collection, 'enabledChange', this.checkEnableButton );
 			this.listenTo( this.collection, 'notAllChecked', this.allCheckedStatus );
 			this.listenTo( this.collection, 'updateItems', this.render );
 		},

 		setupAjax: function() {
 			var Ajax = require( './../models/ajax.js' )( app, {
				action      : 'gc_pull_items',
				nonce       : gc._edit_nonce,
				flush_cache : gc.queryargs.flush_cache ? 1 : 0,
 			} );

 			this.ajax = new Ajax();
 		},

 		/**
 		 * Assembles the UI from loaded templates.
 		 * @internal Obviously, if the templates fail to load, our modal never launches.
 		 */
 		render: function () {

 			// Build the base window and backdrop, attaching them to the $el.
 			// Setting the tab index allows us to capture focus and redirect it in Application.preserveFocus
 			this.$el.attr( 'tabindex', '0' )
 				.html( this.template( {
						btns     : this.btns.toJSON(),
						navItems : this.navItems.toJSON(),
						currID   : this.currNav ? this.currNav.get( 'id' ) : ''
				} ) )
 				.append( this.backdrop );

			this.$el.find( 'tbody' ).html( this.getRenderedSelected() );

			// Make sync button enabled/disabled
			this.buttonStatus( this.collection.syncEnabled );

			// Make check-all inputs checked/unchecked
			this.allCheckedStatus( this.collection.allChecked );

 			// Handle any attempt to move focus out of the modal.
 			$( document ).on( 'focusin', this.preserveFocus );

 			// set overflow to "hidden" on the body so that it ignores any scroll events
 			// while the modal is active and append the modal to the body.
 			$( document.body ).addClass( 'gc-modal-open' ).append( this.$el );

 			// Set focus on the modal to prevent accidental actions in the underlying page
 			// Not strictly necessary, but nice to do.
 			this.$el.focus();
 		},

 		getSelected: function() {
 			return this.collection.filter( function( model ) {
 				return -1 !== _.indexOf( thisView.selected, model.get( 'id' ) ) && ! model.get( 'disabled' );
 			} );
 		},

 		getRenderedSelected: function() {
 			var selected = this.getSelected();

 			var addedElements = document.createDocumentFragment();

 			_.each( selected, function( model ) {
 				var view = ( new app.views.modalPostRow({ model: model }) ).render();
 				addedElements.appendChild( view.el );
 			});

 			return addedElements;
 		},

 		/**
 		 * Ensures that keyboard focus remains within the Modal dialog.
 		 * @param evt {object} A jQuery-normalized event object.
 		 */
 		preserveFocus: function ( evt ) {
 			if ( this.$el[0] !== evt.target && ! this.$el.has( evt.target ).length ) {
 				this.$el.focus();
 			}
 		},

 		/**
 		 * Closes the modal and cleans up after the instance.
 		 * @param evt {object} A jQuery-normalized event object.
 		 */
 		closeModal: function ( evt ) {
 			evt.preventDefault();
 			this.undelegateEvents();
 			$( document ).off( 'focusin' );
 			$( document.body ).removeClass( 'gc-modal-open' );
 			this.remove();

 			gc.$id( 'bulk-edit' ).find( 'button.cancel' ).trigger( 'click' );
 			app.modalView = undefined;
 		},

 		/**
 		 * Responds to the gc-btn-ok.click event
 		 * @param evt {object} A jQuery-normalized event object.
 		 * @todo You should make this your own.
 		 */
 		saveModal: function ( evt ) {
 			this.closeModal( evt );
 		},

 		clickSelectTab: function( evt ) {
 			evt.preventDefault();

 			this.selectTab( $( evt.target ).data( 'id' ) );
 		},

 		selectTab: function( id ) {
 			this.currID = id;
 			this.currNav = this.navItems.getById( id );
 			this.navItems.trigger( 'activate', id );
 		},

		checkEnableButton: function( btnEnabled ) {
			this.buttonStatus( btnEnabled );
		},

		buttonStatus: function( enable ) {
			this.$( '.media-toolbar button' ).prop( 'disabled', ! enable );
		},

		allCheckedStatus: function( checked ) {
			this.$( '.gc-field-th.check-column input' ).prop( 'checked', checked );
		},

		checkAll: function( evt ) {
			this.collection.trigger( 'checkAll', $( evt.target ).is( ':checked' ) );
		},

		startPull: function( evt ) {
			evt.preventDefault();
			var selected = this.modelsToJSON(
				this.setSelectedMappingStatus( 'starting' )
			);

			this.doAjax( selected, 'pull' );
		},

		startPush: function( evt ) {
			evt.preventDefault();

			var selected = this.modelsToJSON(
				this.setSelectedMappingStatus( 'starting' )
			);

			this.doAjax( selected, 'push' );
		},

		getSelectedAndChecked: function() {
			var checked = _.filter( this.getSelected(), function( model ) {
				return model.get( 'checked' );
			});

			return checked;
		},

		modelsToJSON: function( models ) {
			return _.map( models, function( model ) {
				return model.toJSON();
			} );
		},

		ajaxSuccess: function( response ) {
			if ( ! response.data.mappings ) {
				return this.ajaxFail();
			}

			var mappings = [];
			_.each( this.getSelectedAndChecked(), function( model ) {
				if ( response.data.mappings.length && -1 !== _.indexOf( response.data.mappings, model.get( 'mapping' ) ) ) {
					model.set( 'mappingStatus', 'syncing' );
					mappings.push( model.get( 'mapping' ) );
				} else {
					model.set( 'checked', false );
					model.set( 'mappingStatus', 'complete' );
					model.fetch().done( function() {
						model.trigger( 'render' );
					} );
				}
			} );

			if ( ! mappings.length ) {
				return this.clearTimeout();
			}

			this.checkStatus( mappings, response.data.direction );
		},

		ajaxFail: function( response ) {
			this.setSelectedMappingStatus( 'failed' );
			this.clearTimeout();
		},

		setSelectedMappingStatus: function( status ) {
			var selectedChecked = this.getSelectedAndChecked();
			_.each( selectedChecked, function( model ) {
				model.set( 'mappingStatus', status );
			} );

			return selectedChecked;
		},

		checkStatus: function( mappings, direction ) {
			this.clearTimeout();
			this.timeoutID = window.setTimeout( function() {
				thisView.doAjax( { check : mappings }, direction );
			}, 1000 );
		},

		clearTimeout: function() {
			window.clearTimeout( this.timeoutID );
			this.timeoutID = null;
		},

		doAjax: function( formData, direction ) {
			this.ajax.set( 'action', 'gc_'+ direction +'_items' );

			this.ajax.send(
				formData,
				this.ajaxSuccess.bind( this ),
				0,
				this.ajaxFail.bind( this )
			);
		},

 	});
};
