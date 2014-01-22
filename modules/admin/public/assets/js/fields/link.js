(function($) {
	
	$(document).ready(function() {
	    
	    $('.field-type-link').each(init);
	    
	    // When a new form is added, run it again!
	    $(window).bind('cmf.newform', function(e, data) {
	    	
	    	data.wrap.find('.field-type-link').each(init);
	        
	    });
	    
	});
	
	function init() {
		
		var $wrap = $(this),
		$external = $wrap.find('.external-link'),
		$internal = $wrap.find('.internal-link'),
		$checkbox = $wrap.find('.external-checkbox input').change(update);
		
		// Don't run on temporary fields...
		if ($checkbox.attr('name').indexOf('__TEMP__') >= 0) { return; }
		
		update();
		
		function update() {
			
			var isExternal = $checkbox.prop('checked');
			
			if (isExternal) {
				
				$wrap.addClass('external');
				$external.appendTo($wrap);
				$internal.detach();
				
			} else {
				
				$wrap.removeClass('external');
				$external.detach();
				$internal.appendTo($wrap);
				
			}
			
		}
		
	}
	
})(jQuery);