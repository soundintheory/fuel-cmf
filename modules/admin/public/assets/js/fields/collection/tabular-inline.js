(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.widget-type-tabular-inline').each(function() {
			
			var $wrap = $(this),
			$template = $wrap.find('.item-template').eq(0),
			$items = $wrap.find('.item'),
			$table = $wrap.find('table').eq(0),
			name = $table.attr('data-field-name'),
			settings = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
			inc = $items.length,
			$noItemsRow = $wrap.find('.no-items-row'),
			sortable = $table.hasClass('sortable'),
			positions = {};
			
			$wrap.find('.btn-add').click(function() {
				addItem();
				return false;
			});
			
			$wrap.find('.btn-remove').click(removeButtonHandler);
			
			if (sortable) { initSorting(); }
			
			function addItem() {
				
				var $item = $template.clone();
				
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
				
				update();
				
			}
			
			function removeButtonHandler() {
				
				if (!confirm("Do you really want to remove this item? You can't undo!")) { return false; }
				var $item = $(this).parents('.item').eq(0);
				$item.remove();
				$items = $wrap.find('.item');
				
				update();
				
				return false;
				
			}
			
			function update() {
				
				if ($items.length > 0) {
					$table.addClass('populated');
				} else {
					$table.removeClass('populated');
				}
				
				updatePositions();
				
			}
			
			function updatePositions() {
				
				if (!sortable) { return; }
				
				$items = $wrap.find('.item');
				
				$items.each(function(i) {
					
					var $el = $(this),
					$pos = $el.find('input[data-field-name="pos"]').val(i+''),
					id = $el.find('input.item-id').val();
					
					positions[id] = { 'pos':i };
					
				});
				
			}
			
			function initSorting() {
				
				var $tableBody = $table.find("tbody"),
				$rows = $tableBody.find('tr'),
				$body = $('body'),
				tableName = settings['target_table'],
				saveAll = settings['save_all'];
				
				$tableBody.sortable({ helper:fixHelper, handle:'.handle' });
				$tableBody.sortable('option', 'start', function (evt, ui) {
					
					$body.addClass('sorting');
					
				});
				$tableBody.sortable('option', 'stop', function(evt, ui) {
					
					$body.removeClass('sorting');
					
					var id = ui.item.find('input.item-id').val(),
					pos = 0;
					
					if (typeof(id) == 'undefined') { return; }
					
					if (saveAll) {
						
						updatePositions();
						
						var cRequest = $.ajax({
							'url': '/admin/' + tableName + '/populate',
							'data': positions,
							'dataType': 'json',
							'async': true,
							'type': 'POST',
							'success': onListSaved,
							'cache': false
						});
						
					} else {
						
						$tableBody.find('tr.item').each(function(i) {
							if ($(this).find('input.item-id').val() == id) {
								pos = i;
								return false;
							}
						});
						
						updatePositions();
						
						var cRequest = $.ajax({
							'url': '/admin/' + tableName + '/' + id + '/populate',
							'data': { 'pos':pos },
							'dataType': 'json',
							'async': true,
							'type': 'POST',
							'success': onListSaved,
							'cache': false
						});
						
					}
					
				});
				
				updatePositions()
				
			}
			
			function onListSaved(result) {
				
				//console.log(result);
				
			}
			
		});
		
	}
	
})(jQuery);