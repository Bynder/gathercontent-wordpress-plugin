module.exports = function( app ) {
	return app.views.tab.extend({
		events : {
			'change select'          : 'changeDefault',
			'click .gc-reveal-items' : 'toggleExpanded'
		},

		defaultTabTemplate  : wp.template( 'gc-mapping-defaults-tab' ),

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
			var json = this.model.toJSON();

			this.$el.html( this.template( json ) );

			this.$el.find( 'tbody' ).html( this.defaultTabTemplate( json ) );

			this.renderSelect2();

			return this;
		},

		select2Args: function( data ) {
			var args = {};

			switch ( data.column ) {
				case 'gc_status':
					args = app.views.statusSelect2.prototype.select2Args.call( this, data );
					break;

				case 'post_author':
					args.width = '250px';
					args.minimumInputLength = 2;
					args.ajax = {
						url: data.url,
						data: function ( params ) {
							return {
								q: params.term,
								column: data.column
							};
						},
						delay: 250,
						cache: true
					};

					break;
			}

			return args;
		},

	});
};
