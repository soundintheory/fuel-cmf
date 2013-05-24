(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.widget-type-tabular-inline > .widget-content').each(function() {
			
			var $wrap = $(this),
			$actionsTop = $wrap.find('> .widget-actions-top'),
			$footer = $wrap.find('> .widget-footer'),
			$table = $wrap.find('> table').eq(0),
			$items = $table.find('> tbody > tr.item'),
			$template = $table.find('> tbody > tr.item-template').eq(0),
			$noItemsRow = $table.find('> tbody > tr.no-items-row'),
			name = $table.attr('data-field-name'),
			settings = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
			inc = $items.length,
			sortable = $table.hasClass('sortable'),
			positions = {};
			
			// Don't run on temporary fields...
			if (name.indexOf('__TEMP__') >= 0) { return; }
			
			$footer.find('.btn-add').click(addItem);
			$actionsTop.find('.btn-add').click(addItem);
			$noItemsRow.find('.btn-add').click(addItem);
			
			$items.find('.btn-remove').click(removeButtonHandler);
			
			if (sortable) { initSorting(); }
			update();
			
			function addItem() {
				
				var $item = $template.clone();
				
				$item.addClass('item').removeClass('item-template').find('*[name], *[data-field-name]').each(function() {
					
					var $el = $(this),
					origName = $el.attr('name'),
					origId = $el.attr('id');
					
					var isData = origName === undefined || origName === false || origName == '';
					if (isData) { origName = $el.attr('data-field-name'); }
					if (origName.indexOf('__TEMP__') === -1) { return; }
					
					var lastName = origName.replace('__TEMP__', '').replace('__NUM__', inc-1),
					name = origName.replace('__TEMP__', '').replace('__NUM__', inc);
					
					if (origId != undefined && origId != '') {
						
						var newId = origId.replace('__TEMP__', '').replace('__NUM__', inc);
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
					
					if (isData) {
						$el.attr('data-field-name', name);
					} else {
						$el.attr('name', name);
					}
					
				});
				
				if ($items.length == 0) {
					$table.append($item);
				} else {
					$item.insertAfter($items.last());
				}
				
				$item.find('.btn-remove').click(removeButtonHandler);
				$items = $table.find('> tbody > tr.item');
				inc++;
				
				// So all the various plugins can run their magic on relevant inputs...
				$(window).trigger('cmf.newform', { 'wrap':$item });
				
				update();
				return false;
				
			}
			
			function removeButtonHandler() {
				
				if (!confirm("Do you really want to remove this item? You can't undo!")) { return false; }
				var $item = $(this).parents('.item').eq(0);
				$item.remove();
				$items = $table.find('> tbody > tr.item');
				
				update();
				
				return false;
				
			}
			
			function update() {
				
				if ($items.length > 0) {
					$table.addClass('populated');
				} else {
					$table.removeClass('populated');
				}
				
				var value = [];
				$items.each(function(i) {
					var id = $(this).find('input.item-id').val();
					if (typeof(id) != 'undefined') { value.push(id+''); }
				});
				setFieldValue(name, value);
				
				updatePositions();
				
			}
			
			function updatePositions() {
				
				if (!sortable) { return; }
				
				$items = $table.find('> tbody > tr.item');
				
				$items.each(function(i) {
					
					var $el = $(this),
					$pos = $el.find('input[data-field-name="pos"]').val(i+''),
					id = $el.find('input.item-id').val();
					
					positions[id] = { 'pos':i };
					
				});
				
			}
			
			function initSorting() {
				
				var $tableBody = $table.find("> tbody"),
				$rows = $tableBody.find('> tr.item'),
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
						
						$tableBody.find('> tr.item').each(function(i) {
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