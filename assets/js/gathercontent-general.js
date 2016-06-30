/**
 * GatherContent Importer - v3.0.0 - 2016-06-30
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
			var modelId = model.get('id');
			return modelId === id || modelId && id && modelId == id;
		});
	}
});

},{}],2:[function(require,module,exports){
"use strict";

module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.post
	});
};

},{}],3:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.general = gc.general || {};
	var app = gc.general;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
  * Post Row setup
  */

	app.models.post = require('./models/post.js')(app);
	app.collections.posts = require('./collections/posts.js')(app);
	app.views.postRow = require('./views/post-row.js')(app, gc);
	app.views.statusSelect2 = require('./views/status-select2.js')(app);
	app.views.postRows = require('./views/post-rows.js')(app, gc, $);

	app.getPosts = function () {
		var posts = [];
		$('.gc-status-column').each(function () {
			posts.push($(this).data());
		});
		return posts;
	};

	app.monkeyPatchQuickEdit = function (cb) {
		// we create a copy of the WP inline edit post function
		var edit = window.inlineEditPost.edit;

		// and then we overwrite the function with our own code
		window.inlineEditPost.edit = function () {
			// "call" the original WP edit function
			// we don't want to leave WordPress hanging
			edit.apply(this, arguments);

			// now we take care of our business
			cb.apply(this, arguments);
		};
	};

	app.ajaxResponse = function (response) {
		if (response.success) {
			// Kick it off.
			app.generalView = new app.views.postRows({
				collection: new app.collections.posts(response.data)
			});

			app.monkeyPatchQuickEdit(function () {
				app.generalView.trigger('quickEdit', arguments, this);
			});
		}
	};

	app.init = function () {
		$(document.body).addClass('gathercontent-admin-select2');

		$.post(window.ajaxurl, {
			action: 'gc_get_items',
			posts: app.getPosts(),
			flush_cache: !!gc.queryargs.flush_cache
		}, app.ajaxResponse);
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/posts.js":2,"./initiate-objects.js":4,"./models/post.js":6,"./views/post-row.js":8,"./views/post-rows.js":9,"./views/status-select2.js":10}],4:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":5,"./views/base.js":7}],5:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],6:[function(require,module,exports){
"use strict";

module.exports = function (app) {
	return app.models.base.extend({
		defaults: {
			id: 0,
			item: 0,
			mapping: 0,
			status: {},
			statuses: [],
			statusesChecked: false,
			statusSetting: {}
		}
	});
};

},{}],7:[function(require,module,exports){
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

},{}],8:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		template: wp.template('gc-post-column-row'),
		tagName: 'span',
		className: 'gc-status-column',
		id: function id() {
			return 'gc-status-row-' + this.model.get('id');
		},

		html: function html() {
			return this.template(this.model.toJSON());
		}

	});
};

},{}],9:[function(require,module,exports){
'use strict';

module.exports = function (app, gc, $) {
	var thisView;
	return app.views.statusSelect2.extend({
		template: wp.template('gc-status-select2'),

		el: '#posts-filter tbody',

		width: '200px',

		events: {
			'change .gc-select2': 'storeStatus'
		},

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this, 'quickEdit', this.edit);
			this.render();
		},

		storeStatus: function storeStatus(evt) {
			var $this = jQuery(evt.target);
			var val = $this.val();
			var model = this.collection.getById($this.data('id'));

			model.set('setStatus', val);
		},

		edit: function edit(id, inlineEdit) {
			// get the post ID
			var postId = 0;
			if ('object' === typeof id) {
				postId = parseInt(inlineEdit.getId(id), 10);
			}

			this.waitSpinner(postId);

			if (!postId) {
				return;
			}

			var model = this.collection.getById(postId);

			if (model.get('statusesChecked')) {
				return this.renderStatuses(model);
			}

			$.post(window.ajaxurl, {
				action: 'gc_get_post_statuses',
				postId: postId,
				flush_cache: !!gc.queryargs.flush_cache
			}, this.ajaxResponse).done(function () {
				thisView.renderStatuses(model);
			});
		},

		ajaxResponse: function ajaxResponse(response) {
			if (!response.data) {
				return;
			}

			var model = thisView.collection.getById(response.data.postId);
			if (!model) {
				return;
			}

			model.set('statusesChecked', true);

			if (response.success) {
				model.set('statuses', response.data.statuses);

				if (model.get('statuses').length) {
					thisView.$('.gc-select2').each(function () {
						$(this).select2('destroy');
					});

					thisView.renderStatuses(model);
				}
			}
		},

		renderStatuses: function renderStatuses(model) {
			var postId = model.get('id');
			this.editSelect(postId).html(this.template(model.toJSON()));
			if (model.get('statuses').length) {
				this.renderSelect2(gc.$id('edit-' + postId));
			}
		},

		waitSpinner: function waitSpinner(postId) {
			this.editSelect(postId).html('<span class="spinner"></span>');
		},

		editSelect: function editSelect(postId) {
			return gc.$id('edit-' + postId).find('.inline-edit-group .gc-status-select2');
		},

		render: function render() {
			this.collection.each(function (model) {
				var html = new app.views.postRow({ model: model }).html();

				var $td = gc.$id('post-' + model.get('id')).find('.column-gathercontent');

				$td.html(html);
			});
			return this;
		}
	});
};

},{}],10:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	var thisView;
	return app.views.base.extend({
		select2ItemTemplate: wp.template('gc-select2-item'),
		width: '250px',

		renderSelect2: function renderSelect2($context) {
			var $selector = $context ? $context.find('.gc-select2') : this.$('.gc-select2');
			thisView = this;

			$selector.each(function () {
				var $this = jQuery(this);
				var data = $this.data();
				$this.select2(thisView.select2Args(data));
				var s2Data = $this.data('select2');

				// Add classes for styling.
				s2Data.$results.addClass('select2-' + data.column);
				s2Data.$container.addClass('select2-' + data.column);
			});

			return this;
		},

		select2Args: function select2Args(data) {
			var args = {
				width: thisView.width
			};

			args.templateResult = (function (status, showDesc) {
				var data = jQuery.extend(status, jQuery(status.element).data());
				data.description = false === showDesc ? false : data.description || '';
				return jQuery(thisView.select2ItemTemplate(status));
			}).bind(thisView);

			args.templateSelection = function (status) {
				return args.templateResult(status, false);
			};

			return args;
		}

	});
};

},{}]},{},[3]);
