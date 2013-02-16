(function($) {
	
	////////////////// PLACEHOLDER PLUGIN ///////////////////
	
	if (typeof RedactorPlugins === 'undefined') RedactorPlugins = {};

	RedactorPlugins.placeholder = {

		init: function()
		{
			var editor = this,
			placeholders = editor.opts.placeholders,
			dropdown = {};
			
			if (typeof(placeholders) == 'undefined' || placeholders.length == 0) { return; }
			
			// The callback for when an item in the dropdown is clicked
			function itemCallback(editor, evt, id) {
				
				var settings = dropdown[id],
				inline = typeof(settings.inline) != 'undefined' && settings.inline,
				tagName = inline ? 'span' : 'div',
				html = '<'+ tagName +' class="placeholder placeholder-' + id + '" contenteditable="false">{{ ' + id + ' }}</'+ tagName +'>',
				currentNode = editor.getCurrentNode(),
				parentTagName = currentNode.tagName.toLowerCase();
				
				var node = $(html);
				editor.setBuffer();
				
				if (!inline && parentTagName != 'div') {
					node.insertAfter($(currentNode));
				} else {
					editor.insertNodeAtCaret(node[0]);
				}
				
			}
			
			// Populate the dropdown
			for (var i = 0; i < placeholders.length; i++) {
				
				var obj = placeholders[i];
				
				if (!obj.title) { obj.title = ucfirst(obj.id); }
				if (obj.description) { obj.title += ' <span>' + obj.description + '</span>'; }
				
				placeholders[i].callback = itemCallback;
				dropdown[obj.id] = obj;
				
			}
			
			this.addBtnAfter('link', 'placeholder', 'Placeholder', function() {
				// something?
			}, dropdown);
			
		},
		
		insertPlaceholder: function(html)
		{
			//this.restoreSelection();
			//this.execCommand('inserthtml', html);
			//this.modalClose();
		}

	}
	
	RedactorPlugins.fontSize = {

		init: function()
		{
			var editor = this,
			dropdown = {},
			minSize = 0.2,
			maxSize = 5,
			sizeIncrement = .2;
			
			editor.addBtnAfter('backcolor', 'fontsmaller', 'Smaller Text', smaller);
			editor.addBtnAfter('fontsmaller', 'fontlarger', 'Larger Text', larger);
			
			function changeSize(increment) {
				
				editor.setBuffer();
				var html = $.trim(editor.getSelectedHtml()),
				oHtml = html;
				if (html.length == 0) { return false; }
				
				var overwrite = true;
				var fontSize = 1 + increment;
				var selectedNode = $(editor.getSelectedNode());
				var currentNode = selectedNode;
				var pWrap = html.toLowerCase().indexOf('<p') === 0;
				var rootSelected = selectedNode.hasClass('redactor_editor') || selectedNode.hasClass('redactor_box');
				
				if (pWrap) {
					$html = $(html);
					html = $.trim($html.html());
				}
				
				var spanWrap = html.toLowerCase().indexOf('<span') === 0;
				
				if (spanWrap) {
					$html = $(html);
					fontSize = getFontSizeEm($html, increment);
					html = $.trim($html.html());
				}
				
				if (selectedNode.hasClass('editor-style')) {
					currentNode = selectedNode;
					html = $.trim(selectedNode.html());
					overwrite = false;
				} else {
					currentNode = $(editor.getCurrentNode());
					overwrite = false;
				}
				
				var replaceElement = (currentNode.hasClass('editor-style') && oHtml == currentNode.html());
				var node = html;
				
				if (replaceElement) {
					
					fontSize = getFontSizeEm(currentNode, increment);
					
					if (overwrite === false && fontSize !== 1) {
						currentNode.css('font-size', fontSize + 'em');
						editor.syncCode();
						return true;
					}
					
				}
				
				var d = new Date();
				var cid = 'fs-' + d.getTime();
				
				if (fontSize != 1) {
					node = '<span data-cid="' + cid + '" class="editor-style" style="font-size:' + fontSize + 'em;">' + html + '</span>';
				} else {
					node = html;
				}
				
				if (rootSelected) {
					node = '<p>' + node + '</p>';
				}
				
				if ($.browser.msie) {
					editor.$editor.focus();
					editor.document.selection.createRange().pasteHTML(node);
				} else {
					editor.pasteHtmlAtCaret(node);
				}
				
				var $el = editor.$editor.find('span[data-cid="' + cid + '"]');
				if ($el.length > 0) {
					$el.removeAttr('data-cid');
					selectElementText($el[0]);
					//editor.saveSelection();
				}
				
				editor.syncCode();
				
			}
			
			function smaller() {
				changeSize(-sizeIncrement);
			}
			
			function larger() {
				changeSize(sizeIncrement);
			}
			
			function getFontSizeEm($node, increment) {
				var styleAttr = $node.attr('style');
				var regex = /font-size:(.*)em/gi;
				var match = regex.exec(styleAttr);
				var output = 1 + increment;
				
				if (match != null && match.length > 1) {
					output = Math.round((parseFloat(match[1]) + increment) * 100) / 100;
					if (output < minSize) { output = minSize; }
					if (output > maxSize) { output = maxSize; }
				}
				return output;
			}
			
			function selectElementText(el, win) {
			    win = win || window;
			    var doc = win.document, sel, range;
			    if (win.getSelection && doc.createRange) {
			        sel = win.getSelection();
			        range = doc.createRange();
			        range.selectNodeContents(el);
			        sel.removeAllRanges();
			        sel.addRange(range);
			    } else if (doc.body.createTextRange) {
			        range = doc.body.createTextRange();
			        range.moveToElementText(el);
			        range.select();
			    }
			}
			
		}

	}
	
	RedactorPlugins.cmfimages = {

		init: function()
		{
			var editor = this,
			placeholders = editor.opts.placeholders;
			
			editor.addBtnAfter('image', 'cmfimage', 'Insert Image (CMF)', $.proxy(function() { editor.showCMFImage(); }, editor));
			//editor.removeBtn('image');
			
			function editImage() {
				console.log('woo ok yeah');
			}
			
		},
		
		modal_cmfimage_edit: String() +
		'<div id="redactor_modal_content">' +
		'<label>' + RLANG.title + '</label>' +
		'<input id="redactor_file_alt" class="redactor_input" />' +
		'<label>' + RLANG.link + '</label>' +
		'<input id="redactor_file_link" class="redactor_input" />' +
		'<label>' + RLANG.image_position + '</label>' +
		'<select id="redactor_form_image_align">' +
			'<option value="none">' + RLANG.none + '</option>' +
			'<option value="left">' + RLANG.left + '</option>' +
			'<option value="right">' + RLANG.right + '</option>' +
		'</select>' +
		'</div>' +
		'<div id="redactor_modal_footer">' +
			'<a href="javascript:void(null);" id="redactor_image_delete_btn" class="redactor_modal_btn">' + RLANG._delete + '</a>&nbsp;&nbsp;&nbsp;' +
			'<a href="javascript:void(null);" class="redactor_modal_btn redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
			'<input type="button" name="save" class="redactor_modal_btn" id="redactorSaveBtn" value="' + RLANG.save + '" />' +
		'</div>',

		modal_cmfimage: String() +
		'<div id="redactor_modal_content">' +
		'<div id="redactor_tabs">' +
			'<a href="javascript:void(null);" class="redactor_tabs_act">' + RLANG.upload + '</a>' +
			'<a href="javascript:void(null);">' + RLANG.choose + '</a>' +
			'<a href="javascript:void(null);">' + RLANG.link + '</a>' +
		'</div>' +
		'<form id="redactorInsertImageForm" method="post" action="" enctype="multipart/form-data">' +
			'<div id="redactor_tab1" class="redactor_tab">' +
				'<input type="file" id="redactor_file" name="file" />' +
			'</div>' +
			'<div id="redactor_tab2" class="redactor_tab" style="display: none;">' +
				'<div id="redactor_image_box"></div>' +
			'</div>' +
		'</form>' +
		'<div id="redactor_tab3" class="redactor_tab" style="display: none;">' +
			'<label>' + RLANG.image_web_link + '</label>' +
			'<input type="text" name="redactor_file_link" id="redactor_file_link" class="redactor_input"  />' +
		'</div>' +
		'</div>' +
		'<div id="redactor_modal_footer">' +
			'<a href="javascript:void(null);" class="redactor_modal_btn redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
			'<input type="button" name="upload" class="redactor_modal_btn" id="redactor_upload_btn" value="' + RLANG.insert + '" />' +
		'</div>',
		
		// When inserting an image
		
		showCMFImage: function()
		{
			this.saveSelection();

			var callback = $.proxy(function()
			{
				// json
				if (this.opts.imageGetJson !== false)
				{
					$.getJSON(this.opts.imageGetJson, $.proxy(function(data) {

						var folders = {};
						var z = 0;

						// folders
						$.each(data, $.proxy(function(key, val)
						{
							if (typeof val.folder !== 'undefined')
							{
								z++;
								folders[val.folder] = z;
							}

						}, this));

						var folderclass = false;
						$.each(data, $.proxy(function(key, val)
						{
							// title
							var thumbtitle = '';
							if (typeof val.title !== 'undefined')
							{
								thumbtitle = val.title;
							}

							var folderkey = 0;
							if (!$.isEmptyObject(folders) && typeof val.folder !== 'undefined')
							{
								folderkey = folders[val.folder];
								if (folderclass === false)
								{
									folderclass = '.redactorfolder' + folderkey;
								}
							}

							var img = $('<img src="' + val.thumb + '" class="redactorfolder redactorfolder' + folderkey + '" rel="' + val.image + '" title="' + thumbtitle + '" />');
							$('#redactor_image_box').append(img);
							$(img).click($.proxy(this.imageSetThumb, this));


						}, this));

						// folders
						if (!$.isEmptyObject(folders))
						{
							$('.redactorfolder').hide();
							$(folderclass).show();

							var onchangeFunc = function(e)
							{
								$('.redactorfolder').hide();
								$('.redactorfolder' + $(e.target).val()).show();
							}

							var select = $('<select id="redactor_image_box_select">');
							$.each(folders, function(k,v)
							{
								select.append($('<option value="' + v + '">' + k + '</option>'));
							});

							$('#redactor_image_box').before(select);
							select.change(onchangeFunc);
						}

					}, this));
				}
				else
				{
					$('#redactor_tabs a').eq(1).remove();
				}

				if (this.opts.imageUpload !== false)
				{

					// dragupload
					if (this.opts.uploadCrossDomain === false && this.isMobile() === false)
					{

						if ($('#redactor_file').size() !== 0)
						{
							$('#redactor_file').dragupload(
							{
								url: this.opts.imageUpload,
								uploadFields: this.opts.uploadFields,
								success: $.proxy(this.imageUploadCallback, this),
								error: $.proxy(this.opts.imageUploadErrorCallback, this)
							});
						}
					}

					// ajax upload
					this.uploadInit('redactor_file',
					{
						auto: true,
						url: this.opts.imageUpload,
						success: $.proxy(this.imageUploadCallback, this),
						error: $.proxy(this.opts.imageUploadErrorCallback, this)
					});
				}
				else
				{
					$('.redactor_tab').hide();
					if (this.opts.imageGetJson === false)
					{
						$('#redactor_tabs').remove();
						$('#redactor_tab3').show();
					}
					else
					{
						var tabs = $('#redactor_tabs a');
						tabs.eq(0).remove();
						tabs.eq(1).addClass('redactor_tabs_act');
						$('#redactor_tab2').show();
					}
				}

				$('#redactor_upload_btn').click($.proxy(this.imageUploadCallbackLink, this));

				if (this.opts.imageUpload === false && this.opts.imageGetJson === false)
				{
					setTimeout(function()
					{
						$('#redactor_file_link').focus();
					}, 200);

				}

			}, this);

			this.modalInit(RLANG.image + ' into your anus', this.modal_cmfimage, 610, callback);

		}

	}
	
	////////////////// INITIALISE REDACTOR ///////////////////
	
	$(document).ready(init);
	
	function init() {
		
		$('.redactor').each(function() {
			
			var $input = $(this),
			name = $(this).attr('name'),
			settings = typeof(field_settings[name]) != 'undefined' ? field_settings[name] : {};
			
			var opts = {
				imageUpload: '/admin/redactor/imageupload',
				fileUpload: '/admin/redactor/fileupload',
				imageGetJson: '/admin/redactor/getimages',
				minHeight: 300,
				convertDivs: false,
				autoresize: true,
				formattingTags: ['p', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4'],
				plugins: ['cmfimages']
			};
			
			if (typeof(settings['plugins']) != 'undefined' && settings['plugins'].length > 0) {
				opts['plugins'] = opts['plugins'].concat(settings['plugins']);
			}
			
			if (typeof(settings['placeholders']) != 'undefined' && settings['placeholders'].length > 0) {
				opts['placeholders'] = settings['placeholders'];
			}
			
			$input.redactor(opts);
			
		});
		
	}	
	
})(jQuery);