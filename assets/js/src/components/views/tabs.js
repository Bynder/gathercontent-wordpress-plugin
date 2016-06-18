module.exports = function( app ) {
	return Backbone.View.extend({
		el : '#mapping-tabs',

		template : function() {
			return wp.template( 'gc-tabs-wrapper' );
		},

		initialize: function() {
			// this.listenTo( this.collection, 'change:post_status change:post_type change:post_author', this.changeDefault );
			// this.listenTo( this.collection, 'change:label', this.render );
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this, 'render', this.render );

			this.render();

			app.defaults = this.collection.getById( 'mapping-defaults' );
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
			this.appendViewItems( '.nav-tab-wrapper', 'linkViewId' );

			// Add tab content
			this.appendViewItems( '.gc-template-tab-group', 'viewId' );

			return this;
		},

		appendViewItems: function( appendSelector, viewIdId ) {
			var addedElements = document.createDocumentFragment();

			this.collection.each( function( model ) {
				var viewid = model.get( viewIdId );
				var view = new app.views[ viewid ]({ model: model });

				addedElements.appendChild( view.render().el );
			});

			this.$el.find( appendSelector ).append( addedElements );
		}

	});
};
