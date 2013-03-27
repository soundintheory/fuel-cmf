(function($) {
	
	$(document).ready(function() {
		
		$('.field-type-gallery-inline').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('.field-type-gallery-inline').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $wrap = $(this),
		name = $wrap.attr('data-field-name'),
		modalHtml = '<div class="gallery-modal modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">' +
			'<div class="modal-header">' +
				'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' +
				'<h3 class="item-modal-label">Edit item</h3>' +
			'</div>' +
			'<div class="modal-body">' +
			// Item form will be inserted here
			'</div>' +
			'<div class="modal-footer">' +
				'<button class="btn btn-primary btn-close" data-dismiss="modal"><i class="icon icon-ok"></i> Done</button>' +
			'</div>' +
		'</div>',
		$modal = $(modalHtml).appendTo('body').on('hide', onModalClose),
		$modalBody = $modal.find('.modal-body').eq(0),
		$modalClose = $modal.find('.modal-footer .btn-close').click(onModalClose),
		$currentItem = null,
		$currentForm = null,
		$selectedActions = $wrap.find('.gallery-controls .selected-actions').hide(),
		$selectableItems = $wrap.find('.gallery-items .gallery-item.selectable'),
		$removeSelected = $selectedActions.find('.btn-remove-selected').click(removeSelected),
		numSelected = 0;
		
		// Don't run on temporary fields...
		if (name.indexOf('%TEMP%') >= 0) { return; }
		var $itemsWrap = $wrap.find('.gallery-items').on('click', 'a.edit-link', onEditClick).on('change', 'input[type="checkbox"]', updateSelected);
		updateSelected();
		
		function onModalClose() {
			
			console.log('on close!!!');
			clearCurrentForm();
			
		}
		
		function onEditClick() {
			
			clearCurrentForm();
			
			var $item = $(this).parent(),
			$form = $item.find('.item-form');
			
			// Insert this item's details into the modal and show it
			$modalBody.append($form);
			$modal.modal('toggle');
			$currentForm = $form;
			$currentItem = $item;
			
			return false;
			
		}
		
		function clearCurrentForm() {
			
			// Move out the old form if there is one
			if ($currentForm !== null && $currentItem !== null) {
				$currentForm.appendTo($currentItem);
				 $currentForm = $currentItem = null;
			}
			
		}
		
		function removeSelected() {
			
			var $selectedItems = $wrap.find('.gallery-items .gallery-item.selected');
			if (!confirm("Do you really want to remove " + ($selectedItems.length === 1 ? 'this' : 'these '+$selectedItems.length) + " item" + ($selectedItems.length === 1 ? '' : 's') + "? You can't undo!")) { return false; }
			
			$selectedItems.remove();
			updateSelected();
			return false;
			
		}
		
		function updateSelected() {
			
			var numSelected = 0;
			
			$wrap.find('.gallery-item input[type="checkbox"]').each(function() {
				
				var $self = $(this),
				$item = $self.parent().parent().parent();
				
				if ($self.is(':checked')) {
					$item.addClass('selected');
					numSelected++;
				} else {
					$item.removeClass('selected');
				}
				
			});
			
			if (numSelected > 0) {
				$selectedActions.show();
			} else {
				$selectedActions.hide();
			}
			
		}
		
	}
	
})(jQuery);