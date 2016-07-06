module.exports = function( app ) {
	var item = require( './../views/item.js' )( app );
	return item.extend({
		template : wp.template( 'gc-modal-item' ),

		id : function() {
			return 'gc-modal-post-' + this.model.get( 'id' );
		},

		className : function() {
			return 'gc-item ' + ( this.model.get( 'disabled' ) ? 'gc-disabled' : '' );
		},

 		events: {
			'change .check-column input'         : 'toggleCheck',
			'click .gc-modal-item-wp-post-title' : 'toggleCheckAndRender',
 		},

 		toggleCheckAndRender: function( evt ) {
 			this.toggleCheck();
 			this.render();
 		},

		initialize: function () {
			this.listenTo( this.model, 'change:mappingStatus', this.render );
		}
	});
};
