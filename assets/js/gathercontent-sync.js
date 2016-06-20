/**
 * GatherContent Importer - v3.0.0 - 2016-06-20
 * http://www.gathercontent.com
 *
 * Copyright (c) 2016 GatherContent
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

module.exports = Backbone.Collection.extend({
	getById: function getById(id) {
		return this.find(function (model) {
			return model.get('id') === id;
		});
	}
});

},{}],2:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.item,

		initialize: function initialize() {
			this.listenTo(this, 'checkAll', this.toggleChecked);
		},

		toggleChecked: function toggleChecked(checked) {
			this.each(function (model) {
				model.set('checked', checked ? true : false);
			});
		}
	});
};

},{}],3:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":4,"./views/base.js":7}],4:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],5:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.models.base.extend({
		defaults: {
			id: 0,
			project_id: 0,
			parent_id: 0,
			template_id: 0,
			custom_state_id: 0,
			position: 0,
			name: '',
			config: '',
			notes: '',
			type: '',
			overdue: false,
			archived_by: '',
			archived_at: '',
			created_at: null,
			updated_at: null,
			status: null,
			due_dates: null,
			expanded: false,
			checked: false
		}
	});
};

},{}],6:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.sync = gc.sync || {};
	var app = gc.sync;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
  * Item setup
  */

	app.models.item = require('./models/item.js')(app);
	app.collections.items = require('./collections/items.js')(app);
	app.views.item = require('./views/item.js')(app);
	app.views.items = require('./views/items.js')(app, $, gc.percent);

	app.init = function () {
		// Kick it off.
		app.syncView = new app.views.items({
			collection: new app.collections.items(gc._items)
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/items.js":2,"./initiate-objects.js":3,"./models/item.js":5,"./views/item.js":8,"./views/items.js":9}],7:[function(require,module,exports){
'use strict';

module.exports = Backbone.View.extend({
	toggleExpanded: function toggleExpanded(evt) {
		this.model.set('expanded', !this.model.get('expanded'));
	},

	getRenderedItems: function getRenderedItems(View, items) {
		items = items || this.collection;
		var addedElements = document.createDocumentFragment();

		items.each(function (model) {
			var view = new View({ model: model }).render();
			addedElements.appendChild(view.el);
		});

		return addedElements;
	},

	render: function render() {
		this.$el.html(this.template(this.model.toJSON()));
		return this;
	}
});

},{}],8:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		template: wp.template('gc-item'),
		tagName: 'tr',
		className: 'gc-item',
		id: function id() {
			return this.model.get('id');
		},

		events: {
			'change .check-column input': 'toggleCheck',
			'click .gc-reveal-items': 'toggleExpanded'
		},

		initialize: function initialize() {
			this.listenTo(this.model, 'change:checked', this.render);
		},

		toggleCheck: function toggleCheck() {
			this.model.set('checked', !this.model.get('checked'));
		},

		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		}
	});
};

},{}],9:[function(require,module,exports){
'use strict';

module.exports = function (app, $, percent) {
	var thisView;
	return app.views.base.extend({
		el: '#sync-tabs',
		template: wp.template('gc-items-sync'),
		progressTemplate: wp.template('gc-items-sync-progress'),
		spinnerRow: '<tr><td colspan="3"><span class="gc-loader spinner is-active"></span></td></tr>',

		$wrap: $('.gc-admin-wrap'),
		intervalID: null,
		hits: 0,
		time: 500,
		stopSync: false,

		events: {
			'change th.check-column input': 'checkAll',
			'click .gc-cancel-sync': 'clickCancelSync'
		},

		checkAll: function checkAll(evt) {
			this.collection.trigger('checkAll', $(evt.target).is(':checked'));
		},

		clickCancelSync: function clickCancelSync(evt) {
			evt.preventDefault();
			this.cancelSync();
		},

		cancelSync: function cancelSync(url) {
			console.warn('cancelSync');
			percent = null;
			this.stopSync = true;
			this.hits = 0;
			this.time = 500;
			this.clearInterval();
			if (url) {
				window.location.href = url;
			} else {
				this.initRender();
			}
		},

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this, 'render', this.render);

			this.$wrap.on('submit', 'form', this.submit.bind(this));

			this.initRender();
		},

		doSpinner: function doSpinner() {
			this.$el.find('tbody').html(this.spinnerRow);
		},

		submit: function submit(evt) {
			evt.preventDefault();
			this.startSync(this.$wrap.find('form').serialize());
		},

		startSync: function startSync(formData) {
			this.doSpinner();
			this.stopSync = false;
			this.renderProgress(percent);
			this.ajaxPost(formData, percent);
		},

		ajaxPost: function ajaxPost(formData, completed) {
			$.post(window.ajaxurl, {
				action: 'gc_sync_items',
				data: formData,
				percent: completed
			}, this.ajaxResponse.bind(this));
		},

		ajaxResponse: function ajaxResponse(response) {
			this.hits++;

			if (this.stopSync) {
				return;
			}

			if (response.success) {
				percent = response.data.percent || 1;

				if (this.hits > 25 && this.time < 2000) {
					this.clearInterval();
					this.time = 2000;
				} else if (this.hits > 50 && this.time < 5000) {
					this.clearInterval();
					this.time = 5000;
				}

				this.setInterval(this.checkProgress.bind(this));

				if (percent > 99) {
					this.cancelSync(window.location.href + '&updated=1');
				} else {
					this.renderProgressUpdate(percent);
				}
			}
		},

		setInterval: function setInterval(callback) {
			this.intervalID = this.intervalID || window.setInterval(callback, this.time);
		},

		clearInterval: function clearInterval() {
			window.clearInterval(this.intervalID);
			this.intervalID = null;
		},

		checkProgress: function checkProgress() {
			console.log('checkProgress ' + this.hits + ' ' + this.time);
			this.ajaxPost('check', percent);
		},

		renderProgressUpdate: function renderProgressUpdate(percent) {
			this.$('.gc-progress-bar-partial').css({ width: percent + '%' }).find('span').text(percent + '%');
		},

		renderProgress: function renderProgress(percent) {
			this.$wrap.addClass('sync-progress');
			this.$wrap.find('.button-primary').prop('disabled', true);
			this.$el.html(this.progressTemplate({ percent: percent }));
		},

		initRender: function initRender() {
			if (percent > 0 && percent < 100) {
				this.startSync('check');
			} else {
				this.$wrap.removeClass('sync-progress');
				this.$wrap.find('.button-primary').prop('disabled', false);
				this.$el.html(this.template());
				this.render();
			}
		},

		render: function render() {

			var addedElements = this.getRenderedItems(app.views.item);
			this.$el.find('tbody').html(addedElements);

			return this;
		}
	});
};

},{}]},{},[6]);
