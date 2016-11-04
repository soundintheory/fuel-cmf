(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('select.select2').each(initItem);
		
		function initItem() {
			
			var $select = $(this),
			hasParent = self!=top,
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
						css: { 'background' : "url('" + CMF.baseUrl + "/admin/assets/fancybox/fancybox_overlay.png')" }
					}
				}
			};
			
			// Don't run on temporary fields...
			if (name.indexOf('__TEMP__') >= 0) { return; }

			var opts = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
			can_edit = multiple && typeof(opts.target_table) != 'undefined' && !hasParent && opts['edit'] !== false,
			$addBut = $('#' + cid + ' .btn-create');
			
			// So the iframe can call this field on save...
			window[cid] = { 'onSave':onSave };
			
			if ($addBut.length > 0 && !hasParent) {
				
				$addBut.fancybox(fancyBoxOpts);
				$addBut.click(function(evt) {
					evt.preventDefault();
				});
				
			} else {
				$addBut.hide();
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
			
			opts.formatSelection = function(object, container) {
				
				var $item = null;
				
				if (can_edit) {
					
					var $editLink = $('<a class="edit-link" href="' + CMF.baseUrl + '/admin/' + opts.target_table + '/' + object.id + '/edit?_mode=inline&_cid=' + cid + '"><span>' + object.text + '</span> <i class="fa fa-pencil"></i></a>')
					.appendTo(container)
					.fancybox(fancyBoxOpts)
					.click(function(evt) {
						evt.preventDefault();
					});
					
				} else {
					
					container.html(object.text);
					
				}
				
				// Needs a class on the wrapper if there is a thumbnail
				if (container.find('img').length > 0) {
					container.parent().addClass('thumbnail-item');
					$select.select2('container').addClass('has-thumbnails');
					$select.data('select2').dropdown.addClass('has-thumbnails');
				}
				
				return undefined;
				
			}
			
			opts.formatResult = function(result, container, query, escapeMarkup) {
				return result.text;
			}
			
			if (typeof(opts.alwaysShowPlaceholder) == 'undefined') { opts.alwaysShowPlaceholder = true; }
			
			$select.select2(opts).change(onChange).select2('container').find('.select2-choices, .select2-choice').addClass('input-xxlarge');
			onChange();
			
			// Tell it we have thumbnails
			if ($select.select2('container').find('img').length > 0) {
				$select.select2('container').addClass('has-thumbnails');
				$select.data('select2').dropdown.addClass('has-thumbnails');
			}
			
			function onChange() {
				setFieldValue(name, $select.select2('val'));
			}
			
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