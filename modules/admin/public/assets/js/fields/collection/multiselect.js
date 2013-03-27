(function($) {
	
	$(document).ready(function() {
		
		$('.field-type-collection').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('.field-type-collection').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $wrap = $(this),
		cid = $wrap.attr('id'),
		$addBut = $wrap.find('.btn-create'),
		$select = $wrap.find('select'),
		$name = $select.attr('name');
		
		// Don't run on temporary fields...
		if (name.indexOf('%TEMP%') >= 0) { return; }
		
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
			$options = $select.find('option');
			
			if (typeof(data['deleted']) != 'undefined' && data['deleted'] === true) {
				
				// This item has actually been deleted.
				var deletedOption = $select.find('option[value="' + id + '"]').remove();
				
			} else {
				
				var newOption = $('<option value="' + id + '" style="text-indent: 0px;" selected="selected">' + title + '</option>');
				
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
				
			}
			
			var selectHtml = $select.html();
			$select.html('');
			$select.html(selectHtml);
			
			$select.scrollTop($select.find('option[value="' + id + '"]').position().top);
			
		}
		
	}
	
})(jQuery);