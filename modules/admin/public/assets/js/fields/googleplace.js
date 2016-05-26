(function($) {
	
	$(document).ready(function() {
	    
	    $('.field-type-google-place').each(init);
	    
	    // When a new form is added, run it again!
	    $(window).bind('cmf.newform', function(e, data) {
	    	data.wrap.find('.field-type-google-place').each(init);
	    });

	    $(document).on('keydown', function(e) {
	    	if (e.keyCode == 13 && $(e.target).parents('.field-type-google-place').length > 0) {
	    		e.preventDefault();
	    	}
	    });
	    
	});
	
	function init() {
		var $wrap = $(this);
		var autocomplete = new google.maps.places.Autocomplete($wrap.children("input")[0]);
		autocomplete.addListener('place_changed', function() {
			var place = autocomplete.getPlace();
			$wrap.children("[data-ref='place-id']").val(place.place_id);
			$wrap.children("[data-ref='address_components']").val(JSON.stringify(place.address_components));
		});
	}
	
})(jQuery);