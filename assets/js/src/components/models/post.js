module.exports = function( app, gc ) {
	return app.models.base.extend({
		defaults: {
			id              : 0,
			item            : 0,
			itemName        : 0,
			mapping         : 0,
			mappingLink     : '',
			mappingStatus   : '',
			status          : {},
			checked         : false,
			disabled        : false,
			statuses        : [],
			statusesChecked : false,
			statusSetting   : {},
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
			return this._get( app.models.base.prototype.get.call( this, attribute ), attribute );
		},

		// hijack the toJSON method and overwrite the data that is sent back to the view.
		toJSON: function() {
			return _.mapObject( app.models.base.prototype.toJSON.call( this ), _.bind( this._get, this ) );
		}

	});
};
