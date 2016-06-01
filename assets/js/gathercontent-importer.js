/**
 * GatherContent Importer - v3.0.0 - 2016-06-01
 * http://www.gathercontent.com
 *
 * Copyright (c) 2016 GatherContent
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, app, undefined) {
	'use strict';

	app.cache = function () {
		app.$ = {};
		app.$.tabNav = $('.gc-nav-tab-wrapper .nav-tab');
		app.$.tabs = $('.gc-template-tab');
	};

	app.init = function () {
		app.cache();
		$(document.body).on('click', '.gc-nav-tab-wrapper .nav-tab', app.changeTabs).on('click', '.gc-reveal-items', app.maybeReveal);
		// put all your jQuery goodness in here.
	};

	app.changeTabs = function (evt) {
		var $this = $(this);
		evt.preventDefault();

		app.$.tabNav.removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		app.$.tabs.addClass('hidden');
		$(document.getElementById($(this).attr('href').substring(1))).removeClass('hidden');
	};

	app.maybeReveal = function (evt) {
		var $this = $(this);
		evt.preventDefault();

		if ($this.hasClass('dashicons-arrow-right')) {
			$this.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
			$this.next().removeClass('hidden');
		} else {
			$this.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
			$this.next().addClass('hidden');
		}
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{}]},{},[1]);
