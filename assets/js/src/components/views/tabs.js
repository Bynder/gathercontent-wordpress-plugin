module.exports = function( app ) {
	return app.views.base.extend({
		el : '#mapping-tabs',

		template : wp.template( 'gc-tabs-wrapper' ),

		initialize: function() {
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this, 'render', this.render );

			this.defaultTab = this.collection.getById( 'mapping-defaults' );
			this.render();
		},

		events : {
			'click .nav-tab'      : 'tabClick',
			'click .nav-tab-link' : 'triggerClick'
		},

		triggerClick: function( evt ) {
			evt.preventDefault();

			this.$( '.nav-tab[href="' + jQuery( evt.target ).attr( 'href' ) + '"]' ).trigger( 'click' );
		},

		tabClick: function( evt ) {
			evt.preventDefault();

			var id = jQuery( evt.target ).attr( 'href' ).substring(1);

			this.$el.attr( 'class', id );
			this.collection.showTab( id );
		},

		render: function() {
			this.$( '.gc-select2' ).each( function() {
				jQuery( this ).select2( 'destroy' );
			} );

			this.$el.html( this.template() );

			// Add tab links
			this.$el.find( '.nav-tab-wrapper' ).append( this.getRenderedModels( app.views.tabLink ) );

			// Add tab content
			this.renderTabs();

			return this;
		},

		renderTabs: function() {
			var frag = document.createDocumentFragment();

			this.collection.each( function( model ) {
				var viewid = 'mapping-defaults' === model.get( 'id' ) ? 'defaultTab' : 'tab';
				var view = new app.views[ viewid ]({ model: model });

				frag.appendChild( view.render().el );
			});

			this.$el.find( '.gc-template-tab-group' ).append( frag );
		}

	});
};
