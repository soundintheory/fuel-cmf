
// Translate function
_ = function(key, vars)
{
	var parts = key.split('.');
	if (typeof(lang) == 'undefined') { return parts[parts.length-1]; }

	// Try using the current language
	var current = (typeof(current_lang) != 'undefined' && !!current_lang) ? current_lang : 'en';
	var fallback = (typeof(fallback_lang) != 'undefined' && !!fallback_lang) ? fallback_lang : ['en'];
	var output = _get(lang, current + '.' + key);

	// Go through the fallback languages and try them
	if (!output && !!fallback && fallback.length)
	{
		for (var i = 0; i < fallback.length; i++) {
			if (fallback[i] == current) { continue; }
			output = _get(lang, fallback[i] + '.' + key);
			if (!!output) { break; }
		}
	}

	// If we have output and vars have been passed, try to fill them
	if (!!output && !!vars) {
		for (var p in vars) {
			output = output.replace(new RegExp(':'+p, 'gi'), vars[p]);
		}
	}

	// Try using the current language
	return !!output ? output : parts[parts.length-1];
}

function _get(obj, prop)
{
	var parts = prop.split('.'),
		current = obj,
		output = null;

	for (var i = 0; i < parts.length; i++)
	{
		// If it's not set, we're at a dead end so stop
		if (typeof(current[parts[i]]) == 'undefined') {
			output = null;
			break;
		}

		// Set the next part
		current = current[parts[i]];

		// If it's an object, carry on descending into it
		if (typeof(current) == 'object' && current != null) {
			continue;
		}

		// It's a value that can be returned
		output = current;
		break;
	}

	return output;
}

$(document).ready(function() {
	
	if ($('.widget.collapsible').length > 0) { initCollapsibleWidgets(); }
	if ($('.item-form').length > 0) { initItemForm(); }
	if ($('.item-list').length > 0) { initItemList(); }
	if ($('.permissions-list').length > 0) { initPermissionsList(); }
	if ($('#item-tree').length > 0) { initTree(); }
	if ($('input.datepicker').length > 0 || $('input.datetimepicker').length > 0) { initDatePickers(); }
	if ($('.fileinput-button').length > 0) { $('.fileinput-button').bootstrapFileInput(); }
	initCopyFields();

	$('.dropdown-toggle').dropdown();
	
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
	var url = CMF.adminUrl + '/' + table + '/' + id + '/populate';
	
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

function initCopyFields() {

	$('body').on('click', '.click-copy-field', function() {
		$(this).select();
	});

}

function initCollapsibleWidgets() {
	
	$('.widget.collapsible').each(function() {
		
		var el = $(this),
		showing = true,
		openIcon = 'chevron-down',
		closedIcon = 'chevron-right',
		titleBar = el.find('.widget-title').click(toggle),
		icon = $('<i class="toggle-arrow fa fa-' + openIcon + '"></i>').appendTo(titleBar);
		
		if (el.hasClass('closed')) {
			showing = false;
			icon.removeClass('fa-' + openIcon).addClass('fa-' + closedIcon);
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
			icon.removeClass('fa-' + closedIcon).addClass('fa-' + openIcon);
			showing = true;
		}
		
		function hide() {
			el.addClass('closed');
			icon.removeClass('fa-' + openIcon).addClass('fa-' + closedIcon);
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

var getHostName = function(href) {
    var l = document.createElement("a");
    l.href = href;
    return l.hostname || href;
};

function initTree() {
	
	if ($('#item-tree').length == 0) { return; }
	
	var el = $('#item-tree'),
	id = el.attr('id'),
	moveStartTime = 0,
	attemptedMove = false,
	permissions = data['item_permissions'],
	baseClassData = data['classes'][data['class_name']];
	
	generateNewItemButton();

	el.tree({
        data: data['tree'],
        autoOpen: 0,
        dragAndDrop: true,
		saveState: id + '-cookie',
		onCreateLi: function(node, $li) {
			
			var className = node['class'],
			classData = data['classes'][className],
			baseUrl = CMF.adminUrl + '/' + classData['table_name'],
			baseUrlId = CMF.adminUrl + '/' + classData['table_name'] + '/' + node.id;
			
			node['div'] = $li.find('.main');
			node['title'] = $li.find('.jqtree-title').text(node.menu_title);
			node['icon'] = classData['icon'];
			node['href'] = baseUrlId + '/edit';
			node['update'] = CMF.adminUrl + '/' + data['table_name'] + '/' + node.id + '/updatetree';
			node['delete'] = baseUrlId + '/delete';
			node['clone'] = baseUrlId + '/duplicate';
			node['visible'] = (node.visible === 1 || node.visible === true);
			node['hidden'] = (typeof node.hidden != 'undefined' && (node.hidden === true || node.hidden === 1));

			// If this item is an alias, append ?alias to the edit URL
			if (node['url'] && (node['url']['alias'] || node['url']['type'] == 'External')) {
				node['href'] += '?alias';
				node['icon'] = 'link';
			}

			if (node.hidden || !node.visible) { node.div.addClass('hidden-item'); }
			if (!node.visible && node.children.length > 0) {
				var len = node.children.length;
				for (var i = 0; i < len; i++) {
					node.children[i].hidden = true;
				}
			}

			// Add the icon
			if (!$li.hasClass('jqtree-folder')) {
				node.title.prepend('<span class="fa fa-wrap"><span class="fa fa-' + node.icon + '"></span></span>');
			}

			var can_edit_item = !(typeof(permissions[node.id]) != 'undefined' && permissions[node.id].length > 0 && $.inArray('edit', permissions[node.id]) == -1);
			var can_edit = node['can_edit'] = classData['can_edit'] && can_edit_item;

			if (node.settings && node.settings.original_id) {
				var importedContent = 'imported';
				if (node.settings.imported_from) {
					importedContent += ' from '+getHostName(node.settings.imported_from);
				}
				node.title.append(' &nbsp;<i class="muted">'+importedContent+'</i>');
			}

			if (can_edit) {
				node.title.append(' <span class="edit-icon fa fa-pencil"></span>');
			} else {
				node.title.append(' <span class="edit-icon fa fa-lock"></span>');
			}

			var actionsContent = '<div class="actions pull-right">';
			//actionsContent += '<a href="#" class="show-hide ' + (node.visible ? 'visible' : '') + '"><i class="fa fa-eye-open"></i></a>';

			var childItems = [];
			var childInfo = [];
			var allowedChildren = (data['classes'][node['class']] || {}).allowed_children;
			var disallowedChildren = (data['classes'][node['class']] || {}).disallowed_children;

			if (can_edit) {

				for (var p in data['classes'])
				{
					var subclassData = data['classes'][p],
					baseUrl = CMF.adminUrl + '/' + subclassData['table_name'];

					// Check if this allows the class as a child
					if ((!!allowedChildren && $.inArray(p, allowedChildren) === -1) ||
						(!!disallowedChildren && $.inArray(p, disallowedChildren) > -1)) {
						continue;
					}

					// Also check if the class allows this as a parent
					if ((!!subclassData.allowed_parents && $.inArray(node['class'], subclassData.allowed_parents) === -1) ||
						(!!subclassData.disallowed_parents && $.inArray(node['class'], subclassData.disallowed_parents) > -1)) {
						continue;
					}

					if (subclassData['static'] || subclassData['can_create'] !== true || subclassData['can_edit'] !== true || subclassData['superclass']) { continue; }
					childItems.push('<li><a tabindex="-1" href="' + baseUrl + '/create?parent=' + node.id + '"><i class="fa fa-' + subclassData['icon'] + '"></i> ' + subclassData['singular'] + '</a></li>');
					childInfo.push({ 'edit':baseUrl + '/create?parent=' + node.id, 'icon':subclassData['icon'], 'singular':subclassData['singular'] });
				}

				// An alias type
				childItems.push('<li><a tabindex="-1" href="' + CMF.adminUrl + '/' + baseClassData['table_name'] + '/create?parent=' + node.id + '&alias"><i class="fa fa-link"></i> Link</a></li>');
				childInfo.push({ 'edit': CMF.adminUrl + '/' + baseClassData['table_name'] + '/create?parent=' + node.id + '&alias', 'icon':baseClassData['icon'], 'singular':baseClassData['singular'] });

				if (childItems.length > 1) {

					actionsContent += '<a class="btn btn-small btn-icon dropdown-toggle" data-toggle="dropdown" href="#" title="' + _('admin.common.add_child') + '"><i class="fa fa-plus"></i></a>' +
					'<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">' +
					'<li class="nav-header">' + _('admin.common.add_child') + '</li>' +
					childItems.join('') +
					'</ul>';

				} else if (childItems.length == 1) {

					actionsContent += '<a class="btn btn-small btn-icon" href="' + childInfo[0]['edit'] + '" rel="tooltip" title="' + _('admin.common.add_child_resource', { resource:childInfo[0]['singular'] }) + '"><i class="fa fa-plus"></i></a>';

				}

			}

			if (classData['can_create'] && !classData['static']) {
				actionsContent += '<a class="btn btn-small btn-icon" href="'+ node['clone'] + '" rel="tooltip" title="' + _('admin.verbs.clone') + '"><i class="fa fa-clone"></i></a>';
			}
			
			var can_delete_item = !(typeof(permissions[node.id]) != 'undefined' && permissions[node.id].length > 0 && $.inArray('delete', permissions[node.id]) == -1);
			var can_delete = node['can_delete'] = classData['can_delete'] && can_delete_item;
			
			if (!(typeof(classData.static) != 'undefined' && classData.static == true) && can_delete) {
				actionsContent += '<a class="btn btn-small btn-icon btn-danger btn-remove" rel="tooltip" title="' + _('admin.verbs.delete') + '" href="' + node['delete'] + '" data-singular="' + classData['singular'] + '"><i class="fa fa-remove"></i></a>';
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

			var movedClassInfo = data['classes'][moved_node['class']] || {},
				parentClass = null,
				parentClassInfo = null;

			if (position == 'inside') {
				parentClass = target_node['class'];
				parentClassInfo = data['classes'][parentClass] || {};
			} else {
				parentClass = ((target_node.parent || {})['class'] || 'root');
				parentClassInfo = data['classes'][parentClass] || {};
			}

			if ((!!parentClassInfo.disallowed_children && $.inArray(moved_node['class'], parentClassInfo.disallowed_children) > -1) ||
				(!!movedClassInfo.disallowed_parents && $.inArray(parentClass, movedClassInfo.disallowed_parents) > -1)) {
				return false;
			}

			if ((!!parentClassInfo.allowed_children && $.inArray(moved_node['class'], parentClassInfo.allowed_children) === -1) ||
				(!!movedClassInfo.allowed_parents && movedClassInfo.allowed_parents.length && $.inArray(parentClass, movedClassInfo.allowed_parents) === -1)) {
				return false;
			}
			
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
			
			return confirm(_('admin.messages.item_delete_confirm'));
		});
		
	});
	
	function generateNewItemButton(){
		
		var buttonContent = '<div class="actions pull-right">';
		
		var childItems = [];
		var childInfo = [];
		
		for (var p in data['classes'])
		{
			var subclassData = data['classes'][p],
			baseUrl = CMF.adminUrl + '/' + subclassData['table_name'];

			// Check if the class allows root as a parent
			if ((!!subclassData.allowed_parents && $.inArray('root', subclassData.allowed_parents) === -1) ||
				(!!subclassData.disallowed_parents && $.inArray('root', subclassData.disallowed_parents) > -1)) {
				continue;
			}

			if (subclassData['static'] || !subclassData['can_create'] || !subclassData['can_edit'] || subclassData['superclass']) { continue; }
			childItems.push('<li><a tabindex="-1" href="' + baseUrl + '/create"><i class="fa fa-' + subclassData['icon'] + '"></i> ' + subclassData['singular'] + '</a></li>');
			childInfo.push({ 'edit':baseUrl + '/create', 'icon':subclassData['icon'], 'singular':subclassData['singular'] });
		}

		// An alias type
		childItems.push('<li><a tabindex="-1" href="' + CMF.adminUrl + '/' + baseClassData['table_name'] + '/create?alias"><i class="fa fa-link"></i> Link</a></li>');
		childInfo.push({ 'edit':CMF.adminUrl + '/' + baseClassData['table_name'] + '/create?alias', 'icon':baseClassData['icon'], 'singular':baseClassData['singular'] });

		if (childItems.length > 1) {
			//group all the items into a dropdown
			buttonContent += '<div class="dropup pull-right">' + 
			'<button class="btn btn-large btn-primary dropdown-toggle"><i class="fa fa-plus"></i> ' + _('admin.common.add_new_resource', { resource:data.singular.toLowerCase() }) + ' <span class="caret"></span></button>' +
			'<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">' + 
			'<li class="nav-header">' + _('admin.common.choose_a_type') + '</li>' + 
			childItems.join('') + 
			'</ul></div>';
			
		} else if (childItems.length == 1) {
			//if there is only one item, make the button here
			buttonContent += '<a class="btn btn-large btn-primary" href="' + childInfo[0]['edit'] + '" title="' + _('admin.common.add_child_resource', { resource:childInfo[0]['singular'].toLowerCase() }) + '"><i class="fa fa-plus"></i> ' + _('admin.common.add_new_resource', { resource:childInfo[0]['singular'].toLowerCase() }) + '</a>';
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
			return confirm(_('admin.messages.item_delete_confirm'));
		});
		
	});

	if ($('.list-filter-select').length > 0) {
		$('.list-filter-select').on('change', function() {
			$form = $(this).parents('form.list-filter-form').submit();
		});
	}
	
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

			if ($table.hasClass('sort-process')) {

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
					'url': CMF.adminUrl + '/' + data['table_name'] + '/' + id + '/populate',
					'data': { 'pos':pos },
					'dataType': 'json',
					'async': true,
					'type': 'POST',
					'success': onListSaved,
					'cache': false
				});

			} else {

				var positions = {};

				$table.find('tr').each(function(i, el) {
					var cid = $(el).attr('data-id');
					positions[cid] = { pos:i+1 };
				});

				var cRequest = $.ajax({
					'url': CMF.adminUrl + '/' + data['table_name'] + '/populate',
					'data': positions,
					'dataType': 'json',
					'async': true,
					'type': 'POST',
					'success': onListSaved,
					'cache': false
				});

			}
			
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
			return confirm(_('admin.messages.item_delete_confirm'));
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

	// When a new form is added, run it again!
	$(window).bind('cmf.newform', function(e, data) {
		data.wrap.each(dateTimePickers);
	});

	function dateTimePickers() {

		$(this).find('.field-type-date').each(function() {
			
			$(this).find('input').not('[name*="__TEMP__"]').datepicker({
		        dateFormat: "dd/mm/yy",
		        changeMonth: true,
		        changeYear: true,
		        yearRange: "c-20:c+20"
		    });
			
		});
		
		$(this).find('.field-type-datetime').each(function() {

            var dateOptions = $.parseJSON($(this).find('input').data('options').replace(/'/g, '"'));

            $(this).find('input').not('[name*="__TEMP__"]').datetimepicker({
                dateFormat: dateOptions.dateFormat?dateOptions.dateFormat:"dd/mm/yy",
                timeFormat: dateOptions.timeFormat?dateOptions.timeFormat:"hh:mm",
                changeMonth: true,
                changeYear: true,
                yearRange: "c-20:c+20"
            });
			
		});

        $(this).find('.field-type-time').each(function() {

            $(this).find('input').not('[name*="__TEMP__"]').timepicker({
                timeFormat: "hh:mm",
            });

        });

	}

	$('body').each(dateTimePickers);
	
	/*
	$('.datetimepicker input[type="text"]').datetimepicker({
		dateFormat: "dd MM yy",
        timeFormat: "hh:mm tt"
    });
	*/
    
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
    		//console.log(latestFormData);
    		//console.log(initialFormData);
    		var msg = _('admin.messages.unsaved_changes');
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
		if ($('[name="'+formData[i].name+'"]').hasClass('ckeditor-cmf')) {
			formData[i].value = $("<div/>").html(strtolower(formData[i].value)).text();
		}
		output += formData[i].name + '=' + $.trim(formData[i].value) + '&';
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
			'url': CMF.adminUrl + '/' + data['table_name'] + '/permissions/' + data['role_id'] + '/save',
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



