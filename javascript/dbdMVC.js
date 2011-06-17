/* Simple JavaScript Inheritance
 * By John Resig http://ejohn.org/
 * MIT Licensed.
 */
// Inspired by base2 and Prototype and Modifed by Will Mason (2011-04-03)
(function(){
  var constructing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\bparent\b/ : /.*/;

  // The base Class implementation (does nothing)
  this.Class = function(){};

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
        (function(name, fn){
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
      if ( !constructing && this.__construct )
        this.__construct.apply(this, arguments);
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
String.prototype.ucfirst = function (){
	if (this.length > 0)
		return this.substring(0, 1).toUpperCase() + this.substring(1);
	return this;
};
String.prototype.lcfirst = function (){
	if (this.length > 0)
		return this.substring(0, 1).toLowerCase() + this.substring(1);
	return this;
};
String.prototype.nl2br = function (){
	return this.replace(/\n/g, '<br />');
};
String.prototype.truncate = function (length, string){
	if (this.length > length){
		if (!string) string = '...';
		return this.substring(0, length - string.length) + string;
	}
	return this;
};

var JQB = 'dbd-bound';

$.ajaxSetup({
//	cache: false,
	headers: {
		'x-requested-by': 'dbdMVC.js'
	},
	beforeSend: function (jqXHR){
		dbdAjax.tag(jqXHR);
	},
	complete: function (jqXHR){
		dbdAjax.release(jqXHR);
	}
});

$.global.cultures['default'].calendar.patterns.q = 'yyyy-MM-dd';
$.global.cultures['default'].calendar.patterns.qq = 'yyyy-MM-dd HH:mm:ss';

$.global.cultures['default'].calendar.patterns.Q = 'yyyy-MM-dd zzz';
$.global.cultures['default'].calendar.patterns.QQ = 'yyyy-MM-dd HH:mm:ss zzz';

$.tmpl.tag.view = {
	_default: { $2: "null" },
	open: "if($notnull_1){__=__.concat($item.nest(dbdView.tmpl($1),$2,dbdView.options($1)));}"
};

$.tmpl.tag.date = {
	_default: { $1: "new Date()", $2: "'d'" },
	open: "if($notnull_1){__.push($.encode($.global.format($1 instanceof Date ? $1 : $.global.parseDate($1), $2)));}"
};

$.tmpl.tag.number = {
	_default: { $1: "0", $2: "'0'" },
	open: "if($notnull_1){__.push($.encode($.global.format(parseFloat($1), 'n' + $2)));}"
};

$.tmpl.tag.decimal = {
	_default: { $1: "0", $2: "''" },
	open: "if($notnull_1){__.push($.encode($.global.format(parseFloat($1), 'd' + $2)));}"
};

$.tmpl.tag.percentage = {
	_default: { $1: "0", $2: "''" },
	open: "if($notnull_1){__.push($.encode($.global.format(parseFloat($1), 'p' + $2)));}"
};

$.tmpl.tag.currency = {
	_default: { $1: "0", $2: "''" },
	open: "if($notnull_1){__.push($.encode($.global.format(parseFloat($1), 'c' + $2)));}"
};

$.tmpl.tag.timepast = {
	_default: { $1: "new Date()", $2: "''" },
	open: "if($notnull_1){__.push($.encode($.timePast($1, $2)));}"
};

var dbdError = {
	NOT_FOUND: 404,
	INTERNAL: 500,
	ensure: function (expression, message){
		if (!expression){
			$.error(message);
//			throw error;
//			var e = ;
//			e.message = message;
//			throw new Error(message);
		}
	}
};

var dbdMVC = function (){
	var _event = null;
	var _request = {};
	var _router = {};
	var _dispatcher = {};

	var _init = function (event, uri, data){
		_event = event || null;
		_request = new dbdRequest(uri, data);
		_router = new dbdRouter();
		_dispatcher = new dbdDispatcher();
		var defaultError = window.onerror;
	};

	var _dispatch = function (){
		_dispatcher.dispatch();
	};

	return {
		run: function (event, uri, data){
			try{
				_init(event, uri, data);
				_dispatch();
			}catch(e){
					throw e;
			}
		},
		getEvent: function (){
			return _event;
		},
		getRequest: function (){
			return _request;
		},
		getRouter: function (){
			return _router;
		}
	};
}();

var dbdURI = function (){
	var _controller = null;
	var _action = null;
	var _params = {};
	var _controller_position_lists = {};
	var _position_lists = {};
	return {
		set: function (uri){
			uri = uri.indexOf('#!') !== false ? uri.replace(/^\/?\#\!/, '') : '';
			_params = [];
			var i = 0;
			var parts = uri.replace(/(^\/|\/$)/g, '').split(/\//);
			_controller = parts.shift();
			var cplist = dbdURI.getControllerPositionList(_controller);
			for (i = 0; i < cplist.length; i++){
				if (parts.length == 0 || !parts[0].match(/^[0-9]+$/))
					break;
				dbdURI.setParam(cplist[i], parts.shift());
			}
			_action = parts.shift();
			var plist = dbdURI.getPositionList(_controller, _action);
			for (i = 0; i < plist.length; i++){
				if (parts[0] && parts[0] != plist[i])
					dbdURI.setParam(plist[i], parts.shift());
			}
			for (i = 0; i < parts.length; i += 2){
				if (parts[i + 1])
					dbdURI.setParam(parts[i], parts[i + 1]);
			}
		},
		setParam: function (name, value){
			var tmp = name.match(/^(.*)\[([^\]]*)\]$/);
			if (tmp){
				var v = [];
				if (tmp[2])
					v[tmp[2]] = value;
				else
					v = [value];
				dbdURI.setParam(tmp[1], v);
			}else if ($.isArray(value)){
				if (!_params[name])
					_params[name] = [];
				_params[name] = $.arrayMerge(_params[name], value);
			}else{
				_params[name] = value;
			}
		},
		setControllerPositionList: function (controller, params){
			_controller_position_lists[controller] = params || [];
		},
		getControllerPositionList: function (controller){
			return _controller_position_lists[controller] ? _controller_position_lists[controller] : [];
		},
		setPositionList: function (controller, action, params){
			if (!_position_lists[controller])
				_position_lists[controller] = [];
			_position_lists[controller][action] = params || [];
		},
		getPositionList: function (controller, action){
			return _position_lists[controller] && _position_lists[controller][action] ? _position_lists[controller][action] : [];
		},
		getController: function (){
			return _controller;
		},
		getAction: function (){
			return _action;
		},
		getParams: function (){
			return _params;
		},
		create: function (controller, action, args, external){
			args = args || {};
			var i = 0;
			var uri = '/';
			if (!external) uri = '/#!' + uri;
			if (controller){
				uri += controller + '/';
				if ($.isArray(args)){
					var tmp = args;
					args = {};
					for (i in tmp)
						args[tmp[i][0]] = tmp[i][1];
				}
				var cplist = dbdURI.getControllerPositionList(controller);
				for (i = 0; i < cplist.length; i++){
					if (args[cplist[i]]){
						uri += args[cplist[i]].replace(/\//, '%5C/') + '/';
						delete args[cplist[i]];
					}
				}
				if (!action)
					action = dbdDispatcher.DEFAULT_ACTION;
				if (!($.count(args) == 0 && action == dbdDispatcher.DEFAULT_ACTION))
					uri += action + '/';
				var plist = dbdURI.getPositionList(controller, action);
				for (i = 0; i < plist.length; i++){
					if (args[plist[i]]){
						uri += args[plist[i]].replace(/\//, '%5C/') + '/';
						delete args[plist[i]];
					}
				}
				var braces = false;
				for (var k in args){
					var v = args[k];
					if (v === '' || v === null)
						return;
					if ($.isArray(v)){
						braces = true;
					}else{
						braces = false;
						v = [v];
					}
					for (var k2 in v){
						var v2 = v[k2];
						uri += k + (braces ? '%5B' + (isNaN(k2) ? k2 : '') + '%5D' : '') + '/' + v2 + '/';
					}
				}
			}
			return uri;
		},
		replace: function (baseUri, controller, action, args, external){
			args = args || [];
			if (baseUri)
				dbdURI.set(baseUri);
			if (!controller)
				controller = dbdURI.getController();
			if (!action)
				action = dbdURI.getAction();
			if ($.isArray(args)){
				for (var i in args){
					dbdURI.setParam(args[i][0], args[i][1]);
				}
			}else{
				for (var k in args){
					dbdURI.setParam(k, args[k]);
				}
			}
			var params = dbdURI.getParams();
			return dbdURI.create(controller, action, params, external);
		}
	};
}();


var dbdRequest = Class.extend({
	_request_uri: null,
	_params: {},
	_query: {},
	_post: {},
	__construct: function (uri, data){
		this.setRequestURI(uri || window.location.hash);
		this._post = data || {};
	},
	setRequestURI: function (uri){
		this._request_uri = uri.replace(/\?.*$/, '');
		if (uri.indexOf('?') > 0){
			var parts = uri.replace(/^.*\?/, '').split(/&/);
			for (var i = 0; i < parts.length; i++){
				var tmp = parts[i].split(/=/);
				this._query[tmp[0]] = tmp[1];
			}
		}
		return this;
	},
	getRequestURI: function (){
		return this._request_uri;
	},
	set: function (key, value){
		this._params[key] = value;
		return this;
	},
	get: function (key){
		return this._params[key] || this._query[key] || this._post[key];
	},
	getQuery: function (key){
		return key ? this._query[key] : this._query;
	},
	getPost: function (key){
		return key ? this._post[key] : this._post;
	},
	has: function (key){
		return (typeof this._params[key] != 'undefined' && typeof this._query[key] != 'undefined' && typeof this._post[key] != 'undefined');
	}
});

var dbdRouter = Class.extend({
	_request: {},
	_controller: null,
	_action: null,
	_baseUrl: null,
	_params: {},
	__construct: function (request){
		this._request = request || dbdMVC.getRequest();
		this._parseRequest()._buildURL();
		return this;
	},
	_parseRequest: function (){
		dbdURI.set(this._request.getRequestURI());
		this._controller = dbdURI.getController();
		this._action = dbdURI.getAction();
		var params = dbdURI.getParams();
		for (var k in params)
			this.setParam(k, params[k]);
		return this;
	},
	_buildURL: function (){
		this._baseUrl = dbdURI.create(this._controller, this._action);
		return this;
	},
	setController: function (controller){
		this._controller = controller;
		this._buildURL();
		return this;
	},
	setAction: function (action){
		this._action = action;
		this._buildURL();
		return this;
	},
	setParam: function (name, value){
		this._params[name] = value;
	},
	unsetParam: function (name, value){
		if (typeof this._params[name] != 'undefined')
			delete this._params[name];
		return this;
	},
	getController: function (){
		return this._controller;
	},
	getAction: function (){
		return this._action;
	},
	getParam: function (name){
		if (typeof this._params[name] != 'undefined')
			return this._params[name];
		else
			return this._request.get(name);
	},
	getParams: function (){
		var params = this._params;
		//merge with request query
		params = $.arrayMerge(params, this._request.getQuery());
		//merge with request post
		params = $.arrayMerge(params, this._request.getPost());
		return params;
	},
	getURL: function (get_params){
		if (get_params)
			return dbdURI.create(this.getController(), this.getAction(), this.getParams());
		return this._baseUrl;
	}
});

var dbdDispatcher = Class.extend({
	_router: {},
	__construct: function (router){
		this._router = router || dbdMVC.getRouter();
		return this;
	},
	_getController: function (){
		var controller = this._router.getController();
		if (!controller)
			controller = this._router.setController(dbdDispatcher.DEFAULT_CONTROLLER).getController();
		controller = controller.ucfirst();
		if (!window[controller] && window[dbdDispatcher.FALLBACK_CONTROLLER])
			controller = dbdDispatcher.FALLBACK_CONTROLLER;
		dbdError.ensure(window[controller], 'Controller (' + controller + ') not found!');
		controller = window[controller];
		controller = new controller();
		return controller;
	},
	_getAction: function (controller){
		var action = this._router.getAction();
		if (!action)
			action = this._router.setAction(dbdDispatcher.DEFAULT_ACTION).getAction();
		var method = dbdDispatcher.ACTION_PREFIX + action.ucfirst();
		if (typeof controller[method] !== 'function'){
			var magic = '__' + dbdDispatcher.ACTION_PREFIX + dbdDispatcher.MAGIC_ACTION.ucfirst();
			if (typeof controller[magic] !== 'function'){
				dbdError.ensure(false, 'Action (' + action + ') could not be executed!');
			}
			method = magic;
		}
		action = method;
		return action;
	},
	dispatch: function (){
		var controller = this._getController();
		var action = this._getAction(controller);
		$.when(controller.promise()).done(function (){
			$.when(controller[action].call(controller)).done(function (){
				controller.autoExec();
			});
		});
		return this;
	}
});
dbdDispatcher.FALLBACK_CONTROLLER = 'dbdEmptyController';
dbdDispatcher.ERROR_CONTROLLER = 'dbdError';
dbdDispatcher.DEFAULT_CONTROLLER = 'index';
dbdDispatcher.DEFAULT_ACTION = 'default';
dbdDispatcher.MAGIC_ACTION = 'action';
dbdDispatcher.ACTION_PREFIX = 'do';

var dbdAjax = function (){
	var _index = 0;
	var _jqXHRs = {};

	return {
		tag: function (jqXHR){
			jqXHR.dbdAjax = _index++;
			_jqXHRs[jqXHR.dbdAjax] = jqXHR;
		},
		release: function (jqXHR){
			delete _jqXHRs[jqXHR.dbdAjax];
		},
		abortAll: function (){
			for (var i in _jqXHRs)
				_jqXHRs[i].abort();
		}
	};
}();

var dbdApi = Class.extend({
	_host: null,
	__construct: function (host){
		this._host = host || '';
		return this;
	},
	_request: function (endpoint, data, type, sync){
		var $D = $.Deferred();
		$.ajax({
			async: !sync,
			url: this._host + endpoint,
			data: data || {},
			success: function (data, textStatus, jqXHR){
				data = data || {};
				if (data.errors)
					$D.reject(jqXHR, textStatus, data);
				else
					$D.resolve(data, textStatus, jqXHR);
			},
//			success: $D.resolve,
			error: $D.reject,
			dataType: 'json',
			type: type || 'GET',
			xhrFields: {withCredentials: true}
		});
		return $D.promise();
	},
	get: function (endpoint, data){
		return this._request(endpoint, data, 'GET');
	},
	post: function (endpoint, data){
		return this._request(endpoint, data, 'POST');
	},
	put: function (endpoint, data){
		return this._request(endpoint, data, 'PUT');
	},
	destroy: function (endpoint, data){
		return this._request(endpoint, data, 'DELETE');
	},
	options: function (endpoint){
		return this._request(endpoint, {}, 'OPTIONS', true);
	}
});

var dbdDataList = Class.extend({
	_list: [],
	__construct: function (list){
		this.load(list);
		return this;
	},
	load: function (list){
		this._list = list || [];
		return this;
	},
	get: function (){
		return this._list;
	},
	first: function (){
		return this._list.length ? this._list[0] : null;
	},
	last: function (){
		return this._list.length ? this._list[this._list.length - 1] : null;
	},
	push: function (data){
		for (var i = 0; i < data.length; i++)
			this._list.push(data[i]);
		return this;
	},
	unshift: function (data){
		for (var i = data.length - 1; i >= 0; i--)
			this._list.unshift(data[i]);
		return this;
	},
	replace: function (item, key){
		for (var i in this._list){
			if (this._list[i][key] == item[key]){
				this._list[i] = item;
				break;
			}
		}
		return this;
	},
	remove: function (key, value){
		for (var i in this._list){
			if (this._list[i][key] == value){
				this._list.splice(i, 1);
				break;
			}
		}
		return this;
	},
	length: function (){
		return this._list.length;
	}
});

var dbdView = Class.extend({
	tmpl: null,
	tmplItem: null,
	options: null,
	__construct: function (name){
		this.tmpl = $('#' + name).template();
		dbdError.ensure(typeof this.tmpl == 'function', 'View (' + name + ') not found!');
		var that = this;
		this.options = {
			rendered: function (tmplItem){
				that.tmplItem = tmplItem;
				that.init.call(that, tmplItem);
			}
		};
	},
	node: function (i){
		return $(this.tmplItem.nodes[i || 0]);
	},
	init: function (){}
});
dbdView.DEFAULT = 'dbdView';
dbdView.PREFIX = 'view';
dbdView._views = {};
//dbdView._tmplItems = {};
dbdView.tmplName = function (name){
	return dbdView.PREFIX + name.ucfirst();
};
dbdView.view = function (name){
	name = dbdView.tmplName(name);
	if (!dbdView._views[name]){
		var view = typeof window[name] == 'function' ? window[name] : window[dbdView.DEFAULT];
		dbdView._views[name] = new view(name);
	}
	return dbdView._views[name];
};
dbdView.tmpl = function (name){
	return dbdView.view(name).tmpl;
};
dbdView.options = function (name){
	return dbdView.view(name).options;
};
dbdView.tmplItem = function (name){
//	if (!dbdView._tmplItems[name]){
//		dbdError.ensure($('#' + name + ',.' + name).size(), 'View (' + name + ') not found!');
//		dbdView._tmplItems[name] = $('#' + name).size() ? $('#' + name).tmplItem() : $('.' + name).tmplItem();
//		dbdError.ensure(typeof dbdView._tmplItems[name] == 'object' && dbdView._tmplItems[name].update, 'View (' + name + ') not found!');
//	}
//	return dbdView._tmplItems[name];
	if (typeof name == 'object'){
		var ti = name.tmplItem();
		dbdError.ensure(typeof ti == 'object' && ti.update, 'View invalid!');
	}else{
		dbdError.ensure($('#' + name + ',.' + name).size(), 'View (' + name + ') not found!');
		var ti = $('#' + name).size() ? $('#' + name).tmplItem() : $('.' + name).tmplItem();
		dbdError.ensure(typeof ti == 'object' && ti.update, 'View (' + name + ') not found!');
	}
	return ti;
};
dbdView.get = function (name, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options));
};
dbdView.appendTo = function (name, target, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options)).appendTo(target);
};
dbdView.prependTo = function (name, target, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options)).prependTo(target);
};
dbdView.insertAfter = function (name, target, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options)).insertAfter(target);
};
dbdView.insertBefore = function (name, target, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options)).insertBefore(target);
};
dbdView.replaceAll = function (name, target, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options)).replaceAll(target);
};
dbdView.replaceInto = function (name, target, data, options){
	return $.tmpl(dbdView.tmpl(name), data, $.extend(dbdView.options(name), options)).replaceInto(target);
};
dbdView.update = function (name, data, merge){
	var ti = dbdView.tmplItem(name);
	if (data){
		if (merge)
			ti.data = $.extend(dbdView.tmplItem(name).data, data);
		else
			ti.data = data;
	}
	ti.update();
	return $(ti.nodes[0]);
};

var dbdRunner = function (){
	var _onces = {};
	var _timed = {};
	return {
		once: function (name, task, context){
			if ($.isFunction(task)){
				if (!_onces[name]){
					_onces[name] = {
						deferred: $.Deferred(),
						started: false,
						running: false,
						failed: false,
						task: function (){
							_onces[name].started = true;
							_onces[name].running = true;
							$.when(task.call(context || window)).done(function (){
								_onces[name].running = false;
								_onces[name].deferred.resolveWith(task, arguments);
							}).fail(function (){
								_onces[name].running = false;
								_onces[name].failed = true;
								_onces[name].deferred.rejectWith(task, arguments);
							});
						}
					};
				}
				if (!_onces[name].started)
					_onces[name].task();
				return _onces[name].deferred.promise();
			}
			return false;
		},
		timed: function (name, interval, task, context, noFirstRun){
			if (arguments.length == 1 || arguments.length == 2 || $.isFunction(task)){
				if (!_timed[name]){
					_timed[name] = {
						deferred: $.Deferred(),
						firstRun: true,
						started: false,
						running: false,
						failed: false,
						timer: 0,
						interval: interval,
						task: function (){
							_timed[name].started = true;
							if (!(_timed[name].firstRun && noFirstRun)){
								_timed[name].running = true;
								$.when(task.call(context || window)).done(function (){
									_timed[name].firstRun = false;
									_timed[name].running = false;
									_timed[name].timer = setTimeout(_timed[name].task, _timed[name].interval);
									_timed[name].deferred.resolveWith(task, arguments);
								}).fail(function (){
									_timed[name].firstRun = false;
									_timed[name].running = false;
									_timed[name].failed = true;
									_timed[name].timer = setTimeout(_timed[name].task, _timed[name].interval);
									_timed[name].deferred.rejectWith(task, arguments);
								});
							}else{
								_timed[name].timer = setTimeout(_timed[name].task, _timed[name].interval);
								_timed[name].firstRun = false;
							}
						}
					};
				}
				if (!_timed[name].started){
					_timed[name].task();
				}else if (arguments.length == 2 && !_timed[name].running){
					clearTimeout(_timed[name].timer);
					_timed[name].task();
				}
				return _timed[name].deferred.promiseForever();
			}
			return false;
		},
		clearTimed: function (name){
			if (name){
				if (_timed[name] && _timed[name].started){
					clearTimeout(_timed[name].timer);
					delete _timed[name];
				}
			}else{
				for (var name in _timed){
					if (_timed[name].started)
						clearTimeout(_timed[name].timer);
					delete _timed[name];
				}
			}
		}
	};
}();

var dbdController = Class.extend({
	_router: null,
	_deferred: null,
//	_model: null,
	__construct: function (){
		this._router = dbdMVC.getRouter();
//		this._model = {};
		this._deferred = $.Deferred();
		$.when(this._init()).done(this._deferred.resolve);
		return this;
	},
	_init: function (){},
	_forward: function (url){
		var $D = $.Deferred();
		window.location.assign(url || '/#!/');
		$D.reject();
		return $D.promise();
	},
	_setTitle: function (title){
		document.title = title;
		return this;
	},
	_getTitle: function (){
		return document.title;
	},
	_getController: function (){
		return this._router.getController();
	},
	_getAction: function (){
		return this._router.getAction() || dbdDispatcher.DEFAULT_ACTION;
	},
	_setParam: function (name, value){
		this._router.setParam(name, value);
		return this;
	},
	_unsetParam: function (name){
		this._router.unsetParam(name);
		return this;
	},
	_getParam: function (name){
		return this._router.getParam(name);
	},
	_getParams: function (){
		return this._router.getParams();
	},
	_getURL: function (get_params, host){
		//need to get host
		return (host ? window.location.protocol + '//' + window.location.host : '') + this._router.getURL(get_params);
	},
	promise: function (){
		return this._deferred.promise();
	},
	autoExec: function (){
		//auto render?
//		$.log('dbdController.autoExec()');
	},
	doDefault: function (){}
});

var dbdEmptyController = dbdController.extend({});

//$(function (){
//	$(window).hashchange(function (e){
//		dbdMVC.run(e);
//	}).hashchange();
//});