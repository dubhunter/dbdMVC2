( function( $ ) {

    // plugin definition
    $.fn.overlabel = function( options ) {

        // build main options before element iteration
        var opts = $.extend( {}, $.fn.overlabel.defaults, options );

        var selection = this.filter( 'label[for]' ).map( function() {

            var label = $( this );
            var id = label.attr( 'for' );
            var field = document.getElementById( id );

            if ( !field ) return;

            // build element specific options
            var o = $.meta ? $.extend( {}, opts, label.data() ) : opts;

            label.addClass( o.label_class );
			var animating = false;
            var showHide = function (e) {
            	if ( this.value ){
            		if ( !animating && label.is(':visible') ){
	            		animating = true;
		            	label.fadeOut( o.duration, function (){
		            		label.css( o.hide_css );
		            		animating = false;
		            	});
            		}
            	}else{
            		label.css( o.show_css );
            	};
            };

            $( field )
                 .parent().addClass( o.wrapper_class ).end()
                 .keyup( showHide ).focus( showHide ).blur( showHide ).each( showHide );

            return this;

        } );

        return opts.filter ? selection : selection.end();
    };

    // publicly accessible defaults
    $.fn.overlabel.defaults = {

        label_class:   'overlabel-apply',
        wrapper_class: 'overlabel-wrapper',
//        hide_css:      { 'text-indent': '-10000px' },
//        show_css:      { 'text-indent': '0px', 'cursor': 'text' },
        hide_css:      { 'display': 'block', 'width': '0', 'height': '0', 'overflow': 'hidden' },
        show_css:      { 'display': 'inline', 'width': 'auto', 'height': 'auto', 'overflow': 'hidden' },
        duration:		100,
        easing:			'easeOutBack',
        filter:        false

    };

} )( jQuery );