module.exports = function( app ) {
	return app.collections.base.extend({
		model : app.models.tab,

		showTab: function( id ) {
			var model = this.getById( id );

			if ( model ) {
				this.invoke( 'set', { 'hidden': true } );
				model.set( 'hidden', false );
				this.trigger( 'render' );
			}
		}

	});
};
