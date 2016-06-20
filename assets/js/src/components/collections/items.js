module.exports = function( app ) {
	return app.collections.base.extend({
		model : app.models.item,

		initialize: function() {
			this.listenTo( this, 'checkAll', this.toggleChecked );
		},

		toggleChecked: function( checked ) {
			this.each( function( model ) {
				model.set( 'checked', checked ? true : false );
			});
		}
	});
};
