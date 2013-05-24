(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		var config = {
			skin: 'BootstrapCK-Skin',
			toolbar:[ ['Source'], ['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'], ['MediaEmbed', 'Image', 'CreatePlaceholder'], ['Format','Styles'] ],
			extraPlugins : 'autogrow,MediaEmbed',
			removePlugins : 'resize',
			filebrowserBrowseUrl : '/admin/assets/filemanager/index.html',
			format_tags: 'p;h2;h3;h4;h5',
			forcePasteAsPlainText: true,
			basicEntities: true
		};
		
		$('.field-type-ckeditor').each(function() {
			
			var $el = $(this),
			id = $el.attr('id');
			
			var cConfig = $.extend(config, ckeditors[id]);
			if (typeof(cConfig['placeholders']) != 'undefined' && cConfig['placeholders'].length > 0) {
				cConfig.extraPlugins = cConfig.extraPlugins + ',placeholder';
			}
			
			$(this).find('textarea').ckeditor(cConfig);
			
		});
		
	}
	
})(jQuery);