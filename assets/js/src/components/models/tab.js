module.exports = function( app ) {
	return app.models.base.extend({
		defaults: {
			id         : '',
			label      : '',
			hidden     : false,
			navClasses : '',
			viewId     : 'tab',
			linkViewId : 'tabLink',
			rows       : [],
		},

		initialize: function() {
			this.rows = new app.collections.tabRows( this.get( 'rows' ), { tab: this } );
			// this.rows.bind( 'change', this.change );
		}/*,

		_get : function( value, attribute ) {
			var action;

			switch ( attribute ) {
				case 'navClass':
					value = 'hide' === this.get( 'action' ) ? '' : 'nav-tab-active';
					break;

				case 'tabClass':
					value = 'hide' === this.get( 'action' ) ? 'hidden' : '';
					break;
			}

			return value;
		},

		get : function( attribute ) {
			return this._get( Backbone.Model.prototype.get.call( this, attribute ), attribute );
		},

		// hijack the toJSON method and overwrite the data that is sent back to the view.
		toJSON: function() {
			return _.mapObject( Backbone.Model.prototype.toJSON.call( this ), _.bind( this._get, this ) );
		}*/
	});
};
