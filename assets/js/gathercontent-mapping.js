/**
 * GatherContent Importer - v3.0.0 - 2016-06-17
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

module.exports = function (args) {
	return args.collectionBase.extend({
		model: args.model,

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

module.exports = function (args) {
	return args.collectionBase.extend({
		model: args.model,

		// initialize: function() {
		// 	console.log('this (collection)', this);
		// },

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

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.mapping = gc.mapping || {};
	var app = gc.mapping;

	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };

	/*
  * Tab Row setup
  */

	app.models.tabRow = require('./models/tab-row.js')(app);
	app.collections.tabRows = require('./collections/tab-rows.js')({
		collectionBase: app.collections.base,
		model: app.models.tabRow
	});
	app.views.tabRow = require('./views/tab-row.js')(app, gc._meta_keys);

	/*
  * Tab setup
  */

	app.models.tab = require('./models/tab.js')({
		modelBase: app.models.base,
		rowCollection: app.collections.tabRows
	});
	app.collections.tabs = require('./collections/tabs.js')({
		collectionBase: app.collections.base,
		model: app.models.tab
	});
	app.views.tab = require('./views/tab.js')({
		viewBase: app.views.base,
		rowView: app.views.tabRow
	});

	app.views.tabLink = require('./views/tab-link.js')({
		viewBase: app.views.base
	});

	app.views.defaultTab = require('./views/default-tab.js')({
		viewTab: app.views.tab
	});

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

},{"./collections/base.js":1,"./collections/tab-rows.js":2,"./collections/tabs.js":3,"./models/base.js":5,"./models/tab-row.js":6,"./models/tab.js":7,"./views/base.js":8,"./views/default-tab.js":9,"./views/tab-link.js":10,"./views/tab-row.js":11,"./views/tab.js":12,"./views/tabs.js":13}],5:[function(require,module,exports){
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
			id: '',
			label: '',
			name: '',
			field_type: '',
			post_type: 'wp-type-post',
			field_value: false,
			expanded: false
		},

		_get: function _get(value, attribute) {

			switch (attribute) {
				case 'post_type':
					if (app.defaults) {
						value = app.defaults.get('post_type');
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

},{}],7:[function(require,module,exports){
'use strict';

module.exports = function (args) {
	return args.modelBase.extend({
		defaults: {
			id: '',
			label: '',
			hidden: false,
			navClasses: '',
			viewId: 'tab',
			linkViewId: 'tabLink',
			rows: []
		},

		initialize: function initialize() {
			this.rows = new args.rowCollection(this.get('rows'), { tab: this });
			// this.rows.bind( 'change', this.change );
		} /*,
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

},{}],8:[function(require,module,exports){
"use strict";

module.exports = Backbone.View.extend({
	render: function render() {
		this.$el.html(this.template(this.model.toJSON()));
		return this;
	}
});

},{}],9:[function(require,module,exports){
'use strict';

module.exports = function (args) {
	return args.viewTab.extend({
		events: {
			'change select': 'changeDefault'
		},

		changeDefault: function changeDefault(evt) {
			var $this = jQuery(evt.target);
			var value = $this.val();
			var column = $this.data('column');
			this.model.set(column, value);
		},

		render: function render() {
			var modelJSON = this.model.toJSON();

			this.$el.html(this.template(modelJSON));

			var template = wp.template('gc-mapping-defaults-tab');
			this.$el.find('tbody').html(template(modelJSON));

			this.$('.gc-select2').each(function () {
				var $this = jQuery(this);
				var column = $this.data('column');
				var url = window.ajaxurl + '?action=gc_get_option_data';
				// console.log('column',column);

				$this.select2({
					width: '250px',
					ajax: {
						url: url,
						data: function data(params) {
							return {
								q: params.term,
								column: column
							};
						},
						delay: 250,
						cache: true
					},
					minimumInputLength: 2
				});
			});

			return this;
		}
	});
};

},{}],10:[function(require,module,exports){
'use strict';

module.exports = function (args) {
	return args.viewBase.extend({
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

},{}],11:[function(require,module,exports){
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

		toggleExpanded: function toggleExpanded(evt) {
			this.model.set('expanded', !this.model.get('expanded'));
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

},{}],12:[function(require,module,exports){
'use strict';

module.exports = function (args) {
	return args.viewBase.extend({
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

			var addedElements = document.createDocumentFragment();
			this.model.rows.each(function (model) {
				var view = new args.rowView({ model: model }).render();
				// console.log('view.$el', view.$el);
				addedElements.appendChild(view.el);
			});

			this.$el.find('tbody').html(addedElements);

			return this;
		}
	});
};

},{}],13:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return Backbone.View.extend({
		el: '#mapping-tabs',

		template: function template() {
			return wp.template('gc-tabs-wrapper');
		},

		initialize: function initialize() {
			// this.listenTo( this.collection, 'change:post_status change:post_type change:post_author', this.changeDefault );
			this.listenTo(this.collection, 'change:label', this.labelChange);
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this, 'render', this.render);

			this.render();

			app.defaults = this.collection.getById('mapping-defaults');
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

		labelChange: function labelChange(model) {
			this.render();
		},

		render: function render() {
			this.$el.html(this.template());

			// Add tab links
			this.appendViewItems('.nav-tab-wrapper', 'linkViewId');

			// Add tab content
			this.appendViewItems('.gc-template-tab-group', 'viewId');

			return this;
		},

		appendViewItems: function appendViewItems(appendSelector, viewIdId) {
			var addedElements = document.createDocumentFragment();

			this.collection.each(function (model) {
				var viewid = model.get(viewIdId);
				var view = new app.views[viewid]({ model: model });

				addedElements.appendChild(view.render().el);
			});

			this.$el.find(appendSelector).append(addedElements);
		}

	});
};

},{}]},{},[4]);