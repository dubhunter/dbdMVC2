(function ($){
	$.fileUpload = function (options){
		return $.fileUpload.impl.init(options);
	};
	$.fileUpload.checkProgress = function (){
		return $.fileUpload.impl.checkProgress();
	};
	$.fileUpload.start = function (form){
		return $.fileUpload.impl.start(form);
	};
	$.fileUpload.cancel = function (){
		return $.fileUpload.impl.cancel();
	};
	$.fileUpload.stop = function (){
		return $.fileUpload.impl.stop();
	};
	$.fileUpload.complete = function (script,href,errors){
		return $.fileUpload.impl.complete(script,href,errors);
	};

	$.fileUpload.defaults = {
		showLoader: true,
		showPercentBar: true,
		showPercent: true,
		showAvgSpeed: false,
		showTimeLeft: true,
		showCancel: true,
		interval: 600,
		progressUrl: '/index/uploadProgress/',
		idUploadKey: 'APC_UPLOAD_PROGRESS',
		idLoader: 'ajaxLoader',
		idProgressDiv: 'FUuploadProgress',
		idPercentBar: 'FUpercentBar',
		idPercent: 'FUpercent',
		idAvgSpeed: 'FUavgSpeed',
		idTimeLeft: 'FUtimeLeft',
		idCancel: 'FUcancel',
		autoUpload: true,
		classBound: 'FUbound',
		classNoAuto: 'FUnoAutoUpload',
		onChange: null,
		onStart: function (){
			$('.errorMsgs').remove();
		},
		onCancel: null,
		onStop: null,
		onComplete: function (script, href, errors){
			if (script){
				eval(script);
			}else if (href){
				if (errors){
					for (var e in errors){
						href += (href.indexOf('?') > 0 ? '&' : '?') + 'errors[]=' + errors[e];
					}
				}
				window.location=href;
			}else if (errors){
				href = window.location.href;
				href = href.replace(/\?.*/, '');
				for (var e in errors){
					href += (href.indexOf('?') > 0 ? '&' : '?') + 'errors[]=' + errors[e];
				}
				window.location=href;
			}
		}
	};

	$.fileUpload.impl = {
		opts: {},
		timer: null,
		input: null,
		iFrame: null,
		div: null,
		percentBar: null,
		percent: null,
		avgSpeed: null,
		timeLeft: null,
		cancelBtn: null,
		count: 0,
		init: function (options){
			var f = this;
			if (!f.div){
				f.opts = $.extend({}, $.fileUpload.defaults, options);
				f.div = $('<div></div>').attr('id', f.opts.idProgressDiv).css('display', 'none').appendTo($('#' + f.opts.idLoader));
				if (f.opts.showPercentBar)
					f.percentBar = $('<div></div>').attr('id', f.opts.idPercentBar).appendTo(f.div);
				if (f.opts.showPercent)
					f.percent = $('<span></span>').attr('id', f.opts.idPercent).appendTo(f.div);
				if (f.opts.showAvgSpeed)
					f.avgSpeed = $('<span></span>').attr('id', f.opts.idAvgSpee).appendTo(f.div);
				if (f.opts.showTimeLeft)
					f.timeLeft = $('<span></span>').attr('id', f.opts.idTimeLeft).appendTo(f.div);
				if (f.opts.showCancel){
					f.cancelBtn = $('<a></a>').attr('id', f.opts.idCancel).attr('href', '#').html('<span>Cancel</span>').appendTo(f.div);
					f.cancelBtn.click(function(e){
						e.preventDefault();
						f.cancel();
					});
				}
			}
			$('#' + f.opts.idUploadKey).val($('#' + f.opts.idUploadKey).val() + '-' + f.count);
			f.bind();
		},
		change: function (file){
			this.input = $(file);
			if ($.isFunction(this.opts.onChange)){
				this.opts.onChange.apply(this, [file]);
			}
			if (this.opts.autoUpload)
				$(file.form).not('.' + this.opts.classNoAuto).trigger('submit');
		},
		clear: function (){
			this.input.removeAttr('value');
		},
		start: function (form){
			if ($.isFunction(this.opts.onStart)){
				this.opts.onStart.apply(this, [form]);
			}
			this.resetProgress();
			this.show();
			this.checkProgress();
			this.iFrame = $('#' + form.target);
		},
		cancel: function (){
			this.stop();
			if ($.isFunction(this.opts.onCancel)){
				this.opts.onCancel.apply(this);
			}
		},
		stop: function (){
			clearTimeout(this.timer);
			this.hide();
			this.clear();
			this.iFrame.attr('src', 'about:blank');
			if ($.isFunction(this.opts.onStop)){
				this.opts.onStop.apply(this);
			}
		},
		complete: function (){
			var a = arguments;
			this.stop();
			if ($.isFunction(this.opts.onComplete)){
				this.opts.onComplete.apply(this, a);
			}
		},
		checkProgress: function (){
			var f = this;
			$.getJSON(f.opts.progressUrl, {id: $('#' + f.opts.idUploadKey).val()}, function (data){
				f.updateProgress(data);
			});
			f.timer = setTimeout('$.fileUpload.checkProgress()', f.opts.interval);
		},
		updateProgress: function (data){
			var f = this;
			if (f.percentBar)
				f.percentBar.css('width', data.percent + '%');
			if (f.percent)
				f.percent.text(data.percent + '%');
			if (f.timeLeft)
				f.timeLeft.text(data.timeLeft);
			if (f.avgSpeed)
				f.avgSpeed.text(data.avgSpeed);
		},
		resetProgress: function (){
			this.updateProgress({
				percent: 0,
				timeLeft: 'Calculating...',
				avgSpeed: ''
			});
			var id = $('#' + this.opts.idUploadKey).val();
			$('#' + this.opts.idUploadKey).val(id.substring(0, id.length - 1) + (this.count++));
		},
		show: function (){
			$.ajaxLoader.unBind();
			$.ajaxLoader.show();
			this.div.show();
		},
		hide: function (){
			this.div.hide();
			$.ajaxLoader.hide();
			$.ajaxLoader.bind();
		},
		bind: function (){
			var f = this;
			var $file = $(':file');
			$file.parents('form').not('.' + f.opts.classBound).submit(function (e){
				f.start(this);
			}).addClass(f.opts.classBound);
			$file.not('.' + f.opts.classBound).change(function (e){
				f.change(this);
			}).addClass(f.opts.classBound);
		}
	};
})(jQuery);