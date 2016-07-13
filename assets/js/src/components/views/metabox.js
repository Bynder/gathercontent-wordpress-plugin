module.exports = function( app, $, gc ) {
	var thisView;
	var StatusesView = require( './../views/metabox-statuses.js' )( app, $, gc );

	return app.views.base.extend({
		el : '#gc-related-data',
		template : wp.template( 'gc-metabox' ),
		statusesView : null,
		events : {
			'click .edit-gc-status'   : 'editStatus',
			'click .cancel-gc-status' : 'cancelEditStatus',
			'click .save-gc-status'   : 'saveStatus',
			'click #gc-pull'          : 'pull',
			'click #gc-push'          : 'push'
		},

		initialize: function() {
			thisView = this;
			this.listenTo( this.model, 'change:status', this.renderStatusView );
			this.render();
			this.$el.removeClass( 'no-js' );

			this.refreshData();
		},

		refreshData: function() {
			console.log('refreshData');
			// Trigger an un-cached update for the item data
			this.ajax( {
				action      : 'gc_get_items',
				posts       : [ thisView.model.toJSON() ],
			}, function( response ) {
				if ( response.success && response.data && ! thisView.statusesView.isOpen ) {
					this.updateModel( response.data );
				}
			} );
		},

		updateModel: function( data ) {
			var id = this.model.get( 'id' );
			if ( id in data ) {
				if ( data[ id ].status ) {
					this.model.set( 'status', data[ id ].status );
				}
				if ( data[ id ].itemName ) {
					this.model.set( 'itemName', data[ id ].itemName );
				}
				if ( data[ id ].updated ) {
					this.model.set( 'updated', data[ id ].updated );
				}
			}
		},

		editStatus: function( evt ) {
			evt.preventDefault();
			this.statusesView.trigger( 'statusesOpen' );
		},

		cancelEditStatus: function( evt ) {
			evt.preventDefault();
			this.statusesView.trigger( 'statusesClose' );
		},

		saveStatus: function() {
			var newStatusId = this.$( '.gc-default-mapping-select' ).val();
			var oldStatus = this.model.get( 'status' );
			var oldStatusId = oldStatus && oldStatus.id ? oldStatus.id : false;
			var newStatus, statuses, fail, success;

			if ( newStatusId === oldStatusId ) {
				return this.statusesView.trigger( 'statusesClose' );
			}

			statuses = this.model.get( 'statuses' );
			newStatus = _.find( statuses, function( status ) {
				return parseInt( newStatusId, 10 ) === parseInt( status.id, 10 );
			} );

			this.statusesView.trigger( 'statusesClose' );
			this.model.set( 'status', newStatus );

			fail = function() {
				thisView.model.set( 'status', oldStatus );
			};

			success = function( response ) {
				console.warn('set_gc_status response',response);
				if ( response.success ) {
					this.refreshData();
				} else {
					fail();
				}
			};

			this.ajax( {
				action : 'set_gc_status',
				status : newStatusId,
			}, success ).fail( fail );
		},

		pull: function() {
			this.doSync( 'pull' );
		},

		push: function() {
			this.doSync( 'push' );
		},

		doSync: function( direction ) {
			if ( ! window.confirm( gc._sure[ direction ] ) ) {
				return;
			}

			this.$( '.gc-publishing-action .spinner' ).addClass( 'is-active' );

			var fail = function( msg ) {
				msg = msg || gc._errors.unknown;
				window.alert( msg );
			};

			var success = function( response ) {
				console.warn('do '+ direction +' response', response);
				this.$( '.gc-publishing-action .spinner' ).removeClass( 'is-active' );

				if ( response.success ) {
					this.refreshData();
				} else {
					fail( response.data );
				}
			};

			this.ajax( {
				// action : 'gc_'+ direction +'_items',
				action : 'gc_do_' + direction,
			}, success ).fail( function() {
				fail();
			} );
		},

		ajax: function( args, successcb ) {
			return $.post( window.ajaxurl, $.extend( {
				action      : '',
				post        : this.model.toJSON(),
				nonce       : gc.$id( 'gc-edit-nonce' ).val(),
				flush_cache : !! gc.queryargs.flush_cache
			}, args ), successcb.bind( this ) );
		},

		initStatusView: function() {
			if ( this.statusesView ) {
				this.statusesView.close();
			}
			this.statusesView = new StatusesView( {
				model : this.model
			} );
		},

		render : function() {
			this.$el.html( this.template( this.model.toJSON() ) );

			// This needs to happen after rendering.
			this.initStatusView();
			this.$( '.misc-pub-section.gc-item-name' ).after( this.statusesView.render().el );

			return this;
		},

		renderStatusView: function() {
			this.statusesView.$el.replaceWith( this.statusesView.render().el );
		}


	});
};
