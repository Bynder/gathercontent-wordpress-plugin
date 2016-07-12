module.exports = function( gc ) {
	return Backbone.Model.extend({
		defaults: {
			id              : 0,
			item            : 0,
			itemName        : 0,
			updated         : '',
			editLink        : '',
			mapping         : 0,
			mappingName     : 0,
			mappingLink     : '',
			mappingStatus   : '',
			status          : {},
			checked         : false,
			disabled        : false,
			statuses        : [],
			statusesChecked : false,
			statusSetting   : {},
		},

		url: function() {
			return window.ajaxurl +'?action=gc_fetch_js_post&id='+ this.get( 'id' );
		},

		_get : function( value, attribute ) {
			switch ( attribute ) {
				case 'disabled':
					value = ! this.get( 'item' ) || ! this.get( 'mapping' );
					break;
				case 'mappingStatus':
					value = gc._statuses[ value ] ? gc._statuses[ value ] : '';
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
		}

	});
};
