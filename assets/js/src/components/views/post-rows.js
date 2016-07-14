module.exports = function( app, gc, $ ) {
	var thisView;
	return app.views.statusSelect2.extend({
		template : wp.template( 'gc-status-select2' ),

		el : '#posts-filter tbody',

		width: '200px',

		events : {
			'change .gc-select2' : 'storeStatus'
		},

		initialize: function() {
			thisView = this;
			this.listenTo( this, 'quickEdit', this.edit );
			this.render();

			// Trigger an un-cached update for the statuses
			$.post( window.ajaxurl, {
				action      : 'gc_get_items',
				posts       : gc._posts,
				flush_cache : gc.queryargs.flush_cache ? 1 : 0
			}, function( response ) {
				if ( response.success, response.data ) {
					thisView.collection.trigger( 'updateItems', response.data );
				}
			} );

		},

		storeStatus: function( evt ) {
			var $this = jQuery( evt.target );
			var val = $this.val();
			var model = this.collection.getById( $this.data( 'id' ) );

			model.set( 'setStatus', val );
		},

		edit: function( id, inlineEdit ) {
			// get the post ID
			var postId = 0;
			if ( 'object' === typeof( id ) ) {
				postId = parseInt( inlineEdit.getId( id ), 10 );
			}

			this.waitSpinner( postId );

			if ( ! postId ) {
				return;
			}

			var model = this.collection.getById( postId );

			if ( model.get( 'statusesChecked' ) ) {
				return this.renderStatuses( model );
			}

			$.post( window.ajaxurl, {
				action      : 'gc_get_post_statuses',
				postId      : postId,
				flush_cache : gc.queryargs.flush_cache ? 1 : 0
			}, this.ajaxResponse ).done( function() {
				thisView.renderStatuses( model );
			} );
		},

		ajaxResponse : function( response ) {
			if ( ! response.data ) {
				return;
			}

			var model = thisView.collection.getById( response.data.postId );
			if ( ! model ) {
				return;
			}

			model.set( 'statusesChecked', true );

			if ( response.success ) {
				model.set( 'statuses', response.data.statuses );

				if ( model.get( 'statuses' ).length ) {
					thisView.$( '.gc-select2' ).each( function() {
						$( this ).select2( 'destroy' );
					} );

					thisView.renderStatuses( model );
				}
			}

		},

		renderStatuses: function( model ) {
			var postId = model.get( 'id' );
			this.editSelect( postId ).html( this.template( model.toJSON() ) );
			if ( model.get( 'statuses' ).length ) {
				this.renderSelect2( gc.$id( 'edit-' + postId ) );
			}
		},

		waitSpinner: function( postId ) {
			this.editSelect( postId ).html( '<span class="spinner"></span>' );
		},

		editSelect: function( postId ) {
			return gc.$id( 'edit-' + postId ).find( '.inline-edit-group .gc-status-select2' );
		},

		render: function() {
			this.collection.each( function( model ) {
				( new app.views.postRow({ model: model }) ).render();
			} );
			return this;
		},
	});
};
