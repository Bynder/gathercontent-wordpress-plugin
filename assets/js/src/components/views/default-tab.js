module.exports = function( args ) {
	return args.viewTab.extend({
		events : {
			'change select' : 'changeDefault'
		},

		changeDefault: function( evt ) {
			var $this = jQuery( evt.target );
			var value = $this.val();
			var column = $this.data( 'column' );
			this.model.set( column, value );
		},

		render : function() {
			var modelJSON = this.model.toJSON();

			this.$el.html( this.template( modelJSON ) );

			var template = wp.template( 'gc-mapping-defaults-tab' );
			this.$el.find( 'tbody' ).html( template( modelJSON ) );

			this.$( '.gc-select2' ).each( function() {
				var $this = jQuery( this );
				var column = $this.data( 'column' );
				console.log('column',column);
				$this.select2({
					width: '250px',
					ajax: {
						url: ajaxurl + '?action=gc_get_option_data_' + column
					}
				});

			} );

			return this;
		}
	});
};
