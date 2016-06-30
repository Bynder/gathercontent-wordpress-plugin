module.exports = function( app ) {
	return app.models.base.extend({
		defaults: {
			id              : 0,
			item            : 0,
			mapping         : 0,
			status          : {},
			statuses        : [],
			statusesChecked : false,
			statusSetting   : {},
		}
	});
};
