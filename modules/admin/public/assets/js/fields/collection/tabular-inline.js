(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.widget-type-tabular-inline').each(function() {
			
			var $wrap = $(this),
			$template = $wrap.find('.item-template').eq(0),
			$items = $wrap.find('.item'),
			$table = $wrap.find('table').eq(0),
			inc = $items.length,
			$noItemsRow = $wrap.find('.no-items-row');
			
			$wrap.find('.btn-add').click(function() {
				addItem();
				return false;
			});
			
			$wrap.find('.btn-remove').click(removeButtonHandler);
			
			function addItem() {
				
				var $item = $template.clone();
				$noItemsRow.detach();
				
				$item.addClass('item').removeClass('item-template').find('*[name]').each(function() {
					
					var $el = $(this),
					origName = $el.attr('name'),
					origId = $el.attr('id'),
					lastName = origName.replace('%TEMP%', '').replace('%num%', inc-1),
					name = origName.replace('%TEMP%', '').replace('%num%', inc);
					
					if (origId != undefined && origId != '') {
						
						var newId = origId.replace('%TEMP%', '').replace('%num%', inc);
						$label = $item.find('label[for="' + origId + '"]');
						$el.attr('id', newId);
						
						if ($label.length > 0) {
							$label.attr('for', newId);
						}
						
					}
					
					if (typeof(field_settings[lastName]) != 'undefined') {
						field_settings[name] = field_settings[lastName];
					} else if (typeof(field_settings[origName]) != 'undefined') {
						field_settings[name] = field_settings[origName];
					}
					
					$el.attr('name', name);
				});
				
				if ($items.length == 0) {
					$table.append($item);
				} else {
					$item.insertAfter($items.last());
				}
				
				$item.find('.btn-remove').click(removeButtonHandler);
				$items = $wrap.find('.item');
				inc++;
				
				// So all the various plugins can run their magic on relevant inputs...
				$(window).trigger('cmf.newform', { 'wrap':$item });
				
			}
			
			function removeButtonHandler() {
				
				if (!confirm("Do you really want to remove this item? You can't undo!")) { return false; }
				var $item = $(this).parents('.item').eq(0);
				$item.remove();
				$items = $wrap.find('.item');
				return false;
				
			}
			
		});
		
	}
	
})(jQuery);