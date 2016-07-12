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
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":3,"./views/base.js":5}],3:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],4:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.single = gc.single || {};
	var app = gc.single;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
  * Posts
  */

	// app.models.post = require( './models/post.js' )( gc );
	// app.collections.posts = require( './collections/posts.js' )( app );
	// app.views.postRow = require( './views/post-row.js' )( app, gc );
	// app.views.statusSelect2 = require( './views/status-select2.js' )( app );
	// app.views.postRows = require( './views/post-rows.js' )( app, gc, $ );

	app.init = function () {
		console.warn('single init');
		// Kick it off.
		// app.singleView = new app.views.postRows( {
		// 	collection : new app.collections.posts( [ gc._post ] )
		// } );
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./initiate-objects.js":2}],5:[function(require,module,exports){
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

},{}]},{},[4]);
