//$(function(){
//	$.ajaxLoader();
//});
(function ($){
	$.ajaxLoader = function (options){
		return $.ajaxLoader.impl.init(this,options);
	};
	$.ajaxLoader.show = function (){
		return $.ajaxLoader.impl.loading();
	};
	$.ajaxLoader.hide = function (){
		return $.ajaxLoader.impl.doneLoading();
	};
	$.ajaxLoader.bind = function (){
		return $.ajaxLoader.impl.bind();
	};
	$.ajaxLoader.unBind = function (){
		return $.ajaxLoader.impl.unBind();
	};

	$.ajaxLoader.defaults = {
		bind: true,
		bindings: 0,
		opacity: 100,
		overlayId: 'ajaxLoader',
		overlayClass: 'ajaxLoader',
		overlayBg: 'transparent',
		containerId: 'ajaxLoaderBox',
		containerClass: 'ajaxLoaderBox',
		imageId: 'ajaxLoaderImg',
		imageClass: 'ajaxLoaderImg',
		imageAlt: 'Loading...',
		imageSrc: '/images/gfx/ajax_load_indicator.gif',
		imageW: 16,
		imageH: 16,
		appendTo: '#pageAll',
		zIndex: 10000,
		preloadClass: 'ajaxLoaderPrelaod',
		preload: []
	};

	$.ajaxLoader.impl = {
		opts: {},
		overlay: {},
		div: {},
		preload: {},
		msg: {},
		image: {},
		init: function (data, options){
			this.opts = $.extend({}, $.ajaxLoader.defaults, options);
			this.overlay = $('<div/>')
				.attr('id', this.opts.overlayId)
				.addClass(this.opts.overlayClass)
				.css({
					opacity: this.opts.opacity / 100,
					cursor: 'wait',
					backgroundColor: this.opts.overlayBg,
					height: '100%',
					width: '100%',
					position: 'fixed',
					left: 0,
					top: 0,
					display: 'none',
					zIndex: this.opts.zIndex
				})
				.appendTo(this.opts.appendTo);
			this.div = $('<div/>')
				.attr('id', this.opts.containerId)
				.addClass(this.opts.containerClass)
				.css({
					position: 'fixed',
					right: '0',
					bottom: '0',
					textAlign: 'right',
					minHeight: this.opts.imageH,
					padding: '3px',
					paddingLeft: this.opts.imageW + 5,
					zIndex: (this.opts.zIndex + 1)
				})
				.appendTo(this.overlay);
			this.image = $('<img/>')
				.attr('id', this.opts.imageId)
				.attr('src', this.opts.imageSrc)
				.attr('alt', this.opts.imageAlt)
				.addClass(this.opts.imageClass)
				.css({
					position: 'absolute',
					left: '3px',
					top: '50%',
					marginTop: '-' + (this.opts.imageH / 2) + 'px'
				})
				.appendTo(this.div);
			this.msg = $('<span/>')
				.text(this.opts.imageAlt)
				.appendTo(this.div);
			this.preload = $('<div/>')
				.addClass(this.opts.preloadClass)
				.hide()
				.appendTo(this.overlay);
			for (var i in this.opts.preload){
				$('<img/>')
					.attr('src', this.opts.preload[i])
					.attr('alt', '')
					.addClass(this.opts.preloadClass)
					.appendTo(this.preload);
			}
			if (this.opts.bind)
				this.bind();
		},
		bind: function (){
			var a = this;
			if (a.bindings == 0){
				this.div.bind('ajaxStart', function (){
					a.loading();
				});
				this.div.bind('ajaxStop', function (){
					a.doneLoading();
				});
				this.div.bind('ajaxError', function (){
					a.doneLoading();
				});
			}
			a.bindings++;
		},
		unBind: function (){
			if (this.bindings == 1){
				this.div.unbind('ajaxStart');
				this.div.unbind('ajaxStop');
			}
			this.bindings--;
		},
		loading: function (){
			this.overlay.show();
		},
		doneLoading: function (){
			this.overlay.hide();
		}
	};
})(jQuery);