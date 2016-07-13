module.exports = function( app, $, gc ) {
	var thisView;
	return app.views.statusSelect2.extend({
		className       : 'misc-pub-section misc-pub-post-status',
		select2template : wp.template( 'gc-status-select2' ),
		template        : wp.template( 'gc-metabox-statuses' ),
		isOpen          : false,

		initialize: function() {
			thisView = this;
			this.listenTo( this, 'render', this.render );
			this.listenTo( this, 'statusesOpen', this.statusesOpen );
			this.listenTo( this, 'statusesClose', this.statusesClose );
		},

		statusesOpen: function() {
			this.isOpen = true;
			if ( ! this.model.get( 'statusesChecked' ) ) {
				this.asyncInit();
			}
			this.$( '.edit-gc-status' ).addClass( 'hidden' );
			this.$( '#gc-post-status-select' ).slideDown( 'fast'/*, function() {
				thisView.$( '#gc-set-status' ).focus();
			}*/ );
		},

		statusesClose: function() {
			this.isOpen = false;
			this.$( '.edit-gc-status' ).removeClass( 'hidden' );
			this.$( '#gc-post-status-select' ).slideUp( 'fast' );
		},

		asyncInit: function() {
			$.post( window.ajaxurl, {
				action      : 'gc_get_post_statuses',
				postId      : this.model.get( 'id' ),
				flush_cache : !! gc.queryargs.flush_cache
			}, this.ajaxResponse.bind( this ) ).done( function() {
				thisView.renderStatuses();
			} ).fail( function() {
				thisView.model.set( 'statusesChecked', false );
			});

			this.model.set( 'statusesChecked', true );
		},

		ajaxResponse : function( response ) {
			if ( ! response.data || ! response.success ) {
				this.model.set( 'statusesChecked', false );
				return;
			}

			this.model.set( 'statusesChecked', true );
			this.model.set( 'statuses', response.data.statuses );

			if ( this.model.get( 'statuses' ).length ) {
				thisView.$( '.gc-select2' ).each( function() {
					$( this ).select2( 'destroy' );
				} );

				thisView.renderStatuses();
			}

		},

		renderStatuses: function() {
			var postId = this.model.get( 'id' );
			this.$( '#gc-status-selec2' ).html( this.select2template( this.model.toJSON() ) );
			if ( this.model.get( 'statuses' ).length ) {
				this.renderSelect2( this.$el );
			}
		}

	});
};
