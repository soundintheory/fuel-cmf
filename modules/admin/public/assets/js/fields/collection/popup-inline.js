(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.widget-type-popup-inline > .widget-content').each(function() {
			
			var $wrap = $(this),
			$body = $('body'),
			$itemsWrap = $wrap.find('> .items').eq(0),
			$items = $itemsWrap.find('> .item'),
			$noItems = $itemsWrap.find('> .no-items'),
			$actionsTop = $wrap.find('> .widget-actions-top'),
			$footer = $wrap.find('> .widget-footer'),
			name = $itemsWrap.attr('data-field-name'),
			settings = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
			cid = settings.cid,
			inc = $items.length,
			sortable = $itemsWrap.hasClass('sortable'),
			positions = {};
			
			// Don't run on temporary fields...
			if (name.indexOf('__TEMP__') >= 0) { return; }
			
			// Bootstrap dropdowns
			$actionsTop.find('.dropdown-toggle').dropdown();
			$footer.find('.dropdown-toggle').dropdown();
			
			// Add buttons
			$wrap.find('.btn-add, .btn-edit').fancybox({
				width: '100%',
				height: '100%',
				maxWidth: 1200,
				type : 'iframe',
				padding : 2,
				margin: 20,
				preload : false,
				helpers : {
					overlay: {
						css: { 'background' : "url('/admin/assets/fancybox/fancybox_overlay.png')" }
					}
				}
			});

			// So the iframe can call this field on save...
			window[cid] = { 'onSave':onSave };

			// $footer.find('.btn-add').click(dropdownClick);
			// $actionsTop.find('.btn-add').click(dropdownClick);
			// $noItems.find('.btn-add').click(dropdownClick);
			
			function dropdownClick() {
				$('html').trigger('click.dropdown.data-api');
				addItem($(this).attr('data-type'));
				return false;
			}
			
			$items.find('.btn-remove').click(removeButtonHandler);
			
			if (sortable) { initSorting(); }
			update();

			function onSave(data) {
				
				var pos = data['pos'],
				title = data['title'],
				id = data['id'],
				type = data['type'],
				$item = $wrap.find('.item-id[value="'+id+'"]').parents('.item').eq(0);

				if (data['deleted'] === true) {
					$item.remove();
					$items = $itemsWrap.find('> .item');
					update();
					return;
				}

				if (!$item.length) {
					$item = addItem(type);
				}

				$item.find('[href*="__ID__"]').each(function() {
					$(this).attr('href', $(this).attr('href').replace('__ID__', id));
				});
				$item.find('.item-id').val(id);
				$item.find('.title-value').text(title);
			}
			
			function addItem(type) {
				
				type = type.replace("\\", "\\\\");
				var $item = $wrap.find('> .item-template[data-type="' + type + '"]').eq(0).clone();
				
				$item.addClass('item '+(sortable ? ' draggable' : '')).removeClass('item-template').find('*[name], *[data-field-name]').each(function() {
					
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
						$el.attr('id', newId);
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

				return $item;
				
			}
			
			function removeButtonHandler() {
				
				if (!confirm(_('admin.messages.item_delete_confirm'))) { return false; }
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

				$itemsWrap.sortable({ helper:fixHelper, handle:'.handle' });

				// Sort start
				$itemsWrap.sortable('option', 'start', function (evt, ui) {

					ui.item.trigger('cmf.dragstart');
					$body.addClass('sorting');
				});

				// Sort stop
				$itemsWrap.sortable('option', 'stop', function(evt, ui) {
					
					ui.item.trigger('cmf.dragstop');
					$body.removeClass('sorting');
					updatePositions();
					
				});
				
				updatePositions();
				
			}
			
			function onListSaved(result) {
				
				//console.log(result);
				
			}
			
		});
		
	}
	
})(jQuery);