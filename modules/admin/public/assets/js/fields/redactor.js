(function(){
	
	////////////////// INITIALISE REDACTOR ///////////////////
	
	$(document).ready(function() {
		
		// Run through each input
		$('.redactor').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('.redactor').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $input = $(this),
		name = $(this).attr('name'),
		settings = typeof(field_settings[name]) != 'undefined' ? field_settings[name] : {};
		
		// Don't run on temporary fields...
		if (name.indexOf('__TEMP__') >= 0) { return; }
		
		/*
		var opts = {
			buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'image', 'video', 'file', 'table', 'link', '|', 'fontcolor', 'backcolor', '|', 'alignment', '|', 'horizontalrule'],
			imageUpload: CMF.baseUrl + '/admin/redactor/imageupload',
			fileUpload: CMF.baseUrl + '/admin/redactor/fileupload',
			imageGetJson: CMF.baseUrl + '/admin/redactor/getimages',
			minHeight: settings['minHeight'],
			convertDivs: false,
			autoresize: true,
			air: false,
			airButtons: ['link', 'formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'fontcolor', 'backcolor'],
			formattingTags: ['p', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4'],
			plugins: [ 'cmflink', 'cmfimages']
		};
		 */
		
		var opts = {
			imageGetJson: CMF.baseUrl + '/admin/redactor/getimages',
			imageUpload: CMF.baseUrl + '/admin/redactor/imageupload',
			fileUpload: CMF.baseUrl + '/admin/redactor/fileupload',
			minHeight: settings['minHeight']
		}
		
		$input.redactor(opts);
		
	}
	
})(jQuery);