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
				var url = window.ajaxurl + '?action=gc_get_option_data';
				// console.log('column',column);

				$this.select2({
					width: '250px',
					ajax: {
						url: url,
						data: function ( params ) {
							return {
								q: params.term,
								column: column
							};
						},
						delay: 250,
						cache: true
					},
					minimumInputLength: 2,
				});

			} );

			return this;
		}
	});
};
