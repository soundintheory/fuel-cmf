(function($) {
    
    $(document).ready(function() {
        
        $('.field-type-file.file').each(initItem);
        
        // When a new form is added, run it again!
        $(window).bind('cmf.newform', function(e, data) {
            data.wrap.find('.field-type-file.file').each(initItem);
        });
        
    });
    
    function initItem() {
        
        var $wrap = $(this),
        fieldName = $wrap.attr('data-field-name');
        
        if (fieldName.indexOf('__TEMP__') > -1) { return; }
        
        var $el = $wrap.find('.async-upload'),
        $srcInput = $wrap.find('input[name="' + fieldName + '[src]"]'),
        originalValue = $srcInput.val();
        
        var settings = typeof(field_settings[fieldName]) != 'undefined' ? field_settings[fieldName] : {},
        opts = {
            multiple: false,
            debug: false,
            request: {
                endpoint: '/admin/upload',
                params: { 'path':settings['path'], 'fieldName':fieldName, 'model':(settings.model || '') },
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
                '<div class="qq-uploader input-xxlarge">' +
                '<div class="file-preview"><em class="muted">' + _('admin.upload.no_file_selected') + '...</em></div>' +
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
                '<span class="qq-upload-cancel btn btn-small btn-cancel" title="{cancelButtonText}"><i class="fa fa-remove qq-upload-cancel"></i></span>' +
                '<!-- span class="qq-upload-retry btn btn-small" title="{retryButtonText}"><i class="fa fa-repeat icon-white qq-upload-retry"></i></span -->' +
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
        .on('complete', completeHandler);
        
        var $topRow = $el.find('.top-row'),
        $clearBut = $('<span class="btn btn-small btn-clear"><i class="fa fa-remove"></i></span>').appendTo($topRow).click(clear),
        $filePreview = $el.find('.file-preview');
        
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
            setStatus(_('admin.upload.upload_in_progress'));
            
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
        
        // Sets a path value
        function setValue(val, save) {
            
            $srcInput.appendTo($el).val(val);
            if (val == null || val == undefined || val == '') {

                setStatus(_('admin.upload.no_file_selected'));
                $el.removeClass('populated');
                if ($wrap.find('.click-copy-field').length > 0) {
                    $wrap.find('.click-copy-field').addClass('hide');
                }

            } else {

                var pathParts = val.split('/'),
                displayName = pathParts[pathParts.length-1];
                $filePreview.html('<a href="/' + val + '" target="_blank">' + fileNameFormat(displayName) + '</a>');
                $el.addClass('populated');
                if ($wrap.find('.click-copy-field').length > 0) {
                    $wrap.find('.click-copy-field').removeClass('hide').val($wrap.find('.click-copy-field').attr('data-base')+val);
                }

            }
            
            if (save === true && isFunction(saveData)) {
                originalValue = val;
                var _data = {};
                _data[fieldName+'[src]'] = val;
                saveData(null, null, _data);
            }
            
        }
        
        function setStatus(status) {
            
            $filePreview.html('<em class="muted">' + status + '</em>');
            
        }
        
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
    
})(jQuery);