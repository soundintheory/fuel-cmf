(function($) {
	
	$(document).ready(function() {
	    
	    $('.field-type-color').each(init);
	    
	    // When a new form is added, run it again!
	    $(window).bind('cmf.newform', function(e, data) {
	    	
	    	data.wrap.find('.field-type-color').each(init);
	        
	    });
	    
	});
	
	function init() {
		
		var $wrap = $(this);
		$wrap.colorpicker();		
		
	}
	
})(jQuery);