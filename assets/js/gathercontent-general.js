/**
 * GatherContent Importer - v3.0.0 - 2016-07-21
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
		processing: false,

		initialize: function initialize() {
			this.listenTo(this, 'checkAll', this.toggleChecked);
			this.listenTo(this, 'checkSome', this.toggleCheckedIf);
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

		toggleCheckedIf: function toggleCheckedIf(cb) {
			this.processing = true;
			this.each(function (model) {
				model.set('checked', Boolean('function' === typeof cb ? cb(model) : cb));
			});
			this.processing = false;
			this.trigger('render');
		},

		toggleChecked: function toggleChecked(checked) {
			this.allChecked = checked;
			this.toggleCheckedIf(checked);
		},

		checkedCan: function checkedCan(pushOrPull) {
			switch (pushOrPull) {
				case 'pull':
					pushOrPull = 'canPull';
					break;
				case 'assign':
					pushOrPull = 'disabled';
					break;
				// case 'push':
				default:
					pushOrPull = 'canPush';
					break;
			}

			var can = this.find(function (model) {
				return model.get(pushOrPull) && model.get('checked');
			});

			return can;
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
					if (data[id].updated) {
						model.set('updated', data[id].updated);
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
			app.modalView.checked(posts);
			app.generalView.listenTo(app.modalView, 'updateModels', app.generalView.updatePosts);
		}
	};

	app.getChecked = function () {
		return $('tbody th.check-column input[type="checkbox"]:checked').map(function () {
			return parseInt($(this).val(), 10);
		}).get();
	};

	app.init = function () {
		$(document.body).addClass('gathercontent-admin-select2').on('click', '#gc-sync-modal', app.triggerModal);

		$(document).ajaxSend(function (evt, request, settings) {
			if (settings.data && -1 !== settings.data.indexOf('&action=inline-save')) {
				app.generalView.trigger('quickEditSend', request, settings);
			}
		});

		app.generalView = new app.views.postRows({
			collection: new app.collections.posts(gc._posts)
		});

		app.monkeyPatchQuickEdit(function () {
			app.generalView.trigger('quickEdit', arguments, this);
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/modal-nav-items.js":3,"./collections/posts.js":4,"./initiate-objects.js":6,"./models/modal-nav-item.js":9,"./models/post.js":10,"./views/modal-post-row.js":16,"./views/modal.js":17,"./views/post-row.js":18,"./views/post-rows.js":19,"./views/status-select2.js":20}],6:[function(require,module,exports){
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
			itemName: '',
			updated: '',
			current: true,
			editLink: '',
			mapping: 0,
			mappingName: 0,
			mappingLink: '',
			mappingStatus: '',
			mappingStatusId: '',
			status: {},
			checked: false,
			disabled: false,
			canPull: false,
			canPush: false,
			statuses: [],
			statusesChecked: false
		},

		url: function url() {
			var url = window.ajaxurl + '?action=gc_fetch_js_post&id=' + this.get('id');
			if (this.get('uncached')) {
				this.set('uncached', false);
				url += '&flush_cache=force';
			}
			return url;
		},

		_get: function _get(value, attribute) {
			switch (attribute) {
				case 'disabled':
					value = !this.get('mapping');
					break;

				case 'canPull':
					value = this.get('item') > 0 && this.get('mapping') > 0;
					break;

				case 'canPush':
					value = this.get('mapping') > 0;
					break;

				case 'mappingStatus':
					value = gc._statuses[value] ? gc._statuses[value] : '';
					break;
				case 'mappingStatusId':
					value = Backbone.Model.prototype.get.call(this, 'mappingStatus');
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
	},

	close: function close() {
		this.remove();
		this.unbind();
		if (this.onClose) {
			this.onClose();
		}
	}
});

},{}],12:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		template: wp.template('gc-item'),
		tagName: 'tr',
		className: 'gc-item gc-enabled',
		id: function id() {
			return this.model.get('id');
		},

		events: {
			'change .check-column input': 'toggleCheck',
			'click .gc-reveal-items': 'toggleExpanded',
			'click .gc-status-column': 'toggleCheckAndRender'
		},

		initialize: function initialize() {
			this.listenTo(this.model, 'change:checked', this.render);
		},

		toggleCheck: function toggleCheck() {
			this.model.set('checked', !this.model.get('checked'));
		},

		toggleCheckAndRender: function toggleCheckAndRender(evt) {
			this.toggleCheck();
			this.render();
		}
	});
};

},{}],13:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var thisView;
	var base = require('./../views/metabox-base.js')(app, $, gc);
	return base.extend({
		template: wp.template('gc-mapping-metabox'),
		stepArgs: false,
		events: {
			'click #gc-map': 'step',
			'change #select-gc-next-step': 'setProperty',
			'click #gc-map-cancel': 'cancel'
		},

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this.model, 'change:waiting', this.toggleWaitingRender);
			this.listenTo(this.model, 'change', this.maybeEnableAndRender);
			this.listenTo(this.model, 'change:step', this.changeStep);
			this.listenTo(this, 'cancel', this.resetAndRender);
			this.render();
			this.$el.removeClass('no-js').addClass('gc-mapping-metabox');
		},

		changeStep: function changeStep(model) {
			if ('accounts' === model.changed.step) {
				this.$el.addClass('gc-mapping-started');
			}

			if (model.changed.step) {
				this.stepArgs = this['step_' + model.changed.step]();
			}
		},

		setProperty: function setProperty(evt) {
			var value = $(evt.target).val();

			this.model.set(this.stepArgs.property, value);

			if ('account' === this.stepArgs.property || 'project' === this.stepArgs.property) {
				// Autoclick "next" for user.
				this.step();
			}
		},

		setMapping: function setMapping() {
			var success = function success(data) {
				this.model.set('waiting', false);

				// Goodbye
				this.trigger('complete', this.model, data);
			};

			this.ajax({
				action: 'gc_save_mapping_id'
			}, success, this.failMsg);
		},

		maybeEnableAndRender: function maybeEnableAndRender(model) {
			if (model.changed.account || model.changed.project || model.changed.mapping) {
				this.model.set('btnDisabled', false);
				this.render();
			}
		},

		toggleWaitingRender: function toggleWaitingRender(model) {
			if (model.changed.waiting) {
				this.model.set('btnDisabled', true);
			}
			this.render();
		},

		step: function step() {
			this.model.set('waiting', true);

			if ('mapping' === this.stepArgs.property) {
				return this.setMapping();
			}

			this.setStep();

			var properties = this.model.get(this.stepArgs.properties);

			if (properties && properties.length) {

				this.successHandler(properties);
			} else {

				this.ajax({
					action: 'gc_wp_filter_mappings',
					property: this.stepArgs.property
				}, this.successHandler, this.failMsg);
			}

			return this;
		},

		failMsg: function failMsg(msg) {
			msg = 'string' === typeof msg ? msg : gc._errors.unknown;
			window.alert(msg);
			thisView.model.set('waiting', false);
		},

		successHandler: function successHandler(objects) {
			this.model.set(this.stepArgs.properties, objects);
			if (objects.length < 2) {
				this.model.set('btnDisabled', false);
			}
			this.model.set('waiting', false);
		},

		setStep: function setStep() {
			if (!this.model.get('step')) {
				return this.model.set('step', 'accounts');
			}

			if ('accounts' === this.model.get('step')) {
				return this.model.set('step', 'projects');
			}

			if ('projects' === this.model.get('step')) {
				return this.model.set('step', 'mappings');
			}
		},

		step_accounts: function step_accounts() {
			return {
				property: 'account',
				properties: 'accounts'
			};
		},

		step_projects: function step_projects() {
			return {
				property: 'project',
				properties: 'projects'
			};
		},

		step_mappings: function step_mappings() {
			return {
				property: 'mapping',
				properties: 'mappings'
			};
		},

		cancel: function cancel(evt) {
			this.trigger('cancel', evt);
		},

		resetModel: function resetModel() {
			this.stepArgs = false;
			this.model.set({
				'step': false,
				'account': 0,
				'project': 0,
				'mapping': 0
			});
			return this.model;
		},

		resetAndRender: function resetAndRender() {
			this.resetModel();
			this.render();
		},

		render: function render() {
			var json = this.model.toJSON();
			if (this.stepArgs) {
				json.label = gc._step_labels[json.step];
				json.property = this.stepArgs.property;
			}
			this.$el.html(this.template(json));
			return this;
		}

	});
};

},{"./../views/metabox-base.js":14}],14:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	return app.views.base.extend({
		el: '#gc-related-data',

		ajax: function ajax(args, successcb, failcb) {
			var view = this;
			var success = function success(response) {
				if (response.success) {
					successcb.call(view, response.data);
				} else if (failcb) {
					failcb.call(view, response.data);
				}
			};

			var promise = $.post(window.ajaxurl, $.extend({
				action: '',
				post: this.model.toJSON(),
				nonce: gc.$id('gc-edit-nonce').val(),
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, args), success);

			if (failcb) {
				promise.fail(function () {
					failcb.call(view);
				});
			}

			return promise;
		}
	});
};

},{}],15:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var base = require('./../views/mapping-metabox.js')(app, $, gc);

	var model = new Backbone.Model({
		id: true,
		cancelBtn: true,
		accounts: [],
		projects: [],
		mappings: []
	});

	var View = base.extend({
		close: function close() {
			model = this.resetModel();
			base.prototype.close.call(this);
		}
	});

	return function (postIds) {
		model.set('ids', postIds);

		var view = new View({
			model: model
		});

		view.$el.addClass('postbox');

		return view.step();
	};
};

},{"./../views/mapping-metabox.js":13}],16:[function(require,module,exports){
'use strict';

module.exports = function (app, gc) {
	var item = require('./../views/item.js')(app);
	return item.extend({
		template: wp.template('gc-item'),

		id: function id() {
			return 'gc-modal-post-' + this.model.get('id');
		},

		className: function className() {
			return 'gc-item ' + (this.model.get('disabled') ? 'gc-disabled' : 'gc-enabled');
		},

		events: {
			'change .check-column input': 'toggleCheck',
			'click .gc-status-column': 'toggleCheckAndRender'
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
		}

	});
};

},{"./../views/item.js":12}],17:[function(require,module,exports){
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
		metaboxView: null,

		events: {
			'click .gc-bb-modal-close': 'closeModal',
			'click #btn-cancel': 'closeModal',
			'click #gc-btn-ok': 'saveModal',
			'click .gc-bb-modal-nav-tabs a': 'clickSelectTab',
			'change .gc-field-th.check-column input': 'checkAll',
			'click #gc-btn-pull': 'startPull',
			'click #gc-btn-push': 'startPush',
			'click .gc-cloak': 'maybeResetMetaboxView',
			'click #gc-btn-assign-mapping': 'startAssignment'
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
			this.listenTo(this.collection, 'notAllChecked', this.allCheckedStatus);
			this.listenTo(this.collection, 'updateItems', this.maybeRender);
			this.listenTo(this.collection, 'change:checked', this.checkEnableButton);

			this.initMetaboxView = require('./../views/modal-assign-mapping.js')(app, $, gc);
		},

		checked: function checked(selected) {
			this.selected = selected;
			if (!selected.length) {
				return;
			}

			if (selected.length === this.collection.length) {
				return this.collection.trigger('checkAll', true);
			}

			this.collection.trigger('checkSome', function (model) {
				return -1 !== _.indexOf(thisView.selected, model.get('id')) && !model.get('disabled');
			});

			return this;
		},

		setupAjax: function setupAjax() {
			var Ajax = require('./../models/ajax.js')(app, {
				action: 'gc_pull_items',
				nonce: gc._edit_nonce,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
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
			this.$el.removeClass('gc-set-mapping').attr('tabindex', '0').html(this.template({
				btns: this.btns.toJSON(),
				navItems: this.navItems.toJSON(),
				currID: this.currNav ? this.currNav.get('id') : ''
			})).append(this.backdrop);

			// this.$el.find( 'tbody' ).html( this.getRenderedSelected() );
			this.$el.find('tbody').html(this.getRenderedModels(app.views.modalPostRow));

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

		// getRenderedSelected: function() {
		// 	var selected = this.getSelected();

		// 	var addedElements = document.createDocumentFragment();

		// 	_.each( selected, function( model ) {
		// 		var view = ( new app.views.modalPostRow({ model: model }) ).render();
		// 		addedElements.appendChild( view.el );
		// 	});

		// 	return addedElements;
		// },

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
			this.resetMetaboxView();
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
			if (this.collection.processing) {
				return;
			}
			if (!enable) {
				this.$('.media-toolbar button').prop('disabled', true);
			} else {
				this.$('#gc-btn-assign-mapping').prop('disabled', !this.collection.checkedCan('assign'));
				this.$('#gc-btn-push').prop('disabled', !this.collection.checkedCan('push'));
				this.$('#gc-btn-pull').prop('disabled', !this.collection.checkedCan('pull'));
			}
		},

		allCheckedStatus: function allCheckedStatus(checked) {
			this.$('.gc-field-th.check-column input').prop('checked', checked);
		},

		checkAll: function checkAll(evt) {
			this.collection.trigger('checkAll', $(evt.target).is(':checked'));
		},

		startPull: function startPull(evt) {
			evt.preventDefault();
			this.startSync('pull');
		},

		startPush: function startPush(evt) {
			evt.preventDefault();
			this.startSync('push');
		},

		startSync: function startSync(direction) {
			var toCheck = 'push' === direction ? 'canPush' : 'canPull';
			var selected = this.selectiveGet(toCheck);

			if (window.confirm(gc._sure[direction])) {
				selected = _.map(selected, function (model) {
					model.set('mappingStatus', 'starting');
					return model.toJSON();
				});

				this.doAjax(selected, direction);
			}
		},

		startAssignment: function startAssignment(evt) {
			var postIds = _.map(this.selectiveGet('disabled'), function (model) {
				return model.get('id');
			});

			this.resetMetaboxView();

			this.$el.addClass('gc-set-mapping');

			this.$('#gc-btn-assign-mapping').prop('disabled', true);

			this.metaboxView = this.initMetaboxView(postIds);
			this.listenTo(this.metaboxView, 'cancel', this.maybeResetMetaboxView);
			this.listenTo(this.metaboxView, 'complete', function (model, data) {
				model.set('waiting', true);

				this.collection.map(function (model) {
					if (model.get('id') in data.ids) {
						model.set('mapping', data.mapping);
						model.set('mappingName', data.mappingName);
						model.set('mappingLink', data.mappingLink);
					}
				});

				this.render();
			});
		},

		maybeResetMetaboxView: function maybeResetMetaboxView() {
			if (this.metaboxView) {
				this.resetMetaboxView();
				this.buttonStatus(true);
			}
		},

		resetMetaboxView: function resetMetaboxView() {
			if (this.metaboxView) {
				this.stopListening(this.metaboxView);
				this.metaboxView.close();
				this.$el.removeClass('gc-set-mapping');
			}
		},

		selectiveGet: function selectiveGet(toCheck) {
			var selected = [];
			var staysChecked;

			this.collection.trigger('checkSome', function (model) {
				staysChecked = model.get('checked') && model.get(toCheck);
				if (staysChecked) {
					selected.push(model);
				}

				return staysChecked;
			});

			return selected;
		},

		getChecked: function getChecked(cb) {
			this.collection.filter(function (model) {
				var shouldGet = model.get('checked');
				if (shouldGet && cb) {
					cb(model);
				}
				return shouldGet;
			});
		},

		ajaxSuccess: function ajaxSuccess(response) {
			if (!response.data.mappings) {
				return this.ajaxFail();
			}

			var mappings = [];

			var toCheck = 'push' === response.data.direction ? 'canPush' : 'canPull';
			var checked = this.getChecked(function (model) {
				if (!model.get(toCheck)) {
					return;
				}

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
			this.setSelectedMappingStatus('failed');
			this.clearTimeout();
		},

		setSelectedMappingStatus: function setSelectedMappingStatus(status) {
			return this.getChecked(function (model) {
				model.set('mappingStatus', status);
			});
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
		},

		maybeRender: function maybeRender() {
			if (!this.metaboxView) {
				this.render();
			}
		}

	});
};

},{"./../models/ajax.js":7,"./../views/modal-assign-mapping.js":15}],18:[function(require,module,exports){
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

},{}],19:[function(require,module,exports){
'use strict';

module.exports = function (app, gc, $) {
	var thisView;
	return app.views.statusSelect2.extend({
		template: wp.template('gc-status-select2'),

		el: '#posts-filter tbody',

		width: '200px',

		initialize: function initialize() {
			thisView = this;
			this.listenTo(this, 'quickEdit', this.edit);
			this.listenTo(this, 'quickEditSend', this.sending);
			this.render();
			this.updatePosts();
		},

		updatePosts: function updatePosts() {
			// Trigger an un-cached update for the posts
			$.post(window.ajaxurl, {
				action: 'gc_get_posts',
				posts: gc._posts,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, function (response) {
				if ((response.success, response.data)) {
					thisView.collection.trigger('updateItems', response.data);
				}
			});
		},

		sending: function sending(request, settings) {
			var data = this.parseQueryString(settings.data);
			if (data.post_ID && data.gc_status) {
				var model = this.collection.getById(data.post_ID);

				var status = _.find(model.get('statuses'), function (status) {
					return parseInt(status.id, 10) === parseInt(data.gc_status, 10);
				});

				model.set('status', status);
			}
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
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
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
		},

		/**
   * Parse query string.
   * ?a=b&c=d to {a: b, c: d}
   * @param {String} (option) queryString
   * @return {Object} query params
   */
		parseQueryString: function parseQueryString(string) {
			if (!string) {
				return {};
			}
			return _.chain(string.split('&')).map(function (params) {
				var p = params.split('=');
				return [p[0], decodeURIComponent(p[1])];
			}).object().value();
		}

	});
};

},{}],20:[function(require,module,exports){
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
