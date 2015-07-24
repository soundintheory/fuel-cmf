(function($) {
	
	$(document).ready(init);
	
	function init() {
		
		$.fn.bootstrapTransfer.defaults['template'] = '<table width="100%" cellspacing="0" cellpadding="0">\
            <tr>\
                <td width="50%">\
                    <div class="selector-available">\
                        <h2>Available</h2>\
                        <div class="selector-filter">\
                            <table width="100%" border="0">\
                                <tr>\
                                    <td style="width:16px;" class="search-icon">\
                                        <i class="fa fa-search"></i>\
                                    </td>\
                                    <td>\
                                        <div style="padding-left:10px;">\
                                            <input type="text" class="filter-input">\
                                        </div>\
                                    </td>\
                                </tr>\
                            </table>\
                        </div>\
                        <select multiple="multiple" class="filtered remaining">\
                        </select>\
                        <a href="#" class="selector-chooseall">Choose all&nbsp; <i class="fa fa-caret-right"></i></a>\
                    </div>\
                </td>\
                <td>\
                    <div class="selector-chooser">\
                        <a href="#" class="selector-add"><i class="fa fa-circle-arrow-right"></i></a>\
                        <a href="#" class="selector-remove"><i class="fa fa-circle-arrow-left"></i></a>\
                    </div>\
                </td>\
                <td width="50%">\
                    <div class="selector-chosen">\
                        <h2>Chosen</h2>\
                        <div class="selector-filter right">\
                            <span><em>Select then click</em> <i class="fa fa-circle-arrow-right action-arrow"></i></span>\
                        </div>\
                        <select multiple="multiple" class="filtered target">\
                        </select>\
                        <a href="#" class="selector-clearall"><i class="fa fa-caret-left"></i>&nbsp; Clear all</a>\
                    </div>\
                </td>\
            </tr>\
        </table>';
        
        $.fn.bootstrapTransfer.defaults['height'] = '12em';
		
		$('.field-type-multiselect-transfer').each(function() {
			
			var $wrap = $(this),
			$transfer = $wrap.find('.transfer-wrap'),
			$select = $wrap.find('select').html(''),
			name = $select.attr('name'),
			t = $transfer.bootstrapTransfer({ 'hilite_selection': false });
			
			name = name.replace('[]', '');
			
			t.populate(field_settings[name]['options']);
			t.set_values(field_settings[name]['values']);
			
			$transfer.on('transfer.update', updateTransfer);
			updateTransfer();
			
			function updateTransfer() {
				
				var values = t.get_values();
				$select.html('');
				
				for (var i = 0; i < values.length; i++) {
					$select.append($('<option value="' + values[i] + '" style="text-indent: 10px;" selected="selected">' + values[i] + '</option>'));
				}
				
			}
			
		});
		
	}
	
})(jQuery);