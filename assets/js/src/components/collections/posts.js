module.exports = function( app ) {
	var items = require( './../collections/items.js' )( app );
	return items.extend({
		model : app.models.post,

		initialize: function( models, options ) {
			items.prototype.initialize.call( this, models, options );

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
					if ( data[ id ].updated ) {
						model.set( 'updated', data[ id ].updated );
					}
				}
			} );
		},

		checkedCan: function( pushOrPull ) {
			switch( pushOrPull ) {
				case 'pull' :
					pushOrPull = 'canPull';
					break;
				case 'assign' :
					pushOrPull = 'disabled';
					break;
				// case 'push':
				default :
					pushOrPull = 'canPush';
					break;
			}

			var can = this.find( function( model ){
				return model.get( pushOrPull ) && model.get( 'checked' );
			} );

			return can;
		},

	});
};
