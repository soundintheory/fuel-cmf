(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-permissions').each(function() {
			
			var $wrap = $(this),
			matrix = new CheckboxMatrix($wrap);
			
		});
		
	}
	
	
	
})(jQuery);