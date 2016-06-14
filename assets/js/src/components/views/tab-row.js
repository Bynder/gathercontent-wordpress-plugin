module.exports = function( args ) {
	return args.viewBase.extend({
		tagName : 'tr',
		template : wp.template( 'gc-mapping-tab-row' ),

		events : {
			'change .wp-type-select'       : 'changeType',
			'change .wp-type-value-select' : 'changeValue',
			'click  .gc-reveal-items'      : 'toggleExpanded'
		},

		initialize: function() {
			this.listenTo( this.model, 'change:field_type', this.render );
		},

		changeType: function( evt ) {
			var value = jQuery( evt.target ).val();
			this.model.set( 'field_type', value );
		},

		changeValue: function( evt ) {
			var value = jQuery( evt.target ).val();
			if ( '' === value ) {
				this.model.set( 'field_value', '' );
				this.model.set( 'field_type', '' );
			} else {
				this.model.set( 'field_value', value );
			}
		},

		toggleExpanded: function( evt ) {
			this.model.set( 'expanded', ! this.model.get( 'expanded' ) );
		},

		render : function() {
			this.$el.html( this.template( this.model.toJSON() ) );
			this.$( '.gc-select2' ).select2({
				width: '250px'
			});
			return this;
		}

	});
};
