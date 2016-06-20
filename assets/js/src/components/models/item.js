module.exports = function( app ) {
	return app.models.base.extend({
		defaults: {
			id              : 0,
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
		}
	});
};
