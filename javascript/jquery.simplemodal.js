/*
 * SimpleModal 1.1 - jQuery Plugin
 * http://www.ericmmartin.com/projects/simplemodal/
 * http://plugins.jquery.com/project/SimpleModal
 * http://code.google.com/p/simplemodal/
 *
 * Copyright (c) 2007 Eric Martin - http://ericmmartin.com
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Revision: $Id: jquery.simplemodal.js 71 2008-01-04 04:51:02Z emartin24 $
 *
 */

/**
 * SimpleModal is a lightweight jQuery plugin that provides a simple
 * interface to create a modal dialog.
 *
 * The goal of SimpleModal is to provide developers with a cross-browser
 * overlay and container that will be populated with data provided to
 * SimpleModal.
 *
 * There are two ways to call SimpleModal:
 * 1) As a chained function on a jQuery object, like $('#myDiv').modal();.
 * This call would place the DOM object, #myDiv, inside a modal dialog.
 * Chaining requires a jQuery object. An optional options object can be
 * passed as a parameter.
 *
 * @example $('<div>my data</div>').modal({options});
 * @example $('#myDiv').modal({options});
 * @example jQueryObject.modal({options});
 *
 * 2) As a stand-alone function, like $.modal(data). The data parameter
 * is required and an optional options object can be passed as a second
 * parameter. This method provides more flexibility in the types of data
 * that are allowed. The data could be a DOM object, a jQuery object, HTML
 * or a string.
 *
 * @example $.modal('<div>my data</div>', {options});
 * @example $.modal('my data', {options});
 * @example $.modal($('#myDiv'), {options});
 * @example $.modal(jQueryObject, {options});
 * @example $.modal(document.getElementById('myDiv'), {options});
 *
 * A SimpleModal call can contain multiple elements, but only one modal
 * dialog can be created at a time. Which means that all of the matched
 * elements will be displayed within the modal container.
 *
 * SimpleModal internally sets the CSS needed to display the modal dialog
 * properly in all browsers, yet provides the developer with the flexibility
 * to easily control the look and feel. The styling for SimpleModal can be
 * done through external stylesheets, or through SimpleModal, using the
 * overlayCss and/or containerCss options.
 *
 * SimpleModal has been tested in the following browsers:
 * - IE 6, 7
 * - Firefox 2
 * - Opera 9
 * - Safari 3
 *
 * @name SimpleModal
 * @type jQuery
 * @requires jQuery v1.1.2
 * @cat Plugins/Windows and Overlays
 * @author Eric Martin (http://ericmmartin.com)
 * @version 1.1
 */
(function ($) {
	/*
	 * Stand-alone function to create a modal dialog.
	 *
	 * @param {string, object} data A string, jQuery object or DOM object
	 * @param {object} [options] An optional object containing options overrides
	 */
	$.modal = function (data, options) {
		return $.modal.impl.init(data, options);
	};

	/*
	 * Stand-alone close function to close the modal dialog
	 */
	$.modal.close = function () {
		// call close with the external parameter set to true
		$.modal.impl.close(true);
	};

	/*
	 * Chained function to create a modal dialog.
	 *
	 * @param {object} [options] An optional object containing options overrides
	 */
	$.fn.modal = function (options) {
		return $.modal.impl.init(this, options);
	};

	/*
	 * SimpleModal default options
	 *
	 * overlay: (Number:50) The overlay div opacity value, from 0 - 100
	 * overlayId: (String:'modalOverlay') The DOM element id for the overlay div
	 * overlayCss: (Object:{}) The CSS styling for the overlay div
	 * containerId: (String:'modalContainer') The DOM element id for the container div
	 * containerCss: (Object:{}) The CSS styling for the container div
	 * close: (Boolean:true) Show the default window close icon? Uses CSS class modalCloseImg
	 * closeTitle: (String:'Close') The title value of the default close link. Depends on close
	 * closeClass: (String:'modalClose') The CSS class used to bind to the close event
	 * persist: (Boolean:false) Persist the data across modal calls? Only used for existing
	            DOM elements. If true, the data will be maintained across modal calls, if false,
				the data will be reverted to its original state.
	 * onOpen: (Function:null) The callback function used in place of SimpleModal's open
	 * onShow: (Function:null) The callback function used after the modal dialog has opened
	 * onClose: (Function:null) The callback function used in place of SimpleModal's close
	 */
	$.modal.defaults = {
		overlay: 50,
		zIndexStart: 2000,
		overlayId: 'modalOverlay',
		overlayClass: 'modalOverlay',
		overlayCss: {},
		windowId: 'modalWindow',
		windowClass: 'modalWindow',
		windowCss: {},
		headerId: 'modalHeader',
		headerClass: 'modalHeader',
		headerCss: {},
		containerId: 'modalContainer',
		containerClass: 'modalContainer',
		containerCss: {},
		dataClass: 'modalData',
		windowTitle: '',
		windowTitleTag: 'h3',
		header: false,
		close: true,
		closeTitle: 'Close',
		closeClass: 'modalClose',
		appendTo: 'body',
		persist: false,
		onOpen: null,
		onShow: null,
		onClose: null
	};

	/*
	 * Main modal object
	 */
	$.modal.impl = {
		/*
		 * Modal dialog options
		 */
		opts: null,
		/*
		 * Contains the modal dialog elements and is the object passed
		 * back to the callback (onOpen, onShow, onClose) functions
		 */
		dialog: {},
		dialogs: [],
		/*
		 * Initialize the modal dialog
		 */
		init: function (data, options) {
			// don't allow multiple calls
//			if (this.dialog.data) {
//				return false;
//			}

			this.dialog = {};

			// merge defaults and user options
			this.opts = $.extend({}, $.modal.defaults, options);

			// determine how to handle the data based on its type
			if (typeof data == 'object') {
				// convert DOM object to a jQuery object
				data = data instanceof jQuery ? data : $(data);

				// if the object came from the DOM, keep track of its parent
				if (data.parent().parent().size() > 0) {
					this.dialog.parentNode = data.parent();

					// persist changes? if not, make a clone of the element
					if (!this.opts.persist) {
						this.dialog.original = data.clone(true);
					}
				}
			}
			else if (typeof data == 'string' || typeof data == 'number') {
				// just insert the data as innerHTML
				data = $('<div>').html(data);
			}
			else {
				// unsupported data type!
				if (console) {
					console.log('SimpleModal Error: Unsupported data type: ' + typeof data);
				}
				return false;
			}
			this.dialog.data = data.addClass(this.opts.dataClass);
			data = null;

			// create the modal overlay, container and, if necessary, iframe
			this.create();

			// display the modal dialog
			this.open();

			this.dialogs.push(this.dialog);

			// don't break the chain =)
			return this;
		},
		/*
		 * Create and add the modal overlay and container to the page
		 */
		create: function () {
			var zStart = this.opts.zIndexStart + (1000 * (this.dialogs.length || 0));
			// create the overlay
			this.dialog.overlay = $('<div>')
				.attr('id', this.opts.overlayId)
				.addClass(this.opts.overlayClass)
				.css($.extend(this.opts.overlayCss, {
					opacity: this.opts.overlay / 100,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					zIndex: zStart
				}))
				.hide()
				.appendTo(this.opts.appendTo);
			// create the window
			this.dialog.window = $('<div>')
				.attr('id', this.opts.windowId)
				.addClass(this.opts.windowClass)
				.css($.extend(this.opts.windowCss, {
//					overflow: 'hidden',
					position: 'fixed',
					zIndex: (zStart + 100)
				}))
				.hide()
				.appendTo(this.opts.appendTo);
			if (this.opts.header) {
				// create the header
				this.dialog.header = $('<div>')
					.attr('id', this.opts.headerId)
					.addClass(this.opts.headerClass)
					.css(this.opts.headerCss)
					.append(this.opts.windowTitle != ''
						? '<' + this.opts.windowTitleTag + '>'
							+ this.opts.windowTitle
							+ '</' + this.opts.windowTitleTag + '>'
						: '')
					.hide()
					.appendTo(this.dialog.window);
			}
			// create the container
			this.dialog.container = $('<div>')
				.attr('id', this.opts.containerId)
				.addClass(this.opts.containerClass)
				.css($.extend(this.opts.containerCss, {
					overflow: 'auto',
					height: '100%'
				}))
				.hide()
				.appendTo(this.dialog.window);
			if (this.opts.close) {
				this.dialog.close = $('<a></a>')
					.attr('id', this.opts.closeClass + (this.dialogs.length || 0))
					.attr('title', this.opts.closeTitle)
					.addClass(this.opts.closeClass)
					.css({
						position: 'absolute',
						zIndex: (zStart + 200)
					})
					.append('<span>' + this.opts.closeTitle + '</span>')
					.appendTo(this.opts.header
						? this.dialog.header
						: this.dialog.container);
			}
			// fix issues with IE and create an iframe
			if ($.browser.msie && ($.browser.version < 7)) {
				this.fixIE();
			}

			// hide the data and add it to the container
			this.dialog.container.append(this.dialog.data.hide());
		},
		/*
		 * Bind events
		 */
		bindEvents: function () {
			var modal = this;

			// bind the close event to any element with the closeClass class
			$(this.opts.close ? this.dialog.close : '.' + this.opts.closeClass).click(function (e) {
				e.preventDefault();
				modal.close();
			});
			$(document).unbind('keyup');
			$(document).keyup(function (e) {
				key = e == null ? event.keyCode : e.which;
				if (key == 27){ //Escape key
					modal.close();
				}
			});
		},
		/*
		 * Unbind events
		 */
		unbindEvents: function () {
			// remove the close event
			$(this.dialog.close).unbind('click');
		},
		/*
		 * Fix issues in IE 6
		 */
		fixIE: function () {
			var wHeight = $(document.body).height() + 'px';
			var wWidth = $(document.body).width() + 'px';

			// position hacks
			this.dialog.overlay.css({position: 'absolute', height: wHeight, width: wWidth});
			this.dialog.window.css({position: 'absolute'});

			// add an iframe to prevent select options from bleeding through
			this.dialog.iframe = $('<iframe src="javascript:false;">')
				.css($.extend(this.opts.iframeCss, {
					opacity: 0,
					position: 'absolute',
					height: wHeight,
					width: wWidth,
					zIndex: 1000,
					width: '100%',
					top: 0,
					left: 0
				}))
				.hide()
				.appendTo('body');
		},
		/*
		 * Open the modal dialog elements
		 * - Note: If you use the onOpen callback, you must "show" the
		 *         overlay and container elements manually
		 *         (the iframe will be handled by SimpleModal)
		 */
		open: function () {
			// display the iframe
			if (this.dialog.iframe) {
				this.dialog.iframe.show();
			}

			if ($.isFunction(this.opts.onOpen)) {
				// execute the onOpen callback
				this.opts.onOpen.apply(this, [this.dialog]);
			}
			else {
				// display the remaining elements
				this.dialog.overlay.show();
				this.dialog.window.show();
				if (this.dialog.header)
					this.dialog.header.show();
				this.dialog.container.show();
				this.dialog.data.show();
			}

			// bind default events
			this.bindEvents();

			// useful for adding events/manipulating data in the modal dialog
			if ($.isFunction(this.opts.onShow)) {
				this.opts.onShow.apply(this, [this.dialog]);
			}
		},
		/*
		 * Close the modal dialog
		 * - Note: If you use an onClose callback, you must remove the
		 *         overlay, container and iframe elements manually
		 *
		 * @param {boolean} external Indicates whether the call to this
		 *     function was internal or external. If it was external, the
		 *     onClose callback will be ignored
		 */
		close: function (external) {
			// prevent close when dialog does not exist
			if (!this.dialog.data) {
				return false;
			}
			if ($.isFunction(this.opts.onClose) && !external) {
				// execute the onClose callback
				this.opts.onClose.apply(this, [this.dialog]);
				this.finalClose();
			}
			else {
				this.finalClose();
			}
		},
		finalClose: function () {
			// if the data came from the DOM, put it back
			if (this.dialog.parentNode) {
				// save changes to the data?
				if (this.opts.persist) {
					// insert the (possibly) modified data back into the DOM
					this.dialog.data.hide().appendTo(this.dialog.parentNode);
				}
				else {
					// remove the current and insert the original,
					// unmodified data back into the DOM
					this.dialog.data.remove();
					this.dialog.original.appendTo(this.dialog.parentNode);
				}
			}
			else {
				// otherwise, remove it
				this.dialog.data.remove();
			}

			// remove the remaining elements
			this.dialog.container.remove();
			if (this.dialog.header)
				this.dialog.header.remove();
			this.dialog.window.remove();
			this.dialog.overlay.remove();
			if (this.dialog.iframe)
				this.dialog.iframe.remove();

			// remove the default events
			this.unbindEvents();

			this.dialogs.pop();

			if (this.dialogs.length > 0)
				this.dialog = this.dialogs[this.dialogs.length - 1];
			else
				this.dialog = {};
		}
	};
})(jQuery);