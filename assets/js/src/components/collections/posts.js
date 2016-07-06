module.exports = function( app ) {
	var items = require( './../collections/items.js' )( app );
	return items.extend({
		model : app.models.post,

		initialize: function() {
			items.prototype.initialize.call( this );

			this.listenTo( this, 'updateItems', this.updateItems );
		},

		updateItems: function( data ) {
			this.each( function( model ) {
				var id = model.get( 'id' );
				if ( id in data ) {
					if ( data[ id ].status ) {
						model.set( 'status', data[ id ].status );
					}
					if ( data[ id ].itemName ) {
						model.set( 'itemName', data[ id ].itemName );
					}
				}
			} );
		}
	});
};
