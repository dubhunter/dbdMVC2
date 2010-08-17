(function ($){
	$.fn.checkAllMaster = function (children, callback, inverse){
		if (callback && !inverse && !$.isFunction(callback)){
			inverse = callback;
			callback = null;
		}
		var master = this;
		var children = $(children).not(this).filter('input[@type=checkbox]');
		this.click(function(){
			var masterState = this.checked;
			children.each(function(){
				this.checked = inverse && masterState ? !masterState : masterState;
			});
		});
		children.click(function(){
			var allChecked = this.checked;
			var anyChecked = this.checked;
//			if (anyChecked){
				$(children).each(function(){
					if (!this.checked)
						allChecked = false;
					else
						anyChecked = true;
				});
//			}
			master.get(0).checked = inverse ? !anyChecked : allChecked;
			if ($.isFunction(callback)){
				callback.apply(master, [allChecked, anyChecked]);
			}
		});
	};
	$.fn.checkAll = function (not){
		this.each(function(){
			var n = $(this).filter('input[@type=checkbox]:not(:checked)');
			if (not) n = n.not(not);
			n.each(function(){
				this.checked = true;
			});
		});
		return this;
	};
	$.fn.unCheckAll = function (not){
		this.each(function(){
			var n = $(this).filter('input[@type=checkbox]:checked');
			if (not) n = n.not(not);
			n.each(function(){
				this.checked = false;
			});
		});
		return this;
	};
	$.fn.checkToggle = function (not){
		this.each(function(){
			var n = $(this).filter('input[@type=checkbox]');
			if (not) n = n.not(not);
			n.each(function(){
				this.checked = !this.checked;
			});
		});
	};
	$.fn.checkRadio = function (not){
		this.each(function(){
			var n = $(this).filter('input[@type=checkbox]');
			if (not) n = n.not(not);
			n.click(function(){
				$('input[@name=' + this.name + ']')
					.unCheckAll(this);
			});
		});
	};
})(jQuery);