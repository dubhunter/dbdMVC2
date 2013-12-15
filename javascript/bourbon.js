/* Simple JavaScript Inheritance
 * By John Resig http://ejohn.org/
 * MIT Licensed.
 */
// Inspired by base2 and Prototype and Modifed by Will Mason (2011-04-03)
(function() {
	var constructing = false, fnTest = /xyz/.test(function() {xyz;}) ? /\bparent\b/ : /.*/;

	// The base Class implementation (does nothing)
	this.Class = function() {};

	// Create a new Class that inherits from this class
	Class.extend = function(prop) {
		var parent = this.prototype;

		// Instantiate a base class (but only create the instance,
		// don't run the init constructor)
		constructing = true;
		var prototype = new this();
		constructing = false;

		// Copy the properties over onto the new prototype
		for (var name in prop) {
			// Check if we're overwriting an existing function
			prototype[name] = typeof prop[name] == "function" &&
				typeof parent[name] == "function" && fnTest.test(prop[name]) ?
				(function(name, fn) {
					return function() {
						var tmp = this.parent;

						// Add a new ._parent() method that is the same method
						// but on the parent-class
						this.parent = parent[name];

						// The method only need to be bound temporarily, so we
						// destroy it when we're done executing
						var ret = fn.apply(this, arguments);
						this.parent = tmp;

						return ret;
					};
				})(name, prop[name]) :
				prop[name];
		}

		// The dummy class constructor
		function Class() {
			// All construction is actually done in the __construct method
			if ( !constructing && this.__construct ) {
				this.__construct.apply(this, arguments);
			}
		}

		// Populate our constructed prototype object
		Class.prototype = prototype;

		// Enforce the constructor to be what we expect
		Class.constructor = Class;

		// And make this class extendable
		Class.extend = arguments.callee;

		return Class;
	};
})();

//some string functions
String.prototype.ucfirst = function () {
	if (this.length > 0) {
		return this.substring(0, 1).toUpperCase() + this.substring(1);
	}
	return this;
};
String.prototype.lcfirst = function () {
	if (this.length > 0) {
		return this.substring(0, 1).toLowerCase() + this.substring(1);
	}
	return this;
};
String.prototype.escape = function () {
	var c = [
		['&', '&amp;'],
		['"', '&quot;'],
		['\'', '&#039;'],
		['<', '&lt;'],
		['>', '&gt;']
	];
	var s = this;
	for (var i in c) {
		s = s.replace(c[i][0], c[i][1]);
	}
	return s;
};
String.prototype.nl2br = function () {
	return this.replace(/\n/g, '<br />');
};
String.prototype.truncate = function (length, string) {
	if (this.length > length) {
		if (!string) string = '...';
		return this.substring(0, length - string.length) + string;
	}
	return this;
};

var JQB = 'bourbon-bound';

$.ajaxSetup({
//	cache: false,
	headers: {
		'x-requested-by': 'bMVC.js'
	},
	beforeSend: function (jqXHR) {
		bAjax.tag(jqXHR);
	},
	complete: function (jqXHR) {
		bAjax.release(jqXHR);
	}
});

$.global.cultures['default'].calendar.patterns.q = 'yyyy-MM-dd';
$.global.cultures['default'].calendar.patterns.qq = 'yyyy-MM-dd HH:mm:ss';

$.global.cultures['default'].calendar.patterns.Q = 'yyyy-MM-dd zzz';
$.global.cultures['default'].calendar.patterns.QQ = 'yyyy-MM-dd HH:mm:ss zzz';

Handlebars.registerHelper('view', function(data, name) {
	var ret = '';
	if (!$.isArray(data)) {
		data = [data];
	}
	for (var i = 0; i < data.length; i++) {
		ret += this.view.child(name, $.extend({}, data[i]));
	}
	return new Handlebars.SafeString(ret);
});

Handlebars.registerHelper('date', function(date, options) {
	return $.global.format(date instanceof Date ? date : $.global.parseDate(date), options.hash['format']);
});

Handlebars.registerHelper('time', function(date, options) {
	return $.global.format(date instanceof Date ? date : $.global.parseDate('1970-01-01 ' + date), options.hash['format']);
});

Handlebars.registerHelper('number', function(number, options) {
	return $.global.format(parseFloat(number), 'n' + options.hash['format']);
});

Handlebars.registerHelper('decimal', function(number, options) {
	return $.global.format(parseFloat(number), 'd' + options.hash['format']);
});

Handlebars.registerHelper('percentage', function(number, options) {
	return $.global.format(parseFloat(number), 'p' + options.hash['format']);
});

Handlebars.registerHelper('currency', function(number, options) {
	return $.global.format(parseFloat(number), 'c' + options.hash['format']);
});

Handlebars.registerHelper('timelength', function(date, options) {
	return $.timeLength(date, options.hash['short-names'], options.hash['lower']);
});

Handlebars.registerHelper('timepast', function(date, options) {
	return $.timePast(date, options.hash['short-names'], options.hash['lower']);
});

var bError = {
	NOT_FOUND: 404,
	INTERNAL: 500,
	ensure: function (expression, message) {
		if (!expression) {
			$.error(message);
		}
	}
};

var bourbon = function () {
	var _event = null;
	var _request = {};
	var _router = {};
	var _dispatcher = {};

	var _init = function (event, uri, method, data) {
		_event = event || _event;
		_request = new bRequest(uri, method, data);
		_router = new bRouter();
		_dispatcher = new bDispatcher();
	};

	var _dispatch = function () {
		_dispatcher.dispatch();
	};

	return {
		run: function (event, uri, method, data) {
			try{
				_init(event, uri, method, data);
				_dispatch();
			}catch(e) {
				throw e;
			}
		},
		getEvent: function () {
			return _event;
		},
		getRequest: function () {
			return _request;
		},
		getRouter: function () {
			return _router;
		}
	};
}();

var bRequest = Class.extend({
	_request_uri: null,
	_method: null,
	_params: {},
	_query: {},
	_post: {},
	__construct: function (uri, method, data) {
		this.setRequestURI(uri || window.location.pathname);
		this._method = method || 'get';
		this._params = {};
		this._post = data || {};
	},
	setRequestURI: function (uri) {
		this._request_uri = uri.replace(/\?.*$/, '');
		this._query = {};
		if (uri.indexOf('?') > 0) {
			var parts = uri.replace(/^.*\?/, '').split(/&/);
			for (var i = 0; i < parts.length; i++) {
				var tmp = parts[i].split(/=/);
				this._query[tmp[0]] = tmp[1];
			}
		}
		return this;
	},
	getRequestUri: function () {
		return this._request_uri;
	},
	getMethod: function () {
		return this._method;
	},
	set: function (key, value) {
		this._params[key] = value;
		return this;
	},
	get: function (key) {
		return this._params[key] || this._query[key] || this._post[key];
	},
	has: function (key) {
		return (typeof this._params[key] != 'undefined' && typeof this._query[key] != 'undefined' && typeof this._post[key] != 'undefined');
	},
	getQuery: function (key) {
		return key ? this._query[key] : this._query;
	},
	getPost: function (key) {
		return key ? this._post[key] : this._post;
	}
});

var bRouter = Class.extend({
	_request: {},
	_controller: null,
	_baseUrl: null,
	_params: {},
	__construct: function (request) {
		this._request = request || bourbon.getRequest();
		this._route();
		return this;
	},
	_route: function () {
		var uri = this._request.getRequestUri();
		for (var r in bRouter.routes) {
			var matches = XRegExp.exec(uri, XRegExp(r));
			if (matches) {
				this._controller = bRouter.routes[r];
				for (var k in matches) {
					if (isNaN(k) && !(k == 'index' || k == 'input')) {
						this.setParam(k, matches[k]);
					}
				}
				break;
			}
		}
		bError.ensure(this._controller, 'Could not route ' + uri);
		return this;
	},
	setParam: function (name, value) {
		this._params[name] = value;
	},
	unsetParam: function (name, value) {
		if (typeof this._params[name] != 'undefined') {
			delete this._params[name];
		}
		return this;
	},
	getController: function () {
		return this._controller;
	},
	getMethod: function () {
		return this._request.getMethod();
	},
	getParam: function (name) {
		if (typeof this._params[name] != 'undefined') {
			return this._params[name];
		} else {
			return this._request.get(name);
		}
	},
	getParams: function () {
		var params = this._params;
		//merge with request query
		params = $.arrayMerge(params, this._request.getQuery());
		//merge with request post
		params = $.arrayMerge(params, this._request.getPost());
		return params;
	},
	getUrl: function (get_params) {
		return this._request.getRequestUri();
	}
});
bRouter.routes = {};

var bDispatcher = Class.extend({
	_router: {},
	__construct: function (router) {
		this._router = router || bourbon.getRouter();
		return this;
	},
	_getController: function () {
		var controller = this._router.getController();
		bError.ensure(window[controller], 'Controller (' + controller + ') not found!');
		controller = window[controller];
		controller = new controller();
		return controller;
	},
	_getMethod: function (controller) {
		var method = this._router.getMethod();
		if (method == 'delete') {
			method = 'destroy'; //since delete is a javascript keyword
		}
		bError.ensure(typeof controller[method] === 'function', 'Method (' + method + ') could not be executed!');
		return method;
	},
	dispatch: function () {
		var controller = this._getController();
		var method = this._getMethod(controller);
		$.when(controller.promise()).done(function () {
			$.when(controller[method].call(controller)).done(function () {
				controller.autoExec();
			});
		});
		return this;
	}
});

var bAjax = function () {
	var _index = 0;
	var _jqXHRs = {};

	return {
		tag: function (jqXHR) {
			jqXHR.bAjax = _index++;
			_jqXHRs[jqXHR.bAjax] = jqXHR;
		},
		release: function (jqXHR) {
			delete _jqXHRs[jqXHR.bAjax];
		},
		abortAll: function () {
			for (var i in _jqXHRs) {
				_jqXHRs[i].abort();
			}
		}
	};
}();

var bApi = Class.extend({
	_host: null,
	__construct: function (host) {
		this._host = host || '';
		return this;
	},
	_request: function (endpoint, data, type, sync) {
		var $D = $.Deferred();
		$.ajax({
			async: !sync,
			url: this._host + endpoint,
			data: data || {},
			success: function (data, textStatus, jqXHR) {
				data = data || {};
				if (data.errors) {
					$D.reject(jqXHR, textStatus, data);
				} else {
					$D.resolve(data, textStatus, jqXHR);
				}
			},
			error: $D.reject,
			dataType: 'json',
			type: type || 'GET',
			xhrFields: {withCredentials: true}
		});
		return $D.promise();
	},
	get: function (endpoint, data) {
		return this._request(endpoint, data, 'GET');
	},
	post: function (endpoint, data) {
		return this._request(endpoint, data, 'POST');
	},
	put: function (endpoint, data) {
		return this._request(endpoint, data, 'PUT');
	},
	destroy: function (endpoint, data) {
		return this._request(endpoint, data, 'DELETE');
	},
	options: function (endpoint) {
		return this._request(endpoint, {}, 'OPTIONS');
	}
});

var bDataList = Class.extend({
	_list: [],
	__construct: function (list) {
		this.load(list);
		return this;
	},
	load: function (list) {
		this._list = list || [];
		return this;
	},
	get: function () {
		return this._list;
	},
	first: function () {
		return this._list.length ? this._list[0] : null;
	},
	last: function () {
		return this._list.length ? this._list[this._list.length - 1] : null;
	},
	append: function (data) {
		for (var i = 0; i < data.length; i++) {
			this.push(data[i]);
		}
		return this;
	},
	prepend: function (data) {
		for (var i = data.length - 1; i >= 0; i--) {
			this.unshift(data[i]);
		}
		return this;
	},
	push: function (data) {
		this._list.push(data);
		return this;
	},
	unshift: function (data) {
		this._list.unshift(data);
		return this;
	},
	merge: function (data, key) {
		var merged;
		for (var i = data.length - 1; i >= 0; i--) {
			merged = false;
			for (var j in this._list) {
				if (this._list[j][key] == data[i][key]) {
					this._list[j] = data[i];
					merged = true;
					break;
				}
			}
			if (!merged) {
				this._list.unshift(data[i]);
			}
		}
		return this;
	},
	replace: function (item, key) {
		for (var i in this._list) {
			if (this._list[i][key] == item[key]) {
				this._list[i] = item;
				break;
			}
		}
		return this;
	},
	remove: function (key, value) {
		for (var i in this._list) {
			if (this._list[i][key] == value) {
				this._list.splice(i, 1);
				break;
			}
		}
		return this;
	},
	sort: function (key, direction) {
		direction = direction || bDataList.SORT_ASC;
		this._list.sort(function compare(a,b) {
			if (a[key] == b[key]) {
				return 0;
			}
			if (a[key] < b[key]) {
				return (direction == bDataList.SORT_ASC ? -1 : 1);
			} else {
				return (direction == bDataList.SORT_ASC ? 1 : -1);
			}
		});
		return this;
	},
	length: function () {
		return this._list.length;
	}
});
bDataList.SORT_ASC = 1;
bDataList.SORT_DESC = -1;

var bView = Class.extend({
	name: null,
	template: null,
	html: null,
	node: null,
	data: {},
	children: [],
	__construct: function (name) {
		this.name = name;
		this.template = Handlebars.compile($('#' + this.name).html());
		bError.ensure(typeof this.template == 'function', 'View (' + this.name + ') not found!');
	},
	render: function (data){
		if (data) {
			this.data = data;
		}
		this.data.view = this;
		this.html = this.template(this.data);
		this.node = $(this.html).data('bView', this);
		this.init();
		for (var i = 0; i < this.children.length; i++) {
			this.children[i].node.replaceAll(this.node.find('style#' + bView.CHILD + i));
		}
		return this;
	},
	update: function (data) {
		var old = $.extend(true, {}, this.node);
		this.children = [];
		this.render(data).node.replaceAll(old);
	},
	child: function (name, data) {
		var i = this.children.length;
		this.children[i] = bView.view(name).render(data);
		return '<style id="' + bView.CHILD + i + '"></style>';
	},
	init: function () {}
});
bView.DEFAULT = 'bView';
bView.PREFIX = 'view';
bView.CHILD = 'bViewChild';
bView._views = {};
bView.templateName = function (name) {
	if (name.indexOf(bView.PREFIX) === 0) {
		return name;
	}
	return bView.PREFIX + name.ucfirst();
};
bView.view = function (name) {
	name = bView.templateName(name);
	if (!bView._views[name]) {
		var view = typeof window[name] == 'function' ? window[name] : window[bView.DEFAULT];
		bView._views[name] = new view(name);
	}
	return $.extend(true, {}, bView._views[name]);
};
bView.templateView = function (elem) {
	var view;
	if (typeof elem != 'object') {
		elem = $('#' + elem + ',.' + elem);
	}
	bError.ensure(elem.size(), 'View (' + elem + ') not found!');
	while (elem && !(view = elem.data('bView')) && (elem = elem.parent())) {}
	bError.ensure(typeof view == 'object', 'View invalid!');
	return view;
};
bView.get = function (name, data) {
	return bView.view(name).render(data).node;
};
bView.getHtml = function (name, data) {
	return bView.view(name).render(data).html;
};
bView.appendTo = function (name, target, data) {
	return bView.get(name, data).appendTo(target);
};
bView.prependTo = function (name, target, data) {
	return bView.get(name, data).prependTo(target);
};
bView.insertAfter = function (name, target, data) {
	return bView.get(name, data).insertAfter(target);
};
bView.insertBefore = function (name, target, data) {
	return bView.get(name, data).insertBefore(target);
};
bView.replaceAll = function (name, target, data) {
	return bView.get(name, data).replaceAll(target);
};
bView.replaceInto = function (name, target, data) {
	return bView.get(name, data).replaceInto(target);
};
bView.update = function (name, data, merge) {
	var view = bView.templateView(name);
	if (data) {
		if (merge) {
			view.data = $.extend({},  view.data, data);
		} else {
			view.data = data;
		}
	}
	return view.update();
};

var bRunner = function () {
	var _onces = {};
	var _timed = {};
	return {
		once: function (name, task, context) {
			if ($.isFunction(task)) {
				if (!_onces[name]) {
					_onces[name] = {
						deferred: $.Deferred(),
						started: false,
						running: false,
						failed: false,
						task: function () {
							_onces[name].started = true;
							_onces[name].running = true;
							$.when(task.call(context || window)).done(function () {
								_onces[name].running = false;
								_onces[name].deferred.resolveWith(task, arguments);
							}).fail(function () {
								_onces[name].running = false;
								_onces[name].failed = true;
								_onces[name].deferred.rejectWith(task, arguments);
							});
						}
					};
				}
				if (!_onces[name].started) {
					_onces[name].task();
				}
				return _onces[name].deferred.promise();
			}
			return false;
		},
		timed: function (group, name, interval, task, context, noFirstRun) {
			if (!_timed[group]) _timed[group] = {};
			if (arguments.length == 2 || arguments.length == 3 || $.isFunction(task)) {
				if (!_timed[group][name]) {
					_timed[group][name] = {
						deferred: $.Deferred(),
						firstRun: true,
						started: false,
						running: false,
						failed: false,
						timer: 0,
						interval: interval,
						task: function () {
							_timed[group][name].started = true;
							if (!(_timed[group][name].firstRun && noFirstRun)) {
								_timed[group][name].running = true;
								$.when(task.call(context || window)).done(function () {
									_timed[group][name].firstRun = false;
									_timed[group][name].running = false;
									_timed[group][name].timer = setTimeout(_timed[group][name].task, _timed[group][name].interval);
									_timed[group][name].deferred.resolveWith(task, arguments);
								}).fail(function () {
									_timed[group][name].firstRun = false;
									_timed[group][name].running = false;
									_timed[group][name].failed = true;
									_timed[group][name].timer = setTimeout(_timed[group][name].task, _timed[group][name].interval);
									_timed[group][name].deferred.rejectWith(task, arguments);
								});
							} else {
								_timed[group][name].timer = setTimeout(_timed[group][name].task, _timed[group][name].interval);
								_timed[group][name].firstRun = false;
							}
						}
					};
				}
				if (!_timed[group][name].started) {
					_timed[group][name].task();
				}else if (arguments.length == 3 && !_timed[group][name].running) {
					clearTimeout(_timed[group][name].timer);
					_timed[group][name].task();
				}
				return _timed[group][name].deferred.promiseForever();
			}
			return false;
		},
		clearTimed: function (group, name) {
			if (name) {
				if (_timed[group] && _timed[group][name] && _timed[group][name].started) {
					clearTimeout(_timed[group][name].timer);
					delete _timed[group][name];
				}
			}else if (group) {
				if (_timed[group]) {
					for (var name in _timed[group]) {
						if (_timed[group][name].started) {
							clearTimeout(_timed[group][name].timer);
						}
						delete _timed[group][name];
					}
				}
			} else {
				for (var group in _timed) {
					for (var name in _timed[group]) {
						if (_timed[group][name].started) {
							clearTimeout(_timed[group][name].timer);
						}
						delete _timed[group][name];
					}
				}
			}
		}
	};
}();

var bController = Class.extend({
	_router: null,
	_deferred: null,
	__construct: function () {
		this._router = bourbon.getRouter();
		this._deferred = $.Deferred();
		$.when(this._init()).done(this._deferred.resolve);
		return this;
	},
	_init: function () {},
	_forward: function (url) {
		var $D = $.Deferred();
		url = url || '/';
		if (url == window.location.pathname) {
			$(window).trigger('statechange');
		} else {
			History.pushState({}, '', url);
		}
		$D.reject();
		return $D.promise();
	},
	_setTitle: function (title) {
		document.title = title;
		return this;
	},
	_getTitle: function () {
		return document.title;
	},
	_getController: function () {
		return this._router.getController();
	},
	_getMethod: function () {
		return this._router.getMethod();
	},
	_setParam: function (name, value) {
		this._router.setParam(name, value);
		return this;
	},
	_unsetParam: function (name) {
		this._router.unsetParam(name);
		return this;
	},
	_getParam: function (name) {
		return this._router.getParam(name);
	},
	_getParams: function () {
		return this._router.getParams();
	},
	_getUrl: function (get_params, host) {
		//need to get host
		return (host ? window.location.protocol + '//' + window.location.host : '') + this._router.getUrl(get_params);
	},
	promise: function () {
		return this._deferred.promise();
	},
	autoExec: function () {
		//auto render?
//		$.log('bController.autoExec()');
	},
	get: function () {}
});

//$(function () {
//	$(window).bind('statechange', function (e) {
//		bourbon.run(e);
//	}).trigger('statechange');
//});
