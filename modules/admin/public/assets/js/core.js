$(document).ready(function() {
	
	if ($('.widget.collapsible').length > 0) { initCollapsibleWidgets(); }
	if ($('.item-form').length > 0) { initItemForm(); }
	if ($('.item-list').length > 0) { initItemList(); }
	if ($('#item-tree').length > 0) { initTree(); }	
});

$(window).load(function() {
	
	initFixedHeadTables();
	
});

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

function initTree() {
	
	if ($('#item-tree').length == 0) { return; }
	
	var el = $('#item-tree'),
	id = el.attr('id'),
	moveStartTime = 0,
	attemptedMove = false;
	
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
			
			node.div = $li.find('.main');
			node.title = $li.find('.jqtree-title');
			node.icon = classData['icon'];
			node.href = baseUrlId + '/edit';
			node.update = '/admin/' + data['table_name'] + '/' + node.id + '/updatetree';
			node.delete = baseUrlId + '/delete';
			node.visible = (node.visible === 1 || node.visible === true);
			node.hidden = (typeof node.hidden != 'undefined' && (node.hidden === true || node.hidden === 1));
			
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
			
			node.title.append(' <span class="edit-icon icon-pencil"></span>');
			
			var actionsContent = '<div class="actions pull-right">';
			//actionsContent += '<a href="#" class="show-hide ' + (node.visible ? 'visible' : '') + '"><i class="icon icon-eye-open"></i></a>';
			
			var childItems = [];
			var childInfo = [];
			
			for (var p in data['classes']) {
				var subclassData = data['classes'][p],
				baseUrl = '/admin/' + subclassData['table_name'];
				if (subclassData['static']) { continue; }
				childItems.push('<li><a tabindex="-1" href="' + baseUrl + '/create?parent=' + node.id + '"><i class="icon icon-' + subclassData['icon'] + '"></i> ' + subclassData['singular'] + '</a></li>');
				childInfo.push({ 'edit':baseUrl + '/create?parent=' + node.id, 'icon':subclassData['icon'], 'singular':subclassData['singular'] });
			}
			
			if (childItems.length > 1) {
				
				actionsContent += '<a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#" title="Add Child..."><i class="icon icon-plus"></i></a>' + 
				'<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">' + 
				'<li class="nav-header">Add a child...</li>' + 
				childItems.join('') + 
				'</ul>';
				
			} else if (childItems.length == 1) {
				
				actionsContent += '<a class="btn btn-small" href="' + childInfo[0]['edit'] + '" rel="tooltip" title="Add Child ' + childInfo[0]['singular'] + '..."><i class="icon icon-plus"></i></a>';
				
			}
			
			if (!(typeof(classData.static) != 'undefined' && classData.static == true)) {
				actionsContent += '<a class="btn btn-small btn-danger btn-remove" rel="tooltip" title="Delete" href="' + node.delete + '" data-singular="' + classData['singular'] + '"><i class="icon icon-remove"></i></a>';
			}
			
			actionsContent += '</div>';
			
			// Add the actions
			var actions = $(actionsContent).appendTo(node.div);
			
			// Apply any js functionality to the actions
			actions.find('.dropdown-toggle').dropdown();
			
	    },
		onCanMove: function(node) {
			attemptedMove = false;
			moveStartTime = new Date().getTime();
			return true;
		},
		onCanMoveTo: function(moved_node, target_node, position) {
			
			attemptedMove = true;
			
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
		
		$('a[rel="tooltip"]').tooltip({ 'placement':'top' });
		
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
	
	$('a[rel="tooltip"]').tooltip({ 'placement':'top' });
	
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
			if (subclassData['static']) { continue; }
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
	
}

function initItemForm() {
	
	var submitted = false;
	
	$('#controls-fixed-bot .btn-remove').each(function() {
		
		$(this).click(function() {
			return confirm("Do you really want to delete this " + $(this).attr('data-singular').toLowerCase() + "? You can't undo!");
		});
		
	});
	
	$('a.btn-permissions[rel="tooltip"]').tooltip({ 'placement':'left' });
	
	$('form .submit-item').click(function() {
		$(this).button('loading');
	});
	
	$('#item-nav a').each(function() {
		var el = $(this);
		var selector = el.attr('href');
		
		var tab = $(selector);
		if (tab.length > 0 && tab.find('.error').length > 0) {
			$(this).tab('show');
		}
		
		el.click(function (e) {
		    e.preventDefault();
		    el.tab('show');
	    });
	});
	
	$('.field-type-date').each(function() {
		
		$(this).find('input').datepicker({
	        dateFormat: "dd/mm/yy"
	    });
		
	});
	
	$('.datetimepicker input[type="text"]').datetimepicker({
		dateFormat: "dd MM yy",
        timeFormat: "hh:mm tt"
    });
	//.datetimepicker( "setDate" , new Date());

    $('.help-icon').popover({ 'placement':'right', 'trigger':'click' });
	
	$('.field-type-file, .field-type-image').each(function() {
		
		var $wrap = $(this);
		var $el = $wrap.find('input.file-enhanced');
		var $clear = $wrap.find('.btn-warning').hide();
		
		if ($el.length == 0) { return; }
		
		$el.bind('change', function() {
			$clear.show();
		});
		
		$el.bind('reset', function() {
			$clear.hide();
		});
		
		var value = $el.attr('title');
		if (value == '' || typeof(value) == 'undefined' || value == null) {
			value = 'No file selected...';
			$el.trigger('reset');
		} else {
			var segments = value.split('/');
			value = segments[segments.length-1];
			$clear.show();
		}
		$el.customFileInput({
			value_text: value,
	        button_position : 'right'
	    });
		$el.trigger('update');
		
		$clear.click(function() {
			$wrap.find('.thumbnail').remove();
			$wrap.find('.customfile-feedback').html('No file selected...');
			$el.trigger('reset');
			return false;
		});
		
	});
	
	$("table.sortable tbody").sortable({ helper:fixHelper, handle:'.handle' });
	
	$('table.selectable tr').each(function() {
		
		var el = $(this), cb = $('td input[type="checkbox"]', el).eq(0);
		if (cb.length == 0) { return; }
		
		el.click(function(evt) {
			
			if (evt.target == cb[0]) { return true; }
			var tag = (''+evt.target.tagName).toLowerCase();
			var parentTag = (''+$(evt.target).parent()[0].tagName).toLowerCase();
			if (tag == 'input' || tag == 'a' || tag == 'select' || tag == 'option' || parentTag == 'a') { return; }
			
			cb.prop('checked', !cb.prop('checked'));
			return false;
			
		});
		
	});
	
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



