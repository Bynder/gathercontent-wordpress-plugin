/**
 * GatherContent Importer - v3.0.0 - 2016-06-23
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
		totalChecked: 0,
		allChecked: false,
		syncEnabled: false,

		initialize: function initialize() {
			this.listenTo(this, 'checkAll', this.toggleChecked);
			this.listenTo(this, 'change:checked', this.checkChecked);
		},

		checkChecked: function checkChecked(model) {
			var render = false;

			if (model.changed.checked) {
				this.totalChecked++;
			} else {
				if (this.totalChecked === this.length) {
					this.allChecked = false;
					render = true;
				}
				this.totalChecked--;
			}

			var syncWasEnabled = this.syncEnabled;
			this.syncEnabled = this.totalChecked > 0;

			if (syncWasEnabled !== this.syncEnabled) {
				this.trigger('enabledChange', this.syncEnabled);
			}

			if (this.totalChecked < this.length) {
				this.trigger('notAllChecked', false);
			}
		},

		toggleChecked: function toggleChecked(checked) {
			this.allChecked = checked;
			this.each(function (model) {
				model.set('checked', checked ? true : false);
			});
			this.trigger('render');
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

},{"./collections/base.js":1,"./models/base.js":5,"./views/base.js":8}],4:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var log = gc.log;

	return app.models.base.extend({
		defaults: {
			action: 'gc_sync_items',
			data: '',
			percent: 0,
			nonce: '',
			id: '',
			stopSync: true
		},

		initialize: function initialize() {
			this.defaults.nonce = gc.el('_wpnonce').value;
			this.defaults.id = gc.el('gc-input-mapping_id').value;
			this.set('nonce', this.defaults.nonce);
			this.set('id', this.defaults.id);

			this.listenTo(this, 'send', this.send);
		},

		reset: function reset() {
			this.clear().set(this.defaults);
			return this;
		},

		send: function send(formData, cb, percent, failcb) {
			if (percent) {
				this.set('percent', percent);
			}

			$.post(window.ajaxurl, {
				action: this.get('action'),
				percent: this.get('percent'),
				nonce: this.get('nonce'),
				id: this.get('id'),
				data: formData
			}, (function (response) {
				this.trigger('response', response, formData);

				if (response.success) {
					return cb(response);
				}

				if (failcb) {
					return failcb(response);
				}
			}).bind(this));

			return this;
		}

	});
};

},{}],5:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],6:[function(require,module,exports){
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

},{}],7:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.sync = gc.sync || {};
	var app = gc.sync;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	app.ajax = require('./models/ajax.js')(app, $, gc);

	/*
  * Item setup
  */

	app.models.item = require('./models/item.js')(app);
	app.collections.items = require('./collections/items.js')(app);
	app.views.item = require('./views/item.js')(app);
	app.views.items = require('./views/items.js')(app, $, gc);

	app.init = function () {
		// Kick it off.
		app.syncView = new app.views.items({
			collection: new app.collections.items(gc._items)
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/items.js":2,"./initiate-objects.js":3,"./models/ajax.js":4,"./models/item.js":6,"./views/item.js":9,"./views/items.js":10}],8:[function(require,module,exports){
'use strict';

module.exports = Backbone.View.extend({
	toggleExpanded: function toggleExpanded(evt) {
		this.model.set('expanded', !this.model.get('expanded'));
	},

	getRenderedModels: function getRenderedModels(View, models) {
		models = models || this.collection;
		var addedElements = document.createDocumentFragment();

		models.each(function (model) {
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

},{}],9:[function(require,module,exports){
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

},{}],10:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var percent = gc.percent;
	var log = gc.log;
	var thisView;
	var masterCheckSelector = '.gc-field-th.check-column input';

	return app.views.base.extend({
		el: '#sync-tabs',
		template: wp.template('gc-items-sync'),
		progressTemplate: wp.template('gc-items-sync-progress'),
		spinnerRow: '<tr><td colspan="3"><span class="gc-loader spinner is-active"></span></td></tr>',
		$wrap: $('.gc-admin-wrap'),
		intervalID: null,

		events: function events() {
			var evts = {
				'click .gc-cancel-sync': 'clickCancelSync'
			};
			evts['change ' + masterCheckSelector] = 'checkAll';

			return evts;
		},

		initialize: function initialize() {
			thisView = this;

			app.ajax.prototype.defaults.checkHits = 0;
			app.ajax.prototype.defaults.time = 500;

			this.ajax = new app.ajax({
				percent: percent
			});

			this.listenTo(this.ajax, 'response', this.ajaxResponse);
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this.collection, 'enabledChange', this.checkEnableButton);
			this.listenTo(this.collection, 'notAllChecked', this.allCheckedStatus);
			this.listenTo(this, 'render', this.render);

			this.$wrap.on('submit', 'form', this.submit.bind(this));

			this.initRender();
		},

		checkEnableButton: function checkEnableButton(syncEnabled) {
			this.buttonStatus(syncEnabled);
		},

		buttonStatus: function buttonStatus(enable) {
			this.$wrap.find('.button-primary').prop('disabled', !enable);
		},

		allCheckedStatus: function allCheckedStatus(checked) {
			this.$wrap.find(masterCheckSelector).prop('checked', checked);
		},

		checkAll: function checkAll(evt) {
			this.collection.trigger('checkAll', $(evt.target).is(':checked'));
		},

		clickCancelSync: function clickCancelSync(evt) {
			evt.preventDefault();
			this.cancelSync();
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
			this.ajax.reset().set('stopSync', false);
			this.renderProgress(percent);
			this.doAjax(formData, percent);
		},

		cancelSync: function cancelSync(url) {
			console.warn('cancelSync');
			percent = null;

			this.ajax.reset();
			this.clearInterval();

			if (url) {
				this.doAjax('cancel', 0, function () {
					window.location.href = url;
				});
			} else {
				this.doAjax('cancel', 0, function () {});
				this.initRender();
			}
		},

		doAjax: function doAjax(formData, completed, cb) {
			cb = cb || this.ajaxSuccess.bind(this);
			this.ajax.send(formData, cb, completed);
		},

		ajaxSuccess: function ajaxSuccess(response) {
			if (this.ajax.get('stopSync')) {
				return;
			}

			percent = response.data.percent || 1;
			var hits = this.checkHits();
			var time = this.ajax.get('time');

			if (hits > 25 && time < 2000) {
				this.clearInterval();
				this.ajax.set('time', 2000);
			} else if (hits > 50 && time < 5000) {
				this.clearInterval();
				this.ajax.set('time', 5000);
			}

			this.setInterval(this.checkProgress.bind(this));

			if (percent > 99) {
				this.cancelSync(window.location.href + '&updated=1');
			} else {
				this.renderProgressUpdate(percent);
			}
		},

		setInterval: function setInterval(callback) {
			this.intervalID = this.intervalID || window.setInterval(callback, this.ajax.get('time'));
		},

		clearInterval: function clearInterval() {
			window.clearInterval(this.intervalID);
			this.intervalID = null;
		},

		checkProgress: function checkProgress() {
			console.log('checkProgress ' + this.checkHits() + ' ' + this.ajax.get('time'));
			this.doAjax('check', percent);
		},

		checkHits: function checkHits() {
			return window.parseInt(this.ajax.get('checkHits'), 10);
		},

		ajaxResponse: function ajaxResponse(response, formData) {
			log('warn', 'response', response);

			if ('check' === formData) {
				this.ajax.set('checkHits', this.checkHits() + 1);
			} else {
				this.ajax.set('checkHits', 0);
			}

			if (!response.success) {
				this.renderProgressUpdate(0);
				if (response.data) {
					window.alert(response.data);
				}
				this.cancelSync();
			}
		},

		renderProgressUpdate: function renderProgressUpdate(percent) {
			this.$('.gc-progress-bar-partial').css({ width: percent + '%' }).find('span').text(percent + '%');
		},

		renderProgress: function renderProgress(percent) {
			this.$wrap.addClass('sync-progress');
			this.buttonStatus(false);
			this.$el.html(this.progressTemplate({ percent: percent }));
		},

		initRender: function initRender() {
			// If sync is going, show that status.
			if (percent > 0 && percent < 100) {
				this.startSync('check');
			} else {
				this.$el.html(this.template({ checked: this.collection.allChecked }));
				this.render();
			}
		},

		render: function render() {
			// Not syncing, so remove wrap-class
			this.$wrap.removeClass('sync-progress');

			// Re-render and replace table rows.
			this.$el.find('tbody').html(this.getRenderedModels(app.views.item));

			// Make sync button enabled/disabled
			this.buttonStatus(this.collection.syncEnabled);

			// Make check-all inputs checked/unchecked
			this.allCheckedStatus(this.collection.allChecked);

			return this;
		}
	});
};

},{}]},{},[7]);
