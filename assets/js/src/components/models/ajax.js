module.exports = function( app, $, gc ) {
	var log = gc.log;

	return app.models.base.extend({
		defaults: {
			action    : 'gc_sync_items',
			data      : '',
			percent   : 0,
			nonce     : '',
			id        : '',
			stopSync  : true,
		},

		initialize: function() {
			this.defaults.nonce = gc.el( '_wpnonce' ).value;
			this.defaults.id    = gc.el( 'gc-input-mapping_id' ).value;
			this.set( 'nonce', this.defaults.nonce );
			this.set( 'id', this.defaults.id );

			this.listenTo( this, 'send', this.send );
		},

		reset: function() {
			this.clear().set( this.defaults );
			return this;
		},

		send: function( formData, cb, percent, failcb ) {
			if ( percent ) {
				this.set( 'percent', percent );
			}

			$.post(
				window.ajaxurl,
				{
					action      : this.get( 'action' ),
					percent     : this.get( 'percent' ),
					nonce       : this.get( 'nonce' ),
					id          : this.get( 'id' ),
					data        : formData,
					flush_cache : !! gc.queryargs.flush_cache
				},
				function( response ) {
					this.trigger( 'response', response, formData );

					if ( response.success ) {
						return cb( response );
					}

					if ( failcb ) {
						return failcb( response );
					}
				}.bind( this )
			);

			return this;
		},

	});
};
