/**
 * GatherContent Importer - v3.0.0 - 2016-06-28
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
		model: app.models.tabRow,

		initialize: function initialize(models, options) {
			this.tab = options.tab;
		},

		getById: function getById(id) {
			return this.find(function (model) {
				return model.get('id') === id;
			});
		},

		showTab: function showTab(id) {
			var model = this.getById(id);

			if (model) {
				this.invoke('set', { 'hidden': true });
				model.set('hidden', false);
				this.trigger('render');
			}
		}

	});
};

},{}],3:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.tab,

		showTab: function showTab(id) {
			var model = this.getById(id);

			if (model) {
				this.invoke('set', { 'hidden': true });
				model.set('hidden', false);
				this.trigger('render');
			}
		}

	});
};

},{}],4:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":6,"./views/base.js":9}],5:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.mapping = gc.mapping || {};
	var app = gc.mapping;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
  * Tab Row setup
  */

	app.models.tabRow = require('./models/tab-row.js')(app);
	app.collections.tabRows = require('./collections/tab-rows.js')(app);
	app.views.tabRow = require('./views/tab-row.js')(app, gc._meta_keys);

	/*
  * Tab setup
  */

	app.models.tab = require('./models/tab.js')(app);
	app.collections.tabs = require('./collections/tabs.js')(app);
	app.views.tab = require('./views/tab.js')(app);

	app.views.tabLink = require('./views/tab-link.js')(app);

	app.views.defaultTab = require('./views/default-tab.js')(app);

	/*
  * Overall view setup
  */

	app.views.tabs = require('./views/tabs.js')(app);

	app.init = function () {
		// Kick it off.
		app.mappingView = new app.views.tabs({
			collection: new app.collections.tabs(gc._tabs)
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/tab-rows.js":2,"./collections/tabs.js":3,"./initiate-objects.js":4,"./models/tab-row.js":7,"./models/tab.js":8,"./views/default-tab.js":10,"./views/tab-link.js":11,"./views/tab-row.js":12,"./views/tab.js":13,"./views/tabs.js":14}],6:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],7:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.models.base.extend({
		defaults: {
			id: '',
			label: '',
			name: '',
			field_type: '',
			post_type: 'post',
			field_value: false,
			expanded: false
		},

		_get: function _get(value, attribute) {

			switch (attribute) {
				case 'post_type':
					if (app.mappingView) {
						value = app.mappingView.defaultTab.get('post_type');
					}
					break;
			}

			return value;
		},

		get: function get(attribute) {
			return this._get(app.models.base.prototype.get.call(this, attribute), attribute);
		},

		// hijack the toJSON method and overwrite the data that is sent back to the view.
		toJSON: function toJSON() {
			return _.mapObject(app.models.base.prototype.toJSON.call(this), _.bind(this._get, this));
		}

	});
};

},{}],8:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.models.base.extend({
		defaults: {
			id: '',
			label: '',
			hidden: false,
			navClasses: '',
			rows: []
		},

		initialize: function initialize() {
			this.rows = new app.collections.tabRows(this.get('rows'), { tab: this });
		}
	});
};

},{}],9:[function(require,module,exports){
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

},{}],10:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.tab.extend({
		events: {
			'change select': 'changeDefault',
			'click .gc-reveal-items': 'toggleExpanded'
		},

		defaultTabTemplate: wp.template('gc-mapping-defaults-tab'),
		select2ItemTemplate: wp.template('gc-select2-item'),

		changeDefault: function changeDefault(evt) {
			var $this = jQuery(evt.target);
			var value = $this.val();
			var column = $this.data('column');

			if (value) {
				if ($this.data('select2')) {
					var data = $this.select2('data')[0];
					if (data.text) {
						this.model.set('select2:' + column + ':' + value, data.text);
					}
				}
				this.model.set(column, value);
			}
		},

		render: function render() {
			var json = this.model.toJSON();

			this.$el.html(this.template(json));

			this.$el.find('tbody').html(this.defaultTabTemplate(json));

			var that = this;
			this.$('.gc-select2').each(function () {
				var $this = jQuery(this);
				var data = $this.data();
				$this.select2(that.select2Args(data));
				var s2Data = $this.data('select2');

				// Add classes for styling.
				s2Data.$results.addClass('select2-' + data.column);
				s2Data.$container.addClass('select2-' + data.column);
			});

			return this;
		},

		select2Args: function select2Args(_data) {
			var args = {
				width: '250px'
			};

			switch (_data.column) {
				case 'gc_status':

					args.templateResult = (function (status, showDesc) {
						var data = jQuery.extend(status, jQuery(status.element).data());
						data.description = false === showDesc ? false : data.description || '';
						return jQuery(this.select2ItemTemplate(status));
					}).bind(this);

					args.templateSelection = function (status) {
						return args.templateResult(status, false);
					};

					break;

				case 'post_author':

					args.minimumInputLength = 2;
					args.ajax = {
						url: _data.url,
						data: function data(params) {
							return {
								q: params.term,
								column: _data.column
							};
						},
						delay: 250,
						cache: true
					};

					break;
			}

			return args;
		}

	});
};

},{}],11:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		tagName: 'a',

		id: function id() {
			return 'tabtrigger-' + this.model.get('id');
		},

		className: function className() {
			return 'nav-tab ' + (this.model.get('hidden') ? '' : 'nav-tab-active') + ' ' + this.model.get('navClasses');
		},

		render: function render() {
			this.$el.text(this.model.get('label')).attr('href', '#' + this.model.get('id'));

			return this;
		}

	});
};

},{}],12:[function(require,module,exports){
'use strict';

module.exports = function (app, _meta_keys) {
	return app.views.base.extend({
		tagName: 'tr',
		template: wp.template('gc-mapping-tab-row'),

		events: {
			'change .wp-type-select': 'changeType',
			'change .wp-type-value-select': 'changeValue',
			'click  .gc-reveal-items': 'toggleExpanded'
		},

		initialize: function initialize() {
			this.listenTo(this.model, 'change:field_type', this.render);

			// Initiate the metaKeys collection.
			this.metaKeys = new (app.collections.base.extend({
				model: app.models.base.extend({ defaults: {
						value: ''
					} }),
				getByValue: function getByValue(value) {
					return this.find(function (model) {
						return model.get('value') === value;
					});
				}
			}))(_meta_keys);
		},

		changeType: function changeType(evt) {
			this.model.set('field_type', jQuery(evt.target).val());
		},

		changeValue: function changeValue(evt) {
			var value = jQuery(evt.target).val();
			// console.log('value',value);
			if ('' === value) {
				this.model.set('field_value', '');
				this.model.set('field_type', '');
			} else {
				this.model.set('field_value', value);
			}
		},

		render: function render() {
			var val = this.model.get('field_value');

			if (val && !this.metaKeys.getByValue(val)) {
				this.metaKeys.add({ value: val });
			}

			var json = this.model.toJSON();
			json.metaKeys = this.metaKeys.toJSON();

			this.$el.html(this.template(json));

			this.$('.gc-select2').each(function () {
				var $this = jQuery(this);
				var args = {
					width: '250px'
				};

				if ($this.hasClass('gc-select2-add-new')) {
					args.tags = true;
				}

				$this.select2(args);
			});

			return this;
		}

	});
};

},{}],13:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		template: wp.template('gc-tab-wrapper'),

		tagName: 'fieldset',

		id: function id() {
			return this.model.get('id');
		},

		className: function className() {
			return 'gc-template-tab ' + (this.model.get('hidden') ? 'hidden' : '');
		},

		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));

			var rendered = this.getRenderedModels(app.views.tabRow, this.model.rows);

			this.$el.find('tbody').html(rendered);

			return this;
		}
	});
};

},{}],14:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		el: '#mapping-tabs',

		template: wp.template('gc-tabs-wrapper'),

		initialize: function initialize() {
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this, 'render', this.render);

			this.defaultTab = this.collection.getById('mapping-defaults');
			this.render();
		},

		events: {
			'click .nav-tab': 'tabClick',
			'click .nav-tab-link': 'triggerClick'
		},

		triggerClick: function triggerClick(evt) {
			evt.preventDefault();

			this.$('.nav-tab[href="' + jQuery(evt.target).attr('href') + '"]').trigger('click');
		},

		tabClick: function tabClick(evt) {
			evt.preventDefault();

			var id = jQuery(evt.target).attr('href').substring(1);

			this.$el.attr('class', id);
			this.collection.showTab(id);
		},

		render: function render() {
			this.$('.gc-select2').each(function () {
				jQuery(this).select2('destroy');
			});

			this.$el.html(this.template());

			// Add tab links
			this.$el.find('.nav-tab-wrapper').append(this.getRenderedModels(app.views.tabLink));

			// Add tab content
			this.renderTabs();

			return this;
		},

		renderTabs: function renderTabs() {
			var frag = document.createDocumentFragment();

			this.collection.each(function (model) {
				var viewid = 'mapping-defaults' === model.get('id') ? 'defaultTab' : 'tab';
				var view = new app.views[viewid]({ model: model });

				frag.appendChild(view.render().el);
			});

			this.$el.find('.gc-template-tab-group').append(frag);
		}

	});
};

},{}]},{},[5]);
