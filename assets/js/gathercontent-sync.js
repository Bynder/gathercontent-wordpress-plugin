/**
 * GatherContent Importer - v3.0.0 - 2016-06-18
 * http://www.gathercontent.com
 *
 * Copyright (c) 2016 GatherContent
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, undefined) {
	'use strict';

	this.sync = this.sync || {};
	var app = this.sync;
	var log = this.log;

	log(this);

	app.init = function () {
		log('warn', 'GC Sync init');
	};

	$(app.init);
}).call(window.GatherContent, window, document, jQuery);

},{}]},{},[1]);
