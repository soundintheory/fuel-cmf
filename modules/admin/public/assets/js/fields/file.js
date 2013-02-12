(function($) {
    
    $(document).ready(function() {
        $('.field-type-file').each(initItem);
    });
    
    function initItem() {
        
        var $wrap = $(this),
        $el = $wrap.find('.async-upload'),
        $originalInput = $wrap.find('input.file-value'),
        originalValue = $originalInput.val(),
        fieldName = $originalInput.attr('name');
        
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
            
            $input.appendTo($el).val(val);
            if (val == null || val == undefined || val == '') {
                setStatus('No file selected...');
                $el.removeClass('populated');
            } else {
                var pathParts = val.split('/'),
                displayName = pathParts[pathParts.length-1];
                $filePreview.html('<a href="/' + val + '" target="_blank">' + fileNameFormat(displayName) + '</a>');
                $el.addClass('populated');
            }
            
            if (save === true && isFunction(saveData)) {
                originalValue = val;
                var _data = {};
                _data[fieldName] = val;
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

/*

ADVANCED:


element: null,
listElement: null,
dragAndDrop: {
    extraDropzones: [],
    hideDropzones: true,
    disableDefaultDropzone: false
},
text: {
    uploadButton: 'Upload a file',
    cancelButton: 'Cancel',
    retryButton: 'Retry',
    failUpload: 'Upload failed',
    dragZone: 'Drop files here to upload',
    dropProcessing: 'Processing dropped files...',
    formatProgress: "{percent}% of {total_size}",
    waitingForResponse: "Processing..."
},
template: '<div class="qq-uploader">' +
    ((!this._options.dragAndDrop || !this._options.dragAndDrop.disableDefaultDropzone) ? '<div class="qq-upload-drop-area"><span>{dragZoneText}</span></div>' : '') +
    (!this._options.button ? '<div class="qq-upload-button"><div>{uploadButtonText}</div></div>' : '') +
    '<span class="qq-drop-processing"><span>{dropProcessingText}</span><span class="qq-drop-processing-spinner"></span></span>' +
    (!this._options.listElement ? '<ul class="qq-upload-list"></ul>' : '') +
    '</div>',

// template for one item in file list
fileTemplate: '<li>' +
    '<div class="qq-progress-bar"></div>' +
    '<span class="qq-upload-spinner"></span>' +
    '<span class="qq-upload-finished"></span>' +
    '<span class="qq-upload-file"></span>' +
    '<span class="qq-upload-size"></span>' +
    '<a class="qq-upload-cancel" href="#">{cancelButtonText}</a>' +
    '<a class="qq-upload-retry" href="#">{retryButtonText}</a>' +
    '<span class="qq-upload-status-text">{statusText}</span>' +
    '</li>',
classes: {
    button: 'qq-upload-button',
    drop: 'qq-upload-drop-area',
    dropActive: 'qq-upload-drop-area-active',
    dropDisabled: 'qq-upload-drop-area-disabled',
    list: 'qq-upload-list',
    progressBar: 'qq-progress-bar',
    file: 'qq-upload-file',
    spinner: 'qq-upload-spinner',
    finished: 'qq-upload-finished',
    retrying: 'qq-upload-retrying',
    retryable: 'qq-upload-retryable',
    size: 'qq-upload-size',
    cancel: 'qq-upload-cancel',
    retry: 'qq-upload-retry',
    statusText: 'qq-upload-status-text',

    success: 'qq-upload-success',
    fail: 'qq-upload-fail',

    successIcon: null,
    failIcon: null,

    dropProcessing: 'qq-drop-processing',
    dropProcessingSpinner: 'qq-drop-processing-spinner'
},
failedUploadTextDisplay: {
    mode: 'default', //default, custom, or none
    maxChars: 50,
    responseProperty: 'error',
    enableTooltip: true
},
messages: {
    tooManyFilesError: "You may only drop one file"
},
retry: {
    showAutoRetryNote: true,
    autoRetryNote: "Retrying {retryNum}/{maxAuto}...",
    showButton: false
},
showMessage: function(message){
    setTimeout(function() {
        alert(message);
    }, 0);
}


BASIC:

debug: false,
button: null,
multiple: true,
maxConnections: 3,
disableCancelForFormUploads: false,
autoUpload: true,
request: {
    endpoint: '/server/upload',
    params: {},
    paramsInBody: false,
    customHeaders: {},
    forceMultipart: true,
    inputName: 'qqfile',
    uuidName: 'qquuid',
    totalFileSizeName: 'qqtotalfilesize'
},
validation: {
    allowedExtensions: [],
    sizeLimit: 0,
    minSizeLimit: 0,
    stopOnFirstInvalidFile: true
},
callbacks: {
    onSubmit: function(id, fileName){},
    onComplete: function(id, fileName, responseJSON){},
    onCancel: function(id, fileName){},
    onUpload: function(id, fileName){},
    onUploadChunk: function(id, fileName, chunkData){},
    onResume: function(id, fileName, chunkData){},
    onProgress: function(id, fileName, loaded, total){},
    onError: function(id, fileName, reason) {},
    onAutoRetry: function(id, fileName, attemptNumber) {},
    onManualRetry: function(id, fileName) {},
    onValidateBatch: function(fileData) {},
    onValidate: function(fileData) {}
},
messages: {
    typeError: "{file} has an invalid extension. Valid extension(s): {extensions}.",
    sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
    minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
    emptyError: "{file} is empty, please select files again without it.",
    noFilesError: "No files to upload.",
    onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
},
retry: {
    enableAuto: false,
    maxAutoAttempts: 3,
    autoAttemptDelay: 5,
    preventRetryResponseProperty: 'preventRetry'
},
classes: {
    buttonHover: 'qq-upload-button-hover',
    buttonFocus: 'qq-upload-button-focus'
},
chunking: {
    enabled: false,
    partSize: 2000000,
    paramNames: {
        partIndex: 'qqpartindex',
        partByteOffset: 'qqpartbyteoffset',
        chunkSize: 'qqchunksize',
        totalFileSize: 'qqtotalfilesize',
        totalParts: 'qqtotalparts',
        filename: 'qqfilename'
    }
},
resume: {
    enabled: false,
    id: null,
    cookiesExpireIn: 7, //days
    paramNames: {
        resuming: "qqresume"
    }
},
formatFileName: function(fileName) {
    if (fileName.length > 33) {
        fileName = fileName.slice(0, 19) + '...' + fileName.slice(-14);
    }
    return fileName;
},
text: {
    sizeSymbols: ['kB', 'MB', 'GB', 'TB', 'PB', 'EB']
}

 */