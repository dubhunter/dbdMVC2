(function ($){
	$.fn.photoCrop = function (options){
		return $.photoCrop.impl.init(this, options);
	};
	$.photoCrop = function (img, options){
		return $.photoCrop.impl.init(img, options);
	};
	$.photoCrop.getWidth = function (){
		return $.photoCrop.impl.getWidth();
	};
	$.photoCrop.getHeight = function (){
		return $.photoCrop.impl.getHeight();
	};
	$.photoCrop.getPosition = function (){
		return $.photoCrop.impl.getPosition();
	};
	$.photoCrop.defaults = {
		containerClass: 'photoCropCon',
		containerColor: '#000000',
		containerWrap: false,
		idWidth: 'width',
		idHeight: 'height',
		idX: 'x',
		idY: 'y',
		imageClass: 'photoCropImage',
		imageOpacity: 0.4,
		windowClass: 'photoCropWindow',
		windowCursor: 'move',
		windowImageClass: 'photoCropWindowImage',
		resizeClass: 'photoCropResize',
		resizeNWClass: 'photoCropResizeNW',
		resizeNEClass: 'photoCropResizeNE',
		resizeSWClass: 'photoCropResizeSW',
		resizeSEClass: 'photoCropResizeSE',
		resizeNWCursor: 'nw-resize',
		resizeNECursor: 'ne-resize',
		resizeSWCursor: 'sw-resize',
		resizeSECursor: 'se-resize',
		resizeColor: '#ffffff',
		resizeSize: 10,
		resizeOffset: 0,
		resizeOpacity: 0.5,
		startWidth: 200,
		startHeight: 200,
		minWidth: -1,
		minHeight: -1,
		resizable: true,
		fixedRatio: false
	};
	$.photoCrop.impl = {
		opts: {},
		crop: {},
		form: {},
		init: function (img,options){
			var p = this;
			p.opts = $.extend({}, $.photoCrop.defaults, options);
			p.crop.img = img;
			p.crop.img.addClass(p.opts.imageClass);
			p.form.width = p.crop.img.parents('form').find('#' + p.opts.idWidth);
			p.form.height = p.crop.img.parents('form').find('#' + p.opts.idHeight);
			p.form.x = p.crop.img.parents('form').find('#' + p.opts.idX);
			p.form.y = p.crop.img.parents('form').find('#' + p.opts.idY);
			p.crop.con = $('<div/>')
				.addClass(p.opts.windowClass)
				.css({
					position: 'relative',
					overflow: 'hidden',
					backgroundColor: p.opts.containerColor
				}).appendTo(p.crop.img.parent());
			p.crop.img.remove().appendTo(p.crop.con);
			p.crop.win = $('<div/>')
				.addClass(p.opts.windowClass)
				.css({
					position: 'absolute',
					overflow: 'hidden',
					cursor: p.opts.windowCursor
				})
				.appendTo(p.crop.con);
			p.crop.winImg = p.crop.img.clone()
				.removeClass()
				.addClass(p.opts.containerClass)
				.css({
					position: 'absolute'
				})
				.appendTo(p.crop.win);
			p.crop.img.css({
					position: 'relative',
					opacity: p.opts.imageOpacity
				})
			p.crop.dragNW = $('<div/>')
				.addClass(p.opts.resizeClass)
				.addClass(p.opts.resizeClassNW)
				.css({
					position: 'absolute',
					width: p.opts.resizeSize + 'px',
					height: p.opts.resizeSize + 'px',
					opacity: p.opts.resizeOpacity,
					backgroundColor: p.opts.resizeColor,
					cursor: p.opts.resizeNWCursor
				})
				.appendTo(p.crop.con);
			p.crop.dragNE = $('<div/>')
				.addClass(p.opts.resizeClass)
				.addClass(p.opts.resizeClassNE)
				.css({
					position: 'absolute',
					width: p.opts.resizeSize + 'px',
					height: p.opts.resizeSize + 'px',
					opacity: p.opts.resizeOpacity,
					backgroundColor: p.opts.resizeColor,
					cursor: p.opts.resizeNECursor
				})
				.appendTo(p.crop.con);
			p.crop.dragSW = $('<div/>')
				.addClass(p.opts.resizeClass)
				.addClass(p.opts.resizeClassSW)
				.css({
					position: 'absolute',
					width: p.opts.resizeSize + 'px',
					height: p.opts.resizeSize + 'px',
					opacity: p.opts.resizeOpacity,
					backgroundColor: p.opts.resizeColor,
					cursor: p.opts.resizeSWCursor
				})
				.appendTo(p.crop.con);
			p.crop.dragSE = $('<div/>')
				.addClass(p.opts.resizeClass)
				.addClass(p.opts.resizeClassSE)
				.css({
					position: 'absolute',
					width: p.opts.resizeSize + 'px',
					height: p.opts.resizeSize + 'px',
					opacity: p.opts.resizeOpacity,
					backgroundColor: p.opts.resizeColor,
					cursor: p.opts.resizeSECursor
				})
				.appendTo(p.crop.con);
			if (p.opts.containerWrap){
				p.crop.con.css({
					width: p.crop.img.attr('width') + 'px',
					height: p.crop.img.attr('height') + 'px'
				});
			}
			p.opts.resizeOffset = p.opts.resizeSize / 2;
			p.opts.startRatio = p.opts.startWidth / p.opts.startHeight;
			p.crop.winPos = p.crop.win.position();
			p.crop.winDim = {width: p.crop.win.width(), height: p.crop.win.height()};
			p.setDims(p.opts.startWidth, p.opts.startHeight, 0, 0);
			p.bind();
		},
		bind: function (){
			var p = this;
			p.crop.win.draggable({
				containment: p.crop.con,
				start: function (e, ui){
					p.crop.dragNW.css({opacity: 1.0});
					p.crop.dragNE.css({opacity: 1.0});
					p.crop.dragSW.css({opacity: 1.0});
					p.crop.dragSE.css({opacity: 1.0});
				},
				stop: function (e, ui){
					p.crop.dragNW.css({opacity: p.opts.resizeOpacity});
					p.crop.dragNE.css({opacity: p.opts.resizeOpacity});
					p.crop.dragSW.css({opacity: p.opts.resizeOpacity});
					p.crop.dragSE.css({opacity: p.opts.resizeOpacity});
				},
				drag: function (e, ui){
					p.setDims(p.crop.win.width(), p.crop.win.height(), ui.position.left, ui.position.top);
				}
			});
			p.crop.dragNW.draggable({
				helper: 'clone',
				start: function (e, ui){
					p.crop.winPos = p.crop.win.position();
					p.crop.winDim = {width: p.crop.win.width(), height: p.crop.win.height()};
				},
				drag: function (e, ui){
					var pos = {
						left: ui.position.left + p.opts.resizeOffset,
						top: ui.position.top + p.opts.resizeOffset
					};
					pos.left = pos.left < 0 ? 0 : pos.left;
					pos.top = pos.top < 0 ? 0 : pos.top;
					var dims = {
						width: p.crop.winDim.width + p.crop.winPos.left - pos.left,
						height: p.crop.winDim.height + p.crop.winPos.top - pos.top
					};
					if (p.opts.minWidth > 0 && dims.width < p.opts.minWidth){
						dims.width = p.opts.minWidth;
						pos.left = p.crop.winPos.left + p.crop.winDim.width - dims.width;
					}
					if (p.opts.minHeight > 0 && dims.height < p.opts.minHeight){
						dims.height = p.opts.minHeight;
						pos.top = p.crop.winPos.top + p.crop.winDim.height - dims.height;
					}
					if (p.opts.fixedRatio){
						if (p.opts.startRatio > dims.width / dims.height){
							dims.height = dims.width / p.opts.startRatio;
							pos.top = p.crop.winPos.top + p.crop.winDim.height - dims.height;
						}else{
							dims.width = dims.height * p.opts.startRatio;
							pos.left = p.crop.winPos.left + p.crop.winDim.width - dims.width;
						}
					}
					p.setDims(
						dims.width,
						dims.height,
						pos.left,
						pos.top
					);
				}
			});
			p.crop.dragNE.draggable({
				helper: 'clone',
				start: function (e, ui){
					p.crop.winPos = p.crop.win.position();
					p.crop.winDim = {width: p.crop.win.width(), height: p.crop.win.height()};
				},
				drag: function (e, ui){
					var pos = {
						left: ui.position.left + p.opts.resizeOffset,
						top: ui.position.top + p.opts.resizeOffset
					};
					pos.left = pos.left > p.crop.img.width() ? p.crop.img.width() : pos.left;
					pos.top = pos.top < 0 ? 0 : pos.top;
					var dims = {
						width: pos.left - p.crop.winPos.left,
						height: p.crop.winDim.height + p.crop.winPos.top - pos.top
					};
					if (p.opts.minWidth > 0 && dims.width < p.opts.minWidth)
						dims.width = p.opts.minWidth;
					if (p.opts.minHeight > 0 && dims.height < p.opts.minHeight){
						dims.height = p.opts.minHeight;
						pos.top = p.crop.winPos.top + p.crop.winDim.height - dims.height;
					}
					if (p.opts.fixedRatio){
						if (p.opts.startRatio > dims.width / dims.height){
							dims.height = dims.width / p.opts.startRatio;
							pos.top = p.crop.winPos.top + p.crop.winDim.height - dims.height;
						}else{
							dims.width = dims.height * p.opts.startRatio;
						}
					}
					p.setDims(
						dims.width,
						dims.height,
						p.crop.winPos.left,
						pos.top
					);
				}
			});
			p.crop.dragSW.draggable({
				helper: 'clone',
				start: function (e, ui){
					p.crop.winPos = p.crop.win.position();
					p.crop.winDim = {width: p.crop.win.width(), height: p.crop.win.height()};
				},
				drag: function (e, ui){
					var pos = {
						left: ui.position.left + p.opts.resizeOffset,
						top: ui.position.top + p.opts.resizeOffset
					};
					pos.left = pos.left < 0 ? 0 : pos.left;
					pos.top = pos.top > p.crop.img.height() ? p.crop.img.height() : pos.top;
					var dims = {
						width: p.crop.winDim.width + p.crop.winPos.left - pos.left,
						height: pos.top - p.crop.winPos.top
					};
					if (p.opts.minWidth > 0 && dims.width < p.opts.minWidth){
						dims.width = p.opts.minWidth;
						pos.left = p.crop.winPos.left + p.crop.winDim.width - dims.width;
					}
					if (p.opts.minHeight > 0 && dims.height < p.opts.minHeight)
						dims.height = p.opts.minHeight;
					if (p.opts.fixedRatio){
						if (p.opts.startRatio > dims.width / dims.height){
							dims.height = dims.width / p.opts.startRatio;
						}else{
							dims.width = dims.height * p.opts.startRatio;
							pos.left = p.crop.winPos.left + p.crop.winDim.width - dims.width;
						}
					}
					p.setDims(
						dims.width,
						dims.height,
						pos.left,
						p.crop.winPos.top
					);
				}
			});
			p.crop.dragSE.draggable({
				helper: 'clone',
				start: function (e, ui){
					p.crop.winPos = p.crop.win.position();
					p.crop.winDim = {width: p.crop.win.width(), height: p.crop.win.height()};
				},
				drag: function (e, ui){
					var pos = {
						left: ui.position.left + p.opts.resizeOffset,
						top: ui.position.top + p.opts.resizeOffset
					};
					pos.left = pos.left > p.crop.img.width() ? p.crop.img.width() : pos.left;
					pos.top = pos.top > p.crop.img.height() ? p.crop.img.height() : pos.top;
					var dims = {
						width: pos.left - p.crop.winPos.left,
						height: pos.top - p.crop.winPos.top
					};
					if (p.opts.minWidth > 0 && dims.width < p.opts.minWidth)
						dims.width = p.opts.minWidth;
					if (p.opts.minHeight > 0 && dims.height < p.opts.minHeight)
						dims.height = p.opts.minHeight;
					if (p.opts.fixedRatio){
						if (p.opts.startRatio > dims.width / dims.height){
							dims.height = dims.width / p.opts.startRatio;
						}else{
							dims.width = dims.height * p.opts.startRatio;
						}
					}
					p.setDims(
						dims.width,
						dims.height,
						p.crop.winPos.left,
						p.crop.winPos.top
					);
				}
			});
		},
		unbind: function (){
			this.crop.win.draggable('destroy');
			this.crop.dragNW.draggable('destroy');
			this.crop.dragNE.draggable('destroy');
			this.crop.dragSW.draggable('destroy');
			this.crop.dragSE.draggable('destroy');
		},
		setDims: function (width, height, x, y){
			var p = this;
			p.crop.win.css({
					top: y + 'px',
					left: x + 'px',
					width: width + 'px',
					height: height + 'px'
				});
			p.crop.winImg.css({
					top: '-' + y + 'px',
					left: '-' + x + 'px'
				});
			p.crop.dragNW.css({
					top: (y - p.opts.resizeOffset) + 'px',
					left: (x - p.opts.resizeOffset) + 'px'
				});
			p.crop.dragNE.css({
					top: (y - p.opts.resizeOffset) + 'px',
					left: (x + width - p.opts.resizeOffset) + 'px'
				});
			p.crop.dragSW.css({
					top: (y + height - p.opts.resizeOffset) + 'px',
					left: (x - p.opts.resizeOffset) + 'px'
				});
			p.crop.dragSE.css({
					top: (y + height - p.opts.resizeOffset) + 'px',
					left: (x + width - p.opts.resizeOffset) + 'px'
				});
			if (p.form.width.size() > 0)
				p.form.width.val(width);
			if (p.form.height.size() > 0)
				p.form.height.val(height);
			if (p.form.x.size() > 0)
				p.form.x.val(x);
			if (p.form.y.size() > 0)
				p.form.y.val(y);
		},
		getWidth: function (){
			return this.crop.win.width();
		},
		getHeight: function (){
			return this.crop.win.height();
		},
		getPosition: function (){
			return this.crop.win.position();
		}
	};
})(jQuery);