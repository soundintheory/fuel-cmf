(function($) {
	
	////////////////// PLACEHOLDER PLUGIN ///////////////////
	
	if (typeof RedactorPlugins === 'undefined') RedactorPlugins = {};

	RedactorPlugins.placeholder = {

		init: function()
		{
			var editor = this,
			placeholders = editor.opts.placeholders,
			dropdown = {};
			
			if (placeholders.length == 0) { return; }
			
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
				plugins: []
			};
			
			if (typeof(settings['placeholders']) != 'undefined' && settings['placeholders'].length > 0) {
				opts['plugins'].push('placeholder');
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