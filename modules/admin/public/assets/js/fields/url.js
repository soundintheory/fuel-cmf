(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-url').each(function() {

			var $el = $(this),
			$input = $el.find('input[type="text"]'),
			copyFrom = ($input.attr('data-copy-from') + '').split(','),
			copy = false,
			$cb = $el.find('input[type="checkbox"]').change(updateInput),
			$copy = [],
			slug = '';
			
			for (var i = 0; i < copyFrom.length; i++) {
				$target = $('input[name="' + copyFrom[i] + '"]').change(updateCopy).keyup(updateCopy);
				if ($target.length > 0) { $copy.push($target); }
			}
			
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
				
				var val = '', slug = '';
				for (var i = 0; i < $copy.length; i++) {
					val += ' ' + $copy[i].val();
				};
				
				slug = generateSlug(val);
				$input.val(slug).change();

			}

		});
		
	}
	
})(jQuery);