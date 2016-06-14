module.exports = function( args ) {
	return args.collectionBase.extend({
		model : args.model,

		initialize: function( models, options ) {
			this.tab = options.tab;
		},

		getById : function( id ) {
			return this.find( function( model ) {
				return model.get( 'id' ) === id;
			} );
		},

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
