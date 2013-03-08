
/*
 * HTML Parser By John Resig (ejohn.org)
 * Original code by Erik Arvidsson, Mozilla Public License
 * http://erik.eae.net/simplehtmlparser/simplehtmlparser.js
 *
 * // Use like so:
 * HTMLParser(htmlString, {
 *     start: function(tag, attrs, unary) {},
 *     end: function(tag) {},
 *     chars: function(text) {},
 *     comment: function(text) {}
 * });
 *
 * // or to get an XML string:
 * HTMLtoXML(htmlString);
 *
 * // or to get an XML DOM Document
 * HTMLtoDOM(htmlString);
 *
 * // or to inject into an existing document/DOM node
 * HTMLtoDOM(htmlString, document);
 * HTMLtoDOM(htmlString, document.body);
 *
 */

(function(){

	// Regular Expressions for parsing tags and attributes
	var startTag = /^<([-A-Za-z0-9_]+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/,
		endTag = /^<\/([-A-Za-z0-9_]+)[^>]*>/,
		attr = /([-A-Za-z0-9_]+)(?:\s*=\s*(?:(?:"((?:\\.|[^"])*)")|(?:'((?:\\.|[^'])*)')|([^>\s]+)))?/g;
		
	// Empty Elements - HTML 4.01
	var empty = makeMap("area,base,basefont,br,col,frame,hr,img,input,isindex,link,meta,param,embed");

	// Block Elements - HTML 4.01
	var block = makeMap("address,applet,blockquote,button,center,dd,del,dir,div,dl,dt,fieldset,form,frameset,hr,iframe,ins,isindex,li,map,menu,noframes,noscript,object,ol,p,pre,script,table,tbody,td,tfoot,th,thead,tr,ul");

	// Inline Elements - HTML 4.01
	var inline = makeMap("a,abbr,acronym,applet,b,basefont,bdo,big,br,button,cite,code,del,dfn,em,font,i,iframe,img,input,ins,kbd,label,map,object,q,s,samp,script,select,small,span,strike,strong,sub,sup,textarea,tt,u,var");

	// Elements that you can, intentionally, leave open
	// (and which close themselves)
	var closeSelf = makeMap("colgroup,dd,dt,li,options,p,td,tfoot,th,thead,tr");

	// Attributes that have their values filled in disabled="disabled"
	var fillAttrs = makeMap("checked,compact,declare,defer,disabled,ismap,multiple,nohref,noresize,noshade,nowrap,readonly,selected");

	// Special Elements (can contain anything)
	var special = makeMap("script,style");

	var HTMLParser = this.HTMLParser = function( html, handler ) {
		var index, chars, match, stack = [], last = html;
		stack.last = function(){
			return this[ this.length - 1 ];
		};

		while ( html ) {
			chars = true;

			// Make sure we're not in a script or style element
			if ( !stack.last() || !special[ stack.last() ] ) {

				// Comment
				if ( html.indexOf("<!--") == 0 ) {
					index = html.indexOf("-->");
	
					if ( index >= 0 ) {
						if ( handler.comment )
							handler.comment( html.substring( 4, index ) );
						html = html.substring( index + 3 );
						chars = false;
					}
	
				// end tag
				} else if ( html.indexOf("</") == 0 ) {
					match = html.match( endTag );
	
					if ( match ) {
						html = html.substring( match[0].length );
						match[0].replace( endTag, parseEndTag );
						chars = false;
					}
	
				// start tag
				} else if ( html.indexOf("<") == 0 ) {
					match = html.match( startTag );
	
					if ( match ) {
						html = html.substring( match[0].length );
						match[0].replace( startTag, parseStartTag );
						chars = false;
					}
				}

				if ( chars ) {
					index = html.indexOf("<");
					
					var text = index < 0 ? html : html.substring( 0, index );
					html = index < 0 ? "" : html.substring( index );
					
					if ( handler.chars )
						handler.chars( text );
				}

			} else {
				html = html.replace(new RegExp("(.*)<\/" + stack.last() + "[^>]*>"), function(all, text){
					text = text.replace(/<!--(.*?)-->/g, "$1")
						.replace(/<!\[CDATA\[(.*?)]]>/g, "$1");

					if ( handler.chars )
						handler.chars( text );

					return "";
				});

				parseEndTag( "", stack.last() );
			}

			if ( html == last )
				throw "Parse Error: " + html;
			last = html;
		}
		
		// Clean up any remaining tags
		parseEndTag();

		function parseStartTag( tag, tagName, rest, unary ) {
			tagName = tagName.toLowerCase();

			if ( block[ tagName ] ) {
				while ( stack.last() && inline[ stack.last() ] ) {
					parseEndTag( "", stack.last() );
				}
			}

			if ( closeSelf[ tagName ] && stack.last() == tagName ) {
				parseEndTag( "", tagName );
			}

			unary = empty[ tagName ] || !!unary;

			if ( !unary )
				stack.push( tagName );
			
			if ( handler.start ) {
				var attrs = [];
	
				rest.replace(attr, function(match, name) {
					var value = arguments[2] ? arguments[2] :
						arguments[3] ? arguments[3] :
						arguments[4] ? arguments[4] :
						fillAttrs[name] ? name : "";
					
					attrs.push({
						name: name,
						value: value,
						escaped: value.replace(/(^|[^\\])"/g, '$1\\\"') //"
					});
				});
	
				if ( handler.start )
					handler.start( tagName, attrs, unary );
			}
		}

		function parseEndTag( tag, tagName ) {
			// If no tag name is provided, clean shop
			if ( !tagName )
				var pos = 0;
				
			// Find the closest opened tag of the same type
			else
				for ( var pos = stack.length - 1; pos >= 0; pos-- )
					if ( stack[ pos ] == tagName )
						break;
			
			if ( pos >= 0 ) {
				// Close all the open elements, up the stack
				for ( var i = stack.length - 1; i >= pos; i-- )
					if ( handler.end )
						handler.end( stack[ i ] );
				
				// Remove the open elements from the stack
				stack.length = pos;
			}
		}
	};
	
	this.HTMLtoXML = function( html ) {
		var results = "";
		
		HTMLParser(html, {
			start: function( tag, attrs, unary ) {
				results += "<" + tag;
		
				for ( var i = 0; i < attrs.length; i++ )
					results += " " + attrs[i].name + '="' + attrs[i].escaped + '"';
		
				results += (unary ? "/" : "") + ">";
			},
			end: function( tag ) {
				results += "</" + tag + ">";
			},
			chars: function( text ) {
				results += text;
			},
			comment: function( text ) {
				results += "<!--" + text + "-->";
			}
		});
		
		return results;
	};
	
	this.HTMLtoDOM = function( html, doc ) {
		// There can be only one of these elements
		var one = makeMap("html,head,body,title");
		
		// Enforce a structure for the document
		var structure = {
			link: "head",
			base: "head"
		};
	
		if ( !doc ) {
			if ( typeof DOMDocument != "undefined" )
				doc = new DOMDocument();
			else if ( typeof document != "undefined" && document.implementation && document.implementation.createDocument )
				doc = document.implementation.createDocument("", "", null);
			else if ( typeof ActiveX != "undefined" )
				doc = new ActiveXObject("Msxml.DOMDocument");
			
		} else
			doc = doc.ownerDocument ||
				doc.getOwnerDocument && doc.getOwnerDocument() ||
				doc;
		
		var elems = [],
			documentElement = doc.documentElement ||
				doc.getDocumentElement && doc.getDocumentElement();
				
		// If we're dealing with an empty document then we
		// need to pre-populate it with the HTML document structure
		if ( !documentElement && doc.createElement ) (function(){
			var html = doc.createElement("html");
			var head = doc.createElement("head");
			head.appendChild( doc.createElement("title") );
			html.appendChild( head );
			html.appendChild( doc.createElement("body") );
			doc.appendChild( html );
		})();
		
		// Find all the unique elements
		if ( doc.getElementsByTagName )
			for ( var i in one )
				one[ i ] = doc.getElementsByTagName( i )[0];
		
		// If we're working with a document, inject contents into
		// the body element
		var curParentNode = one.body;
		
		HTMLParser( html, {
			start: function( tagName, attrs, unary ) {
				// If it's a pre-built element, then we can ignore
				// its construction
				if ( one[ tagName ] ) {
					curParentNode = one[ tagName ];
					if ( !unary ) {
						elems.push( curParentNode );
					}
					return;
				}
			
				var elem = doc.createElement( tagName );
				
				for ( var attr in attrs )
					elem.setAttribute( attrs[ attr ].name, attrs[ attr ].value );
				
				if ( structure[ tagName ] && typeof one[ structure[ tagName ] ] != "boolean" )
					one[ structure[ tagName ] ].appendChild( elem );
				
				else if ( curParentNode && curParentNode.appendChild )
					curParentNode.appendChild( elem );
					
				if ( !unary ) {
					elems.push( elem );
					curParentNode = elem;
				}
			},
			end: function( tag ) {
				elems.length -= 1;
				
				// Init the new parentNode
				curParentNode = elems[ elems.length - 1 ];
			},
			chars: function( text ) {
				curParentNode.appendChild( doc.createTextNode( text ) );
			},
			comment: function( text ) {
				// create comment node
			}
		});
		
		return doc;
	};

	function makeMap(str){
		var obj = {}, items = str.split(",");
		for ( var i = 0; i < items.length; i++ )
			obj[ items[i] ] = true;
		return obj;
	}
})();



(function($) {
	
	////////////////// PLACEHOLDER PLUGIN ///////////////////
	
	function selectElementText(el, win) {
	    win = win || window;
	    var doc = win.document, sel, range;
	    if (win.getSelection && doc.createRange) {
	        sel = win.getSelection();
	        range = doc.createRange();
	        range.selectNodeContents(el);
	        sel.removeAllRanges();
	        sel.addRange(range);
	    } else if (doc.body.createTextRange) {
	        range = doc.body.createTextRange();
	        range.moveToElementText(el);
	        range.select();
	    }
	}
	
	if (typeof RedactorPlugins === 'undefined') RedactorPlugins = {};

	RedactorPlugins.placeholder = {

		init: function()
		{
			var editor = this,
			placeholders = editor.opts.placeholders,
			numPlaceholders = 0,
			dropdown = {};
			
			if (typeof(placeholders) == 'undefined') { return; }
			for (var p in placeholders) {
				numPlaceholders++;
			}
			if (numPlaceholders === 0) { return; }
			
			// The callback for when an item in the dropdown is clicked
			function itemCallback(editor, evt, id) {
				
				var settings = dropdown[id],
				inline = typeof(settings.inline) != 'undefined' && settings.inline,
				tagName = inline ? 'span' : 'div',
				html = '<'+ tagName +' class="placeholder placeholder-' + id + '" contenteditable="false">{{ ' + id + ' }}</'+ tagName +'>',
				currentNode = editor.getCurrentNode(),
				parentTagName = currentNode.tagName.toLowerCase();
				
				var node = $(html);
				editor.setBuffer();
				
				if (!inline && parentTagName != 'div') {
					node.insertAfter($(currentNode));
				} else {
					editor.insertNodeAtCaret(node[0]);
				}
				
			}
			
			// Populate the dropdown
			for (var id in placeholders) {
				
				var obj = placeholders[id];
				
				obj.id = id;
				if (!obj.title) { obj.title = ucfirst(id); }
				if (obj.description) { obj.title += ' <span>' + obj.description + '</span>'; }
				
				obj.callback = itemCallback;
				dropdown[id] = placeholders[id] = obj;
				
			}
			
			this.addBtnAfter('link', 'placeholder', 'Placeholder', function() {
				// something?
			}, dropdown);
			
		},
		
		insertPlaceholder: function(html)
		{
			//this.restoreSelection();
			//this.execCommand('inserthtml', html);
			//this.modalClose();
		}

	}
	
	RedactorPlugins.fontSize = {

		init: function()
		{
			var editor = this,
			dropdown = {},
			minSize = 0.2,
			maxSize = 5,
			sizeIncrement = .2;
			
			editor.addBtnAfter('backcolor', 'fontsmaller', 'Smaller Text', smaller);
			editor.addBtnAfter('fontsmaller', 'fontlarger', 'Larger Text', larger);
			
			function changeSize(increment) {
				
				editor.setBuffer();
				var html = $.trim(editor.getSelectedHtml()),
				oHtml = html;
				if (html.length == 0) { return false; }
				
				var overwrite = true;
				var fontSize = 1 + increment;
				var selectedNode = $(editor.getSelectedNode());
				var currentNode = selectedNode;
				var pWrap = html.toLowerCase().indexOf('<p') === 0;
				var rootSelected = selectedNode.hasClass('redactor_editor') || selectedNode.hasClass('redactor_box');
				
				if (pWrap) {
					$html = $(html);
					html = $.trim($html.html());
				}
				
				var spanWrap = html.toLowerCase().indexOf('<span') === 0;
				
				if (spanWrap) {
					$html = $(html);
					fontSize = getFontSizeEm($html, increment);
					html = $.trim($html.html());
				}
				
				if (selectedNode.hasClass('editor-style')) {
					currentNode = selectedNode;
					html = $.trim(selectedNode.html());
					overwrite = false;
				} else {
					currentNode = $(editor.getCurrentNode());
					overwrite = false;
				}
				
				var replaceElement = (currentNode.hasClass('editor-style') && oHtml == currentNode.html());
				var node = html;
				
				if (replaceElement) {
					
					fontSize = getFontSizeEm(currentNode, increment);
					
					if (overwrite === false && fontSize !== 1) {
						currentNode.css('font-size', fontSize + 'em');
						editor.syncCode();
						return true;
					}
					
				}
				
				var d = new Date();
				var cid = 'fs-' + d.getTime();
				
				if (fontSize != 1) {
					node = '<span data-cid="' + cid + '" class="editor-style" style="font-size:' + fontSize + 'em;">' + html + '</span>';
				} else {
					node = html;
				}
				
				if (rootSelected) {
					node = '<p>' + node + '</p>';
				}
				
				if ($.browser.msie) {
					editor.$editor.focus();
					editor.document.selection.createRange().pasteHTML(node);
				} else {
					editor.pasteHtmlAtCaret(node);
				}
				
				var $el = editor.$editor.find('span[data-cid="' + cid + '"]');
				if ($el.length > 0) {
					$el.removeAttr('data-cid');
					selectElementText($el[0]);
					//editor.saveSelection();
				}
				
				editor.syncCode();
				
			}
			
			function smaller() {
				changeSize(-sizeIncrement);
			}
			
			function larger() {
				changeSize(sizeIncrement);
			}
			
			function getFontSizeEm($node, increment) {
				var styleAttr = $node.attr('style');
				var regex = /font-size:(.*)em/gi;
				var match = regex.exec(styleAttr);
				var output = 1 + increment;
				
				if (match != null && match.length > 1) {
					output = Math.round((parseFloat(match[1]) + increment) * 100) / 100;
					if (output < minSize) { output = minSize; }
					if (output > maxSize) { output = maxSize; }
				}
				return output;
			}
			
		}

	}
	
	/**
	 * Uses an HTML tidy JS script when code has been written in the source editor... To prevent silly errors
	 */
	RedactorPlugins.tidy = {
		
		init: function()
		{
			this.savePreCode_ = this.savePreCode;
			this.savePreCode = this.savePreCodeAndTidy;
			
		},
		
		savePreCodeAndTidy: function(html)
		{
			output = this.savePreCode_(html);
			
			try {
				output = HTMLtoXML(output);
			} catch(err) {
				// Don't worry if it didn't work
			}
			
			return output;
		},
		
	}
	
	/**
	 * Adds extra functionality to the link box in order to select internal pages etc.
	 */
	RedactorPlugins.cmflink = {
		
		init: function() {
			
			var editor = this,
			$dropdown = null,
			links = typeof(editor.opts['links']) != 'undefined' ? editor.opts['links'] : [];
			
			// Jiggle some things around a bit...
			editor.opts.modal_link = modalContent;
			editor.showLinkOrig = editor.showLink;
			editor.showLink = editor.showCMFLink;
			
			// Don't continue if there are no links
			if (links.length === 0) { return false; }
			
			var modalContent = '<div id="redactor_modal_content">' +
			'<form id="redactorInsertLinkForm" method="post" action="">' +
				'<div id="redactor_tabs">' +
					'<a href="javascript:void(null);" class="redactor_tabs_act">URL</a>' +
					'<a href="javascript:void(null);">Email</a>' +
					'<a href="javascript:void(null);">' + RLANG.anchor + '</a>' +
				'</div>' +
				'<input type="hidden" id="redactor_tab_selected" value="1" />' +
				'<div class="redactor_tab" id="redactor_tab1">' +
					'<div class="redactor-cmf-link"><label>URL</label><input type="text" id="redactor_link_url" class="redactor_input"  /></div>' +
					'<label>' + RLANG.text + '</label><input type="text" class="redactor_input redactor_link_text" id="redactor_link_url_text" />' +
					'<label><input type="checkbox" id="redactor_link_blank"> ' + RLANG.link_new_tab + '</label>' +
				'</div>' +
				'<div class="redactor_tab" id="redactor_tab2" style="display: none;">' +
					'<label>Email</label><input type="text" id="redactor_link_mailto" class="redactor_input" />' +
					'<label>' + RLANG.text + '</label><input type="text" class="redactor_input redactor_link_text" id="redactor_link_mailto_text" />' +
				'</div>' +
				'<div class="redactor_tab" id="redactor_tab3" style="display: none;">' +
					'<label>' + RLANG.anchor + '</label><input type="text" class="redactor_input" id="redactor_link_anchor"  />' +
					'<label>' + RLANG.text + '</label><input type="text" class="redactor_input redactor_link_text" id="redactor_link_anchor_text" />' +
				'</div>' +
			'</form>' +
			'</div>' +
			'<div id="redactor_modal_footer">' +
				'<a href="javascript:void(null);" class="redactor_modal_btn redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
				'<input type="button" class="redactor_modal_btn" id="redactor_insert_link_btn" value="' + RLANG.insert + '" />' +
			'</div>';
			
			// Find the dropdown
			for (var i = 0; i < editor.dropdowns.length; i++) {
				if (editor.dropdowns[i].find('a:contains("Unlink")').length > 0) {
					$dropdown = editor.dropdowns[i];
				}
			}
			
			// Don't continue if the dropdown hasn't been found
			if ($dropdown === null) { return false; }
			
			$dropdown.prepend('<a class="redactor_separator_drop"></a>');
			
			// Now add some stuff to the dropdown...
			for (var type in links) {
				var linkData = links[type];
				linkData['type'] = type;
				var $link = $('<a href="javascript:void(null);"><i class="icon icon-' + linkData.icon + '"></i>&nbsp; ' + linkData.singular + ' link...</a>')
				.prependTo($dropdown)
				.bind('click', linkData, function(evt) {
					editor.showItemLink(evt.data);
					return false;
				});
			}
			
			var date = new Date();
			this.$editor.find('a[data-item-id]').each(function(i, node) {
				$(node).attr('data-item-uid', (date.getTime() - i) + '');
			});
			
			this.$editor.parents('form.item-form').eq(0).submit(function() {
				editor.$editor.find('a[data-item-uid]').each(function(i, node) {
					$(node).removeAttr('data-item-uid');
				});
				editor.syncCode();
				
				return true;
			});
			
			this.$editor.on('mouseover', 'a[data-item-uid]', function(evt) {
				// Hovering over an inserted item link, show thumbnail?
			});
			
			this.$editor.on('mouseout', 'a[data-item-uid]', function(evt) {
				// Hide the thumbnail on mouse out...?
			});
			
			// Hijack the modal close so we can fire an event...
			this.modalCloseOrig = this.modalClose;
			this.modalClose = this.modalCloseNew;
			
		},
		
		modalCloseNew: function() {
			this.modalCloseOrig();
			this.$editor.trigger('redactor.modal_close');
			return false;
		},
		
		showItemLink: function(data) {
			
			this.saveSelection();
			
			var modalItemContent = '<div id="redactor_modal_content">' +
			'<form id="redactorInsertLinkForm" method="post" action="">' +
				'<div class="redactor-cmf-link"><label>Select ' + data.singular.toLowerCase() + '</label>' +
				'<select id="redactor-cmf-select2-link" disabled="disabled"><option selected="selected">Loading...</option></select>' +
				//<input type="text" id="redactor_link_url" class="redactor_input"  />' +
				'</div>' +
				'<label>' + RLANG.text + '</label><input type="text" class="redactor_input redactor_link_text" id="redactor_link_url_text" />' +
				'<label><input type="checkbox" id="redactor_link_blank"> ' + RLANG.link_new_tab + '</label>' +
			'</form>' +
			'</div>' +
			'<div id="redactor_modal_footer">' +
				'<a href="javascript:void(null);" class="redactor_modal_btn redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
				'<input type="button" class="redactor_modal_btn" id="redactor_insert_link_btn" value="' + RLANG.insert + '" />' +
			'</div>';
			
			var callback = $.proxy(function() {
				
				this.insert_link_node = false;
				var sel = this.getSelection();
				var selHtml = $.trim(this.getSelectedHtml() + '');
				var url = '', text = '', target = '', itemId = null;

				if (selHtml.match(/<[^<]+>/)) {
					
					var $selHtml = $(selHtml);
					if ($selHtml.find('a[data-item-uid]').length > 0) { $selHtml = $selHtml.find('a[data-item-uid]').eq(0); }
					
					if ($selHtml.length > 0 && $selHtml[0].tagName.toLowerCase() == 'a') {
						var uid = $(selHtml).attr('data-item-uid');
						var $selectedLink = this.$editor.find('a[data-item-uid="' + uid + '"]').eq(0);
						this.insert_link_node = $selectedLink;
					}
					
				}
				
				if (this.insert_link_node !== false) {
					
					text = this.insert_link_node.text();
					url = this.insert_link_node.attr('href');
					itemId = parseInt(this.insert_link_node.attr('data-item-id'));
					target = this.insert_link_node.attr('target');
					
				} else if ($.browser.msie) {
					var parent = this.getParentNode();
					if (parent.nodeName === 'A')
					{
						this.insert_link_node = $(parent);
						text = this.insert_link_node.text();
						url = this.insert_link_node.attr('href');
						itemId = parseInt(this.insert_link_node.attr('data-item-id'));
						target = this.insert_link_node.attr('target');
					}
					else
					{
						if (this.oldIE())
						{
							text = sel.text;
						}
						else
						{
							text = sel.toString();
						}
					}
				} else {
					if (sel && sel.anchorNode && sel.anchorNode.parentNode.tagName === 'A')
					{
						url = sel.anchorNode.parentNode.href;
						itemId = parseInt(sel.anchorNode.parentNode.getAttribute('data-item-id'));
						text = sel.anchorNode.parentNode.text;
						target = sel.anchorNode.parentNode.target;
						this.insert_link_node = sel.anchorNode.parentNode;
					}
					else
					{
						text = sel.toString();
					}
				}
				
				if (!isSet(url)) { url = ''; }
				$('.redactor_link_text').val(text);
				
				var thref = self.location.href.replace(/\/$/i, '');
				var turl = url.replace(thref, '');
				
				// Can't set the value of the select yet - wait until it's loaded!
				data['itemId'] = itemId;
				// $('#redactor_link_url').val(turl);
				
				if (target === '_blank') {
					$('#redactor_link_blank').attr('checked', true);
				}
				
				$('#redactor_insert_link_btn').click($.proxy(function() {
					this.insertItemLink(data);
				}, this));
				
				this.loadItemData(data);
				
				setTimeout(function() {
					$('#redactor_link_url').focus();
				}, 200);
				
			}, this);
			
			this.modalInit(data.singular + ' link', modalItemContent, 460, callback);
			
		},
		
		loadItemData: function(data) {
			
			// Either get this data from the server, or get it from a collection in the form somewhere...
			var $select = $('#redactor-cmf-select2-link').select2({
				containerCssClass:'input-xxlarge',
				escapeMarkup: function(text) {
					return text;
				},
				formatResult: function(result, container, query, escapeMarkup) {
					return $(result.element).html();
				},
				formatSelection: function(object, container) {
					container.html($(object.element).html());
				},
				matcher: function(term, text, option) {
					
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
			}),
			url = '/admin/' + data['table_name'] + '/options',
			emptyMsg = 'No available ' + data['plural'].toLowerCase() + ' found...';
			
			// If there is a source, try and find the value in the form
			if (typeof(data.source) != 'undefined' && getFieldValue(data.source, null) !== null) {
				var sourceValue = getFieldValue(data.source);
				url += '?find=' + sourceValue.join(',');
			}
			
			var cRequest = $.ajax({
				'url': url,
				'dataType': 'json',
				'async': true,
				'type': 'GET',
				'success': onComplete,
				'cache': false
			});
			
			// Listen for the modal close event so we can manually close the select2 dropdown
			this.$editor.bind('redactor.modal_close', function() {
				$select.select2('close');
			});
			
			function onComplete(results) {
				
				$select.removeAttr('disabled').html('');
				var selectContent = '';
				
				// Create the new HTML for the select
				for (var i = 0; i < results.length; i++) {
					var option = results[i],
					selected = option.id === data['itemId'],
					$content = $('<span>' + option.text + '</span>');
					//thumbnail = ($content.find('img').length > 0) ? $content.find('img').eq(0).attr('src') : '';
					$option = $('<option value="' + option.id + '"' + (selected ? ' selected' : '') + '></option>').append($content).appendTo($select);
				}
				
				if (results.length === 0) {
					$select.append('<option value="">' + emptyMsg + '</option>');
				}
				
				$select.trigger('change').select2('enable');
				
				if ($select.select2('container').find('img').length > 0) {
					$select.select2('container').addClass('has-thumbnails');
					$select.data('select2').dropdown.addClass('has-thumbnails');
				}
				
			}
			
		},
		
		insertItemLink: function(data)
		{
			var link = '', text = '', target = '', itemId = '';
			
			itemId = $('#redactor-cmf-select2-link').select2('val');
			type = data['type'];
			text = $('#redactor_link_url_text').val();
			
			if (!isSet(itemId) || itemId.length === 0) {
				// Don't bother if there is no item ID set
				this.$editor.focus();
				this.restoreSelection();
				this.setBuffer();
				this.modalClose();
				return false;
			}
			
			if ($('#redactor_link_blank').attr('checked')) {
				target = ' target="_blank"';
			}
			
			// test url
			var pattern = '((xn--)?[a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}';
			var re = new RegExp('^(http|ftp|https)://' + pattern,'i');
			var re2 = new RegExp('^' + pattern,'i');
			if (link.search(re) == -1 && link.search(re2) == 0 && this.opts.protocol !== false) {
				link = this.opts.protocol + link;
			}
			
			this._insertItemLink('<a href="#" data-item-uid="' + (new Date()).getTime() + '" data-item-id="' + itemId + '" data-item-type="' + type + '"' + target + '>' +  text + '</a>', $.trim(text), itemId, type, target);
			
		},
		
		_insertItemLink: function(a, text, itemId, type, target)
		{
			this.$editor.focus();
			this.restoreSelection();
			this.setBuffer();

			if (text !== '')
			{
				if (this.insert_link_node)
				{
					this.insert_link_node = $(this.insert_link_node)
					.text(text)
					.attr('href', '#')
					.attr('data-item-uid', (new Date()).getTime() + '')
					.attr('data-item-id', itemId)
					.attr('data-item-type', type);
					
					if (target !== '')
					{
						this.insert_link_node.attr('target', target);
					}
					else
					{
						this.insert_link_node.removeAttr('target');
					}
					this.syncCode();
					//selectElementText(this.insert_link_node[0]);
				}
				else
				{
					this.execCommand('inserthtml', a);
				}
			}

			this.modalClose();
		},
		
		showCMFLink: function() {
			
			this.showLinkOrig();
			//var $modal = $('#redactor_modal_content');
			
		}
		
	}
	
	RedactorPlugins.cmfimages = {

		init: function()
		{
			var editor = this,
			placeholders = editor.opts.placeholders;
			
			//editor.addBtnAfter('image', 'cmfimage', 'Insert Image (CMF)', $.proxy(function() { editor.showCMFImage(); }, editor));
			//editor.removeBtn('image');
			
			function editImage() {
				console.log('woo ok yeah');
			}
			
		},
		
		modal_cmfimage_edit: String() +
		'<div id="redactor_modal_content">' +
		'<label>' + RLANG.title + '</label>' +
		'<input id="redactor_file_alt" class="redactor_input" />' +
		'<label>' + RLANG.link + '</label>' +
		'<input id="redactor_file_link" class="redactor_input" />' +
		'<label>' + RLANG.image_position + '</label>' +
		'<select id="redactor_form_image_align">' +
			'<option value="none">' + RLANG.none + '</option>' +
			'<option value="left">' + RLANG.left + '</option>' +
			'<option value="right">' + RLANG.right + '</option>' +
		'</select>' +
		'</div>' +
		'<div id="redactor_modal_footer">' +
			'<a href="javascript:void(null);" id="redactor_image_delete_btn" class="redactor_modal_btn">' + RLANG._delete + '</a>&nbsp;&nbsp;&nbsp;' +
			'<a href="javascript:void(null);" class="redactor_modal_btn redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
			'<input type="button" name="save" class="redactor_modal_btn" id="redactorSaveBtn" value="' + RLANG.save + '" />' +
		'</div>',

		modal_cmfimage: String() +
		'<div id="redactor_modal_content">' +
		'<div id="redactor_tabs">' +
			'<a href="javascript:void(null);" class="redactor_tabs_act">' + RLANG.upload + '</a>' +
			'<a href="javascript:void(null);">' + RLANG.choose + '</a>' +
			'<a href="javascript:void(null);">' + RLANG.link + '</a>' +
		'</div>' +
		'<form id="redactorInsertImageForm" method="post" action="" enctype="multipart/form-data">' +
			'<div id="redactor_tab1" class="redactor_tab">' +
				'<input type="file" id="redactor_file" name="file" />' +
			'</div>' +
			'<div id="redactor_tab2" class="redactor_tab" style="display: none;">' +
				'<div id="redactor_image_box"></div>' +
			'</div>' +
		'</form>' +
		'<div id="redactor_tab3" class="redactor_tab" style="display: none;">' +
			'<label>' + RLANG.image_web_link + '</label>' +
			'<input type="text" name="redactor_file_link" id="redactor_file_link" class="redactor_input"  />' +
		'</div>' +
		'</div>' +
		'<div id="redactor_modal_footer">' +
			'<a href="javascript:void(null);" class="redactor_modal_btn redactor_btn_modal_close">' + RLANG.cancel + '</a>' +
			'<input type="button" name="upload" class="redactor_modal_btn" id="redactor_upload_btn" value="' + RLANG.insert + '" />' +
		'</div>',
		
		// When inserting an image
		
		showCMFImage: function()
		{
			this.saveSelection();

			var callback = $.proxy(function()
			{
				// json
				if (this.opts.imageGetJson !== false)
				{
					$.getJSON(this.opts.imageGetJson, $.proxy(function(data) {

						var folders = {};
						var z = 0;

						// folders
						$.each(data, $.proxy(function(key, val)
						{
							if (typeof val.folder !== 'undefined')
							{
								z++;
								folders[val.folder] = z;
							}

						}, this));

						var folderclass = false;
						$.each(data, $.proxy(function(key, val)
						{
							// title
							var thumbtitle = '';
							if (typeof val.title !== 'undefined')
							{
								thumbtitle = val.title;
							}

							var folderkey = 0;
							if (!$.isEmptyObject(folders) && typeof val.folder !== 'undefined')
							{
								folderkey = folders[val.folder];
								if (folderclass === false)
								{
									folderclass = '.redactorfolder' + folderkey;
								}
							}

							var img = $('<img src="' + val.thumb + '" class="redactorfolder redactorfolder' + folderkey + '" rel="' + val.image + '" title="' + thumbtitle + '" />');
							$('#redactor_image_box').append(img);
							$(img).click($.proxy(this.imageSetThumb, this));


						}, this));

						// folders
						if (!$.isEmptyObject(folders))
						{
							$('.redactorfolder').hide();
							$(folderclass).show();

							var onchangeFunc = function(e)
							{
								$('.redactorfolder').hide();
								$('.redactorfolder' + $(e.target).val()).show();
							}

							var select = $('<select id="redactor_image_box_select">');
							$.each(folders, function(k,v)
							{
								select.append($('<option value="' + v + '">' + k + '</option>'));
							});

							$('#redactor_image_box').before(select);
							select.change(onchangeFunc);
						}

					}, this));
				}
				else
				{
					$('#redactor_tabs a').eq(1).remove();
				}

				if (this.opts.imageUpload !== false)
				{

					// dragupload
					if (this.opts.uploadCrossDomain === false && this.isMobile() === false)
					{

						if ($('#redactor_file').size() !== 0)
						{
							$('#redactor_file').dragupload(
							{
								url: this.opts.imageUpload,
								uploadFields: this.opts.uploadFields,
								success: $.proxy(this.imageUploadCallback, this),
								error: $.proxy(this.opts.imageUploadErrorCallback, this)
							});
						}
					}

					// ajax upload
					this.uploadInit('redactor_file',
					{
						auto: true,
						url: this.opts.imageUpload,
						success: $.proxy(this.imageUploadCallback, this),
						error: $.proxy(this.opts.imageUploadErrorCallback, this)
					});
				}
				else
				{
					$('.redactor_tab').hide();
					if (this.opts.imageGetJson === false)
					{
						$('#redactor_tabs').remove();
						$('#redactor_tab3').show();
					}
					else
					{
						var tabs = $('#redactor_tabs a');
						tabs.eq(0).remove();
						tabs.eq(1).addClass('redactor_tabs_act');
						$('#redactor_tab2').show();
					}
				}

				$('#redactor_upload_btn').click($.proxy(this.imageUploadCallbackLink, this));

				if (this.opts.imageUpload === false && this.opts.imageGetJson === false)
				{
					setTimeout(function()
					{
						$('#redactor_file_link').focus();
					}, 200);

				}

			}, this);

			this.modalInit(RLANG.image + ' into your anus', this.modal_cmfimage, 610, callback);

		}

	}
	
	////////////////// INITIALISE REDACTOR ///////////////////
	
	$(document).ready(function() {
		
		// Run through each input
		$('.redactor').each(initItem);
		
		// When a new form is added, run it again!
		$(window).bind('cmf.newform', function(e, data) {
			data.wrap.find('.redactor').each(initItem);
		});
		
	});
	
	function initItem() {
		
		var $input = $(this),
		name = $(this).attr('name'),
		settings = typeof(field_settings[name]) != 'undefined' ? field_settings[name] : {};
		
		// Don't run on temporary fields...
		if (name.indexOf('%TEMP%') >= 0) { return; }
		
		var opts = {
			buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|', 'image', 'video', 'file', 'table', 'link', '|', 'fontcolor', 'backcolor', '|', 'alignment', '|', 'horizontalrule'],
			imageUpload: '/admin/redactor/imageupload',
			fileUpload: '/admin/redactor/fileupload',
			imageGetJson: '/admin/redactor/getimages',
			minHeight: 300,
			convertDivs: false,
			autoresize: true,
			formattingTags: ['p', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4'],
			plugins: ['tidy', 'cmflink', 'cmfimages']
		};
		
		// Any additional plugins in the field settings
		if (typeof(settings['plugins']) != 'undefined' && settings['plugins'].length > 0) {
			opts['plugins'] = opts['plugins'].concat(settings['plugins']);
		}
		
		// Any placeholders in the field settings
		if (typeof(settings['placeholders']) != 'undefined') {
			opts.plugins.push('placeholder');
			opts['placeholders'] = settings['placeholders'];
		}
		
		// Any links in the field settings
		if (typeof(settings['links']) != 'undefined') {
			opts['links'] = settings['links'];
		}
		
		$input.redactor(opts);
		
	}
	
})(jQuery);