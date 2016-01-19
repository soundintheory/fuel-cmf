(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		//////// DYNAMIC OBJECT ///////
		
		$('.dynamic-object-tabular').each(function() {
			
			var $el = $(this),
			$template = $el.find('.item-template').detach(),
			$rows = $el.find('.object-item'),
			$add = $el.find('.btn-add').click(addItem);
			
			// The remove buttons
			$el.find('.btn-remove').click(removeItem);
			
			function addItem() {
				var $newItem = $template.clone();
				$newItem.removeClass('item-template').addClass('object-item').find('*[data-name]').each(function() {
					$(this).attr('name', $(this).attr('data-name'));
				});
				$newItem.insertAfter($rows.last()).find('.btn-remove').click(removeItem);
				$rows = $el.find('.object-item');
				return false;
			}
			
			function removeItem() {
				if (confirm(_('admin.messages.item_delete_confirm'))) {
					$(this).parents('.object-item').eq(0).detach();
					$rows = $el.find('.object-item');
				}
				return false;
			}
			
		});
		
	}
	
})(jQuery);