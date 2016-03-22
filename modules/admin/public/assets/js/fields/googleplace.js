(function($) {
	
	$(document).ready(function() {
	    
	    $('.field-type-google-place').each(init);
	    
	    // When a new form is added, run it again!
	    $(window).bind('cmf.newform', function(e, data) {
	    	data.wrap.find('.field-type-google-place').each(init);
	    });
	    
	});
	
	function init() {
		var $wrap = $(this);
		var autocomplete = new google.maps.places.Autocomplete($wrap.children("input")[0]);
		autocomplete.addListener('place_changed', function() {
			var place = autocomplete.getPlace();
			$wrap.children("[data-ref='place-id']").val(place.id);
			$wrap.children("[data-ref='address_components']").val(JSON.stringify(place.address_components));
		});
	}
	
})(jQuery);