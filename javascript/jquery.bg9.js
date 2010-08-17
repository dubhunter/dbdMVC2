(function ($){
	$.fn.bg9 = function (){
		var suffixes = [
			'TL', 'TC', 'TR',
			'CL', 'CC', 'CR',
			'BL', 'BC', 'BR'
		];
		this.each(function(){
			var $c = $(this);
			if ($c.css('position') != 'absolute' && $c.css('position') != 'relative')
				$c.css('position', 'relative');
			for (i in suffixes){
				$('<span>&nbsp;</span>')
					.addClass('bg9' + suffixes[i])
					.css({
						position: 'absolute',
						zIndex: -1,
						display: 'block'
					})
					.prependTo($c);
			}
		});
	};
})(jQuery);