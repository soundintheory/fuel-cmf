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
				var html = $.trim(editor.getSelectedHtml());
				if (html.length == 0) { return false; }
				
				var overwrite = true;
				var fontSize = 1 + increment;
				var selectedNode = $(editor.getSelectedNode());
				var currentNode = selectedNode;
				
				if (selectedNode.hasClass('editor-style')) {
					currentNode = selectedNode;
					html = $.trim(selectedNode.html());
					overwrite = false;
				} else {
					currentNode = $(editor.getCurrentNode());
					overwrite = false;
				}
				
				var replaceElement = (currentNode.hasClass('editor-style') && html == currentNode.html());
				var node = html;
				
				if (replaceElement) {
					
					var styleAttr = currentNode.attr('style');
					var regex = /font-size:(.*)em/gi;
					var match = regex.exec(styleAttr);
					
					if (match != null && match.length > 1) {
						fontSize = Math.round((parseFloat(match[1]) + increment) * 100) / 100;
						if (fontSize < minSize) { fontSize = minSize; }
						if (fontSize > maxSize) { fontSize = maxSize; }
					}
					
					if (overwrite === false) {
						currentNode.css('font-size', fontSize + 'em');
						editor.syncCode();
						return true;
					}
					
				}
				
				var d = new Date();
				var cid = 'fs-' + d.getTime();
				node = '<span data-cid="' + cid + '" class="editor-style" style="font-size:' + fontSize + 'em;">' + html + '</span>';
				//node = '<span class="editor-style" style="font-size:' + fontSize + 'em;">' + html + '</span>';
				
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
			
		},
		
		insertPlaceholder: function(html)
		{
			//this.restoreSelection();
			//this.execCommand('inserthtml', html);
			//this.modalClose();
		}

	}
	
	////////////////// INITIALISE REDACTOR ///////////////////
	
	$(document).ready(init);
	
	function init() {
		
		$('.redactor').each(function() {
			
			$input = $(this),
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
				plugins: ['fontSize']
			};
			
			if (typeof(settings['css']) != 'undefined' && settings['css'].length > 0) {
				//opts['plugins'].push('stylesheet');
				//opts['stylesheet'] = settings['css'];
			}
			
			if (typeof(settings['placeholders']) != 'undefined' && settings['placeholders'].length > 0) {
				c
				opts['placeholders'] = settings['placeholders'];
			}
			
			$input.redactor(opts);
			
		});
		
	}	
	
})(jQuery);

/*

 $('#redactor_content').redactor({
focus: true,
buttonsAdd: ["|", "list"],
buttonsCustom: {
list: {
title: "Advanced List",
dropdown: {
point1: {
title: 'Point 1',
callback: point1callback
},
point2: {
title: 'Point 2',
callback: point2callback
}
}
}
}
});	

 */