module.exports = function( app, _meta_keys ) {
	return app.views.base.extend({
		tagName : 'tr',
		template : wp.template( 'gc-mapping-tab-row' ),

		events : {
			'change .wp-type-select'       : 'changeType',
			'change .wp-type-value-select' : 'changeValue',
			'click  .gc-reveal-items'      : 'toggleExpanded'
		},

		initialize: function() {
			this.listenTo( this.model, 'change:field_type', this.render );

			// Initiate the metaKeys collection.
			this.metaKeys = new ( app.collections.base.extend( {
				model : app.models.base.extend( { defaults: {
					value : ''
				} } ),
				getByValue : function( value ) {
					return this.find( function( model ) {
						return model.get( 'value' ) === value;
					} );
				},
			} ) )( _meta_keys );
		},

		changeType: function( evt ) {
			this.model.set( 'field_type', jQuery( evt.target ).val() );
		},

		changeValue: function( evt ) {
			var value = jQuery( evt.target ).val();
			// console.log('value',value);
			if ( '' === value ) {
				this.model.set( 'field_value', '' );
				this.model.set( 'field_type', '' );
			} else {
				this.model.set( 'field_value', value );
			}
		},

		render : function() {
			var val = this.model.get( 'field_value' );

			if ( val && ! this.metaKeys.getByValue( val ) ) {
				this.metaKeys.add( { value : val } );
			}

			var json = this.model.toJSON();
			json.metaKeys = this.metaKeys.toJSON();

			this.$el.html( this.template( json ) );

			this.$( '.gc-select2' ).each( function() {
				var $this = jQuery( this );
				var args = {
					width: '250px'
				};

				if ( $this.hasClass( 'gc-select2-add-new' ) ) {
					args.tags = true;
				}

				$this.select2( args );
			} );

			return this;
		}

	});
};
