(function($){
	$.extend({
		cssFile: function (f, m){
			$('<link>')
				.attr('href', f)
				.attr('type', 'text/css')
				.attr('rel', 'stylesheet')
				.attr('media', m || 'screen')
				.appendTo('head');
		},
		arrayMerge: function (){
			var a = {};
			var n = 0;
			var argv = $.arrayMerge.arguments;
			for (var i = 0; i < argv.length; i++){
				if ($.isArray(argv[i])){
					for (var j = 0; j < argv[i].length; j++){
						a[n++] = argv[i][j];
					}
					a = $.makeArray(a);
				} else {
					for (var k in argv[i]){
						if (isNaN(k)){
							var v = argv[i][k];
							if (typeof v == 'object' && a[k]){
								v = $.arrayMerge(a[k], v);
							}
							a[k] = v;
						} else {
							a[n++] = argv[i][k];
						}
					}
				}
			}
			return a;
		},
		arrayFilter: function (array, callback){
			var i, a = [];
			for (i in array){
				if (callback(array[i]))
					a.push(array[i]);
			}
			return a;
		},
		object: function (){
			var o = {};
			for (var i in arguments)
				o[arguments[i][0]] = arguments[i][1];
			return o;
		},
		count: function (arr){
			if (arr.length){
				return arr.length;
			} else {
				var n = 0;
				for (var k in arr) n++;
				return n;
			}
		},
		formatNumber: function (n){
			n = n || 0;
			var neg = false;
			if (n < 0){
				neg = true;
				n *= -1;
			}
			var s = '';
			n += '';
			for (var i = 0; i < n.length; i++){
				if (i > 0 && i % 3 == 0) s = ',' + s;
				s = n[n.length - i - 1] + s;
			}
			return (neg ? '-' : '' ) + s;

		},
		timePast: function (date, short_names, lower){
			var times = {
				sec: 1000,
				min: 60000,
				hrs: 3600000,
				day: 86400000,
				week: 604800000,
				mon: 2592000000,
				year: 31536000000
			};
			var names = {
				last: 'Last',
				ago: 'Ago',
				yesterday: 'Yesterday'
			};
			if (short_names){
				names.sec = 'Sec';
				names.min = 'Min';
				names.hrs = 'Hr';
				names.day = 'Day';
				names.week = 'Wk';
				names.mon = 'Mon';
				names.year = 'Yr';
			}else{
				names.sec = 'Second';
				names.min = 'Minute';
				names.hrs = 'Hour';
				names.day = 'Day';
				names.week = 'Week';
				names.mon = 'Month';
				names.year = 'Year';
			}
			date = date || new Date();
			var time = date instanceof Date ? date : $.global.parseDate(date),
				now = new Date(), diff, n, past;
			diff = now.getTime() - time.getTime();
			if (diff < 0) diff = 0;
			if (Math.floor(diff / times.day) > 0){
				time.setHours(0, 0, 0, 0);
				diff = now.getTime() - time.getTime();
			}
			switch (true){
				case ((n = Math.floor(diff / times.year)) > 0):
					past = n > 1 ? n + ' ' + names.year + 's ' + names.ago : names.last + ' ' + names.year;
					break;
				case ((n = Math.floor(diff / times.mon)) > 0):
					past = n > 1 ? n + ' ' + names.mon + 's ' + names.ago : names.last + ' ' + names.mon;
					break;
				case ((n = Math.floor(diff / times.week)) > 0):
					past = n > 1 ? n + ' ' + names.week + 's ' + names.ago : names.last + ' ' + names.week;
					break;
				case ((n = Math.floor(diff / times.day)) > 0):
					past = n > 1 ? n + ' ' + names.day + 's ' + names.ago : names.yesterday;
					break;
				case ((n = Math.floor(diff / times.hrs)) > 0):
					past = n + ' ' + names.hrs + (n > 1 ? 's' : '') + ' ' + names.ago;
					break;
				case ((n = Math.floor(diff / times.min)) > 0):
					past = n + ' ' + names.min + (n > 1 ? 's' : '') + ' ' + names.ago;
					break;
				default:
					past = Math.floor(diff / times.sec) + ' ' + names.sec + (n > 1 ? 's' : '') + ' ' + names.ago;
			}
			return lower ? past.toLowerCase() : past;
		},
		log: function (msg, forceAlert){
			if (window.console)
				console.log(msg);
			else if (forceAlert)
				alert(msg);
		}
	});
	$.fn.extend({
		replaceInto: function (target){
			return this.appendTo($(target).empty());
		},
		serializeAssoc: function (){
			var o = {
				aa: {},
				add: function (name, value){
					var tmp = name.match(/^(.*)\[([^\]]*)\]$/);
					if (tmp){
						var v = {};
						if (tmp[2])
							v[tmp[2]] = value;
						else
							v[$.count(v)] = value;
						this.add(tmp[1], v);
					}
					else if (typeof value == 'object'){
						if (typeof this.aa[name] != 'object'){
							this.aa[name] = {};
						}
						this.aa[name] = $.arrayMerge(this.aa[name], value);
					}
					else {
						this.aa[name] = value;
					}
				}
			};
			var a = $(this).serializeArray();
			for (var i = 0; i < a.length; i++){
				o.add(a[i].name, a[i].value);
			}
			return o.aa;
		}
	});


	var // Promise methods
		promiseMethods = "then done fail isResolved isRejected promise".split( " " ),
		// Static reference to slice
		sliceDeferred = [].slice;

	jQuery.extend({
		// Create a simple deferred (one callbacks list)
		_Deferred: function() {
			var // callbacks list
				callbacks = [],
				// stored [ context , args ]
				fired,
				// to avoid firing when already doing so
				firing,
				// flag to know if the deferred can be resolved infinitely
				infinite,
				// flag to know if the deferred has been cancelled
				cancelled,
				// the deferred itself
				deferred  = {

					// done( f1, f2, ...)
					done: function() {
						if ( !cancelled ) {
							var args = arguments,
								i,
								length,
								elem,
								type,
								_fired;
							if ( fired ) {
								_fired = fired;
								fired = 0;
							}
							for ( i = 0, length = args.length; i < length; i++ ) {
								elem = args[ i ];
								type = jQuery.type( elem );
								if ( type === "array" ) {
									deferred.done.apply( deferred, elem );
								} else if ( type === "function" ) {
									callbacks.push( elem );
								}
							}
							if ( _fired ) {
								deferred.resolveWith( _fired[ 0 ], _fired[ 1 ], _fired[ 2 ] );
							}
						}
						return this;
					},

					// resolve with given context and args
					resolveWith: function( context, args, start ) {
						if ( !cancelled && (!fired || infinite) && !firing ) {
							// make sure args are available (#8421)
							args = args || [];
							firing = 1;
							try {
								if (infinite){
									for (var i = start && start < callbacks.length ? start : 0; i < callbacks.length; i++){
										callbacks[ i ].apply( context, args );
									}
								}else{
									while( callbacks[ 0 ] ) {
										callbacks.shift().apply( context, args );
									}
								}
							}
							finally {
								fired = [ context, args, callbacks.length ];
								firing = 0;
							}
						}
						return this;
					},

					// resolve with this as context and given arguments
					resolve: function() {
						deferred.resolveWith( this, arguments );
						return this;
					},

					// Has this deferred been resolved?
					isResolved: function() {
						return !!( firing || fired );
					},

					// wmason - 2011/04/26
					// Infinite Resolutions
					infinite: function() {
						infinite = 1;
						return this;
					},

					// Cancel
					cancel: function() {
						cancelled = 1;
						callbacks = [];
						return this;
					}
				};

			return deferred;
		},

		// Full fledged deferred (two callbacks list)
		Deferred: function( func ) {
			var deferred = jQuery._Deferred(),
				failDeferred = jQuery._Deferred(),
				promise;
			// Add errorDeferred methods, then and promise
			jQuery.extend( deferred, {
				then: function( doneCallbacks, failCallbacks ) {
					deferred.done( doneCallbacks ).fail( failCallbacks );
					return this;
				},
				// wmason - 2011/04/26
				// 'then' like 'always' method
				// by Dan Heberden - http://danheberden.com/jquery-making-then-a-little-more-useful-2011-04-02/
				always: function( callbacks ) {
					return this.then( callbacks, callbacks );
				},
				fail: failDeferred.done,
				rejectWith: failDeferred.resolveWith,
				reject: failDeferred.resolve,
				isRejected: failDeferred.isResolved,
				// Get a promise for this deferred
				// If obj is provided, the promise aspect is added to the object
				promise: function( obj ) {
					if ( obj == null ) {
						if ( promise ) {
							return promise;
						}
						promise = obj = {};
					}
					var i = promiseMethods.length;
					while( i-- ) {
						obj[ promiseMethods[i] ] = deferred[ promiseMethods[i] ];
					}
					return obj;
				},
				// Get a promise for this deferred
				// If obj is provided, the promise aspect is added to the object
				promiseForever: function( obj ) {
					deferred.infinite();
					return this.promise( obj );
				}
			} );
			// Make sure only one callback list will be used
			deferred.done( failDeferred.cancel ).fail( deferred.cancel );
			// Unexpose cancel
			delete deferred.cancel;
			// Call given func if any
			if ( func ) {
				func.call( deferred, deferred );
			}
			return deferred;
		},

		// Deferred helper
		whenQueued: function(){
			var deferred,
				queue = [],
				run = function (queue, deferred){
					if (queue.length > 0){
						var f = queue.shift();
						jQuery.when($.isArray(f) ? f[1].call(f[0]) : f.call()).then(function (){
							jQuery.whenQueued(queue, deferred);
						}, deferred.reject);
					}else{
						deferred.resolve();
					}
				};
			if (arguments.length == 2 && jQuery.isArray(arguments[0]) && jQuery.isFunction(arguments[1].promise)){
				queue = arguments[0];
				deferred = arguments[1];
			}else{
				for (var i = 0; i < arguments.length; i++)
					queue[i] = arguments[i];
				deferred = jQuery.Deferred();
			}
			run(queue, deferred);
			return deferred.promise();
		}
	});
})(jQuery);