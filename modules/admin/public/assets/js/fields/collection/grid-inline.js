(function($) {
	
	$(document).ready(function() {
		
		$('.field-type-grid-inline').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('.field-type-grid-inline').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $wrap = $(this),
		name = $wrap.attr('data-field-name');
		
		// Don't run on temporary fields...
		if (name.indexOf('__TEMP__') >= 0) { return; }
		
		var settings = typeof(field_settings[name]) != 'undefined' ? field_settings[name] : {},
		modalHtml = '<div class="gallery-modal modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">' +
			'<div class="modal-header">' +
				'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' +
				'<h3 class="item-modal-label">' + _('admin.common.edit_item') + '</h3>' +
			'</div>' +
			'<div class="modal-body">' +
			// Item form will be inserted here
			'</div>' +
			'<div class="modal-footer">' +
				'<button class="btn btn-primary btn-close" data-dismiss="modal"><i class="fa fa-ok"></i> ' + _('admin.common.done') + '</button>' +
			'</div>' +
		'</div>',
		$body = $('body'),
		$modal = $(modalHtml).appendTo('body').on('hide', onModalClose),
		$modalBody = $modal.find('.modal-body').eq(0),
		$modalClose = $modal.find('.modal-footer .btn-close').click(onModalClose),
		$currentItem = null,
		$currentForm = null,
		$selectedActions = $wrap.find('.gallery-controls .selected-actions').hide(),
		$actions = $wrap.find('.gallery-controls .actions').hide(),
		$selectableItems = $wrap.find('.gallery-items .gallery-item.selectable'),
		$toggleSelection = $actions.find('.btn-toggle-select').click(toggleSelection),
		$showHidden = $actions.find('.gallery-show-hidden').click(toggleShowHidden),
		$removeSelected = $selectedActions.find('.btn-remove-selected').click(removeSelected),
		$uploader = $wrap.find('.gallery-uploader').eq(0),
		numSelected = 0,
		numItems = 0,
		uploadOpts = {
            multiple: true,
            debug: false,
            request: {
                endpoint: '/admin/upload',
                params: { 'path':'uploads/' },
                paramsInBody: false
            },
            text: {
                uploadButton: '<i class="fa fa-upload-alt icon-white"></i> ' + _('admin.verbs.upload'),
                cancelButton: _('admin.verbs.cancel'),
                retryButton: _('admin.verbs.retry'),
                failUpload: _('admin.upload.fail_upload'),
                dragZone: _('admin.upload.drag_zone'),
                dropProcessing: _('admin.upload.drop_processing'),
                formatProgress: _('admin.upload.format_progress'),
                waitingForResponse: _('admin.upload.waiting_for_response')
            },
            messages: {
            	typeError: _('admin.upload.type_error'),
            	sizeError: _('admin.upload.size_error'),
            	minSizeError: _('admin.upload.min_size_error'),
            	emptyError: _('admin.upload.empty_error'),
            	noFilesError: _('admin.upload.no_files_error'),
            	onLeave: _('admin.upload.on_leave'),
                tooManyFilesError: _('admin.upload.too_many_files_error')
            },
            template:
            	'<div class="qq-upload-list"></div>' +
            	'<div class="gallery-item qq-uploader">' +
            		'<span href="#" class="img action qq-upload-button"><i class="fa fa-plus"></i> &nbsp;' + _('admin.verbs.add') + '...</span>' +
				'</div>' +
                '<pre class="qq-upload-drop-area"><span>{dragZoneText}</span></pre>' +
                '<span class="qq-drop-processing" style="display:none;"><span>{dropProcessingText}</span><span class="qq-drop-processing-spinner"></span></span>',
            fileTemplate: 
            	'<div class="file-item gallery-item uploading">' +
            		'<div href="#" class="img action">' +
            			'<div class="upload-progress progress progress-info"><div class="bar qq-progress-bar"></div></div>' +
	            		'<div class="progress-overlay"><span class="qq-upload-file"></span><span class="qq-upload-size"></span></div>' +
	            		'<span class="qq-upload-spinner"></span>' +
	            		'<span class="qq-upload-finished">Yah</span>' +
	            		'<span class="qq-upload-cancel btn btn-small btn-cancel" title="{cancelButtonText}"><i class="fa fa-remove qq-upload-cancel"></i></span>' +
	            		'<span class="qq-upload-status-text">{statusText}</span>' +
            		'</div>' +
            	'</div>',
            classes: {
                success: 'success',
                fail: 'error'
            },
            formatFileName: fileNameFormat
        },
        $itemsWrap = $wrap.find('.gallery-items').eq(0),
        inc = $itemsWrap.find('.gallery-item').length,
        cookieId = data['model'] + '_' + data['item_id'] + '_' + name,
        positions = {};
		
		// Initialise the items we have to start with
		$itemsWrap.find('.gallery-item').each(SelectableItem);
		
		// Start the uploader
		$uploader.fineUploader(uploadOpts)
		.on('submit', submitHandler)
		.on('upload', uploadHandler)
		.on('error', errorHandler)
		.on('cancel', cancelHandler)
		.on('complete', completeHandler);
		
		// Update a couple of bits
		readCookies();
		updateSelected();
		initSorting();
		toggleShowHidden();
		
		function submitHandler(evt, id, fileName) {
            
            var $file = $($uploader.fineUploader('getItemByFileId', id));
            
        }
        
        function completeHandler(evt, id, fileName, responseJSON) {
            
            var $file = $($uploader.fineUploader('getItemByFileId', id)),
            $imgWrap = $file.find('.img');
            
            if (typeof(responseJSON['success']) != 'undefined' && responseJSON['success'] === true) {
                buildNewItem($file, responseJSON, settings['target_class']);
            } else {
                $file.remove();
            }
            
        }
        
        function uploadHandler(evt, id, fileName) {
            
            var $file = $($uploader.fineUploader('getItemByFileId', id));
            
        }
        
        function errorHandler(id, fileName, reason) {
            
            var $file = $($uploader.fineUploader('getItemByFileId', id));
            
        }
        
        function cancelHandler(id, fileName) {
            
            var $file = $($uploader.fineUploader('getItemByFileId', id));
            
        }
		
		function fileNameFormat(fileName) {
			
		    if (fileName.length > 33) {
		        fileName = fileName.slice(0, 19) + '...' + fileName.slice(-14);
		    }
		    return fileName;
		    
		}
		
		function buildNewItem($itemWrap, _data, type) {
			
			var info = _data['info'] || [0,0],
			imgWidth = Math.round(90 * (info[0] / info[1]));
			
			var html = '<img class="gallery-thumb" src="/image/1/0/90/' + _data['path'] + '" style="width:' + imgWidth + 'px;height:90px;" />' +
			'<label class="gallery-label"><span><input type="checkbox" /></span></label>' +
			'<div class="item-form">';
			
			// Create the item form from the template
			$template = $wrap.find('.item-template[data-type="' + type + '"]').eq(0).clone();
			if ($template.length > 0) {
				
				$template.removeClass('item-template').find('*[name], *[data-field-name]').each(function() {
					
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
						$label = $template.find('label[for="' + origId + '"]');
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
				
				html += $template.html();
				
			}
			
			html += '</div>';
			$itemWrap.html(html).removeClass('uploading').addClass('selectable').each(SelectableItem);
			
			// Populate our image field with the one that was uploaded
			var imageFieldName = name+'['+inc+']['+settings['image_field']+']',
			newFormData = {};
			
			newFormData[imageFieldName] = {
				'src': _data['path'],
				'width': info[0],
				'height': info[1]
			};
			
			inc++;
			updateCount();
			
			// So all the various plugins can run their magic on relevant inputs...
			$(window).trigger('cmf.newform', { 'wrap':$itemWrap, 'data':newFormData });
			
		}
		
		function onModalClose() {
			
			clearCurrentForm();
			
		}
		
		// Opens the modal with the item's form inside
		function onEditClick() {
			
			clearCurrentForm();
			
			var $item = $(this).parent(),
			$form = $item.find('.item-form');
			
			$modalBody.append($form);
			$modal.modal('toggle');
			$currentForm = $form;
			$currentItem = $item;
			
			return false;
			
		}
		
		// Used in a jQuery each() call to wrap created gallery items
		function SelectableItem() {
			
			var $self = $(this),
			$img = $self.find('.gallery-thumb'),
			$overlay = $('<span class="gallery-overlay"></span>').insertAfter($img),
			$editLink = $('<span class="edit-link"><i class="fa fa-cog"></i> Edit</span>').insertAfter($img).click(onEditClick),
			$handle = $('<span class="handle"></span>').insertAfter($img)
			$showHide = $('<span class="showhide-link"><i class="fa fa-eye-open"></i><i class="fa fa-eye-close"></i></span>').insertAfter($editLink).click(showHide),
			$checkbox = $self.find('> .gallery-label input[type="checkbox"]').change(updateSelected),
			$posField = $self.find('> input[data-field-name="pos"]'),
			$visibleInput = $self.find('.item-form > div > label > input[type="checkbox"][name$="[visible]"]').eq(0).change(updateVisible);
			
			// Check if it's visible to start with
			if ($visibleInput.length > 0) { updateVisible(); }
			
			function updateVisible() {
				var checked = $visibleInput.is(':checked');
				if (checked) {
					$self.removeClass('visible-false');
				} else {
					$self.addClass('visible-false');
				}
			}
			
			function showHide() {
				var checked = $visibleInput.is(':checked');
				if (checked) {
					$visibleInput.removeAttr('checked');
				} else {
					$visibleInput.attr('checked', 'checked');
				}
				updateVisible();
			}
			
		}
		
		// Moves the current form out of the modal back where it came from
		function clearCurrentForm() {
			
			if ($currentForm !== null && $currentItem !== null) {
				$currentForm.appendTo($currentItem);
				$currentForm = $currentItem = null;
			}
			
		}
		
		// Completely removes the selected items from the DOM (and ultimately from the database, if the user clicks save)
		function removeSelected() {
			
			var $selectedItems = $wrap.find('.gallery-items .gallery-item.selected');
			if (!confirm(_($selectedItems.length === 1 ? 'admin.messages.item_delete_confirm' : 'admin.messages.items_delete_confirm'))) { return false; }
			
			$selectedItems.remove();
			updateSelected();
			return false;
			
		}
		
		function toggleSelection() {
			if (numSelected > 0) {
				deselectAll();
			} else {
				selectAll();
			}
		}
		
		function toggleShowHidden() {
			var showHidden = $showHidden.is(':checked');
			if (showHidden) {
				$.cookie(cookieId + '_showhidden', '1');
				$itemsWrap.removeClass('hide-hidden');
			} else {
				$.cookie(cookieId + '_showhidden', '0');
				$itemsWrap.addClass('hide-hidden');
			}
		}
		
		function readCookies() {
			if ($.cookie(cookieId + '_showhidden') === '0') {
				$showHidden.removeAttr('checked');
			} else {
				$showHidden.attr('checked', 'checked');
			}
		}
		
		function selectAll() {
			$wrap.find('.gallery-item .gallery-label input[type="checkbox"]').attr('checked', 'checked');
			updateSelected();
		}
		
		function deselectAll() {
			$wrap.find('.gallery-item .gallery-label input[type="checkbox"]').removeAttr('checked');
			updateSelected();
		}
		
		function updateSelected() {
			
			numSelected = 0;
			updateCount();
			
			$wrap.find('.gallery-item .gallery-label input[type="checkbox"]').each(function() {
				
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
				$toggleSelection.text('Deselect all');
				$selectedActions.show();
			} else {
				$toggleSelection.text('Select all');
				$selectedActions.hide();
			}
			
		}
		
		function updateCount() {
			
			numItems = $wrap.find('.gallery-items .gallery-item.selectable').length;
			if (numItems > 0) {
				$actions.show();
			} else {
				$actions.hide();
			}
			
		}
		
		function updatePositions() {
			
			if (settings['sortable'] !== true) { return false; }
			
			$itemsWrap.find('> .gallery-item').each(function(i) {
				
				var $el = $(this),
				$pos = $el.find('> input[data-field-name="pos"]').val(i+''),
				id = $el.find('> input.item-id').val();
				
				positions[id] = { 'pos':i };
				
			});
			
		}
		
		function initSorting() {
			
			if (settings['sortable'] !== true) { return false; }
			
			$itemsWrap.sortable({ handle:'.handle', placeholder: 'gallery-item gallery-sort-placeholder', 'forcePlaceholderSize':true });
			$itemsWrap.sortable('option', 'start', function (evt, ui) {
				
				$body.addClass('sorting');
				
			});
			$itemsWrap.sortable('option', 'stop', function(evt, ui) {
				
				$body.removeClass('sorting');
				updatePositions();
				
			});
			
			updatePositions()
			
		}
		
	}
	
})(jQuery);