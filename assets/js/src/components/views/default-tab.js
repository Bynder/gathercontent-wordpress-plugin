module.exports = function( app ) {
	return app.views.tab.extend({
		events : {
			'change select'          : 'changeDefault',
			'click .gc-reveal-items' : 'toggleExpanded'
		},

		changeDefault: function( evt ) {
			var $this = jQuery( evt.target );
			var value = $this.val();
			var column = $this.data( 'column' );

			if ( value ) {
				if ( $this.data( 'select2' ) ) {
					var data = $this.select2( 'data' )[0];
					if ( data.text ) {
						this.model.set( 'select2:'+ column +':'+ value, data.text );
					}
				}
				this.model.set( column, value );
			}
		},

		render : function() {
			var modelJSON = this.model.toJSON();

			this.$el.html( this.template( modelJSON ) );

			var template = wp.template( 'gc-mapping-defaults-tab' );
			this.$el.find( 'tbody' ).html( template( modelJSON ) );

			this.$( '.gc-select2' ).each( function() {
				var $this = jQuery( this );
				var data  = $this.data();

				$this.select2( {
					width: '250px',
					ajax: {
						url: data.url,
						data: function ( params ) {
							return {
								q: params.term,
								column: data.column
							};
						},
						delay: 250,
						cache: true
					},
					minimumInputLength: 2
				} );

			} );

			return this;
		}

	});
};
