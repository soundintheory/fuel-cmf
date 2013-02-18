(function($) {
    
    $(document).ready(function() {
        $('.field-type-file').each(initItem);
    });
    
    function initItem() {
        
        var $wrap = $(this),
        fieldId = $wrap.attr('id'),
        $label = $wrap.find('.item-label'),
        title = $label.html(),
        $el = $wrap.find('.async-upload'),
        $originalInput = $wrap.find('input.file-value'),
        originalValue = $originalInput.val(),
        fieldName = $originalInput.attr('name'),
        $previewLink = null,
        $modal = null,
        cValue = null;
        
        if (fieldName.indexOf('%TEMP%') > -1) { return; }
        
        var settings = typeof(field_settings[fieldName]) != 'undefined' ? field_settings[fieldName] : {},
        opts = {
            multiple: false,
            debug: false,
            request: {
                endpoint: '/admin/upload',
                params: { 'path':settings['path'], 'fieldName':fieldName },
                paramsInBody: false
            },
            text: {
                uploadButton: '<i class="icon-upload-alt icon-white"></i> Upload',
                formatProgress: " - {percent}% of {total_size}",
            },
            template:
                '<div class="qq-uploader input-xxlarge">' +
                '<div class="file-preview image"></div>' +
                '<div class="top-row"><span class="qq-upload-button btn btn-small">{uploadButtonText}</span></div>' +
                '<pre class="qq-upload-drop-area"><span>{dragZoneText}</span></pre>' +
                '<span class="qq-drop-processing" style="display:none;"><span>{dropProcessingText}</span><span class="qq-drop-processing-spinner"></span></span>' +
                '<div class="qq-upload-list"></div>' +
                '</div>',
            fileTemplate: '<div class="file-item">' +
                '<div class="progress progress-info progress-striped">' +
                '<div class="bar qq-progress-bar"></div>' +
                '<div class="bar-overlay"><span class="qq-upload-file"></span><span class="qq-upload-size"></span></div>' +
                '</div>' +
                '<span class="qq-upload-spinner"></span>' +
                '<span class="qq-upload-finished"></span>' +
                '<div class="file-controls">' +
                '<span class="qq-upload-cancel btn btn-small btn-cancel" title="{cancelButtonText}"><i class="icon-remove qq-upload-cancel"></i></span>' +
                '<!-- span class="qq-upload-retry btn btn-small" title="{retryButtonText}"><i class="icon-repeat icon-white qq-upload-retry"></i></span -->' +
                '</div>' +
                '<span class="qq-upload-status-text">{statusText}</span>' +
                '<div class="clear"><!-- --></div></div>',
            classes: {
                success: 'success',
                fail: 'error'
            },
            formatFileName: fileNameFormat
        };
        
        $el.fineUploader(opts)
        .on('submit', submitHandler)
        .on('upload', uploadHandler)
        .on('error', errorHandler)
        .on('cancel', cancelHandler)
        .on('manualRetry', manualRetryHandler)
        .on('complete', completeHandler);
        
        var $topRow = $el.find('.top-row'),
        $clearBut = $('<span class="btn btn-small btn-clear"><i class="icon-remove"></i></span>').appendTo($topRow).click(clear),
        $filePreview = $el.find('.file-preview'),
        $input = $('<input type="hidden" name="' + fieldName + '" value="" />');
        
        // This will show / hide any stuff appropriately if there is a value
        setValue(originalValue);
        
        function submitHandler(evt, id, fileName) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            $topRow.hide();
            
        }
        
        function completeHandler(evt, id, fileName, responseJSON) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            
            if (typeof(responseJSON['success']) != 'undefined' && responseJSON['success'] === true) {
                setValue(responseJSON['path'], true);
            } else {
                setValue(originalValue);
            }
            
            $file.hide();
            $topRow.show();
            
        }
        
        function uploadHandler(evt, id, fileName) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            $file.find('.progress').addClass('active');
            $el.find('.top-row').hide();
            setStatus('Uploading...');
            
        }
        
        function errorHandler(id, fileName, reason) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            $file.hide();
            $topRow.show();
            
        }
        
        function cancelHandler(id, fileName) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            $file.hide();
            $topRow.show();
            
        }
        
        function manualRetryHandler(id, fileName) {
            
            //var $file = $($el.fineUploader('getItemByFileId', id));
            //$el.fineUploader('clearStoredFiles');
            
        }
        
        // Sets a path value
        function setValue(val, save) {
            
            cValue = val;
            
            $input.appendTo($el).val(val);
            if (val == null || val == undefined || val == '') {
                
                $filePreview.html('<img style="width:' + settings['thumb_size']['width'] + 'px;height:' + settings['thumb_size']['height'] + 'px;" src="/image/2/' + settings['thumb_size']['width'] + '/' + settings['thumb_size']['height'] + '/placeholder.png" class="thumbnail" />');
                setStatus('No file selected...');
                $el.removeClass('populated');
                $label.html(title);
                
            } else {
                
                var pathParts = val.split('/'),
                displayName = fileNameFormat(pathParts[pathParts.length-1]),
                cropMode = (settings['crop'] === true) ? 2 : 1;
                
                if (cropMode === 1) {
                    settings['thumb_size']['width'] = 0;
                }
                
                var img = '<img src="/image/' + cropMode + '/' + settings['thumb_size']['width'] + '/' + settings['thumb_size']['height'] + '/' + val + '" />';
                var icon = '<span class="hover-icon"><i class="icon icon-cog"></i></span>';
                
                $previewLink = $('<a href="#' + fieldId +'_modal" target="_blank" class="thumbnail" role="button">' + img + icon + '</a>').click(launchModal);
                $filePreview.html('<div></div>');
                $el.addClass('populated');
                $filePreview.find('div').append($previewLink);
                
                $label.html(title + ' <span class="help">(click to edit)</span>');
                
                // Set up the modal dialog
                initModal();
                
            }
            
            if (save === true && isFunction(saveData)) {
                originalValue = val;
                var _data = {};
                _data[fieldName] = val;
                saveData(null, null, _data);
            }
            
        }
        
        function launchModal() {
            $modal.modal({});
            return false;
        }
        
        function initModal() {
            
            if ($modal != null) { return; }
            
            var cropSettings = settings['crop'],
            canCrop = typeof(cropSettings) != 'undefined' && cropSettings !== false;
            
            // Top part of the modal
            var modalContent = '<div id="#' + fieldId +'_modal" class="image-modal modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">' +
            '<div class="modal-header">' +
            '<div class="left-col">' +
                '<h3>Edit image</h3>' +
            '</div>' + // .left-col (header)
            '<div class="right-col">';
            
            // Add the crop options here, if any
            if (canCrop && cropSettings.length > 1) {
                modalContent += '<ul class="crop-nav nav nav-pills">';
                for (var i = 0; i < cropSettings.length; i++) {
                    var cropOption = cropSettings[i];
                    modalContent += '<li><a href="#' + fieldId + '-crop-' + cropOption['id'] + '">' + cropOption['title'] + '</a></li>';
                }
                modalContent += '</ul>';
            }
            
            modalContent += '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' +
            '</div>' + // .right-col (header)
            '<div class="clear"></div>' +
            '</div>' + // .modal-header
            '<div class="modal-body">' +
            '<div class="left-col">';
            
            /*
            // Add little explanation if there are any crop versions
            modalContent += '<div class="popover bottom">' +
            '<div class="arrow"></div>' +
            '<div class="popover-content">' +
            '<p>Select which occurence of this image you\'d like to edit...</p>' +
            '</div>' +
            '</div>';
            */
            
            /*
            // Add the crop options here, if any
            modalContent += '<ul class="nav nav-pills nav-stacked">';
            modalContent += '<li><a href="#">Crop version #1</a></li>';
            modalContent += '<li><a href="#">Crop version #2</a></li>';
            modalContent += '<li><a href="#">Crop version #3</a></li>';
            modalContent += '</ul>';
            */
            
            // Add any data fields here - alt, title, caption etc
            modalContent += '<div class="image-data">';
            modalContent += '<div class="controls control-group"><label class="item-label">Title</label><input type="text" class="input-xxlarge" value="The title of the image" /></div>';
            modalContent += '<div class="controls control-group"><label class="item-label">Caption</label><input type="text" class="input-xxlarge" value="Image caption" /></div>';
            modalContent += '<div class="controls control-group last"><label class="item-label">Credit</label><input type="text" class="input-xxlarge" value="Image credit" /></div>';
            modalContent += '</div>'; // .image-data
            
            modalContent += '</div>' + // .left-col
            '<div class="right-col ' + (cropSettings.length > 1 ? 'tab-content' : '') + '">';
            
            // Add the image(s) here
            if (canCrop) {
                
                for (var i = 0; i < cropSettings.length; i++) {
                    var cropOption = cropSettings[i];
                    modalContent += '<div id="' + fieldId + '-crop-' + cropOption['id'] + '" class="img tab-pane">';
                    modalContent += '<img src="/image/3/575/400/' + cValue + '" />';
                    modalContent += '</div>'; // .img
                }
                
            }
            
            modalContent += '<div class="clear"></div></div>' + // .right-col
            '</div>' + // .modal-body
            '<div class="modal-footer">' +
            '<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>' +
            '<button class="btn btn-primary">Save changes</button>' +
            '</div>' +
            '</div>';
            
            $modal = $(modalContent);
            $('body').append($modal);
            
            if (canCrop && cropSettings.length > 1) {
                
                $modal.find('.crop-nav a')
                .click(function (e) {
                    e.preventDefault();
                    $(this).tab('show');
                })
                .eq(0)
                .click();
                
            }
            
        }
        
        function setStatus(status) {}
        
        // Puts the input back to the state it started in
        function reset() {
            
            setValue(originalValue, true);
            
        }
        
        // Clears the value from the input completely
        function clear() {
            
            setValue('', true);
            return false;
            
        }
        
        function fileNameFormat(fileName) {
            if (fileName.length > 33) {
                fileName = fileName.slice(0, 19) + '...' + fileName.slice(-14);
            }
            return fileName;
        }
        
    }
    
    // When a new form is added, run it again!
    $(window).bind('cmf.newform', function(e, data) {
        data.wrap.find('.field-type-file').each(initItem);
    });
    
})(jQuery);