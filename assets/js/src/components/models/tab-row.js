module.exports = function( app ) {
	return app.models.base.extend({
		defaults: {
			id          : '',
			label       : '',
			name        : '',
			field_type  : '',
			post_type   : 'wp-type-post',
			field_value : false,
			expanded    : false,
		},

		_get : function( value, attribute ) {

			switch ( attribute ) {
				case 'post_type':
					if ( app.defaults ) {
						value = app.defaults.get( 'post_type' );
					}
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
