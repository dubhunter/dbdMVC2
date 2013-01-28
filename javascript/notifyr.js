/*!
 * notifyr.js
 * notifyr Javascript Client Library v1.0
 *
 * Copyright 2011, notifyr
 * Available under the MIT License (MIT)
 * https://bitbucket.org/nehz/notifyr-js
 */

;(function(global) {
	'use strict';

	/* Defaults */
	var API_HOST = 'api.notifyr.io';
	var RESOLVE_HOST = 'notifyr.io';
	var MAX_BUFFER = 32 * 1048576; // 32 MB

	/* XMLHttpRequest constants */
	var XHR_UNSENT = 0;
	var XHR_OPENED = 1;
	var XHR_HEADERS_RECEIVED = 2;
	var XHR_LOADING = 3;
	var XHR_DONE = 4;

	/* EventSource constants */
	var ES_CONNECTING = 0;
	var ES_OPEN = 1;
	var ES_CLOSED = 2;

	/* Private */
	var dom_loaded = false;
	var route_loaded = false;
	var loaded_callbacks = [];

	/* Per browser */
	if (global.navigator && global.navigator.userAgent) {
		var user_agent = global.navigator.userAgent;
		switch (true) {
			case /opera/i.test(user_agent):
				// Opera buggy EventSource, use XHR fallback
				global.EventSource = undefined;
				break;
			case /android/i.test(user_agent):
				// Flush 4K for every msg
				var flush_response = true;
				break;
		}
	}

	/* Onload */
	if (global.addEventListener) {
		global.addEventListener('load', function() {
			global.setTimeout(function() {
				dom_loaded = true;
			}, 100);
		}, false);
	}
	else if(global.attachEvent){
		global.attachEvent('onload', function() {
			global.setTimeout(function() {
				dom_loaded = true;
			}, 100);
		}, false);
	}
	else {
		dom_loaded = true;
	}

	var load_interval = global.setInterval(function() {
		if (dom_loaded && route_loaded) {
			for (var i = 0; i < loaded_callbacks.length; i++) {
				loaded_callbacks[i]();
			}
			global.clearInterval(load_interval);
		}
	}, 100);

	var onload = function(f) {
		if (dom_loaded && route_loaded) {
			f();
		}
		else {
			loaded_callbacks.push(f);
		}
	};

	// http://en.wikipedia.org/wiki/XMLHttpRequest
	var XMLHttpRequest = global.XDomainRequest || global.XMLHttpRequest;
	if (!XMLHttpRequest) {
		XMLHttpRequest = function() {
			try {
				return new global.ActiveXObject("Msxml2.XMLHTTP.6.0");
			} catch (e1) {}
			try {
				return new global.ActiveXObject("Msxml2.XMLHTTP.3.0");
			} catch (e2) {}
			try {
				return new global.ActiveXObject("Microsoft.XMLHTTP");
			} catch (e3) {}
			// Microsoft.XMLHTTP points to Msxml2.XMLHTTP and is redundant
			throw new Error("This browser does not support XMLHttpRequest.");
		};
	}

	function uri(instance, secure) {
		if (instance.options.no_subdomain) {
			return 'http{0}://{1}'._format(
				secure || instance.options.ssl ? 's': '',
				instance.api_host
			);
		}
		return 'http{0}://{1}.{2}'._format(
			secure || instance.options.ssl ? 's': '',
			Math.random().toString(36).substr(2, 3),
			instance.api_host
		);
	}

	function connect(xhr, conn) {
		try {
			xhr.offset = 0;
			xhr.open('GET', conn, true);
			xhr.send();
		} catch (e) {
			global.console.error(e);
		}
	}

	/* Channel object */
	var Channel = function(channel, instance) {
		if (this === global) {
			global.console.error('Must create new channel');
			return;
		}

		var self = this;

		self.channel = channel;
		self.instance = instance;
		self.callbacks = [];
		self.ready_callbacks = [];
		self.is_ready = false;

		// Function to generate conn string
		var conn = function() {
			if (flush_response) {
				return '{0}/subscribe/{1}?key={2}&flush=yes&always_ok'.
					_format(uri(instance), channel, instance.key);
			}
			else {
				return '{0}/subscribe/{1}?key={2}&always_ok'.
					_format(uri(instance), channel, instance.key);
			}
		};

		// Function to set ready status, and fire ready callbacks
		var set_ready = function() {
			if (!self.is_ready) {
				self.is_ready = true;

				for (var i = 0; i < self.ready_callbacks.length; i++) {
					self.ready_callbacks[i]();
				}
				if (self.onready) {
					self.onready();
				}
			}
		};

		onload(function() {
			if (global.EventSource) {
				try {
					var make_es  = function() {
						self.eventsource = new global.EventSource(conn());
						if (!self.eventsource.url || self.readyState === ES_CLOSED) {
							throw 'Browser implementation';
						}

						self.eventsource.addEventListener('message', function(e) {
							var data = JSON.parse(e.data);
							if (!data) {}
							else if (data.error) {
								global.console.error('Error: {0}'.
									_format(data.error));
								self.eventsource.onerror = null;
								self.eventsource.close();
								return;
							}
							else {
								for (var i = 0; i < self.callbacks.length; i++) {
									if (data) {
										self.callbacks[i](data);
									}
								}
								if (self.onlisten) {
									self.onlisten(data);
								}
							}
							set_ready();
						});

						self.eventsource.onerror = function() {
							self.is_ready = false;
							if (self.eventsource.readyState == ES_CLOSED) {
								global.console.error('{0} disconnected, reconnecting...'.
									_format(self.channel));

								// Reconnect
								global.setTimeout(function() {
									make_es();
								}, 2000);
							}
						};
					};
					make_es()
				} catch (e) {
					global.console.warn('Problem with EventSource: {0}'._format(e));
					self.eventsource = undefined;
				}
			}

			// XHR Streaming fallback
			if (!self.eventsource) {
				self.xhr = new XMLHttpRequest();
				self.xhr.conn = conn();

				var reconnect = function() {
					self.is_ready = false;
					global.console.error('{0} disconnected, reconnecting...'.
						_format(self.channel));

					// Reconnect, while trying another subdomain via conn()
					global.setTimeout(function() {
						if (self.is_ready) {
							return;
						}

						// Check if  route has changed
						instance.get_api_host();

						self.xhr.conn = conn();
						connect(self.xhr, self.xhr.conn);
					}, 2000);
				};

				var ondata = function() {
					// Optimization
					if (self.xhr.responseText.length - self.xhr.offset < 10) {
						self.xhr.offset = self.xhr.responseText.length;

						// Check buffer and reset if needed
						if (self.xhr.offset > MAX_BUFFER) {
							connect(self.xhr, self.xhr.conn);
						}
						return;
					}

					// Process data
					var data = self.xhr.responseText.slice(self.xhr.offset);
					self.xhr.offset = self.xhr.responseText.length;

					// Check buffer and reset if needed
					if (self.xhr.offset > MAX_BUFFER) {
						connect(self.xhr, self.xhr.conn);
					}

					var rexp = /[\s]*?([^\s]*):[\s]?(.*?)\n\n[\s]*/g;
					var match;
					while (match = rexp.exec(data)) {
						if (match[1] == 'data') {
							var parsed = JSON.parse(match[2]);
							if (!parsed) {}
							else if (parsed.error) {
								global.console.error('Error: {0}'.
									_format(parsed.error));
								self.xhr.onload = null;
								self.xhr.onerror = null;
								self.xhr.onabort = null;
								self.xhr.ontimeout = null;
								self.xhr.abort();
								return;
							}
							else {
								for (var i = 0; i < self.callbacks.length; i++) {
									self.callbacks[i](parsed);
								}
								if (self.onlisten) {
									self.onlisten(parsed);
								}
							}
							set_ready();
						}
					}
				};

				self.xhr.onload = function() {
					// Deal with XDR
					ondata();
					if (self.xhr.onload) {
						reconnect();
					}
				};
				self.xhr.onprogress = ondata;
				self.xhr.onerror = reconnect;
				self.xhr.onabort = reconnect;
				self.xhr.ontimeout = reconnect;

				// Connect
				connect(self.xhr, self.xhr.conn);
			}
		});
	};

	Channel.prototype = {
		listenEvent: function(callback) {
			this.callbacks.push(callback);
			return this;
		},
		listen: function(callback) {
			this.onlisten = callback;
			return this;
		},
		ignore: function(callback) {
			for (var i = 0; i < this.callbacks.length; i++) {
				delete this.callbacks[i];
			}
			this.callbacks._clean(undefined);
			return this;
		},
		close: function() {
			if (this.eventsource) {
				this.eventsource.close();
			}
			if (this.xhr) {
				this.xhr.onerror = null;
				this.xhr.onabort = null;
				this.xhr.ontimeout = null;
				this.xhr.onreadystatechange = null;
				this.xhr.abort();
			}
			this.callbacks = [];
			delete this.instance.channels[this.channel];
			return this;
		},
		publish: function(data, secret) {
			var self = this;
			onload(function() {
				var xhr = new XMLHttpRequest();
				var conn = '{0}/publish/{1}'._format(
					uri(self.instance, true), self.channel);
				var params = JSON.stringify({
					key: self.instance.key,
					data: data,
					secret: secret,
					always_ok: true
				});

				xhr.onload = function() {
					if (xhr.responseText) {
						var response = JSON.parse(xhr.responseText);
						if (response.error) {
							global.console.error('Error: {0}'.
								_format(response.error));
						}
					}
				};

				xhr.open('POST', conn, true);
				if (xhr.setRequestHeader) {
					xhr.setRequestHeader('Content-type', 'application/json');
				}
				xhr.send(params);
			});
			return this;
		},
		readyEvent: function(callback) {
			if (this.is_ready) {
				callback();
			}
			else {
				this.ready_callbacks.push(callback);
			}
			return this;
		},
		ready: function(callback) {
			this.onready = callback;

			if (this.is_ready) {
				callback();
			}
			return this;
		}
	};

	/* Notifyr object */
	var Notifyr = function(key, options) {
		var self = this;

		if (!key) {
			global.console.error('Error: No key supplied');
			return;
		}

		self.key = key;
		self.channels = {};
		self.options = options || {};

		self.api_host = self.options.api_host || API_HOST;
		self.resolve_host = self.options.resolve_host || RESOLVE_HOST;

		// Auto-detect SSL
		self.options.ssl = global.location.protocol.match(/https:/) != null
			|| self.options.ssl;

		// Contact resolver to get api host
		self.get_api_host = function() {
			var xhr = new XMLHttpRequest();
			var conn = 'http{0}://{1}/api/route/{2}?always_ok'._format(
				self.options.ssl ? 's': '', self.resolve_host, key);

			var reconnect = function() {
				global.console.error('Cannot contact hub, reconnecting...');

				// Reconnect
				global.setTimeout(function() {
					connect(xhr, conn);
				}, 2000);
			};

			xhr.open('GET', conn, true);
			xhr.onload = function() {
				if (xhr.responseText) {
					var response = JSON.parse(xhr.responseText);
					if (response.error) {
						global.console.error('Error: {0}'._format(response.error));
					}
					else {
						self.api_host = response.host;
						route_loaded = true;
					}
				}
			};

			xhr.onerror = reconnect;
			xhr.onabort = reconnect;
			xhr.ontimeout = reconnect;
			xhr.send();
		};

		if (self.options.resolve) {
			self.get_api_host();
		}
		else {
			route_loaded = true;
		}
	};

	Notifyr.prototype = {
		subscribe: function(channel) {
			if (!channel) {
				return undefined;
			}
			if (this.channels[channel]) {
				return this.channels[channel];
			}

			var c = new Channel(channel, this);
			this.channels[channel] = c;
			return c;
		}
	};

	/* Helpers */
	String.prototype._format = function() {
		var args = arguments;
		return this.replace(/{(\d+)}/g, function(match, number) {
			return typeof args[number] !== 'undefined' ? args[number] : match;
		});
	};

	Array.prototype._clean = function(deleteValue) {
		for (var i = 0; i < this.length; i++) {
			if (this[i] === deleteValue) {
				this.splice(i, 1);
				i--;
			}
		}
		return this;
	};

	/* Public */
	global.Notifyr = Notifyr;
})(this);
