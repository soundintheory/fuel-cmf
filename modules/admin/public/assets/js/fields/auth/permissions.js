(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$('.field-type-permissions').each(function() {
			
			var $wrap = $(this),
			matrix = new PermissionsMatrix($wrap);
			
			
		});
		
	}
	
	function PermissionsMatrix($wrap) {
		
		var self = this,
		rows = [],
		cols = [];
		
		function init() {
			initRows();
			initCols();
		}
		
		function initRows() {
			$wrap.find('.resource-row').each(function(i) {
				rows.push(new CheckboxRow($(this)));
			});
		}
		
		function initCols() {
			$wrap.find('th.resource-action input[type="checkbox"]').each(function() {
				cols.push(new CheckboxColumn($wrap, $(this)));
			});
		}
		
		init();
		
	}
	
	// Wraps up the functionality for a column
	function CheckboxColumn($wrap, $allInput) {
		
		var self = this,
		actionId = $allInput.attr('data-action'),
		$inputs = $wrap.find('td.resource-action input[data-action="' + actionId + '"]').change(onChange),
		numInputs = $inputs.length;
		
		self.all = null;
		$allInput.change(updateAll);
		
		function init() {
			
			updateAll();
			
		}
		
		function setAll(value, trigger) {
			
			if (value == self.value) { return; }
			
			$allInput.prop('checked', value);
			self.all = value;
			
			if (typeof(trigger) != 'undefined' && trigger === true) {
				$inputs.prop('checked', value).change();
			}
			
		}
		
		function updateAll() {
			
			setAll($allInput.prop('checked'), true);
			
		}
		
		function onChange() {
			
			var numChecked = $inputs.filter(':checked').length;
			
			// If the number selected is the whole row,  the 'all' checkbox kicks in...
			if (numChecked == numInputs) {
				setAll(true, false);
			} else {
				$allInput.prop('checked', false);
			}
			
		}
		
		init();
		
	}
	
	// Wraps up the functionality for a row
	function CheckboxRow($wrap) {
		
		var self = this,
		resourceId = $wrap.attr('data-resource'),
		$inputs = $wrap.find('input[type="checkbox"]').not('.all-actions').change(onChange),
		$allInput = $wrap.find('input.all-actions').change(updateAll),
		numInputs = $inputs.length;
		
		// Exposed variables
		self.all = null;
		self.setAll = setAll;
		
		function init() {
			
			updateAll();
			
		}
		
		function setAll(value, trigger) {
			
			if (value == self.value) { return; }
			
			$allInput.prop('checked', value);
			if (self.all = value) {
				$inputs.prop('checked', true).css({ 'opacity':.4 });
			} else {
				$inputs.prop('checked', false).css({ 'opacity':1 });
			}
			
			if (typeof(trigger) != 'undefined' && trigger === true) {
				$inputs.change();
			}
			
		}
		
		function updateAll() {
			
			setAll($allInput.prop('checked'), true);
			
		}
		
		function onChange() {
			
			var numChecked = $inputs.filter(':checked').length;
			
			// If the number selected is the whole row,  the 'all' checkbox kicks in...
			if (numChecked == numInputs) {
				$wrap.removeClass('error warning').addClass('success');
				setAll(true, false);
			} else if (numChecked == 0) {
				$wrap.removeClass('warning success').addClass('error');
			} else {
				$wrap.removeClass('error success').addClass('warning');
				$allInput.prop('checked', false);
				$inputs.css({ 'opacity':1 });
			}
			
			
		}
		
		init();
		
	}
	
})(jQuery);