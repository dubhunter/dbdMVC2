(function ($){
	$.fn.dropMenu = function (options){
		return $.fn.dropMenu.impl.init(this,options);
	};
	$.fn.dropMenu.defaults = {
		menuClass: 'dropMenu',
		titleClass: 'dropMenuTitle',
		titleOpenClass: 'dropMenuTitleOpen',
		listClass: 'dropMenuList',
		listOpenClass: 'dropMenuListOpen',
		titleDom: 'ul>li>div:first-child',
		listDom: 'ul>li>div+div',
		animate: false,
		animateSpeed: 300,
		bodyClose: true,
		hover: false
	};
	$.fn.dropMenu.impl = {
		opts: {},
		init: function (data,options){
			var menu = this;
			this.opts = $.extend({}, $.fn.dropMenu.defaults, options);
			data.addClass(this.opts.menuClass);
			anchor = data.find(this.opts.titleDom)
				.addClass(this.opts.titleClass)
				.find('a');
			if (this.opts.hover)
			{
				anchor.mouseover(function(e){menu.open(this, e)});
				anchor.click(function(e){menu.close(this, e)});
			}
			else
			{
				anchor.toggle(function(e){menu.open(this, e)}, function(e){menu.close(this, e)});
			}
			data.find(this.opts.listDom)
				.addClass(this.opts.listClass);
			this.show(data);
		},
		open: function(dom, e){
			var menu = this;
			if (!this.opts.hover)
				$('.' + this.opts.titleOpenClass).click();
			list = $(dom).addClass(this.opts.titleOpenClass)
				.parent('.' + this.opts.titleClass)
				.next('.' + this.opts.listClass)
				.addClass(this.opts.listOpenClass);
			this.show(list);
			if (this.opts.hover)
			{
				list.parent('li').hover(function(e){return false;}, function(e){
					$('.' + menu.opts.titleOpenClass).click();
					$(this).unbind('mouseover');
					$(this).unbind('mouseout');
				});
			}
			else
			{
				if (this.opts.bodyClose)
				{
					$('body').bind('click', function(e){
						$('.' + menu.opts.titleOpenClass).click();
						$(this).unbind('click');
					});
				}
				$('.' + this.opts.titleClass)
					.find('a')
					.mouseover(function(e){
						$(this).click().unbind('mouseover');
					});
				$('.' + this.opts.titleOpenClass).unbind('mouseover');
			}
		},
		close: function(dom, e){
			list = $(dom).removeClass(this.opts.titleOpenClass)
				.parent('.' + this.opts.titleClass)
				.next('.' + this.opts.listOpenClass)
				.removeClass(this.opts.listOpenClass);
			if (!this.opts.hover && !$('.' + this.opts.titleOpenClass).size())
			{
				$('.' + this.opts.titleClass)
					.find('a')
					.unbind('mouseover');
			}
			this.hide(list);
		},
		show: function(dom){
			switch (this.opts.animate)
			{
				case 'fade':
					$(dom).fadeIn(this.opts.animateSpeed);
					break;
				case 'slide':
					$(dom).slideDown(this.opts.animateSpeed);
					break;
				default:
					$(dom).show();
			}
		},
		hide: function(dom){
			switch (this.opts.animate)
			{
				case 'fade':
					$(dom).fadeOut(this.opts.animateSpeed);
					break;
				case 'slide':
					$(dom).slideUp(this.opts.animateSpeed);
					break;
				default:
					$(dom).hide();
			}
		}
	};
})(jQuery);