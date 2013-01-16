(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-collection').each(function() {
			
			var $wrap = $(this),
			cid = $wrap.attr('id'),
			$addBut = $wrap.find('.btn-add'),
			$select = $wrap.find('select');
			
			// So the iframe can call this field on save...
			window[cid] = { 'onSave':onSave };
			
			if ($addBut.length > 0) {
				
				$addBut.fancybox({
					width: '100%',
					height: '100%',
					maxWidth: 1200,
					type : 'iframe',
					padding : 2,
					margin: 20,
					preload : false,
					helpers : {
						overlay: {
							css: { 'background' : "url('/admin/assets/fancybox/fancybox_overlay.png')" }
						}
					}
				});
				
			}
			
			function onSave(data) {
				
				var pos = data['pos'],
				title = data['title'],
				id = data['id'],
				$options = $select.find('option'),
				newOption = $('<option value="' + id + '" style="text-indent: 0px;" selected="selected">' + title + '</option>');
				
				if (pos > $options.length) {
					pos = $options.length;
				} else if (pos < 0) {
					pos = 0;
				}
				
				if (pos == $options.length) {
					newOption.appendTo($select);
				} else {
					newOption.insertBefore($options.eq(pos));
				}
				
				var selectHtml = $select.html();
				$select.html(selectHtml);
				
				$select.scrollTop($select.find('option[value="' + id + '"]').offset().top);
				
			}
			
			
		});
		
	}
	
})(jQuery);