(function ($){
	$.fn.qtControl = function (opts){
		return $.qtControl.impl.init(this, opts);
	};
	$.qtControl = function (movie, opts){
		return $.qtControl.impl.init(movie, opts);
	};
	$.qtControl.play = function (){
		return $.qtControl.impl.play();
	};
	$.qtControl.pause = function (){
		return $.qtControl.impl.pause();
	};
	$.qtControl.stop = function (){
		return $.qtControl.impl.stop();
	};
	$.qtControl.getStatus = function (){
		return $.qtControl.impl.getStatus();
	};
	$.qtControl.getPlayerVersion = function (){
		return $.qtControl.impl.getPlayerVersion();
	};
	$.qtControl.getTime = function (){
		return $.qtControl.impl.getTime();
	};
	$.qtControl.setTime = function (sec){
		return $.qtControl.impl.setTime(sec);
	};
	$.qtControl.getDuration = function (){
		return $.qtControl.impl.getDuration();
	};
	$.qtControl.getVolume = function (){
		return $.qtControl.impl.getVolume();
	};
	$.qtControl.setVolume = function (vol){
		return $.qtControl.impl.setVolume(vol);
	};
	$.qtControl.loadURL = function (url){
		return $.qtControl.impl.loadURL(url);
	};
	$.qtControl.getBufferStatus = function (){
		return $.qtControl.impl.getBufferStatus();
	};
	$.qtControl.setFullscreen = function (){
		return $.qtControl.impl.setFullscreen();
	};
	$.qtControl.defaults = {
		buffer: 0,
		interval: 500,
		play: null,
		pause: null,
		stop: null,
		autoplay: false,
		onLoad: null,
		onPlay: null,
		onPause: null,
		onStop: null,
		onStatus: null,
//		poster: null,
		boundClass: 'qtControlBound'
	};
	$.qtControl.impl = {
		opts: {},
		controls: {
			play: {},
			pause: {},
			stop: {}
		},
		movie: null,
		timer: null,
		init: function (movie, opts){
			var qt = this;
			qt.movie = $(movie).get(0);
//			if (qt.movie.type != 'video/quicktime' || !qt.movie.GetPluginStatus){
			if (qt.movie.type != 'video/quicktime' && qt.movie.classid != 'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B'){
				qt.movie = null;
				return $(movie);
			}
			qt.opts = $.extend({}, $.qtControl.defaults, opts);
			if (qt.opts.play && qt.opts.pause && qt.opts.play == qt.opts.pause){
				qt.controls.play = qt.controls.pause = $(qt.opts.play).not('.' + qt.opts.boundClass).click(function (e){
						e.preventDefault();
						qt.isPlaying() ? qt.pause() : qt.play();
					}).addClass(qt.opts.boundClass);
			}
			else{
				if (qt.opts.play){
					qt.controls.play = $(qt.opts.play).not('.' + qt.opts.boundClass).click(function (e){
						e.preventDefault();
						qt.play();
					}).addClass(qt.opts.boundClass);
				}
				if (qt.opts.pause){
					qt.controls.pause = $(qt.opts.pause).not('.' + qt.opts.boundClass).click(function (e){
						e.preventDefault();
						qt.pause();
					}).addClass(qt.opts.boundClass);
				}
			}
			if (qt.opts.stop){
				qt.controls.stop = $(qt.opts.stop).not('.' + qt.opts.boundClass).click(function (e){
					e.preventDefault();
					qt.stop();
				}).addClass(qt.opts.boundClass);
			}
			qt.loop();
			if ($.isFunction(qt.opts.onLoad)){
				qt.opts.onLoad.apply(qt);
			}
			return this;
		},
		loop: function (){
			var qt = this;
			if (qt.movie && qt.getStatus() > 0){
				if (qt.opts.buffer != 0 && new String(qt.opts.buffer).indexOf('%') > 0){
					qt.opts.buffer = parseInt(qt.opts.buffer) / 100 * qt.getDuration();
				}
				if (!qt.isPlaying() && qt.opts.autoPlay && qt.getBufferStatus() / 100 * qt.getDuration() > qt.opts.buffer){
					qt.opts.autoPlay = false;
					qt.play();
				}
				if ($.isFunction(qt.opts.onStatus)){
					qt.opts.onStatus.apply(this);
				}
			}
			clearTimeout(qt.timer);
			qt.timer = setTimeout(function (){qt.loop();}, qt.opts.interval);
		},
		play: function (){
			if (this.movie && !this.isPlaying()){
				this.movie.Play();
				if ($.isFunction(this.opts.onPlay)){
					this.opts.onPlay.apply(this);
				}
			}
			return this;
		},
		pause: function (){
			if (this.movie && this.isPlaying()){
				this.movie.Stop();
				if ($.isFunction(this.opts.onPause)){
					this.opts.onPause.apply(this);
				}
			}
			return this;
		},
		stop: function (){
			if (this.movie){
				this.movie.Stop();
				this.movie.Rewind();
				this.opts.autoPlay = false;
				if ($.isFunction(this.opts.onStop)){
					this.opts.onStop.apply(this);
				}
			}
			return this;
		},
		isPlaying: function (){
			return this.getRate() == 1;
		},
		getStatus: function (){
			if (this.movie){
				switch (this.movie.GetPluginStatus()){
					case 'Waiting':
					case 'Loading':
						return 0;
					case 'Playable':
					case 'Complete':
						return 1;
					default:
						return -1;
				}
			}
			return -1;
		},
		getPlayerVersion: function (){
			if (this.movie){
				return this.movie.GetQuickTimeVersion();
			}
			return -1;
		},
		getRate: function (){
			if (this.movie){
				return this.movie.GetRate();
			}
			return -1;
		},
		setRate: function (rate){
			if (this.movie){
				this.movie.SetRate(rate);
			}
			return this;
		},
		getTime: function (){
			if (this.movie){
				return this.movie.GetTime() / this.movie.GetTimeScale();
			}
			return -1;
		},
		setTime: function (sec){
			if (this.movie){
				this.movie.SetTime(sec * this.movie.GetTimeScale());
			}
			return this;
		},
		getDuration: function (){
			if (this.movie){
				var duration = this.movie.GetDuration(); // returns time scale units
				if (duration == -1) return -1; // -1 returned if movie not fully initialized
				return duration / this.movie.GetTimeScale();
			}
			return -1;
		},
		getVolume: function (){
			if (this.movie){
				return parseInt(this.movie.GetVolume() * 100 / 255);
			}
			return 0;
		},
		setVolume: function (vol){
			if (this.movie){
				this.movie.SetVolume(255 * parseInt(vol) / 100);
			}
			return this;
		},
		loadURL: function (url){
			if (this.movie){
				this.movie.SetURL(url);
				this.movie.SetControllerVisible(false);
			}
			return this;
		},
		getBufferStatus: function (){
			if (this.movie){
				var timenowLoaded = this.movie.GetMaxTimeLoaded();
				var duration = this.movie.GetDuration();
				bufferStatus = parseInt(timenowLoaded * 100 / duration);
				return bufferStatus;
			}
			return -1;
		},
		setFullscreen: function (){
			// currently not supported
		},
		getFormattedTimeString: function(sec) {
			var timeString = "";
			var min = Math.floor(sec / 60);
			var hour = Math.floor(min / 60);
			sec = Math.floor(sec - (min * 60));
			min = Math.floor(min - (hour * 60));
			if (min < 10) min = "0" + min;
			if (sec < 10) sec = "0" + sec;
			if (hour > 0) timeString = hour+":";
			timeString += min+":"+sec;
			return timeString;
		}
	};
})(jQuery);