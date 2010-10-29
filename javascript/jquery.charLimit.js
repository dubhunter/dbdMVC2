(function(jQuery) {
	jQuery.fn.charLimit = function(counter, limit) {
		var that = this[0];
		var isCtrl = false;
		updateCounter();
		function updateCounter(){
			if(typeof that == "object")
				jQuery(counter).text(limit - that.value.length);
		};
		this.keydown(function (e){
			if(e.which == 17 || e.which == 224) isCtrl = true;
			var ctrl_a = (e.which == 65 && isCtrl == true) ? true : false; // detect and allow CTRL + A selects all.
			var ctrl_v = (e.which == 86 && isCtrl == true) ? true : false; // detect and allow CTRL + V paste.
			var ctrl_c = (e.which == 67 && isCtrl == true) ? true : false; // detect and allow CTRL + C copy.
			var ctrl_x = (e.which == 88 && isCtrl == true) ? true : false; // detect and allow CTRL + X cut.
			// 8 is 'backspace' and 46 is 'delete'
			if( this.value.length >= limit && e.which != '8' && e.which != '46' && ctrl_a == false && ctrl_v == false && ctrl_c == false && ctrl_x == false)
				e.preventDefault();
		})
		.keyup(function (e){
			if(e.which == 17 || e.which == 224) isCtrl = false;
			if( this.value.length >= limit ){
				this.value = that.value.substr(0, limit);
			}
			updateCounter();
		});

	};
})(jQuery);