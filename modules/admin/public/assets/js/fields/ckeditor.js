/*
 Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.md or http://ckeditor.com/license
*/
(function(a){CKEDITOR.config.jqueryOverrideVal="undefined"==typeof CKEDITOR.config.jqueryOverrideVal?!0:CKEDITOR.config.jqueryOverrideVal;"undefined"!=typeof a&&(a.extend(a.fn,{ckeditorGet:function(){var a=this.eq(0).data("ckeditorInstance");if(!a)throw"CKEditor is not initialized yet, use ckeditor() with a callback.";return a},ckeditor:function(g,d){if(!CKEDITOR.env.isCompatible)throw Error("The environment is incompatible.");if(!a.isFunction(g))var k=d,d=g,g=k;var i=[],d=d||{};this.each(function(){var b=
a(this),c=b.data("ckeditorInstance"),f=b.data("_ckeditorInstanceLock"),h=this,j=new a.Deferred;i.push(j.promise());if(c&&!f)g&&g.apply(c,[this]),j.resolve();else if(f)c.once("instanceReady",function(){setTimeout(function(){c.element?(c.element.$==h&&g&&g.apply(c,[h]),j.resolve()):setTimeout(arguments.callee,100)},0)},null,null,9999);else{if(d.autoUpdateElement||"undefined"==typeof d.autoUpdateElement&&CKEDITOR.config.autoUpdateElement)d.autoUpdateElementJquery=!0;d.autoUpdateElement=!1;b.data("_ckeditorInstanceLock",
!0);c=a(this).is("textarea")?CKEDITOR.replace(h,d):CKEDITOR.inline(h,d);b.data("ckeditorInstance",c);c.on("instanceReady",function(d){var e=d.editor;setTimeout(function(){if(e.element){d.removeListener();e.on("dataReady",function(){b.trigger("dataReady.ckeditor",[e])});e.on("setData",function(a){b.trigger("setData.ckeditor",[e,a.data])});e.on("getData",function(a){b.trigger("getData.ckeditor",[e,a.data])},999);e.on("destroy",function(){b.trigger("destroy.ckeditor",[e])});e.on("save",function(){a(h.form).submit();
return!1},null,null,20);if(e.config.autoUpdateElementJquery&&b.is("textarea")&&a(h.form).length){var c=function(){b.ckeditor(function(){e.updateElement()})};a(h.form).submit(c);a(h.form).bind("form-pre-serialize",c);b.bind("destroy.ckeditor",function(){a(h.form).unbind("submit",c);a(h.form).unbind("form-pre-serialize",c)})}e.on("destroy",function(){b.removeData("ckeditorInstance")});b.removeData("_ckeditorInstanceLock");b.trigger("instanceReady.ckeditor",[e]);g&&g.apply(e,[h]);j.resolve()}else setTimeout(arguments.callee,
100)},0)},null,null,9999)}});var f=new a.Deferred;this.promise=f.promise();a.when.apply(this,i).then(function(){f.resolve()});this.editor=this.eq(0).data("ckeditorInstance");return this}}),CKEDITOR.config.jqueryOverrideVal&&(a.fn.val=CKEDITOR.tools.override(a.fn.val,function(g){return function(d){if(arguments.length){var k=this,i=[],f=this.each(function(){var b=a(this),c=b.data("ckeditorInstance");if(b.is("textarea")&&c){var f=new a.Deferred;c.setData(d,function(){f.resolve()});i.push(f.promise());
return!0}return g.call(b,d)});if(i.length){var b=new a.Deferred;a.when.apply(this,i).done(function(){b.resolveWith(k)});return b.promise()}return f}var f=a(this).eq(0),c=f.data("ckeditorInstance");return f.is("textarea")&&c?c.getData():g.call(f)}})))})(window.jQuery);

(function($) {
	
	$(document).ready(function() {
		
		$('textarea.ckeditor-cmf').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('textarea.ckeditor-cmf').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $input = $(this),
		id = $input.attr('id'),
		name = $(this).attr('name'),
		settings = typeof(field_settings[name]) != 'undefined' ? field_settings[name] : {},
		$tab = $input.parents('.tab-pane'),
        initialised = false,
        editor = null;

		if (name.indexOf('__TEMP__') > -1) { return; }

        if ($input.parents('.draggable').length > 0) {
            $input.parents('.draggable').on('cmf.dragstart', function() {
                destroyEditor();
            }).on('cmf.dragstop', function() {
                destroyEditor();
                initialise();
            });
        }

		if ($tab.length > 0 && !$tab.is(':visible')) {
            $('a[data-toggle="tab"][href="#'+$tab.attr('id')+'"]').on('shown', initialise);
        } else {
            initialise();
        }

        function destroyEditor() {

            if (editor === null) return;

            editor.destroy()
            editor = null;
            initialised = false;
        }

        function initialise() {

            if (initialised) return;

        	var removePlugins = [
        		"about", "a11yhelp", "bidi", "colorbutton", "colordialog", "div", "elementspath", "find", "font", "forms", "iframe", "smiley", "maximize", "newpage", "pagebreak", "preview", "print", "resize", "save", "undo", "language"
        	];
        	
        	var extraPlugins = [
        		"autogrow", "codemirror", "oembed", "templates"
        	];
        	
        	var removeButtons = [
        		'Cut',
        		'Copy',
        		'Paste',
        		'Indent',
        		'Outdent'
        	];
        	
        	// REMOVED PLUGINS:
        	// "about", "a11yhelp", "bidi", "colorbutton", "colordialog", "div", "elementspath", "find", "font", "forms", "iframe", "smiley", "maximize", "newpage", "pagebreak", "preview", "print", "resize", "save", "undo"
        	
        	var config = {
        		skin: 'moonocolor',
        		removeButtons: removeButtons.join(','),
        		removePlugins: removePlugins.join(','),
        		extraPlugins: extraPlugins.join(','),
        		autoGrow: true,
        		autoGrow_onStartup: true,
        		floatSpaceDockedOffsetX: 20,
        		allowedContent: true,
        		oembed_maxWidth: '560',
        		oembed_maxHeight: '315',
        		oembed_WrapperClass: 'embedded-content',
        		bodyClass: 'editor',
        		contentsCss: [CMF.adminUrl + '/assets/css/ckeditor-contents.css', CMF.adminUrl + '/assets/ckeditor/contents.css'],
        		filebrowserBrowseUrl: CMF.adminUrl + '/finder/browser?start=files',
        		filebrowserImageBrowseUrl: CMF.adminUrl + '/finder/browser?start=images',
        		filebrowserWindowFeatures: 'location=no,menubar=no,toolbar=no,dependent=yes,minimizable=no,modal=yes,alwaysRaised=yes,resizable=no,scrollbars=no',
        		filebrowserWindowWidth: '60%',
        		filebrowserWindowHeight: 600,
                baseFloatZIndex: 10040,
        		codemirror: {
        		    theme: 'default',
        		    lineNumbers: true,
        		    lineWrapping: true,
        		    matchBrackets: true,
        		    autoCloseTags: false,
        		    autoCloseBrackets: true,
        		    enableSearchTools: true,
        		    enableCodeFolding: false,
        		    enableCodeFormatting: true,
        		    autoFormatOnStart: false,
        		    autoFormatOnModeChange: true,
        		    autoFormatOnUncomment: true,
        		    highlightActiveLine: true,
        		    mode: 'htmlmixed',
        		    showSearchButton: false,
        		    showTrailingSpace: true,
        		    highlightMatches: true,
        		    showFormatButton: false,
        		    showCommentButton: false,
        		    showUncommentButton: false,
        		    showAutoCompleteButton: false
        		}
        	};

        	if (typeof(settings['buttons']) != 'undefined') {
        		config['toolbar'] = [
        		    { name: 'custombuttons', items: settings['buttons'] }
        		];
        	}

        	if (typeof(settings['stylesSet']) != 'undefined') {
        		config.stylesSet = settings['stylesSet'];
        	}

        	if (typeof(settings['contentsCss']) != 'undefined') {
        		config.contentsCss.unshift(settings['contentsCss']);
        	}

            if (typeof(settings['templatesFiles']) != 'undefined' && settings['templatesFiles']) {
                config.templates = 'default';
                config.templates_files = settings['templatesFiles'];
                config.templates_replaceContent = false;
            }

            if (!!settings['editor']) {
                config = $.extend(true, {}, config, settings['editor']);
            }
            
        	editor = $input.ckeditor(config).ckeditorGet();

            initialised = true;

        }

	}
	
})(jQuery);