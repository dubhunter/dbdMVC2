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
		zIndex: 10000
	};

	$.ajaxLoader.impl = {
		opts: {},
		overlay: {},
		div: {},
		msg: {},
		image: {},
		init: function (data, options){
			this.opts = $.extend({}, $.ajaxLoader.defaults, options);
			this.overlay = $('<div>')
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
			this.div = $('<div>')
				.attr('id', this.opts.containerId)
				.addClass(this.opts.containerClass)
				.css({
					position: 'fixed',
					right: '0',
					bottom: '0',
					textAlign: 'right',
					display: 'none',
					minHeight: this.opts.imageH,
					padding: '3px',
					paddingLeft: this.opts.imageW + 5,
					zIndex: (this.opts.zIndex + 1)
				})
				.appendTo(this.overlay);
			this.image = $('<img>')
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
			this.msg = $('<span></span>')
				.text(this.opts.imageAlt)
				.appendTo(this.div);
			if (this.opts.bind)
				this.bind();
		},
		bind: function (){
			var a = this;
			this.div.bind('ajaxStart', function (){
				a.loading();
			});
			this.div.bind('ajaxStop', function (){
				a.doneLoading();
			});
		},
		unBind: function (){
			this.div.unbind('ajaxStart');
			this.div.unbind('ajaxStop');
		},
		loading: function (){
			this.overlay.show();
			this.div.show();
			this.msg.show();
			this.image.show();
		},
		doneLoading: function (){
			this.image.hide();
			this.msg.hide();
			this.overlay.hide();
		}
	};
})(jQuery);