$(document).ready(function() {
	
	if ($('.widget.collapsible').length > 0) { initCollapsibleWidgets(); }
	if ($('.item-form').length > 0) { initItemForm(); }
	if ($('.item-list').length > 0) { initItemList(); }
	if ($('.permissions-list').length > 0) { initPermissionsList(); }
	if ($('#item-tree').length > 0) { initTree(); }
	if ($('input.datepicker').length > 0 || $('input.datetimepicker').length > 0) { initDatePickers(); }
	
});

$(window).load(function() {
	
	initFixedHeadTables();
	
});

function getFieldValue(fieldName, fallback) {
	if (typeof(field_values) == 'undefined') { return fallback; }
	if (typeof(field_values[fieldName]) != 'undefined') { return field_values[fieldName]; }
	return fallback;
}

function setFieldValue(fieldName, value) {
	if (typeof(field_values) == 'undefined') { field_values = {}; }
	field_values[fieldName] = value;

}

// Constructs the appropriate URL from the table name and ID, and sends off the specified data to be saved.
var cItemSaveRequest = null;
function saveData(table, id, _data) {
	
	var $itemForm = $('form.item-form'),
	formData = $itemForm.serializeArray();
	
	// Try and populate the stuff automatically if possible...
	if (typeof(data) == 'undefined') { data = null; }
	if (isNull(table) && isSet(data)) { table = data['table_name']; }
	if (isNull(id) && isSet(data)) { id = data['item_id']; }
	if (isNull(_data) && $('form.item-form').length > 0) {
		
		_data = {};
		
		for (var i = 0; i < formData.length; i++) {
			var name = formData[i]['name'] + '';
			if (name.indexOf('__TEMP__') > -1) { continue; }
			_data[name] = formData[i]['value'];
		}
		
	} else {
		
		// Associations need ID fields, otherwise the populate method will think they're new ones!
		for (var p in _data) {
			addAssociationIdField(p);
		}
		
	}
	
	// Don't run if anything is not set...
	if (isNull(table) || isNull(id) || isNull(_data)) { return false; }
	
	// Construct the URL and post the data
	var url = '/admin/' + table + '/' + id + '/populate';
	
	cItemSaveRequest = $.ajax({
		'url': url,
		'data': _data,
		'dataType': 'json',
		'async': true,
		'type': 'POST',
		'success': onSaved,
		'cache': false
	});
	
	function onSaved(result) {
		if (typeof(result['updated_at']) != 'undefined') {
			$('.status .updated-at').text(result['updated_at']);
		}
	}
	
	// Adds the id field for the association containing the given field
	function addAssociationIdField(field) {
		
		var parts = field.replace(/\]/g, '').split('[');
		if (parts.length < 2) { return; }
		var baseField = parts.shift();
		
		while (parts.length > 0) {
			parts[parts.length-1] = 'id';
			var idField = baseField + '[' + parts.join('][') + ']';
			var $field = $itemForm.find('input[name="' + idField + '"]').eq(0);
			if ($field.length > 0) {
				_data[idField] = $field.val();
				break;
			}
			parts.pop();
		}
		
	}
	
}

function initCollapsibleWidgets() {
	
	$('.widget.collapsible').each(function() {
		
		var el = $(this),
		showing = true,
		openIcon = 'chevron-down',
		closedIcon = 'chevron-right',
		titleBar = el.find('.widget-title').click(toggle),
		icon = $('<i class="toggle-arrow icon icon-' + openIcon + '"></i>').appendTo(titleBar);
		
		if (el.hasClass('closed')) {
			showing = false;
			icon.removeClass('icon-' + openIcon).addClass('icon-' + closedIcon);
		}
		
		function toggle() {
			if (showing) {
				hide();
			} else {
				show();
			}
			return false;
		}
		
		function show() {
			el.removeClass('closed');
			icon.removeClass('icon-' + closedIcon).addClass('icon-' + openIcon);
			showing = true;
		}
		
		function hide() {
			el.addClass('closed');
			icon.removeClass('icon-' + openIcon).addClass('icon-' + closedIcon);
			showing = false;
		}
		
	});
	
}

function initDatePickers() {
	
	$('input.datepicker').datepicker({
        dateFormat: "dd/mm/yy"
    });
    
    $('input.datetimepicker').datetimepicker({
		dateFormat: "dd MM yy",
        timeFormat: "hh:mm tt"
    });
    
}

function initTree() {
	
	if ($('#item-tree').length == 0) { return; }
	
	var el = $('#item-tree'),
	id = el.attr('id'),
	moveStartTime = 0,
	attemptedMove = false,
	permissions = data['item_permissions'];
	
	generateNewItemButton();

	el.tree({
        data: data['tree'],
        autoOpen: 0,
        dragAndDrop: true,
		saveState: id + '-cookie',
		onCreateLi: function(node, $li) {
			
			var className = node['class'],
			classData = data['classes'][className],
			baseUrl = '/admin/' + classData['table_name'];
			baseUrlId = '/admin/' + classData['table_name'] + '/' + node.id;
			
			node['div'] = $li.find('.main');
			node['title'] = $li.find('.jqtree-title');
			node['icon'] = classData['icon'];
			node['href'] = baseUrlId + '/edit';
			node['update'] = '/admin/' + data['table_name'] + '/' + node.id + '/updatetree';
			node['delete'] = baseUrlId + '/delete';
			node['visible'] = (node.visible === 1 || node.visible === true);
			node['hidden'] = (typeof node.hidden != 'undefined' && (node.hidden === true || node.hidden === 1));
			
			if (node.hidden || !node.visible) { node.div.addClass('hidden-item'); }
			if (!node.visible && node.children.length > 0) {
				var len = node.children.length;
				for (var i = 0; i < len; i++) {
					node.children[i].hidden = true;
				}
			}
			
			// Add the icon
			if (!$li.hasClass('jqtree-folder')) {
				node.title.prepend('<span class="icon-wrap"><span class="icon-' + node.icon + '"></span></span>');
			}
			
			var can_edit_item = !(typeof(permissions[node.id]) != 'undefined' && permissions[node.id].length > 0 && $.inArray('edit', permissions[node.id]) == -1);
			var can_edit = node['can_edit'] = classData['can_edit'] && can_edit_item;
			
			if (can_edit) {
				node.title.append(' <span class="edit-icon icon-pencil"></span>');
			} else {
				node.title.append(' <span class="edit-icon icon-lock"></span>');
			}
			
			var actionsContent = '<div class="actions pull-right">';
			//actionsContent += '<a href="#" class="show-hide ' + (node.visible ? 'visible' : '') + '"><i class="icon icon-eye-open"></i></a>';
			
			var childItems = [];
			var childInfo = [];
			
			if (can_edit) {
					
				for (var p in data['classes']) {
					var subclassData = data['classes'][p],
					baseUrl = '/admin/' + subclassData['table_name'];
					if (subclassData['static'] || subclassData['can_create'] !== true || subclassData['can_edit'] !== true) { continue; }
					childItems.push('<li><a tabindex="-1" href="' + baseUrl + '/create?parent=' + node.id + '"><i class="icon icon-' + subclassData['icon'] + '"></i> ' + subclassData['singular'] + '</a></li>');
					childInfo.push({ 'edit':baseUrl + '/create?parent=' + node.id, 'icon':subclassData['icon'], 'singular':subclassData['singular'] });
				}
				
				if (childItems.length > 1) {
					
					actionsContent += '<a class="btn btn-small btn-icon dropdown-toggle" data-toggle="dropdown" href="#" title="Add Child..."><i class="icon icon-plus"></i></a>' + 
					'<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">' + 
					'<li class="nav-header">Add a child...</li>' + 
					childItems.join('') + 
					'</ul>';
					
				} else if (childItems.length == 1) {
					
					actionsContent += '<a class="btn btn-small btn-icon" href="' + childInfo[0]['edit'] + '" rel="tooltip" title="Add Child ' + childInfo[0]['singular'] + '..."><i class="icon icon-plus"></i></a>';
					
				}
				
			}
			
			var can_delete_item = !(typeof(permissions[node.id]) != 'undefined' && permissions[node.id].length > 0 && $.inArray('delete', permissions[node.id]) == -1);
			var can_delete = node['can_delete'] = classData['can_delete'] && can_delete_item;
			
			if (!(typeof(classData.static) != 'undefined' && classData.static == true) && can_delete) {
				actionsContent += '<a class="btn btn-small btn-icon btn-danger btn-remove" rel="tooltip" title="Delete" href="' + node['delete'] + '" data-singular="' + classData['singular'] + '"><i class="icon icon-remove"></i></a>';
			}
			
			actionsContent += '</div>';
			
			// Add the actions
			var actions = $(actionsContent).appendTo(node.div);
			
			// Apply any js functionality to the actions
			actions.find('.dropdown-toggle').dropdown();
			
	    },
		onCanMove: function(node) {
			if (node['can_edit'] !== true) { return false; }
			attemptedMove = false;
			moveStartTime = new Date().getTime();
			return true;
		},
		onCanMoveTo: function(moved_node, target_node, position) {
			
			attemptedMove = true;
			
			if (target_node['can_edit'] !== true && position == 'inside') { return false; }
			if (target_node.is_root && position != 'inside') { return false; }
			
			var currentTime = new Date().getTime();
			var elapsedTime = currentTime - moveStartTime;
			
			if (elapsedTime < 300) { return false; }
			
			return true;
		}
    })

	.bind('tree.click', function(evt) {
		if (attemptedMove) {
			return false;
		}
		var node = evt.node;
		
		if (node['can_edit'] !== true) { return false; }
		
		if (typeof(node.href) != 'undefined' && node.href != null && node.href != '') {
			window.location.href = node.href;
		}
	})
	
	.bind('tree.move', function(evt) {
		
		evt.move_info.do_move();
		var cRequest = $.ajax({
			'url': evt.move_info.moved_node.update,
			'data': { 'position':evt.move_info.position, 'target':evt.move_info.target_node.id },
			'dataType': 'json',
			'async': true,
			'type': 'POST',
			'success': onTreeSaved,
			'cache': false
		});
		
		el.data('simple_widget_tree')._saveState();
		
		//$('a[rel="tooltip"]').tooltip({ 'placement':'top' });
		
		//console.log($(evt.target).index());
		evt.preventDefault();
		
	});
	
	// END TREE LOOP
	
	$('html').bind('contextmenu', function() {
		clearContextMenus();
	});
	
	function onTreeSaved(data) {
		
		// Nothing!
		
	}
	
	//$('a[rel="tooltip"]').tooltip({ 'placement':'top' });
	
	$('.btn-remove').each(function() {
		
		$(this).click(function() {
			
			return confirm("Do you really want to delete this " + $(this).attr('data-singular').toLowerCase() + "? You can't undo!");
		});
		
	});
	
	function generateNewItemButton(){
		
		var buttonContent = '<div class="actions pull-right">';
		
		var childItems = [];
		var childInfo = [];
		
		for (var p in data['classes']) {
			var subclassData = data['classes'][p],
			baseUrl = '/admin/' + subclassData['table_name'];
			if (subclassData['static'] || !subclassData['can_create'] || !subclassData['can_edit']) { continue; }
			childItems.push('<li><a tabindex="-1" href="' + baseUrl + '/create"><i class="icon icon-' + subclassData['icon'] + '"></i> ' + subclassData['singular'] + '</a></li>');
			childInfo.push({ 'edit':baseUrl + '/create', 'icon':subclassData['icon'], 'singular':subclassData['singular'] });
		}
		
		if (childItems.length > 1) {
			//group all the items into a dropdown
			buttonContent += '<div class="dropup pull-right">' + 
			'<button class="btn btn-large btn-primary dropdown-toggle"><i class="icon icon-plus icon-white"></i>  Add New ' + data.singular + ' <span class="caret"></span></button>' +
			'<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">' + 
			'<li class="nav-header">Choose a type...</li>' + 
			childItems.join('') + 
			'</ul></div>';
			
		} else if (childItems.length == 1) {
			//if there is only one item, make the button here
			buttonContent += '<a class="btn btn-large btn-primary" href="' + childInfo[0]['edit'] + '" title="Add Child ' + childInfo[0]['singular'] + '..."><i class="icon icon-plus icon-white"></i> Add New ' + childInfo[0]['singular'] + '</a>';
		}
		
		// Add the actions
		var buttonActions = $(buttonContent).appendTo($('#controls-fixed-bot .inner'));
		buttonActions.find('.dropdown-toggle').dropdown();
		
	}
	
}

function initItemList() {
	
	$('a[rel="tooltip"]').tooltip({ 'placement':'top' });
	
	$('.btn-remove').each(function() {
		
		$(this).click(function() {
			return confirm("Do you really want to delete this " + $(this).attr('data-singular').toLowerCase() + "? You can't undo!");
		});
		
	});
	
	var $table = $('.item-list .table');
	
	if ($table.hasClass('sortable')) {
		
		var $tableBody = $table.find("tbody"),
		$rows = $tableBody.find('tr'),
		$body = $('body');
		
		$tableBody.sortable({ helper:fixHelper, handle:'.handle' });
		
		$tableBody.sortable('option', 'start', function (evt, ui) {
			
			$body.addClass('sorting');
			
		});
		
		$tableBody.sortable('option', 'stop', function(evt, ui) {
			
			$body.removeClass('sorting');
			
			var group = ui.item.attr('data-sort-group'),
			pos = parseInt(ui.item.index()),
			id = ui.item.attr('data-id');
			
			if (group != undefined && group != '__ungrouped__' && group.length > 0) {
				$tableBody.find('tr[data-sort-group="' + group + '"]').each(function(i) {
					if ($(this).attr('data-id') == id) {
						pos = i;
						return false;
					}
				});
			}
			
			var cRequest = $.ajax({
				'url': '/admin/' + data['table_name'] + '/' + id + '/populate',
				'data': { 'pos':pos },
				'dataType': 'json',
				'async': true,
				'type': 'POST',
				'success': onListSaved,
				'cache': false
			});
			
		});
		
	}
	
	function onListSaved(result) {
		
		//console.log(result);
		
	}
	
}

function initItemForm() {
	
	var $itemForm = $('form.item-form').eq(0),
	submitted = false,
	hasTabs = $itemForm.find('.nav > li').length > 0,
	cookieId = data['model'],
	isNew = true;
	
	var tabCookie = $.cookie(cookieId+'_tab');
	
	if (isSet(data['item_id']) && data['item_id'] != '') {
		cookieId += '_' + data['item_id'];
		var otherTabCookie = $.cookie(cookieId+'_tab');
		if (isSet(otherTabCookie)) { tabCookie = otherTabCookie; }
		isNew = false;
	}
	
	$('#controls-fixed-bot .btn-remove').each(function() {
		
		$(this).click(function() {
			return confirm("Do you really want to delete this " + $(this).attr('data-singular').toLowerCase() + "? You can't undo!");
		});
		
	});
	
	$('a.btn-permissions[rel="tooltip"]').tooltip({ 'placement':'left' });
	
	$('form .submit-item').click(function() {
		$(this).button('loading');
	});
	
	$itemForm.find('.nav > li > a').each(function() {
		var el = $(this);
		var selector = el.attr('href');
		var tabId = selector.replace('#', '');
		
		var tab = $(selector);
		if (tab.length > 0 && (tab.find('.error').length > 0 || tabId === tabCookie)) {
			$(this).tab('show');
		}
		
		el.click(function (e) {
		    e.preventDefault();
		    el.tab('show');
		    return false;
	    });
	});
	
	$('.field-type-date').each(function() {
		
		$(this).find('input').datepicker({
	        dateFormat: "dd/mm/yy",
	        changeMonth: true,
	        changeYear: true,
	        yearRange: "c-20:c+20"
	    });
		
	});
	
	$('.datetimepicker input[type="text"]').datetimepicker({
		dateFormat: "dd MM yy",
        timeFormat: "hh:mm tt"
    });
    
    // Save the initial state of the form to compare against later
    var initialFormData = null;
    $(window).load(function() {
    	initialFormData = getFormString($itemForm);
    });
    
    // Ask whether people want to leave the page if unsaved changes have been made
    $(window).bind('beforeunload', onBeforeUnload);
    
    $itemForm.submit(function() {
    	
    	// Save the tab position if there is one
    	if (hasTabs) {
    		
    		var date = new Date();
    		date.setTime(date.getTime() + 10000);
    		var tabId = $itemForm.find('.nav > li.active > a').attr('href').replace('#', '');
    		var cookieOptions = { expires: date };
    		if (isNew) { cookieOptions['path'] = '/'; }
    		$.cookie(cookieId + '_tab', tabId, cookieOptions);
    		
    	}
    	
    	// There are some situations when we don't want to prompt before leaving the page
    	$(window).unbind('beforeunload', onBeforeUnload);
    	
    });
    
    $('#controls-fixed-bot .btn-remove').click(function() {
    	$(window).unbind('beforeunload', onBeforeUnload);
    });
    
    function onBeforeUnload() {
    	
    	var e = e || window.event;
    	
    	var latestFormData = getFormString($itemForm);
    	
    	if (initialFormData != null && latestFormData != initialFormData) {
    		console.log(latestFormData.substr(0, 120));
    		console.log(initialFormData.substr(0, 120));
    		var msg = "There are potentially unsaved changes to this item. You have two options:\n\n1) Stay and click the 'save' button.\n\n2) Continue and they may be lost.";
    		e.returnValue = msg;
    		return msg;
    	} else {
    		return;
    	}
    	
    }
    
}

function getFormString($form) {
	
	var formData = $form.serializeArray();
	formData.sort(function(a, b) {
		var textA = a.name.toUpperCase();
		var textB = b.name.toUpperCase();
		return (textA < textB) ? -1 : (textA > textB) ? 1 : 0;
	});
	
	var output = '';
	for (var i = 0; i < formData.length; i++) {
		output += formData[i].name + '=' + formData[i].value + '&';
	}
	return output;
	
}

function initPermissionsList() {
	
	var matrix = new CheckboxMatrix($('.permissions-list .checkbox-matrix')),
	cRequest = null;
	
	matrix.onChange = onMatrixChange;
	
	function onMatrixChange(changed) {
		
		// Abort the current request if it's not complete already
		//if (cRequest != null && cRequest.readyState != 4) {
		//	cRequest.abort();
		//}
		
		cRequest = $.ajax({
			'url': '/admin/' + data['table_name'] + '/permissions/' + data['role_id'] + '/save',
			'data': changed,
			'dataType': 'json',
			'async': true,
			'type': 'POST',
			'success': onSaved,
			'cache': false
		});
		
	}
	
	function onSaved(data) {
		// Nothing
	}
	
}

/************************ CHECKBOX MATRIX ************************/

function CheckboxMatrix($wrap) {
	
	var self = this,
	rows = [],
	cols = [];
	
	self.onChange = null;
	
	function init() {
		initRows();
		initCols();
	}
	
	function initRows() {
		$wrap.find('.item-row').each(function(i) {
			var row = new CheckboxRow($(this));
			row.onChange = onChange;
			rows.push(row);
		});
	}
	
	function initCols() {
		$wrap.find('th.item-action input[type="checkbox"]').each(function() {
			var col = new CheckboxColumn($wrap, $(this));
			col.onChange = onChange;
			cols.push(col);
		});
	}
	
	function onChange() {
		var numChanged = 0;
		var changed = {};
		for (var i = 0; i < rows.length; i++) {
			var row = rows[i];
			if (row.changed === true) {
				row.changed = false;
				numChanged++;
				changed[row.id] = row.values;
			}
		}
		if (numChanged > 0 && self.onChange !== null) { self.onChange(changed); }
	}
	
	init();
	
}

// Wraps up the functionality for a column
function CheckboxColumn($wrap, $allInput) {
	
	var self = this,
	actionId = $allInput.attr('data-action'),
	$inputs = $wrap.find('td.item-action input[data-action="' + actionId + '"]').change(onChange),
	numInputs = $inputs.length;
	
	self.all = null;
	self.onChange = null;
	$allInput.change(updateAll);
	
	function init() {
		
		onChange();
		//updateAll();
		
	}
	
	function setAll(value, trigger) {
		
		if (value == self.value) {
			if (self.onChange !== null && typeof(trigger) != 'undefined' && trigger === true) { self.onChange(); }
			return;
		}
		
		$allInput.prop('checked', value);
		self.all = value;
		
		if (typeof(trigger) != 'undefined' && trigger === true) {
			$inputs.prop('checked', value).change();
			if (self.onChange !== null) { self.onChange(); }
		}
		
	}
	
	function updateAll() {
		
		setAll($allInput.prop('checked'), true);
		
	}
	
	function onChange(evt) {
		
		if (typeof(evt) != 'undefined' && typeof(evt.originalEvent) != 'undefined') {
			if (self.onChange !== null) { self.onChange(); }
		}
		
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
	itemId = $wrap.attr('data-item-id'),
	$inputs = $wrap.find('input[type="checkbox"]').not('.all-actions').change(onChange),
	$allInput = $wrap.find('input.all-actions').change(updateAll),
	$itemLabel = $wrap.find('td.item-label'),
	inputsByAction = {},
	numInputs = $inputs.length,
	cValStr = '-';
	
	// Exposed variables
	self.id = itemId;
	self.all = null;
	self.setAll = setAll;
	self.values = {};
	self.onChange = null;
	self.changed = false;
	
	function init() {
		
		$inputs.each(function() {
			var $el = $(this);
			inputsByAction[$el.attr('data-action')] = $el;
		});
		inputsByAction['all'] = $allInput;
		
		self.all = $allInput.prop('checked')
		onChange();
		if (self.all) {
			$allInput.prop('checked', true);
			$inputs.prop('checked', true).css({ 'opacity':.4 });
		}
		
		$inputs.change();
		self.changed = false;
		
		$itemLabel.click(function() {
			setAll(!$allInput.prop('checked'), true);
			if (self.onChange !== null) { self.onChange(); }
			return false;
		});
		
	}
	
	function setAll(value, trigger) {
		
		if (value == self.value && typeof(trigger) != 'undefined' && trigger === true) {
			//if (self.onChange !== null) { self.onChange(self); }
			return;
		}
		
		$allInput.prop('checked', value);
		if (self.all = value) {
			$inputs.prop('checked', true).css({ 'opacity':.4 });
		} else {
			$inputs.prop('checked', false).css({ 'opacity':1 });
		}
		
		if (typeof(trigger) != 'undefined' && trigger === true) {
			$inputs.change();
			//if (self.onChange !== null) { self.onChange(self); }
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
		} else if (numChecked == 0 && numInputs == 1) {
			$wrap.removeClass('warning success').addClass('error');
			$allInput.prop('checked', false);
		} else if (numChecked == 0) {
			$wrap.removeClass('warning success').addClass('error');
		} else {
			$wrap.removeClass('error success').addClass('warning');
			$allInput.prop('checked', false);
			$inputs.css({ 'opacity':1 });
		}
		
		var valsArr = [];
		for (var p in inputsByAction) {
			self.values[p] = inputsByAction[p].prop('checked') ? 1 : 0;
			if (self.values[p] === 1) { valsArr.push(p); }
		}
		var newValStr = valsArr.join(',');
		if (newValStr !== cValStr) { self.changed = true; }
		cValStr = newValStr;
		
	}
	
	init();
	
}

function initFixedHeadTables() {
	
	$('table.fixed-head').each(function() {
		
		var cTable = new FixedTableHeader($(this));
		cTable.init();
		
	});
	
}

FixedTableHeader = function($table) {
	
	var $window = $(window), $content = $('#content'), $cloneWrap, $tableClone, $tableTitles, $titles, gridOffset = $table.position().top, headerShowing = false;
	
	function init() {

		// create fixed row
		var headerContents = $table.children('thead').clone();
		$tableClone = $('<table>').attr( { 'class': $table.attr('class'), 'id': 'table-fixed-head' } ).html(headerContents);
		$cloneWrap = $('<div>').attr('id', "table-fixed-head-wrap").html($tableClone).hide();
		$tableTitles = $table.children('thead').children('tr:last').children('th');
		$titles = $tableClone.children('thead').children('tr:last').children('th');
		
		setColumnWidths();
		
		//insert fixed header
		$cloneWrap.insertAfter($content);
		
		bindEvents();
		checkHeaderVisibility();
		
	}
	
	function bindEvents() {
		$content.scroll(function() {
			checkHeaderVisibility();
		});

		$window.resize(function() {
			setColumnWidths();
		});
	}
	
	function setColumnWidths() {
		$tableTitles.each(function(i) {
			var titleWidth = $(this).width();
			$titles.eq(i).width(titleWidth);
		});
	}
	
	function checkHeaderVisibility() {
		var scrollTop = $content.scrollTop();
		if(!headerShowing && scrollTop >= gridOffset) {
			$cloneWrap.show();
			headerShowing = true;
		} else if (headerShowing && scrollTop < gridOffset){
			$cloneWrap.hide();			
			headerShowing = false;
		}
	}
	
	return {
		init : init
	}
};

var fixHelper = function(e, ui) {
	ui.children().each(function() {
		$(this).width($(this).width());
	});
	return ui;
};



