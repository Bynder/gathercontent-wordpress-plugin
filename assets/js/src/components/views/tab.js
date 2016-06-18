module.exports = function( app ) {
	return app.views.base.extend({
		template : wp.template( 'gc-tab-wrapper' ),

		tagName : 'fieldset',

		id : function() {
			return this.model.get( 'id' );
		},

		className : function() {
			return 'gc-template-tab ' + ( this.model.get( 'hidden' ) ? 'hidden' : '' );
		},

		render : function() {
			this.$el.html( this.template( this.model.toJSON() ) );

			var addedElements = document.createDocumentFragment();
			this.model.rows.each( function( model ) {
				var view = ( new app.views.tabRow({ model: model }) ).render();
				// console.log('view.$el', view.$el);
				addedElements.appendChild( view.el );
			});

			this.$el.find( 'tbody' ).html( addedElements );

			return this;
		}
	});
};
