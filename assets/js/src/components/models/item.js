module.exports = function( app ) {
	return app.models.base.extend({
		defaults: {
			id              : 0,
			item            : 0,
			project_id      : 0,
			parent_id       : 0,
			template_id     : 0,
			custom_state_id : 0,
			position        : 0,
			name            : '',
			config          : '',
			notes           : '',
			type            : '',
			overdue         : false,
			archived_by     : '',
			archived_at     : '',
			created_at      : null,
			updated_at      : null,
			status          : null,
			due_dates       : null,
			expanded        : false,
			checked         : false,
		},
		_get : function( value, attribute ) {
			switch ( attribute ) {
				case 'item':
					value = this.get( 'id' );
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
