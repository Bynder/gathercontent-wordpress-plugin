module.exports = function( app ) {
	return app.views.base.extend({
		template : wp.template( 'gc-post-column-row' ),
		tagName : 'span',
		className : 'gc-status-column',
		id : function() {
			return 'gc-status-row-' + this.model.get( 'id' );
		},

		html: function() {
			return this.template( this.model.toJSON() );
		}

	});
};
