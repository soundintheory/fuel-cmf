(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.widget-type-stacked-inline > .widget-content').each(function() {
			
			var $wrap = $(this),
			$template = $wrap.find('> .item-template').eq(0),
			$itemsWrap = $wrap.find('> .items').eq(0),
			$items = $itemsWrap.find('> .item'),
			$noItems = $itemsWrap.find('> .no-items'),
			$actionsTop = $wrap.find('> .widget-actions-top'),
			$footer = $wrap.find('> .widget-footer'),
			name = $itemsWrap.attr('data-field-name'),
			settings = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
			inc = $items.length,
			sortable = $itemsWrap.hasClass('sortable'),
			positions = {};
			
			// Don't run on temporary fields...
			if (name.indexOf('%TEMP%') >= 0) { return; }
			
			// Make the items collapsible
			$items.each(initCollapsible)
			
			// Bootstrap dropdowns
			$actionsTop.find('.dropdown-toggle').dropdown();
			$footer.find('.dropdown-toggle').dropdown();
			
			// Add buttons
			$footer.find('.btn-add').click(dropdownClick);
			$actionsTop.find('.btn-add').click(dropdownClick);
			$noItems.find('.btn-add').click(dropdownClick);
			
			function dropdownClick() {
				$('html').trigger('click.dropdown.data-api');
				addItem($(this).attr('data-type'));
				return false;
			}
			
			$items.find('.btn-remove').click(removeButtonHandler);
			
			if (sortable) { initSorting(); }
			update();
			
			function addItem(type) {
				
				var $item = $wrap.find('> .item-template[data-type="' + type + '"]').eq(0).clone();
				
				$item.addClass('item collapsible').removeClass('item-template').each(initCollapsible).find('*[name], *[data-field-name]').each(function() {
					
					var $el = $(this),
					origName = $el.attr('name'),
					origId = $el.attr('id');
					
					var isData = origName === undefined || origName === false || origName == '';
					if (isData) { origName = $el.attr('data-field-name'); }
					if (origName.indexOf('%TEMP%') === -1) { return; }
					
					var lastName = origName.replace('%TEMP%', '').replace('%num%', inc-1),
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
					
					if (isData) {
						$el.attr('data-field-name', name);
					} else {
						$el.attr('name', name);
					}
					
				});
				
				if ($items.length == 0) {
					$itemsWrap.append($item);
				} else {
					$item.insertAfter($items.last());
				}
				
				$item.find('.btn-remove').click(removeButtonHandler);
				$items = $itemsWrap.find('> .item');
				inc++;
				
				// So all the various plugins can run their magic on relevant inputs...
				$(window).trigger('cmf.newform', { 'wrap':$item });
				
				update();
				
			}
			
			function initCollapsible() {
				
				var el = $(this),
				showing = true,
				openIcon = 'chevron-down',
				closedIcon = 'chevron-right',
				titleBar = el.find('> .item-title').click(toggle),
				icon = $('<i class="toggle-arrow icon icon-' + openIcon + '"></i>').appendTo(titleBar);
				
				if (el.hasClass('closed')) {
					showing = false;
					icon.removeClass('icon-' + openIcon).addClass('icon-' + closedIcon);
				}
				
				function toggle() {
					if (showing) {
						hide();
					} else {
						show();
					}
					return false;
				}
				
				function show() {
					el.removeClass('closed');
					icon.removeClass('icon-' + closedIcon).addClass('icon-' + openIcon);
					showing = true;
				}
				
				function hide() {
					el.addClass('closed');
					icon.removeClass('icon-' + openIcon).addClass('icon-' + closedIcon);
					showing = false;
				}
				
			}
			
			function removeButtonHandler() {
				
				if (!confirm("Do you really want to remove this item? You can't undo!")) { return false; }
				var $item = $(this).parents('.item').eq(0);
				$item.remove();
				$items = $itemsWrap.find('> .item');
				
				update();
				
				return false;
				
			}
			
			function update() {
				
				if ($items.length > 0) {
					$itemsWrap.addClass('populated');
				} else {
					$itemsWrap.removeClass('populated');
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
				
				$items = $itemsWrap.find('> .item');
				
				$items.each(function(i) {
					
					var $el = $(this),
					$pos = $el.find('input[data-field-name="pos"]').val(i+''),
					id = $el.find('input.item-id').val();
					
					positions[id] = { 'pos':i };
					
				});
				
			}
			
			function initSorting() {
				
				/*
				var $itemsWrapBody = $itemsWrap.find("tbody"),
				$rows = $itemsWrapBody.find('tr'),
				$body = $('body'),
				tableName = settings['target_table'],
				saveAll = settings['save_all'];
				
				$itemsWrapBody.sortable({ helper:fixHelper, handle:'.handle' });
				$itemsWrapBody.sortable('option', 'start', function (evt, ui) {
					
					$body.addClass('sorting');
					
				});
				$itemsWrapBody.sortable('option', 'stop', function(evt, ui) {
					
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
						
						$itemsWrapBody.find('tr.item').each(function(i) {
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
				
				*/
				
			}
			
			function onListSaved(result) {
				
				//console.log(result);
				
			}
			
		});
		
	}
	
})(jQuery);