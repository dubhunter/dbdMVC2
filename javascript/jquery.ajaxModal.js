(function ($){
	$.fn.ajaxModal = function (opts){
		return $.ajaxModal.impl.init(this, opts);
	};
	$.ajaxModal = function (links, opts){
		return $.ajaxModal.impl.init($(links), opts);
	};
	$.ajaxModal.open = function (href, title, opts){
		return $.ajaxModal.impl.quickOpen(href, title, opts);
	};
	$.ajaxModal.refresh = function (){
		return $.ajaxModal.impl.refresh();
	};
	$.ajaxModal.replace = function (url){
		return $.ajaxModal.impl.replace(url);
	};
	$.ajaxModal.center = function (dialog){
		return $.ajaxModal.impl.center(dialog);
	};
	$.ajaxModal.bindCloseRefresh = function (){
		return $.ajaxModal.impl.bindCloseRefresh();
	};
	$.ajaxModal.getHref = function (){
		return $.ajaxModal.impl.getHref();
	};
	$.ajaxModal.defaults = {
		size: 'full',
		event: 'click',
		boundClass: 'ajaxModalBound',
		appendTo: '#pageAll',
		overlay: 80,
		header: true,
		windowTitle: "",
		windowHref: "",
		windowLinkElem: {},
		closeOther: false,
		autoCenter: false,
		autoCenterTop: 0.5,
		onOpen: modalEffect.fadeIn,
		onClose: modalEffect.fadeOut
	};
	$.ajaxModal.defaults.full = {
		overlayId: 'modalOverlay',
		overlayClass: 'modalOverlay',
		windowId: 'modalWindow',
		windowClass: 'modalWindow',
		headerId: 'modalHeader',
		containerId: 'modalContainer',
		dataClass: 'modalData'
	};
	$.ajaxModal.defaults.small = {
		overlayId: 'smallModalOverlay',
		overlayClass: 'smallModalOverlay',
		windowId: 'smallModalWindow',
		windowClass: 'smallModalWindow',
		headerId: 'smallModalHeader',
		containerId: 'smallModalContainer',
		dataClass: 'smallModalData'
	};
	$.ajaxModal.defaults.mini = {
		overlayId: 'miniModalOverlay',
		overlayClass: 'miniModalOverlay',
		windowId: 'miniModalWindow',
		windowClass: 'miniModalWindow',
		headerId: 'miniModalHeader',
		containerId: 'miniModalContainer',
		dataClass: 'miniModalData'
	};
	$.ajaxModal.impl = {
		modal: {},
		init: function (links, opts){
			var a = this;
			var o = this.setOpts(opts);
			links.not('.' + o.boundClass).not('.disabled').bind(o.event, function(e){
				e.preventDefault();
				if (this.href.indexOf('#') != -1)
					return a.modal;
				o.windowHref = this.href;
				o.windowTitle = this.title;
				o.windowLinkElem = this;
				a.open(o);
			}).addClass(o.boundClass);
			return this.modal;
		},
		setOpts: function (opts){
			var o = $.extend({}, $.ajaxModal.defaults, opts);
			switch (o.size)
			{
				case 'mini':
					o = $.extend({}, $.ajaxModal.defaults.mini, o);
					break;
				case 'small':
					o = $.extend({}, $.ajaxModal.defaults.small, o);
					break;
				case 'full':
				default:
					o = $.extend({}, $.ajaxModal.defaults.full, o);
					break;
			}
			return o;
		},
		quickOpen: function (href, title, opts){
			opts = this.setOpts(opts);
			opts.windowHref = href;
			opts.windowTitle = title;
			opts.windowLinkElem = {};
			this.open(opts);
			return this.modal;
		},
		open: function (o){
			var a = this;
			if (o.closeOther)
				$.modal.close();
			if (o.replaceOther && typeof a.modal.dialog != "undefined" && typeof a.modal.dialog.window != "undefined")
				return a.replace(o.windowHref);
			if (o.autoCenter && o.onOpen == $.ajaxModal.defaults.onOpen && !($.browser.msie && ($.browser.version < 7))){
				o.onOpen = function (dialog){
					modalEffect.fadeIn(dialog, function (){
						$(window).bind('resize', function (){
							$.ajaxModal.center(dialog);
						});
						$(window).resize();
					});
				}
			}
			$.get(o.windowHref, function(data){
				a.modal = $('<div>').html(data).modal(o);
				a.modal.dialog.window.bind('refresh', function (e){
					$.get(a.modal.opts.windowHref, function(data){
						a.modal.dialog.container.children('div').html(data);
						if ($.isFunction(a.modal.opts.onShow)) {
							a.modal.opts.onShow.apply(a.modal, [a.modal.dialog]);
						}
					});
				});
			});
		},
		refresh: function (){
			return this.modal.dialog.window.trigger('refresh');
		},
		replace: function (url){
			this.modal.opts.windowHref = url;
			return this.modal.dialog.window.trigger('refresh');
		},
		bindCloseRefresh: function (){
			if (this.modal.dialog.close.hasClass('refreshBound')){
				return false;
			}
			var a = this;
			var old = null;
			if ($.isFunction(this.modal.opts.onClose)){
				old = this.modal.opts.onClose;
			}
			this.modal.opts.onClose = function (dialog){
				if ($.isFunction(old)){
					old.apply(a.modal, [a.modal.dialog]);
				}
				window.location.reload();
			};
			this.modal.dialog.close.addClass('refreshBound');
		},
		getHref: function (){
			return this.modal.opts.windowHref;
		},
		center: function (d){
			if (d){
				var top = ($(window).height() - d.window.outerHeight()) * this.modal.opts.autoCenterTop;
				var pos = 'fixed';
				if (top < 0){
					$(document).scrollTop(0);
					top = $(document).scrollTop();
					pos = 'absolute';
				}
				d.window.css({
					top: top + 'px',
					position: pos
				});
			}
		}
	};
})(jQuery);