module.exports = Backbone.View.extend({
	toggleExpanded: function( evt ) {
		this.model.set( 'expanded', ! this.model.get( 'expanded' ) );
	},

	render : function() {
		this.$el.html( this.template( this.model.toJSON() ) );
		return this;
	}
});
