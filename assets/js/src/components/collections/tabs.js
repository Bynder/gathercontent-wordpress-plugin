module.exports = function( args ) {
	return args.collectionBase.extend({
		model : args.model,

		// initialize: function() {
		// 	console.log('this (collection)', this);
		// },

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
