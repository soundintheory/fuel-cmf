(function($) {
    
    $(document).ready(function() {
        
        $('.field-type-file.image').each(function() {
            var field = new ImageField($(this), null);
        });
        
        // When a new form is added, run it again!
        $(window).bind('cmf.newform', function(e, data) {
            
            var initialData = (typeof(data.data) != 'undefined') ? data.data : {};
            
            data.wrap.find('.field-type-file.image').each(function() {
                var $wrap = $(this),
                fieldName = $wrap.attr('data-field-name'),
                fieldData = (fieldName != null && typeof(initialData[fieldName]) != 'undefined') ? initialData[fieldName] : null,
                field = new ImageField($wrap, fieldData);
            });
            
        });
        
    });
    
    function ImageField($wrap, initialData) {
        
        var fieldName = $wrap.attr('data-field-name');
        
        if (fieldName.indexOf('__TEMP__') > -1) { return; }
        
        var fieldId = $wrap.attr('id'),
        settings = typeof(field_settings[fieldName]) != 'undefined' ? field_settings[fieldName] : {},
        $label = $wrap.find('.item-label'),
        $el = $wrap.find('.async-upload'),
        $inputs = {
            'src': $wrap.find('input[name="' + fieldName + '[src]"]'),
            'width': $wrap.find('input[name="' + fieldName + '[width]"]'),
            'height': $wrap.find('input[name="' + fieldName + '[height]"]')
        },
        originalValue = null,
        $previewLink = null,
        $modal = null,
        title = $label.html(),
        cValue = null,
        cropSettings = settings['crop'],
        cropOptions = {},
        canCrop = typeof(cropSettings) != 'undefined' && cropSettings !== false,
        jcropSettings = {},
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
        },
        preventSave = false;
        
        // Use the provided initial data, if any
        //if (preventSave = (initialData != null)) {
        if (initialData != null) {
            originalValue = initialData;
        } else {
            originalValue = {
                'src': $inputs['src'].val(),
                'width': $inputs['width'].val(),
                'height': $inputs['height'].val()
            };
        }
        
        // Initialise the uploader
        $el.fineUploader(opts)
        .on('submit', submitHandler)
        .on('upload', uploadHandler)
        .on('error', errorHandler)
        .on('cancel', cancelHandler)
        .on('complete', completeHandler);
        
        // Set up some stuff for our uploading functionality
        var $topRow = $el.find('.top-row'),
        $clearBut = $('<span class="btn btn-small btn-clear"><i class="icon-remove"></i></span>').appendTo($topRow).click(clear),
        $filePreview = $el.find('.file-preview');
        
        // This will show / hide any stuff appropriately if there is a value
        setValue(originalValue);

        function submitHandler(evt, id, fileName) {
            
            resetCrop();
            var $file = $($el.fineUploader('getItemByFileId', id));
            $topRow.hide();
            
        }
        
        function completeHandler(evt, id, fileName, responseJSON) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            
            if (typeof(responseJSON['success']) != 'undefined' && responseJSON['success'] === true) {
                var info = responseJSON['info'] || [0,0],
                newValue = {
                    'src': responseJSON['path'],
                    'width': info[0],
                    'height': info[1]
                };
                resetCrop();
                setValue(newValue, true);
            } else {
                resetCrop();
                setValue(originalValue);
            }
            
            $file.hide();
            $topRow.show();
            
        }
        
        function uploadHandler(evt, id, fileName) {
            
            var $file = $($el.fineUploader('getItemByFileId', id));
            
            console.log(qq.isXhrUploadSupported());
            
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
        
        // Sets a path value
        function setValue(val, save) {
            
            cValue = val;
            
            for (var p in $inputs) {
                $inputs[p].appendTo($el).val((typeof(val[p]) != 'undefined') ? val[p] : '');
            }
            
            if (isNull(val) || isNull(val['src']) || val['src'] == '') {
                
                $filePreview.html('<img style="height:' + settings['thumb_size']['height'] + 'px;" src="/image/2/' + settings['thumb_size']['width'] + '/' + settings['thumb_size']['height'] + '/placeholder.png" class="thumbnail" />');
                setStatus('No file selected...');
                $el.removeClass('populated');
                $label.html(title);
                
            } else {
                
                var pathParts = val['src'].split('/'),
                displayName = fileNameFormat(pathParts[pathParts.length-1]),
                cropMode = (settings['crop'] === true) ? 2 : 1;
                
                if (cropMode === 1) {
                    settings['thumb_size']['width'] = 0;
                }
                
                var img = '<img style="height:'+settings['thumb_size']['height']+'px;" src="/image/' + cropMode + '/' + settings['thumb_size']['width'] + '/' + settings['thumb_size']['height'] + '/' + val['src'] + '" />';
                var icon = '<span class="hover-icon"><i class="icon icon-cog"></i></span>';
                
                $filePreview.html('<div></div>');
                $el.addClass('populated');
                $label.html(title + ' <span class="help">(click to edit)</span>');
                $previewLink = $('<a href="#' + fieldId +'-modal" target="_blank" class="thumbnail" role="button">' + img + icon + '</a>').click(launchModal);
                $filePreview.find('div').append($previewLink);
                
                initModal();
                
            }
            
            if (save === true) {
                originalValue = cValue = val;
                saveImage();
            }
            
        }
        
        /**
         * Saves this whole image field to the database
         */
        function saveImage() {
            
            if (!isFunction(saveData) || preventSave === true) { return; }
            
            var _data = {};
            
            // Add any extra fields...?
            
            // This is the standard image data
            for (var p in cValue) {
                _data[fieldName+'['+p+']'] = cValue[p];
            }
            
            // Finally add the crop data
            for (var p in cropOptions) {
                
                var nameX = fieldName+'[crop]['+p+'][x]',
                nameY = fieldName+'[crop]['+p+'][y]',
                nameW = fieldName+'[crop]['+p+'][width]',
                nameH = fieldName+'[crop]['+p+'][height]';
                
                _data[nameX] = $wrap.find('input[name="'+nameX+'"]').val();
                _data[nameY] = $wrap.find('input[name="'+nameY+'"]').val();
                _data[nameW] = $wrap.find('input[name="'+nameW+'"]').val();
                _data[nameH] = $wrap.find('input[name="'+nameH+'"]').val();
                
            }
            
            saveData(null, null, _data);
            
        }
        
        function launchModal() {
            $modal.modal('toggle');
            
            // Open the first crop tab if there are any
            if (canCrop && cropSettings.length > 1) {
                $modal.find('.crop-nav a').eq(0).tab('show');
            }
            
            return false;
        }
        
        function initModal() {
            
            // If the modal is already created, update it
            if ($modal != null) {
                updateModal();
                return false;
            }
            
            // Will eventually check whether this image has extra fields to manage in this modal
            var hasData = false;
            
            // Top part of the modal
            var modalContent = '<div id="#' + fieldId +'-modal" class="image-modal modal hide fade' + (hasData ? '' : ' no-data') + '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">' +
            '<div class="modal-header">' +
            //'<div class="left-col">' +
            //    '<h3>Edit image</h3>' +
            //'</div>' + // .left-col (header)
            '<div class="right-col">';
            
            // Add the crop options here as tabs, if there's more than one
            if (canCrop && cropSettings.length > 1) {
                modalContent += '<ul class="crop-nav nav nav-pills">';
                for (var i = 0; i < cropSettings.length; i++) {
                    var cropOption = cropSettings[i];
                    modalContent += '<li><a href="#' + fieldId + '-crop-' + cropOption['id'] + '">' + cropOption['title'] + '</a></li>';
                }
                modalContent += '</ul>' +
                '<div class="alert alert-info">Select an option above to edit the different crops for this image</div>';
            }
            
            // The top right close button
            modalContent += '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' +
            '</div>' + // .right-col (header)
            '<div class="clear"></div>' +
            '</div>' + // .modal-header
            '<div class="modal-body">';
            /*
            '<div class="left-col">';
            
            // Add any data fields here - alt, title, caption etc
            modalContent += '<div class="image-data">';
            //modalContent += '<div class="controls control-group"><label class="item-label">Title</label><input type="text" class="input-xxlarge" value="The title of the image" /></div>';
            //modalContent += '<div class="controls control-group"><label class="item-label">Caption</label><input type="text" class="input-xxlarge" value="Image caption" /></div>';
            //modalContent += '<div class="controls control-group last"><label class="item-label">Credit</label><input type="text" class="input-xxlarge" value="Image credit" /></div>';
            modalContent += '</div>'; // .image-data
            
            modalContent += '</div>' + // .left-col
            */
           
            modalContent += '<div class="right-col ' + (cropSettings.length > 1 ? 'tab-content' : '') + '">' +
            '</div>' + // .right-col
            '<div class="clear"></div>' +
            '</div>' + // .modal-body
            '<div class="modal-footer">' +
            '<button class="btn btn-primary save-image" data-dismiss="modal"><i class="icon icon-ok"></i> &nbsp;Done</button>' +
            '</div>' +
            '</div>';
            
            $modal = $(modalContent);
            $('body').append($modal);
            
            $modal.on('hide', saveImage);
            //.on('click', '.save-image', saveImage);
            
            updateModal();
            
            if (canCrop && cropSettings.length > 1) {
                
                $modal.find('.crop-nav a')
                .click(function (e) {
                    e.preventDefault();
                    $(this).tab('show');
                })
                .click();
                
            }
            
        }
        
        function updateModal() {
            
            var maxW = 565,
            maxH = 390,
            imageW = cValue['width'] || maxW,
            imageH = cValue['height'] || maxH,
            newImageW = imageW,
            newImageH = imageH;
            
            cropOptions = {};
            
            if (newImageW > maxW) {
                newImageW = maxW;
                newImageH = newImageW * (imageH / imageW);
            }
            if (newImageH > maxH) {
                newImageH = maxH;
                newImageW = newImageH * (imageW / imageH);
            }
            
            var rightCol = '';
            
            // Add the image(s) here
            if (canCrop) {
                
                for (var i = 0; i < cropSettings.length; i++) {
                    var cropOption = cropSettings[i];
                    cropOptions[cropOption['id']] = cropOption;
                    rightCol += '<div id="' + fieldId + '-crop-' + cropOption['id'] + '" data-cropid="'+cropOption['id']+'" class="img tab-pane">';
                    rightCol += '<img src="/image/3/565/390/' + cValue['src'] + '" />';
                    rightCol += '</div>'; // .img
                }
                
                rightCol += '<div class="clear"></div>';
                
            } else {
                
                rightCol += '<img class="main-img" src="/image/3/565/390/' + cValue + '" />';
                
            }
            
            $modal.find('.modal-body .right-col').html(rightCol);

            // Instantiate Jcrop
            $modal.find('.tab-pane.img').each(function() {
                
                var $img = $(this).find('img').eq(0),
                cropId = $(this).attr('data-cropid'),
                cropOption = cropOptions[cropId],
                imageWidth = cValue['width'] || 0,
                imageHeight = cValue['height'] || 0,
                cropWidth = parseInt(cropOption.width),
                cropHeight = parseInt(cropOption.height),
                aspectRatio = (isSet(cropWidth) && isSet(cropHeight)) ? cropWidth / cropHeight : 0,
                jcrop_api = null,
                jcropSettings = {
                    onChange: updateCoords,
                    onSelect: updateCoords,
                    bgColor:     'white',
                    bgOpacity:   .5,
                },
                
                // The inputs we'll be updating
                $inputX = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][x]"]'),
                $inputY = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][y]"]'),
                $inputW = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][width]"]'),
                $inputH = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][height]"]');
                
                // Tell jcrop the true size of the image
                if (imageWidth > 0 && imageHeight > 0 ) {
                    jcropSettings.trueSize = [imageWidth, imageHeight];
                }
                
                // Get the initial selection from the inputs
                var startX = parseInt($inputX.val()) || -1,
                startY = parseInt($inputY.val()) || -1,
                startW = parseInt($inputW.val()) || 0,
                startH = parseInt($inputH.val()) || 0;
                
                // Use the settings from the inputs if they are valid, or set sensible defaults if not
                if (startW > 0 && startH > 0) {
                    if (startX === -1) { startX = 0; }
                    if (startY === -1) { startY = 0; }
                } else {
                    var startW = imageWidth,
                    startH = imageHeight;
                    if (aspectRatio > 0) {
                        startH = Math.round(startW / aspectRatio);
                        if (startH > imageHeight) {
                            startH = imageHeight;
                            startW = Math.round(startH * aspectRatio);
                        }
                        if (startX === -1) { startX = Math.round((imageWidth - startW) / 2); }
                        if (startY === -1) { startY = Math.round((imageHeight - startH) / 2); }
                    } else {
                        if (startX === -1) { startX = 0; }
                        if (startY === -1) { startY = 0; }
                    }
                }
                
                // Set the initial select
                jcropSettings.setSelect = [startX, startY, startX + startW, startY + startH];
                
                if (aspectRatio > 0) {
                    jcropSettings.aspectRatio = aspectRatio;
                }
                
                $img.Jcrop(jcropSettings, function() {
                    jcrop_api = this;
                });
                
                function updateCoords(c) {
                    $inputX.val(Math.round(c.x));
                    $inputY.val(Math.round(c.y));
                    $inputW.val(Math.round(c.w));
                    $inputH.val(Math.round(c.h));
                }
                
            });
            
        }
        
        function resetCrop() {
            
            for (var p in cropOptions) {
                
                $wrap.find('input[name="' + fieldName + '[crop][' + p + '][x]"]').val(null);
                $wrap.find('input[name="' + fieldName + '[crop][' + p + '][y]"]').val(null);
                $wrap.find('input[name="' + fieldName + '[crop][' + p + '][width]"]').val(null);
                $wrap.find('input[name="' + fieldName + '[crop][' + p + '][height]"]').val(null);
                
            }
            
        }

        function setStatus(status) {}
        
        // Puts the input back to the state it started in
        function reset() {
            setValue(originalValue, true);
        }
        
        // Clears the value from the input completely
        function clear() {
            setValue({}, true);
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