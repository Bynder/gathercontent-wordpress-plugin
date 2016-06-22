module.exports = function( app ) {
	return app.views.tab.extend({
		events : {
			'change select'          : 'changeDefault',
			'click .gc-reveal-items' : 'toggleExpanded'
		},

		defaultTabTemplate  : wp.template( 'gc-mapping-defaults-tab' ),
		select2ItemTemplate : wp.template( 'gc-select2-item' ),

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

			var that = this;
			this.$( '.gc-select2' ).each( function() {
				var $this = jQuery( this );
				var data = $this.data();
				$this.select2( that.select2Args( data ) );
				var s2Data = $this.data( 'select2' );

				// Add classes for styling.
				s2Data.$results.addClass( 'select2-'+ data.column );
				s2Data.$container.addClass( 'select2-'+ data.column );
			} );

			return this;
		},

		select2Args: function( data ) {
			var args = {
				width: '250px'
			};

			switch ( data.column ) {
				case 'gc_status':

					args.templateResult = function( status, showDesc ) {
						var data = jQuery.extend( status, jQuery( status.element ).data() );
						data.description = false === showDesc ? false : ( data.description || '' );
						return jQuery( this.select2ItemTemplate( status ) );
					}.bind( this );

					args.templateSelection = function( status ) {
						return args.templateResult( status, false );
					};

					break;

				case 'post_author':

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
