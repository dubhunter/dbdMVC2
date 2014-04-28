(function ($){
	$.fileUpload = function (options, context, key){
		return $.fileUpload.impl.init(options, context, key);
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
		progressCallback: null,
		idUploadKey: 'PHP_SESSION_UPLOAD_PROGRESS',
		idLoader: 'ajaxLoader',
		idProgressDiv: 'FUuploadProgress',
		idProgressWrapper: 'FUwrapper',
		idPercentBar: 'FUpercentBar',
		classStats: 'FUstats',
		classPercent: 'FUpercent',
		classAvgSpeed: 'FUavgSpeed',
		classTimeLeft: 'FUtimeLeft',
		idCancel: 'FUcancel',
		autoUpload: true,
		classBound: 'FUbound',
		classNoAuto: 'FUnoAutoUpload',
		classIframe: 'hiddenIframe',
		textCancel: 'Cancel',
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
		context: {},
		timer: null,
		input: $(),
		iFrame: $(),
		div: $(),
		wrapper: $(),
		percentBar: $(),
		percent: $(),
		avgSpeed: $(),
		timeLeft: $(),
		cancelBtn: $(),
		count: 0,
		init: function (options, context, key){
			var f = this;
			f.opts = $.extend({}, $.fileUpload.defaults, options);
			f.context = $(context || window);
			if (f.context.find('#' + f.opts.idUploadKey).size()){
				f.context.find('#' + f.opts.idUploadKey).val(key || f.context.find('#' + f.opts.idUploadKey).val().replace(/-.+$/,'') + '-' + f.count);
				f.bind();
			}
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
			var p = {id: f.context.find('#' + f.opts.idUploadKey).val()};
			if (f.opts.progressCallback){
				f.opts.progressCallback(p).done(function (data){
					f.updateProgress(data);
				});

			}else{
				$.getJSON(f.opts.progressUrl, p).done(function (data){
					f.updateProgress(data);
				});
			}
			f.timer = setTimeout('$.fileUpload.checkProgress()', f.opts.interval);
		},
		updateProgress: function (data){
			var f = this;
			f.percentBar.css('width', data.percent + '%');
			f.percent.text(data.percent + '%');
			f.timeLeft.text(data.timeLeft);
			f.avgSpeed.text(data.avgSpeed);
		},
		resetProgress: function (){
			this.updateProgress({
				percent: 0,
				timeLeft: 'Calculating...',
				avgSpeed: ''
			});
			var id = this.context.find('#' + this.opts.idUploadKey).val();
			this.context.find('#' + this.opts.idUploadKey).val(id.substring(0, id.length - 1) + (this.count++));
		},
		show: function (){
			var f = this;
			$.ajaxLoader.unBind();
			$.ajaxLoader.show();
			if (f.div.size() == 0){
				f.div = $('<div/>').attr('id', f.opts.idProgressDiv).css('display', 'none').appendTo($('#' + f.opts.idLoader));
				f.wrapper = $('<div/>').attr('id', f.opts.idProgressWrapper).appendTo(f.div);
				var stats = $('<div/>').addClass(f.opts.classStats);
				if (f.opts.showPercent)
					$('<span></span>').addClass(f.opts.classPercent).appendTo(stats);
				if (f.opts.showAvgSpeed)
					$('<span></span>').addClass(f.opts.classAvgSpeed).appendTo(stats);
				if (f.opts.showTimeLeft)
					$('<span></span>').addClass(f.opts.classTimeLeft).appendTo(stats);
				stats.appendTo(f.wrapper);
				if (f.opts.showPercentBar){
					f.percentBar = $('<div/>').attr('id', f.opts.idPercentBar).appendTo(f.wrapper);
					stats.clone(true).appendTo(f.percentBar);
				}
				f.percent = f.div.find('.' + f.opts.classPercent);
				f.avgSpeed = f.div.find('.' + f.opts.classAvgSpeed);
				f.timeLeft = f.div.find('.' + f.opts.classTimeLeft);
				if (f.opts.showCancel){
					f.cancelBtn = $('<a></a>').attr('id', f.opts.idCancel).attr('href', '#').html('<span>' + f.opts.textCancel + '</span>').appendTo(f.wrapper);
					f.cancelBtn.click(function(e){
						e.preventDefault();
						f.cancel();
					});
				}
			}
			f.div.show();
		},
		hide: function (){
			this.div.hide();
			$.ajaxLoader.hide();
			$.ajaxLoader.bind();
		},
		bind: function (){
			var f = this;
			var $file = f.context.find(':file');
			var $form = $file.parents('form');
			$form.not('.' + f.opts.classBound).submit(function (e){
				f.start(this);
			}).addClass(f.opts.classBound);
			$file.not('.' + f.opts.classBound).change(function (e){
				f.change(this);
			}).addClass(f.opts.classBound);
			$form.each(function (){
				if (f.context.find('#' + this.target).size() == 0){
					$('<iframe/>')
						.attr('id', this.target)
						.attr('name', this.target)
						.attr('src', 'about:blank')
						.addClass(f.opts.classIframe)
						.appendTo(this);
				}
			});
		}
	};
})(jQuery);