(function ($){
	$.audioRecorder = function (opts){
		return $.audioRecorder.impl.init(opts);
	};
	$.audioRecorder.record = function (){
		return $.audioRecorder.impl.record();
	};
	$.audioRecorder.stop = function (){
		return $.audioRecorder.impl.stop();
	};
	$.audioRecorder.play = function (){
		return $.audioRecorder.impl.play();
	};
	$.audioRecorder.pause = function (){
		return $.audioRecorder.impl.pause();
	};
	$.audioRecorder.resume = function (){
		return $.audioRecorder.impl.resume();
	};
	$.audioRecorder.settings = function (){
		return $.audioRecorder.impl.settings();
	};
	$.audioRecorder.updateStatus = function (code){
		return $.audioRecorder.impl.updateStatus(code);
	};
	$.audioRecorder.defaults = {
		idControl: 'audioRecorderControl',
		idFlash: 'audioRecorder',
		classRecord: 'arRecord',
		classStop: 'arStop',
		classPause: 'arPause',
		classPlay: 'arPlay',
		classSettings: 'arSettings',
		classDisabled: 'arDisabled',
		classBound: 'arBound',
		fileExists: false,
		onLoad: null,
		onConnect: null,
		onConnectFail: null,
		onMicDeny: null,
		onMicAllow: null,
		onMicActivity: null,
		beforeRecord: null,
		onRecord: null,
		onRecordStop: null,
		beforePlay: null,
		onPlay: null,
		onPlayTime: null,
		onPlayStop: null,
		onPlayEnd: null,
		onPlayPause: null,
		onPlayResume: null
	};
	$.audioRecorder.impl = {
		opts: {},
		console: {},
		status: {
			recording: false,
			playing: false
		},
		buttons: {
			record: {},
			stop: {},
			pause: {},
			play: {},
			settings: {}
		},
		init: function (opts){
			this.opts = $.extend({}, $.audioRecorder.defaults, opts);
			this.bind();
		},
		initConsole: function (){
			if ($.browser.msie)
				this.console = window[this.opts.idFlash];
			else
				this.console = document[this.opts.idFlash];
		},
		bind: function (){
			var a = this;
			a.initConsole();
			var $c = $('#' + a.opts.idControl);

			a.buttons.record = $('.' + a.opts.classRecord, $c).not('.' + a.opts.classBound).click(function (e){
				e.preventDefault();
				if (!a.isDisabled(this))
					a.record();
			}).addClass(a.opts.classBound);
			a.disableButton(a.buttons.record);

			a.buttons.stop = $('.' + a.opts.classStop, $c).not('.' + a.opts.classBound).click(function (e){
				e.preventDefault();
				a.stop();
			}).addClass(a.opts.classBound);
			a.disableButton(a.buttons.stop);

			a.buttons.play = $('.' + a.opts.classPlay, $c).not('.' + a.opts.classBound).click(function (e){
				e.preventDefault();
				if (!a.isDisabled(this))
					a.play();
			}).addClass(a.opts.classBound);
			a.disableButton(a.buttons.play);

			a.buttons.pause = $('.' + a.opts.classPause, $c).not('.' + a.opts.classBound).click(function (e){
				e.preventDefault();
				if (!a.isDisabled(this))
					a.pause();
			}).addClass(a.opts.classBound);
			a.disableButton(a.buttons.pause);

			a.buttons.settings = $('.' + a.opts.classSettings, $c).not('.' + a.opts.classBound).click(function (e){
				e.preventDefault();
				if (!a.isDisabled(this))
					a.settings();
			}).addClass(a.opts.classBound);
		},
		enableButton: function (b){
			if (this.isDisabled(b))
				$(b).removeClass(this.opts.classDisabled)
		},
		disableButton: function (b){
			if (!this.isDisabled(b))
				$(b).addClass(this.opts.classDisabled);
		},
		isDisabled: function (b){
			return $(b).hasClass(this.opts.classDisabled) || $(b).hasClass('disabled') ? true : false;
		},
		setFile: function (f){
			this.console.setAudio(f);
		},
		record: function (){
			if (this.console.micMuted()){
				this.settings();
			}else{
				if ($.isFunction(this.opts.beforeRecord)) {
					this.opts.beforeRecord.apply(this, []);
				}
				this.console.recAudio();
			}
		},
		stop: function (){
			this.console.stopAudio();
		},
		pause: function (){
			this.console.pauseAudio();
		},
		resume: function (){
			this.console.resumeAudio();
		},
		play: function (){
			if (this.status.playing){
				this.console.resumeAudio();
			}else{
				if ($.isFunction(this.opts.beforePlay)) {
					this.opts.beforePlay.apply(this, []);
				}
				this.console.playAudio();
			}
		},
		settings: function (){
			this.console.showSettings();
		},
		updateStatus: function (data){
			var a = this;
			if (!$.isArray(data))
				data = [data];
			switch (data[0]){
				case 'AudioRecorder.Init.Start':
					//onLoad
					if ($.isFunction(a.opts.onLoad)) {
						a.opts.onLoad.apply(a, data);
					}
					break;
				case 'AudioRecorder.Init.Stop':
					break;
				case 'Microphone.Muted':
					//onMicDeny
					a.stop();
					a.disableButton(a.buttons.record);
					a.disableButton(a.buttons.stop);
					a.disableButton(a.buttons.play);
					if ($.isFunction(a.opts.onMicDeny)) {
						a.opts.onMicDeny.apply(a, data);
					}
					break;
				case 'Microphone.Unmuted':
					//onMicAllow
					a.enableButton(a.buttons.record);
					if (a.opts.fileExists)
						a.enableButton(a.buttons.play);
					if ($.isFunction(a.opts.onMicAllow)) {
						a.opts.onMicAllow.apply(a, data);
					}
					break;
				case 'Microphone.Active':
					//onMicActivity
					if ($.isFunction(a.opts.onMicActivity)) {
						a.opts.onMicActivity.apply(a, data);
					}
					break;
				case 'NetConnection.Connect.Success':
					//onConnect
//					a.bind();
					a.enableButton(a.buttons.record);
					if (a.opts.fileExists)
						a.enableButton(a.buttons.play);
					if ($.isFunction(a.opts.onConnect)) {
						a.opts.onConnect.apply(a, data);
					}
					break;
				case 'NetConnection.Connect.Rejected':
					break;
				case 'NetConnection.Connect.Failed':
					break;
				case 'NetConnection.Connect.Closed':
					//onConnectFail
					a.disableButton(a.buttons.record);
					a.disableButton(a.buttons.stop);
					a.disableButton(a.buttons.play);
					if ($.isFunction(a.opts.onConnectFail)) {
						a.opts.onConnectFail.apply(a, data);
					}
					break;
				case 'NetStream.Publish.Start':
					break;
				case 'NetStream.Record.Start':
					//onRecord
					a.status.recording = true;
					a.disableButton(a.buttons.record);
					a.disableButton(a.buttons.play);
					a.enableButton(a.buttons.stop);
					if ($.isFunction(a.opts.onRecord)) {
						a.opts.onRecord.apply(a, data);
					}
					break;
				case 'NetStream.Record.Stop':
					break;
				case 'NetStream.Unpublish.Success':
					//onRecordStop
					a.opts.fileExists = true;
					a.status.recording = false;
					a.disableButton(a.buttons.stop);
					a.enableButton(a.buttons.play);
					a.enableButton(a.buttons.record);
					if ($.isFunction(a.opts.onRecordStop)) {
						a.opts.onRecordStop.apply(a, data);
					}
					break;
				case 'AudioRecorder.Record.Stop':
					break;
				case 'NetStream.Play.Reset':
					break;
				case 'NetStream.Play.Start':
					//onPlay
					a.status.playing = true;
					a.disableButton(a.buttons.record);
					a.disableButton(a.buttons.play);
					a.enableButton(a.buttons.stop);
					a.enableButton(a.buttons.pause);
					if ($.isFunction(a.opts.onPlay)) {
						a.opts.onPlay.apply(a, data);
					}
					break;
				case 'AudioRecorder.Play.Time':
					//onPlayTime
					if (data[2] == 0)
						a.stop();
					if ($.isFunction(a.opts.onPlayTime)) {
						a.opts.onPlayTime.apply(a, data);
					}
					break;
				case 'NetStream.Play.Stop':
					break;
				case 'NetStream.Buffer.Full':
					break;
				case 'NetStream.Buffer.Flush':
					break;
				case 'NetStream.Buffer.Empty':
					break;
				case 'AudioRecorder.Play.End':
					//onPlayEnd
					if ($.isFunction(a.opts.onPlayEnd)) {
						a.opts.onPlayEnd.apply(a, data);
					}
					break;
				case 'AudioRecorder.Play.Pause':
					a.enableButton(a.buttons.play);
					a.enableButton(a.buttons.stop);
					a.disableButton(a.buttons.pause);
					//onPlayPause
					if ($.isFunction(a.opts.onPlayPause)) {
						a.opts.onPlayPause.apply(a, data);
					}
					break;
				case 'AudioRecorder.Play.Resume':
					//onPlayResume
					if ($.isFunction(a.opts.onPlayResume)) {
						a.opts.onPlayResume.apply(a, data);
					}
					break;
				case 'AudioRecorder.Play.Stop':
					//onPlayStop
					a.status.playing = false;
					a.disableButton(a.buttons.stop);
					a.disableButton(a.buttons.pause);
					a.enableButton(a.buttons.play);
					a.enableButton(a.buttons.record);
					if ($.isFunction(a.opts.onPlayStop)) {
						a.opts.onPlayStop.apply(a, data);
					}
					break;
//				default:
//					$.log(data);
			}
		}
	};
})(jQuery);