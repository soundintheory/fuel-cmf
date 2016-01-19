(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-manytomany_inline').each(function() {
			
			var $el = $(this),
			id = $el.attr('id'),
			config = inlines[id],
			$table = $el.find('table').eq(0),
			$tableBody = $el.find('table tbody').eq(0),
			$addBut = $el.find('.btn-add').click(addItem),
			$addText = $addBut.find('span'),
			$template = $el.find('.child-template').detach(),
			newStr = _('admin.common.add_resource', { resource:config['singular'] }),
			$emptyMsg = $('<span class="help-block"><br />' + _('admin.messages.no_items_added', { resource:config['plural'] }) + ". " + _('admin.messages.get_started_click', { button:newStr }) + "<br /><br /><br /></span>").hide().insertAfter($table),
			inline_fields = config['inline_fields'],
			numItems = $tableBody.find('tr').length;
			
			updateCount();
			
			for (var i = 0; i < inline_fields.length; i++) {
				
				$input = $template.find('*[name^="' + getTemplateFieldName(inline_fields[i]) + '"]');
				if ($input.length == 0) { continue; }
				
				$input.attr('data-name', $input.attr('name'));
				$input.attr('data-id', $input.attr('id'));
				$input.removeAttr('name');
				$input.removeAttr('id');
				
			}
			
			initFileInputs($tableBody);
			
			$('#' + id + ' .btn-remove').live('click', function() {
				
				if (!confirm(_('admin.messages.item_delete_confirm'))) { return false; }
				
				var $clicked = $(this),
				$row = $clicked.parents('tr').eq(0);
				
				$row.remove();
				numItems--;
				updateCount();
				
				return false;
				
			});
			
			function updateCount() {
				if (numItems == 0) {
					$table.hide();
					$emptyMsg.show();
					$addText.text('Add a ' + config['singular']);
				} else {
					$table.show();
					$emptyMsg.hide();
					$addText.text('Add another ' + config['singular']);
				}
			}
			
			function addItem() {
				
				var $newRow = $template.clone();
				$newRow.removeClass('child-template');
				
				for (var i = 0; i < inline_fields.length; i++) {
					
					$input = $newRow.find('*[data-name="' + getTemplateFieldName(inline_fields[i]) + '"]');
					if ($input.length == 0) { continue; }

					$input.attr('name', $input.attr('data-name').replace('__NUM__', numItems));
					$input.attr('id', $input.attr('data-id').replace('__NUM__', numItems));

				}
				
				$selfId = $newRow.find('input[type="hidden"]').eq(0);
				$selfId.attr('name', $selfId.attr('data-name').replace('__NUM__', numItems));
				
				$tableBody.append($newRow);
				initFileInputs($newRow);
				
				numItems++;
				updateCount();
				
				return false;
				
			}
			
			function initFileInputs($wrap) {
				
				
				$wrap.find('.field-type-file-inline, .field-type-image-inline').each(function() {
					
					var $outer = $(this);
					var $el = $outer.find('input[type="file"]');
					var $clear = $outer.find('.btn-warning').hide();
					
					$el.bind('change', function() {
						$clear.show();
					});
					
					$el.bind('reset', function() {
						$clear.hide();
					});
					
					var value = $el.attr('title');
					if (value == '' || typeof(value) == 'undefined' || value == null) {
						value = _('admin.upload.no_file_selected');
						$el.trigger('reset');
					} else {
						var segments = value.split('/');
						value = segments[segments.length-1];
						$clear.show();
					}
					$el.customFileInput({
						value_text: value,
				        button_position : 'right'
				    });
					$el.trigger('update');
					
					$clear.click(function() {
						$outer.find('.thumbnail').remove();
						$outer.find('.customfile-feedback').html(_('admin.upload.no_file_selected'));
						$el.trigger('reset');
						return false;
					});
					
				});
				
			}
			
			function getTemplateFieldName(fieldName) {
				return config['fieldName'] + '[__NUM__][' + fieldName + ']';
			}
			
		});
		
	}
	
})(jQuery);