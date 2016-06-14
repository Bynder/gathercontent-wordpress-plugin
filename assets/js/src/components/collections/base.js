module.exports = Backbone.Collection.extend({
	getById : function( id ) {
		return this.find( function( model ) {
			return model.get( 'id' ) === id;
		} );
	},
});
