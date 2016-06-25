module.exports = function( app ) {
	return app.models.base.extend({
		defaults: {
			id         : '',
			label      : '',
			hidden     : false,
			navClasses : '',
			rows       : [],
		},

		initialize: function() {
			this.rows = new app.collections.tabRows( this.get( 'rows' ), { tab: this } );
		}
	});
};
