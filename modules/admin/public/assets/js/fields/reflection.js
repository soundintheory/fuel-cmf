(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-reflection').each(function() {
			
			var $el = $(this),
			id = $el.attr('id'),
			$input = $el.find('input[type="text"], textarea'),
			copy = false,
			$cb = $el.find('input[type="checkbox"]').change(updateInput),
			options = reflections[id],
			copyFrom = options['copy_from'],
			filters = (typeof options['filters'] == 'undefined') ? [] : options['filters'],
			template = (typeof options['template'] == 'undefined') ? '' : options['template'],
			$copy = $('#field-' + copyFrom).find('input[type="text"], textarea').change(updateCopy).keyup(updateCopy);
			updateInput();

			function updateInput() {
				copy = $cb.prop('checked');
				if (copy) {
					//$input.addClass('uneditable-input');
					$input.attr('readonly', 'readonly');
				} else {
					//$input.removeClass('uneditable-input');
					$input.removeAttr('readonly');
				}
				updateCopy();
			}

			function updateCopy() {

				if (!copy) { return; }
				var value = $copy.val();
				
				if (filters.length > 0) {
					for (var f in filters) {
						var fn = window[filters[f]];
						if(typeof fn === 'function') { value = fn(value); }
					}
				}
				
				if (template != '') {
					value = sprintf(template, value);
				}
				$input.val(value);

			}

		});
		
	}
	
})(jQuery);