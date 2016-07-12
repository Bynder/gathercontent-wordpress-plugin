/**
 * GatherContent Importer - v3.0.0 - 2016-07-12
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
	return app.collections.base.extend({
		model: app.models.navItem,

		initialize: function initialize() {
			this.listenTo(this, 'activate', this.activate);
		},

		getActive: function getActive() {
			return this.find(function (model) {
				return !model.get('hidden');
			});
		},

		activate: function activate(id) {
			this.each(function (model) {
				model.set('hidden', true);
			});
			this.getById(id).set('hidden', false);
			this.trigger('render');
		}
	});
};

},{}],4:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	var items = require('./../collections/items.js')(app);
	return items.extend({
		model: app.models.post,

		initialize: function initialize() {
			items.prototype.initialize.call(this);

			this.listenTo(this, 'updateItems', this.updateItems);
		},

		updateItems: function updateItems(data) {
			this.each(function (model) {
				var id = model.get('id');
				if (id in data) {
					if (data[id].status) {
						model.set('status', data[id].status);
					}
					if (data[id].itemName) {
						model.set('itemName', data[id].itemName);
					}
				}
			});
		}
	});
};

},{"./../collections/items.js":2}],5:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.general = gc.general || {};
	var app = gc.general;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
  * Posts
  */

	app.models.post = require('./models/post.js')(gc);
	app.collections.posts = require('./collections/posts.js')(app);
	app.views.postRow = require('./views/post-row.js')(app, gc);
	app.views.statusSelect2 = require('./views/status-select2.js')(app);
	app.views.postRows = require('./views/post-rows.js')(app, gc, $);

	/*
  * Nav Items
  */
	app.models.navItem = require('./models/modal-nav-item.js')(app);
	app.collections.navItems = require('./collections/modal-nav-items.js')(app);

	app.views.modalPostRow = require('./views/modal-post-row.js')(app, gc);
	app.views.modal = require('./views/modal.js')(app, gc, $);

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

	app.triggerModal = function (evt) {
		evt.preventDefault();

		var posts = app.getChecked();
		if (!posts.length) {
			return;
		}

		if (app.modalView === undefined) {
			app.modalView = new app.views.modal({
				collection: app.generalView.collection
			});
			app.modalView.selected = posts;
			app.modalView.render();
		}
	};

	app.getChecked = function () {
		return $('tbody th.check-column input[type="checkbox"]:checked').map(function () {
			return parseInt($(this).val(), 10);
		}).get();
	};

	app.init = function () {
		$(document.body).addClass('gathercontent-admin-select2').on('click', '#gc-sync-modal', app.triggerModal);

		app.generalView = new app.views.postRows({
			collection: new app.collections.posts(gc._posts)
		});

		app.monkeyPatchQuickEdit(function () {
			app.generalView.trigger('quickEdit', arguments, this);
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/modal-nav-items.js":3,"./collections/posts.js":4,"./initiate-objects.js":6,"./models/modal-nav-item.js":9,"./models/post.js":10,"./views/modal-post-row.js":13,"./views/modal.js":14,"./views/post-row.js":15,"./views/post-rows.js":16,"./views/status-select2.js":17}],6:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":8,"./views/base.js":11}],7:[function(require,module,exports){
'use strict';

module.exports = function (app, defaults) {
	defaults = jQuery.extend({}, {
		action: 'gc_sync_items',
		data: '',
		percent: 0,
		nonce: '',
		id: '',
		stopSync: true,
		flush_cache: false
	}, defaults);

	return app.models.base.extend({
		defaults: defaults,

		initialize: function initialize() {
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

			jQuery.post(window.ajaxurl, {
				action: this.get('action'),
				percent: this.get('percent'),
				nonce: this.get('nonce'),
				id: this.get('id'),
				data: formData,
				flush_cache: this.get('flush_cache')
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

},{}],8:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],9:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.models.base.extend({
		defaults: {
			label: '',
			id: '',
			hidden: true,
			rendered: false
		}
	});
};

},{}],10:[function(require,module,exports){
'use strict';

module.exports = function (gc) {
	return Backbone.Model.extend({
		defaults: {
			id: 0,
			item: 0,
			itemName: 0,
			mapping: 0,
			mappingLink: '',
			mappingStatus: '',
			status: {},
			checked: false,
			disabled: false,
			statuses: [],
			statusesChecked: false,
			statusSetting: {}
		},

		url: function url() {
			return window.ajaxurl + '?action=gc_fetch_js_post&id=' + this.get('id');
		},

		_get: function _get(value, attribute) {
			switch (attribute) {
				case 'disabled':
					value = !this.get('item') || !this.get('mapping');
					break;
				case 'mappingStatus':
					value = gc._statuses[value] ? gc._statuses[value] : '';
					break;
			}

			return value;
		},

		get: function get(attribute) {
			return this._get(Backbone.Model.prototype.get.call(this, attribute), attribute);
		},

		// hijack the toJSON method and overwrite the data that is sent back to the view.
		toJSON: function toJSON() {
			return _.mapObject(Backbone.Model.prototype.toJSON.call(this), _.bind(this._get, this));
		}

	});
};

},{}],11:[function(require,module,exports){
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

},{}],12:[function(require,module,exports){
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
		}
	});
};

},{}],13:[function(require,module,exports){
'use strict';

module.exports = function (app, gc) {
	var item = require('./../views/item.js')(app);
	return item.extend({
		template: wp.template('gc-modal-item'),

		id: function id() {
			return 'gc-modal-post-' + this.model.get('id');
		},

		className: function className() {
			return 'gc-item ' + (this.model.get('disabled') ? 'gc-disabled' : '');
		},

		events: {
			'change .check-column input': 'toggleCheck',
			'click .gc-modal-item-wp-post-title': 'toggleCheckAndRender'
		},

		initialize: function initialize() {
			this.listenTo(this.model, 'change:post_title', this.renderTitle);
			this.listenTo(this.model, 'change:mappingStatus', this.render);
			this.listenTo(this.model, 'render', this.render);
		},

		renderTitle: function renderTitle() {
			var title = this.model.get('post_title');
			var id = this.model.get('id');
			gc.$id('post-' + id).find('.column-title .row-title').text(title);
			gc.$id('edit-' + id).find('[name="post_title"]').text(title);
			gc.$id('inline_' + id).find('.post_title').text(title);
		},

		toggleCheckAndRender: function toggleCheckAndRender(evt) {
			this.toggleCheck();
			this.render();
		}
	});
};

},{"./../views/item.js":12}],14:[function(require,module,exports){
'use strict';

module.exports = function (app, gc, $) {
	app.modalView = undefined;

	var thisView;
	/**
  * Taken from https://github.com/aut0poietic/wp-admin-modal-example
  */
	return app.views.base.extend({
		id: 'gc-bb-modal-dialog',
		template: wp.template('gc-modal-window'),

		navSeparator: '<li class="separator">&nbsp;</li>',

		backdrop: '<div class="gc-bb-modal-backdrop">&nbsp;</div>',
		selected: [],
		navItems: {},
		btns: {},
		currID: '',
		currNav: false,
		timeoutID: null,
		ajax: null,

		events: {
			'click .gc-bb-modal-close': 'closeModal',
			'click #btn-cancel': 'closeModal',
			'click #gc-btn-ok': 'saveModal',
			'click .gc-bb-modal-nav-tabs a': 'clickSelectTab',
			'change .gc-field-th.check-column input': 'checkAll',
			'click #gc-btn-pull': 'startPull',
			'click #gc-btn-push': 'startPush'
		},

		/**
  	 * Instantiates the Template object and triggers load.
  	 */
		initialize: function initialize() {
			thisView = this;

			_.bindAll(this, 'render', 'preserveFocus', 'closeModal', 'saveModal');

			this.setupAjax();

			this.navItems = new app.collections.navItems(gc._nav_items);
			this.btns = new app.collections.base(gc._modal_btns);
			this.currNav = this.navItems.getActive();

			this.listenTo(this.navItems, 'render', this.render);
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this.collection, 'enabledChange', this.checkEnableButton);
			this.listenTo(this.collection, 'notAllChecked', this.allCheckedStatus);
			this.listenTo(this.collection, 'updateItems', this.render);
		},

		setupAjax: function setupAjax() {
			var Ajax = require('./../models/ajax.js')(app, {
				action: 'gc_pull_items',
				nonce: gc._edit_nonce,
				flush_cache: !!gc.queryargs.flush_cache
			});

			this.ajax = new Ajax();
		},

		/**
   * Assembles the UI from loaded templates.
   * @internal Obviously, if the templates fail to load, our modal never launches.
   */
		render: function render() {

			// Build the base window and backdrop, attaching them to the $el.
			// Setting the tab index allows us to capture focus and redirect it in Application.preserveFocus
			this.$el.attr('tabindex', '0').html(this.template({
				btns: this.btns.toJSON(),
				navItems: this.navItems.toJSON(),
				currID: this.currNav ? this.currNav.get('id') : ''
			})).append(this.backdrop);

			this.$el.find('tbody').html(this.getRenderedSelected());

			// Make sync button enabled/disabled
			this.buttonStatus(this.collection.syncEnabled);

			// Make check-all inputs checked/unchecked
			this.allCheckedStatus(this.collection.allChecked);

			// Handle any attempt to move focus out of the modal.
			$(document).on('focusin', this.preserveFocus);

			// set overflow to "hidden" on the body so that it ignores any scroll events
			// while the modal is active and append the modal to the body.
			$(document.body).addClass('gc-modal-open').append(this.$el);

			// Set focus on the modal to prevent accidental actions in the underlying page
			// Not strictly necessary, but nice to do.
			this.$el.focus();
		},

		getSelected: function getSelected() {
			return this.collection.filter(function (model) {
				return -1 !== _.indexOf(thisView.selected, model.get('id')) && !model.get('disabled');
			});
		},

		getRenderedSelected: function getRenderedSelected() {
			var selected = this.getSelected();

			var addedElements = document.createDocumentFragment();

			_.each(selected, function (model) {
				var view = new app.views.modalPostRow({ model: model }).render();
				addedElements.appendChild(view.el);
			});

			return addedElements;
		},

		/**
   * Ensures that keyboard focus remains within the Modal dialog.
   * @param evt {object} A jQuery-normalized event object.
   */
		preserveFocus: function preserveFocus(evt) {
			if (this.$el[0] !== evt.target && !this.$el.has(evt.target).length) {
				this.$el.focus();
			}
		},

		/**
   * Closes the modal and cleans up after the instance.
   * @param evt {object} A jQuery-normalized event object.
   */
		closeModal: function closeModal(evt) {
			evt.preventDefault();
			this.undelegateEvents();
			$(document).off('focusin');
			$(document.body).removeClass('gc-modal-open');
			this.remove();

			gc.$id('bulk-edit').find('button.cancel').trigger('click');
			app.modalView = undefined;
		},

		/**
   * Responds to the gc-btn-ok.click event
   * @param evt {object} A jQuery-normalized event object.
   * @todo You should make this your own.
   */
		saveModal: function saveModal(evt) {
			this.closeModal(evt);
		},

		clickSelectTab: function clickSelectTab(evt) {
			evt.preventDefault();

			this.selectTab($(evt.target).data('id'));
		},

		selectTab: function selectTab(id) {
			this.currID = id;
			this.currNav = this.navItems.getById(id);
			this.navItems.trigger('activate', id);
		},

		checkEnableButton: function checkEnableButton(btnEnabled) {
			this.buttonStatus(btnEnabled);
		},

		buttonStatus: function buttonStatus(enable) {
			this.$('.media-toolbar button').prop('disabled', !enable);
		},

		allCheckedStatus: function allCheckedStatus(checked) {
			this.$('.gc-field-th.check-column input').prop('checked', checked);
		},

		checkAll: function checkAll(evt) {
			this.collection.trigger('checkAll', $(evt.target).is(':checked'));
		},

		startPull: function startPull(evt) {
			evt.preventDefault();
			var selected = this.modelsToJSON(this.setSelectedMappingStatus('starting'));

			console.warn('pull selected', selected);
			this.doAjax(selected, 'pull');
		},

		startPush: function startPush(evt) {
			evt.preventDefault();

			var selected = this.modelsToJSON(this.setSelectedMappingStatus('starting'));

			console.warn('push selected', selected);
			this.doAjax(selected, 'push');
		},

		getSelectedAndChecked: function getSelectedAndChecked() {
			var checked = _.filter(this.getSelected(), function (model) {
				return model.get('checked');
			});

			return checked;
		},

		modelsToJSON: function modelsToJSON(models) {
			return _.map(models, function (model) {
				return model.toJSON();
			});
		},

		ajaxSuccess: function ajaxSuccess(response) {
			console.log('ajaxSuccess response.data', response.data);

			if (!response.data.mappings) {
				return this.ajaxFail();
			}

			var mappings = [];
			_.each(this.getSelectedAndChecked(), function (model) {
				if (response.data.mappings.length && -1 !== _.indexOf(response.data.mappings, model.get('mapping'))) {
					model.set('mappingStatus', 'syncing');
					mappings.push(model.get('mapping'));
				} else {
					model.set('checked', false);
					model.set('mappingStatus', 'complete');
					model.fetch().done(function () {
						model.trigger('render');
					});
				}
			});

			if (!mappings.length) {
				return this.clearTimeout();
			}

			this.checkStatus(mappings, response.data.direction);
		},

		ajaxFail: function ajaxFail(response) {
			console.warn('response', response);
			this.setSelectedMappingStatus('failed');
			this.clearTimeout();
		},

		setSelectedMappingStatus: function setSelectedMappingStatus(status) {
			var selectedChecked = this.getSelectedAndChecked();
			_.each(selectedChecked, function (model) {
				model.set('mappingStatus', status);
			});

			return selectedChecked;
		},

		checkStatus: function checkStatus(mappings, direction) {
			this.clearTimeout();
			this.timeoutID = window.setTimeout(function () {
				thisView.doAjax({ check: mappings }, direction);
			}, 1000);
		},

		clearTimeout: function clearTimeout() {
			window.clearTimeout(this.timeoutID);
			this.timeoutID = null;
		},

		doAjax: function doAjax(formData, direction) {
			this.ajax.set('action', 'gc_' + direction + '_items');

			this.ajax.send(formData, this.ajaxSuccess.bind(this), 0, this.ajaxFail.bind(this));
		}

	});
};

},{"./../models/ajax.js":7}],15:[function(require,module,exports){
'use strict';

module.exports = function (app, gc) {
	return app.views.base.extend({
		template: wp.template('gc-post-column-row'),
		tagName: 'span',
		className: 'gc-status-column',
		id: function id() {
			return 'gc-status-row-' + this.model.get('id');
		},

		initialize: function initialize() {
			this.listenTo(this.model, 'change:status', this.render);
		},

		html: function html() {
			return this.template(this.model.toJSON());
		},

		render: function render() {
			var $td = gc.$id('post-' + this.model.get('id')).find('.column-gathercontent');
			$td.html(this.html());

			return this;
		}
	});
};

},{}],16:[function(require,module,exports){
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

			// Trigger an un-cached update for the statuses
			$.post(window.ajaxurl, {
				action: 'gc_get_items',
				posts: gc._posts,
				flush_cache: !!gc.queryargs.flush_cache
			}, function (response) {
				if ((response.success, response.data)) {
					thisView.collection.trigger('updateItems', response.data);
				}
			});
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
				new app.views.postRow({ model: model }).render();
			});
			return this;
		}
	});
};

},{}],17:[function(require,module,exports){
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

},{}]},{},[5]);
