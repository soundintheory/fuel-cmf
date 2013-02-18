(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('select.select2').each(initItem);
		
		
		function initItem() {
			
			var $select = $(this),
			$wrap = $select.parent(),
			multiple = $select.attr('multiple') == 'multiple',
			cid = $wrap.attr('id'),
			name = $select.attr('name').replace('[]', ''),
			fancyBoxOpts = {
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
			};
			
			// Don't run on temporary fields...
			if (name.indexOf('%TEMP%') >= 0) { return; }
			
			var opts = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
			$addBut = $('#' + cid + ' .btn-add');
			
			// So the iframe can call this field on save...
			window[cid] = { 'onSave':onSave };
			
<<<<<<< HEAD
			if ($addBut.length > 0 && !hasParent) {
=======
			if ($addBut.length > 0) {
>>>>>>> parent of 1744f77... fixes #25
				
				$addBut.fancybox(fancyBoxOpts);
				
				$addBut.click(function(evt) {
					evt.preventDefault();
				});
				
			}
			
			opts.matcher = function(term, text, option) {
				
				if (term === '') { return true; }
				
				text = text.toUpperCase();
				term = term.toUpperCase();
				var terms = term.split(' '),
				matches = 0;
				
				for (var i = terms.length - 1; i >= 0; i--) {
					if (text.indexOf(terms[i])>=0) {
						matches++;
					}
				};
				
				return matches == terms.length;
			}
			
<<<<<<< HEAD
			if (multiple && typeof(opts.target_table) != 'undefined' && !hasParent && opts['edit'] !== false) {
=======
			if (multiple && typeof(opts.target_table) != 'undefined') {
>>>>>>> parent of 1744f77... fixes #25
				
				opts.formatSelection = function(object, container) {
					var $item = $('<div></div>').appendTo(container),
					$editLink = $('<a class="edit-link" href="/admin/' + opts.target_table + '/' + object.id + '/edit?_mode=inline&_cid=' + cid + '">' + object.text + ' <i class="icon icon-pencil"></i></a>')
					.appendTo($item)
					.fancybox(fancyBoxOpts)
					.click(function(evt) {
						evt.preventDefault();
					});
					return undefined;
				}
				
			}
			
			$select.select2(opts);
			
			function onSave(data) {
				
				var pos = data['pos'],
				title = data['title'],
				id = data['id'],
				$options = $select.find('option'),
				newOption = $select.find('option[value="' + id + '"]');
				
				if (typeof(data['deleted']) != 'undefined' && data['deleted'] === true) {
					
					// This item has actually been deleted.
					newOption.remove();
					
				} else {
					
					// An item has been added or amended.
					
					if (multiple === false) {
						$options.removeAttr('selected');
					}
					
					if (newOption.length == 0) {
						
						// Add in the new option if it's not there already
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
						
					} else {
						
						newOption.attr('selected', 'selected').html(title);
						
					}
					
				}
				
				// Update the HTML select's content
				var selectHtml = $select.html();
				$select.html('');
				$select.html(selectHtml).trigger('change');
				
			}
			
		}
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('select.select2').each(initItem);
		});
		
	}
	
})(jQuery);