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
            'alt': $wrap.find('input[name="' + fieldName + '[alt]"]'),
            'width': $wrap.find('input[name="' + fieldName + '[width]"]'),
            'height': $wrap.find('input[name="' + fieldName + '[height]"]')
        },
        originalValue = null,
        $previewLink = null,
        $modal = null,
        modalOpened = false,
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
                endpoint: CMF.baseUrl + '/admin/upload',
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
                '<span class="qq-upload-cancel btn btn-small btn-cancel" title="{cancelButtonText}"><i class="fa fa-remove qq-upload-cancel"></i></span>' +
                '<!-- span class="qq-upload-retry btn btn-small" title="{retryButtonText}"><i class="fa fa-repeat icon-white qq-upload-retry"></i></span -->' +
                '</div>' +
                '<span class="qq-upload-status-text">{statusText}</span>' +
                '<div class="clear"><!-- --></div></div>',
            classes: {
                success: 'success',
                fail: 'error'
            },
            formatFileName: fileNameFormat,
            validation: {
                allowedExtensions: ['jpg', 'jpeg', 'gif', 'png'],
                sizeLimit: 0,
                minSizeLimit: 0,
                stopOnFirstInvalidFile: false
            }
        },
        preventSave = false;
        
        // Use the provided initial data, if any
        //if (preventSave = (initialData != null)) {
        if (initialData != null) {
            originalValue = initialData;
        } else {
            originalValue = {
                'src': $inputs['src'].val(),
                'alt': $inputs['alt'].val(),
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
        $clearBut = $('<span class="btn btn-small btn-clear"><i class="fa fa-remove"></i></span>').appendTo($topRow).click(clear),
        $filePreview = $el.find('.file-preview');
        
        // This will show / hide any stuff appropriately if there is a value
        setValue(originalValue);

        function submitHandler(evt, id, fileName) {

            if (!window.confirm("Quick Check! \n\n By uploading this image you're verifying that you have permission to use it. E.G:\n" +
                    "\n" +
                    "\u25CF The owner of the image has given consent for its use\n" +
                    "\u25CF It's a stock image, and has been purchased with the correct licence\n" +
                    "\u25CF It's a royalty free image (and attribution is being observed if needed)\n" +
                    "\n" +
                    "Not sure? Don't risk it 🙂")) {
                return false;
            }

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
            
            // console.log(qq.isXhrUploadSupported());
            
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
            
            cValue = val;
            
            for (var p in $inputs) {
                $inputs[p].appendTo($el).val((typeof(val[p]) != 'undefined') ? val[p] : '');
            }
            
            if (isNull(val) || isNull(val['src']) || val['src'] == '') {
                
                $filePreview.html('<img style="height:' + settings['thumb_size']['height'] + 'px;" src="' + CMF.baseUrl + '/image/2/' + settings['thumb_size']['width'] + '/' + settings['thumb_size']['height'] + '/assets/images/placeholder.png" class="thumbnail" />');
                setStatus(_('admin.upload.no_file_selected'));
                $el.removeClass('populated');
                $label.html(title);
                
            } else {
                
                var pathParts = val['src'].split('/'),
                displayName = fileNameFormat(pathParts[pathParts.length-1]),
                cropMode = (settings['crop'] === true) ? 2 : 1,
                thumbW = (cropMode === 1) ? 0 : settings['thumb_size']['width'];
                
                var img = '<img style="height:'+settings['thumb_size']['height']+'px;" src="' + CMF.baseUrl + '/image/' + cropMode + '/' + thumbW + '/' + settings['thumb_size']['height'] + '/' + val['src'] + '" />';
                var icon = '<span class="hover-icon"><i class="fa fa-cog"></i></span>';
                
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
            
            // saveData(null, null, _data);
            
        }
        
        function launchModal() {
            modalOpened = true;
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
            
            var cropTabs = [];
            if (canCrop && cropSettings.length > 1) {
                for (var i = 0; i < cropSettings.length; i++) {
                    var cropOption = cropSettings[i];
                    if (typeof(cropOption['visible']) != undefined && cropOption['visible'] === false) { continue; }
                    cropTabs.push(cropOption);
                }
            } else if (canCrop && cropSettings.length === 1) {
                cropTabs = [cropSettings[0]];
            }

            // Add the crop options here as tabs, if there's more than one
            if (cropTabs.length > 1) {
                modalContent += '<ul class="crop-nav nav nav-pills">';
                for (var i = 0; i < cropTabs.length; i++) {
                    var cropOption = cropTabs[i];
                    if (typeof(cropOption['visible']) != undefined && cropOption['visible'] === false) { continue; }
                    modalContent += '<li><a href="#' + fieldId + '-crop-' + cropOption['id'] + '" data-cropid="' + cropOption['id'] + '">' + cropOption['title'] + '</a></li>';
                }
                modalContent += '</ul>' +
                '<div class="alert alert-info">' + _('admin.image.edit_crop_info') + '</div>';
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

            modalContent += '<div class=top-col>' +
            '<div id="field-field-alt" class="field-alt"><label for="field-alt-control">' + _('admin.image.alt') + '</label><input type="text" id="field-alt-control" /></div>' +
            '</div>' + // .top-col
            '<div class="right-col ' + (cropTabs.length > 1 ? 'tab-content' : '') + '">' +
            '</div>' + // .right-col
            '<div class="clear"></div>' +
            '</div>' + // .modal-body
            '<div class="modal-footer">' +
            '<div class="footer-left clearfix"></div>' +
            '<button class="btn btn-primary save-image" data-dismiss="modal"><i class="fa fa-ok"></i> &nbsp;' + _('admin.common.done') + '</button>' +
            '</div>' +
            '</div>';
            
            $modal = $(modalContent);
            $('body').append($modal);
            
            $modal.on('hide', saveImage);
            //.on('click', '.save-image', saveImage);
            
            updateModal();
            
            if (canCrop && cropTabs.length > 1) {
                
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
            var cropTabs = [];
            if (canCrop) {

                if (cropSettings.length > 1) {
                    for (var i = 0; i < cropSettings.length; i++) {
                        var cropOption = cropSettings[i];
                        if (typeof(cropOption['visible']) != undefined && cropOption['visible'] === false) { continue; }
                        cropTabs.push(cropOption);
                    }
                } else if (cropSettings.length === 1) {
                    cropTabs = [cropSettings[0]];
                }
                
                for (var i = 0; i < cropTabs.length; i++) {
                    var cropOption = cropTabs[i];
                    cropOptions[cropOption['id']] = cropOption;
                    rightCol += '<div id="' + fieldId + '-crop-' + cropOption['id'] + '" data-cropid="'+cropOption['id']+'" class="img tab-pane"><div class="crop-canvas">';
                    rightCol += '<img src="' + CMF.baseUrl + '/image/3/565/390/' + cValue['src'] + '" />';
                    rightCol += '</div></div>'; // .img
                }
                
                rightCol += '<div class="clear"></div>';
                
            } else {
                
                rightCol += '<img class="main-img" src="' + CMF.baseUrl + '/image/3/565/390/' + cValue['src'] + '" />';
                
            }
            
            $modal.find('.modal-body .right-col').html(rightCol);

            // Instantiate Jcrop
            $modal.find('.tab-pane.img').each(function() {
                
                var $canvas = $(this).find('.crop-canvas').eq(0),
                $img = $(this).find('img').eq(0),
                $alt = $modal.find('#field-alt-control').eq(0),
                $hiddenAlt = $wrap.find('input[name="' + fieldName + '[alt]"]'),
                cropId = $(this).attr('data-cropid'),
                cropOption = cropOptions[cropId],
                imageWidth = cValue['width'] || 0,
                imageHeight = cValue['height'] || 0,
                canvasWidth = 565,
                canvasHeight = 390,
                sImageWidth = 0,
                sImageHeight = 0,
                origin = { x:0, y:0 },
                imgScale = 1,
                cScale = -1,
                cropWidth = parseInt(cropOption.width),
                cropHeight = parseInt(cropOption.height),
                aspectRatio = (isSet(cropWidth) && isSet(cropHeight)) ? cropWidth / cropHeight : 0,
                $zoomSlider = null,
                $resetBut = $('<span class="btn btn-warning btn-reset"><i class="fa fa-refresh"></i> ' + _('admin.image.reset_crop') + '</span>').on('click', resetCropArea),
                jcrop_api = null,
                jcropSettings = {
                    onChange: updateCoords,
                    onSelect: updateCoords,
                    bgColor:     'white',
                    bgOpacity:   .5
                },
                
                // The inputs we'll be updating
                $inputX = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][x]"]'),
                $inputY = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][y]"]'),
                $inputW = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][width]"]'),
                $inputH = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][height]"]'),
                $inputS = $wrap.find('input[name="' + fieldName + '[crop][' + cropId + '][scale]"]');

                $alt.val($hiddenAlt.val());

                $alt.on('change', function(e) {
                    $hiddenAlt.val(e.target.value);
                });
                
                $modal.find('.crop-nav a[data-cropid="' + cropId + '"]').on('shown', onTabShow);
                
                // Set the scaled image width, in case our load handler doesn't report back properly
                if (imageWidth > 0 && imageHeight > 0) {
                    
                    imgScale = 390 / imageHeight;
                    if (Math.round(imageWidth * imgScale) > 565) imgScale = 565 / imageWidth;
                    if (imgScale > 1) imgScale = 1;
                    
                    sImageWidth = Math.round(imageWidth * imgScale);
                    sImageHeight = Math.round(imageHeight * imgScale);
                    
                    centerImage();
                }
                
                // Make sure our canvas is the correct size
                $canvas.css({ 'width':canvasWidth, 'height':canvasHeight });
                
                // Get the initial selection from the inputs
                var isFresh = false,
                startX = parseInt($inputX.val()),
                startY = parseInt($inputY.val()),
                startW = parseInt($inputW.val()),
                startH = parseInt($inputH.val());
                
                if (isNaN(startX)) { startX = 0; }
                if (isNaN(startY)) { startY = 0; }
                if (isNaN(startW)) { startW = 0; }
                if (isNaN(startH)) { startH = 0; }
                
                if (aspectRatio > 0) {
                    jcropSettings.aspectRatio = aspectRatio;
                }
                
                $canvas.Jcrop(jcropSettings, function() {
                    jcrop_api = this;
                });
                
                // Use the settings from the inputs if they are valid, or set sensible defaults if not
                if (startW > 0 && startH > 0) {
                    
                    if (startX === null) { startX = 0; }
                    if (startY === null) { startY = 0; }
                    
                    setScale(parseInt($inputS.val()) / 100);
                    
                    // Set the initial select
                    jcrop_api.setSelect([
                        ((startX * imgScale) *cScale) + origin.x,
                        ((startY * imgScale) * cScale) + origin.y,
                        (((startX + startW) * imgScale) * cScale) + origin.x,
                        (((startY + startH) * imgScale) * cScale) + origin.y
                    ]);
                    
                    
                } else {
                    isFresh = true;
                    resetCropArea();
                }
                
                if (canCrop && cropTabs.length === 1) {

                    onTabShow();
                }
                
                function onTabShow() {
                    
                    initZoomSlider();
                    
                    // Detach any old zoom sliders and add this tab's
                    
                    $footerLeft = $modal.find('.modal-footer .footer-left');
                    $footerLeft.find('.zoom-slider').detach();
                    $footerLeft.find('.btn-reset').detach();
                    $footerLeft.html('');
                    $footerLeft.append($resetBut);
                    $footerLeft.append($('<div class="slider-label">' + _('admin.image.scale') + ': </div>'));
                    $footerLeft.append($zoomSlider);
                    
                }
                
                function initZoomSlider() {
                    
                    if ($zoomSlider !== null) { return; }
                    
                    $zoomSlider = $('<div class="zoom-slider" id="zoom-slider-'+cropId+'" style="width: 260px; margin: 15px;"></div>');
                    $zoomSlider.slider({
                        value: parseInt($inputS.val()),
                        orientation: "horizontal",
                        range: "min",
                        min: 0,
                        max: 100,
                        animate: true,
                        slide: onZoomSlideChange,
                        change: onZoomSlideChange
                    });
                    
                }
                
                function onZoomSlideChange(evt, ui) {
                    
                    if (ui.value < 20) {
                        evt.preventDefault();
                        evt.stopPropagation();
                        $zoomSlider.slider( "value", 20);
                        ui.value = 20;
                    }
                    
                    // Update the image etc
                    setScale(ui.value / 100);
                }
                
                function setScale(scale) {
                    
                    if (cScale === scale) { return; }
                    
                    sImageWidth = (imageWidth * imgScale) * scale;
                    sImageHeight = (imageHeight * imgScale) * scale;
                    cScale = scale;
                    $inputS.val(Math.round(scale * 100));
                    centerImage();
                    
                    updateCoords(jcrop_api.tellSelect());
                    
                }
                
                function updateCoords(c) {
                    $inputX.val(Math.round(((c.x - origin.x) / imgScale) / cScale));
                    $inputY.val(Math.round(((c.y - origin.y) / imgScale) / cScale));
                    $inputW.val(Math.round((c.w / imgScale) / cScale));
                    $inputH.val(Math.round((c.h / imgScale) / cScale));
                }
                
                function centerImage() {
                    origin.x = Math.round((canvasWidth - sImageWidth) / 2);
                    origin.y = Math.round((canvasHeight - sImageHeight) / 2);
                    
                    $img.css({ 'top':origin.y, 'left':origin.x, 'width':sImageWidth, 'height':sImageHeight });
                }
                
                function resetCropArea() {
                    
                    initZoomSlider();
                    $zoomSlider.slider( "value", 100);
                    
                    startW = sImageWidth;
                    startH = sImageHeight;
                    var startScale = 1;
                    
                    if (aspectRatio > 0) {

                        if (!!cropOption.fit) {

                            // Fit image inside whitespace

                            startH = Math.round(startW / aspectRatio);
                            if (startH < sImageHeight) {
                                startH = sImageHeight;
                                startW = Math.round(startH * aspectRatio);
                            }
                            
                            if (startH > canvasHeight) {
                                startScale = canvasHeight / startH;
                                startH = canvasHeight;
                                startW = Math.round(startH * aspectRatio);
                            }
                            
                            if (startW > canvasWidth) {
                                startScale = startScale * (canvasWidth / startW);
                                startW = canvasWidth;
                                startH = Math.round(startW / aspectRatio);
                            }
                            
                            if (startScale < 1) {
                                $zoomSlider.slider( "value", Math.round(startScale * 100));
                            }
                            
                            startX = Math.round((sImageWidth - startW) / 2);
                            startY = Math.round((sImageHeight - startH) / 2);

                        } else {

                            // Normal crop to fit

                            startH = Math.round(startW / aspectRatio);
                            if (startH > sImageHeight) {
                                startH = sImageHeight;
                                startW = Math.round(startH * aspectRatio);
                            }
                            
                            if (startH > canvasHeight) {
                                startScale = canvasHeight / startH;
                                startH = canvasHeight;
                                startW = Math.round(startH * aspectRatio);
                            }
                            
                            if (startW > canvasWidth) {
                                startScale = startScale * (canvasWidth / startW);
                                startW = canvasWidth;
                                startH = Math.round(startW / aspectRatio);
                            }
                            
                            if (startScale < 1) {
                                $zoomSlider.slider( "value", Math.round(startScale * 100));
                            }
                            
                            startX = Math.round((sImageWidth - startW) / 2);
                            startY = Math.round((sImageHeight - startH) / 2);

                        }
                        
                    } else {
                        startX = 0;
                        startY = 0;
                    }
                    
                    jcrop_api.setSelect([startX + origin.x, startY + origin.y, startX + startW + origin.x, startY + startH + origin.y]);
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