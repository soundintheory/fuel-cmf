(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-link').each(function() {
			
			var $wrap = $(this),
			$external = $wrap.find('.external-link'),
			$internal = $wrap.find('.internal-link'),
			$checkbox = $wrap.find('.external-checkbox input').change(update);
			
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
			
		});
		
	}
	
})(jQuery);