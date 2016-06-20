module.exports = Backbone.View.extend({
	toggleExpanded: function( evt ) {
		this.model.set( 'expanded', ! this.model.get( 'expanded' ) );
	},

	getRenderedItems : function( View, items ) {
		items = items || this.collection;
		var addedElements = document.createDocumentFragment();

		items.each( function( model ) {
			var view = ( new View({ model: model }) ).render();
			addedElements.appendChild( view.el );
		});

		return addedElements;
	},

	render : function() {
		this.$el.html( this.template( this.model.toJSON() ) );
		return this;
	}
});
