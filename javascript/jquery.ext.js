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
		count: function (arr){
			if ($.isArray(arr)){
				return arr.length;
			} else {
				var n = 0;
				for (var k in arr){
					if (!isNaN(k)){
						n++;
					}
				}
				return n;
			}
		},
		log: function (msg){
			if (window.console)
				console.log(msg);
			else
				alert(msg);
		},
		trim: function (str){
			return str.replace(/^\s+|\s+$/g, '');
		},
		ltrim: function (str){
			return str.replace(/^\s+/, '');
		},
		rtrim: function (str){
			return str.replace(/\s+$/, '');
		}
	});
	$.fn.extend({
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
})(jQuery);