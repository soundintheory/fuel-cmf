(function($) {
	
	// Utility method to get and set objects that may or may not exist
	var objectifier = function(splits, create, context) {
		var result = context || window;
		for(var i = 0, s; result && (s = splits[i]); i++) {
			result = (s in result ? result[s] : (create ? result[s] = {} : undefined));
		}
		return result;
	};

	// Gets or sets an object
	jQuery.obj = function(name, value, create, context) {
		// Setter
		if(value != undefined) {
			var splits = name.split("."), s = splits.pop(), result = objectifier(splits, true, context);
			return result && s ? (result[s] = value) : undefined;
		}
		// Getter
		else {
			return objectifier(name.split("."), create, context);
		}
	};
	
	$(document).ready(init);
	
	function init() {
		
		Twig.extendFilter('slug', generateSlug);
		
		function TemplateFields() {
			
			var self = this,
			fields = {},
			listeners = [];
			
			self.data = {};
			
			self.addField = function(fieldName, dotNotation) {
				if (typeof(fields[fieldName]) != 'undefined') { return true; }
				var field = $('input[name="' + fieldName + '"], textarea[name="' + fieldName + '"]')
				.attr('data-dot-notation', dotNotation);
				
				jQuery.obj(dotNotation, 'null', true, self.data);
				
				if (field.length === 0) { return false; }
				field.change(onFieldChange).keyup(onFieldChange);
				fields[fieldName] = field;
				
				return true;
			}
			
			self.update = function() {
				for (var p in fields) {
					fields[p].change();
				}
			}
			
			function onFieldChange() {
				var dotNotation = $(this).attr('data-dot-notation'),
				value = $(this).val();
				jQuery.obj(dotNotation, value, true, self.data);
				for (var i = 0; i < listeners.length; i++) {
					if (typeof(listeners[i]) == 'function') {
						listeners[i]();
					}
				}
			}
			
			self.addListener = function(func) {
				listeners.push(func);
			}
			
		}
		
		templateFields = new TemplateFields();
		
		$('.field-with-template').each(function() {
			
			var $wrap = $(this),
			$input = $wrap.find('input[type="text"], textarea'),
			$checkbox = $wrap.find('input.auto-update').change(onCheckboxChange),
			fieldName = $input.attr('name'),
			settings = field_settings[fieldName],
			autoUpdate = false,
			value = $input.val(),
			data = {},
			valid = true,
			template = twig({
				data: settings['template']
			});
			
			initTokens();
			
			if (valid) {
				templateFields.addListener(updateText);
				templateFields.update();
				onCheckboxChange();
			} else {
				$wrap.find('.auto-update-label').hide();
			}
			
			function initTokens() {
				
				for (var i = 0; i < template.tokens.length; i++) {
					
					var cToken = template.tokens[i];
					if (cToken.type == 'output') {
						
						var inputName = '',
						dotNotation = '';
						
						for (var j = 0; j < cToken.stack.length; j++) {
							
							if (cToken.stack[j].type == 'Twig.expression.type.variable') {
								inputName += cToken.stack[j].value;
								dotNotation += cToken.stack[j].value;
							} else if (cToken.stack[j].type == 'Twig.expression.type.key.period') {
								inputName += '[' + cToken.stack[j].key + ']';
								dotNotation += '.' + cToken.stack[j].key;
							}
							
						}
						
						if (!templateFields.addField(inputName, dotNotation)) {
							valid = false;
							break;
						}
						
					}
					
				}
				
			}
			
			function onCheckboxChange() {
				
				autoUpdate = $checkbox.prop('checked');
				if (autoUpdate) {
					$input.attr('readonly', 'readonly');
				} else {
					$input.removeAttr('readonly');
				}
				
				updateText();
				
			}
			
			function updateText() {
				
				if (!autoUpdate) { return; }
				
				if (template != '') {
					value = template.render(templateFields.data);
				}
				
				$input.val(value).change();
				
			}
			
			
		});
		
	}
	
	
	
})(jQuery);