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

				if (!copy || !$copy.length) { return; }
				
				var val = '', slug = '';
				for (var i = 0; i < $copy.length; i++) {
					val += ' ' + $copy[i].val();
				};
				
				slug = generateSlug(val);
				$input.val(slug).change();

			}

		});

		$('.field-type-url-alias').each(function() {

			var $el = $(this),
			opts = {
				alwaysShowPlaceholder: true,
				placeholder: _('admin.messages.click_select_link')
			};

			opts.matcher = function(term, text, option) {
				
				if (term === '') { return true; }
				
				text = text.toUpperCase();
				term = term.toUpperCase();
				var terms = term.split(' '),
				matches = 0;
				
				for (var i = terms.length - 1; i >= 0; i--) {
					if (text.indexOf(terms[i])>=0) {
						matches++;
					}
				};
				
				return matches == terms.length;
			}

			$el.find('.select2').select2(opts);
			$el.find('.select2').select2('container').find('.select2-choices, .select2-choice').addClass('input-xxlarge');

		});
		
	}
	
})(jQuery);