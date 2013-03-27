(function($) {
	
	$(document).ready(function() {
		
		$('.field-type-gallery-inline').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('.field-type-gallery-inline').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $wrap = $(this);
		name = $wrap.attr('data-field-name');
		
		// Don't run on temporary fields...
		if (name.indexOf('%TEMP%') >= 0) { return; }
		
		console.log('gallery inline init');
		
	}
	
})(jQuery);