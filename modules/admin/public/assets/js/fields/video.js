(function($) {
    
    $(document).ready(function() {
        $('.field-type-file.video').each(initItem);
    });
    
    function initItem() {
        
        var $wrap = $(this),
        fieldName = $wrap.attr('data-field-name');
        
        if (fieldName.indexOf('__TEMP__') > -1) { return; }
        
        var $el = $wrap.find('.async-upload'),
        $srcInput = $wrap.find('input[name="' + fieldName + '[src]"]'),
        $widthInput = $wrap.find('input[name="' + fieldName + '[width]"]'),
        $heightInput = $wrap.find('input[name="' + fieldName + '[height]"]'),
        $durationInput = $wrap.find('input[name="' + fieldName + '[duration]"]'),
        settings = typeof(field_settings[fieldName]) != 'undefined' ? field_settings[fieldName] : {},
        originalValue = settings['value'],
        opts = {
            multiple: false,
            debug: false,
            request: {
                endpoint: '/admin/upload/video',
                params: { 'path':settings['path'], 'fieldName':fieldName },
                paramsInBody: false
            },
            text: {
                uploadButton: '<i class="icon-upload-alt icon-white"></i> Upload',
                formatProgress: " - {percent}% of {total_size}",
            },
            template:
                '<div class="qq-uploader input-xxlarge">' +
                '<div class="file-preview"><em class="muted">No file selected...</em></div>' +
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
                setValue({
                    'src':responseJSON['path'],
                    'width':responseJSON['width'],
                    'height':responseJSON['height'],
                    'duration':responseJSON['duration']
                }, true);
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
            
            if (typeof(val) == 'undefined' || val == null) { val = { 'src':null, 'width':0, 'height':0, 'duration':0 }; }
            
            // Make sure we at least have all the expected values
            if (typeof(val['src']) == 'undefined') { val['src'] = null; }
            if (typeof(val['width']) == 'undefined') { val['width'] = null; }
            if (typeof(val['height']) == 'undefined') { val['height'] = null; }
            if (typeof(val['duration']) == 'undefined') { val['duration'] = null; }
            
            var src = val['src'];
            var poster = val['poster'];
            var converted = val['converted'];
            
            $input.appendTo($el).val(val['src']);
            $widthInput.appendTo($el).val(val['width']);
            $heightInput.appendTo($el).val(val['height']);
            $durationInput.appendTo($el).val(val['duration']);
            
            if (src == null || src == undefined || src == '') {
                
                setStatus('No file selected...');
                $el.removeClass('populated');
                
            } else if (typeof(poster) != 'undefined' && poster != null && poster != '') {
                
                // The poster is defined - we have a converted video!
                var pathParts = src.split('/'),
                displayName = pathParts[pathParts.length-1];
                $filePreview.html('<a href="/' + converted['mp4'] + '" target="_blank"><img src="/image/1/0/200/' + poster + '" /></a>');
                $el.addClass('populated');
                
            } else {
                
                var pathParts = src.split('/'),
                displayName = fileNameFormat(pathParts[pathParts.length-1]);
                $filePreview.html('<div class="converting">"'+displayName+'" is currently being converted into web format...</div>');
                $el.addClass('populated');
                
            }
            
            if (save === true && isFunction(saveData)) {
                originalValue = val;
                var _data = {};
                _data[fieldName+'[src]'] = val['src'];
                _data[fieldName+'[width]'] = val['width'];
                _data[fieldName+'[height]'] = val['height'];
                _data[fieldName+'[duration]'] = val['duration'];
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
    
    // When a new form is added, run it again!
    $(window).bind('cmf.newform', function(e, data) {
        data.wrap.find('.field-type-file').each(initItem);
    });
    
})(jQuery);