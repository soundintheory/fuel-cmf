(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.widget-type-tabular-array > .widget-content').each(function() {
			
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
			
			initList();
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
					$el.attr('data-original-name', origName.replace('__TEMP__', ''));
					
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
				
				if (!confirm(_('admin.messages.item_delete_confirm'))) { return false; }
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
					id = $el.find('input.item-id').val();
					
					$el.find('*[name], *[data-original-name]').each(function() {
						
						var $el = $(this),
						origName = $el.attr('data-original-name');
						
						if (typeof(origName) == 'undefined') {
							origName = $el.attr('name');
							$el.attr('data-original-name', origName);
						}
						
						$el.attr('name', origName.replace('__NUM__', i));
						
					});
					
				});
				
			}
			
			function initSorting() {
				
				var $tableBody = $table.find("> tbody"),
				$rows = $tableBody.find('> tr.item'),
				$body = $('body'),
				tableName = settings['tinitSortingarget_table'],
				saveAll = settings['save_all'];
				
				$tableBody.sortable({ helper:fixHelper, handle:'.handle' });
				$tableBody.sortable('option', 'start', function (evt, ui) {
					
					$body.addClass('sorting');
					
				});
				$tableBody.sortable('option', 'stop', function(evt, ui) {
					
					$body.removeClass('sorting');
					updatePositions();
					
				});
				
				updatePositions()
				
			}

			function initList() {

				$items = $table.find('> tbody > tr.item');
				
				$items.each(function(i) {
					
					var $el = $(this),
					id = $el.find('input.item-id').val();

					$el.find('[data-field-name]').each(function() {

						var $el = $(this),
							fieldName = $el.attr('data-field-name'),
							newFieldName = fieldName.replace('__TEMP__', '').replace('__NUM__', i);

						if (typeof(field_settings[newFieldName]) == 'undefined' && typeof(field_settings[fieldName]) != 'undefined') {
							field_settings[newFieldName] = field_settings[fieldName];
						}

						$el.attr('data-field-name', newFieldName);

					});

					$el.find('[id]').each(function() {
						var $el = $(this);
						$el.attr('id', $el.attr('id').replace('__TEMP__', '').replace('__NUM__', i));
					});
					
					$el.find('*[name], *[data-original-name]').each(function() {
						
						var $el = $(this),
							elName = $el.attr('name'),
							origName = $el.attr('data-original-name');
						
						if (typeof(origName) == 'undefined') {
							origName = elName.replace('__TEMP__', '');
							$el.attr('data-original-name', origName);
						}

						var newName = origName.replace('__NUM__', i);

						if (typeof(field_settings[newName]) == 'undefined' && typeof(field_settings[elName]) != 'undefined') {
							field_settings[newName] = field_settings[elName];
						}
						
						$el.attr('name', newName);
						
					});

					$(window).trigger('cmf.newform', { 'wrap':$el });
					
				});

			}
			
			function onListSaved(result) {
				
				//console.log(result);
				
			}
			
		});
		
	}
	
})(jQuery);