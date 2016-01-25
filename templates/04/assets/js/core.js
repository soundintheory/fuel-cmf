//= require jquery-1.10.2.min.js

(function($) {

	$(document).on('ready', function() {

		window.page = new Page();

	});

	// ------------------------------------------------------------------------------------------------------
	// COMMON PAGE FUNCIONALITY

	Page = function()
	{
		// Equal height groups
		var heightGroups = new EqualHeightGroups();

		// Placeholder support in older browsers
		$('input, textarea').placeholder();

		// Form validation
		this.initValidation();
	}

	/**
	 * Form Validation using Parsley JS: http://parsleyjs.org/doc/index.html
	 */
	Page.prototype.initValidation = function()
	{
		// Validate all forms with the "validate" class
		$('form.validate').parsley({
			trigger: 'change', // Which event to trigger validation on
			errorClass: 'has-error', // Class when there are errors
			successClass: 'has-success', // Class for successful validation
			errorsMessagesDisabled: true, // Whether to add disable messages on each field
			trimValue: true, // Trim whitespace before validation
			validationThreshold: 3, // Minimum number of chars before validation will fire

			classHandler: function(field)
			{
				var tagName = (field.$element.prop('tagName') || '').toLowerCase();
				var type = (field.$element.attr('type') || '').toLowerCase();

				// Add feedback class and icons
				if (type != 'radio' && type != 'checkbox' && tagName != 'textarea') {
					field.$element.after(
						'<i class="fa fa-check form-control-feedback feedback-success" aria-hidden="true"></i>' +
						'<i class="fa fa-remove form-control-feedback feedback-error" aria-hidden="true"></i>'
					);
					field.$element.parents('.form-group').eq(0).addClass('has-feedback');
				}

				return field.$element.parents('.form-group').eq(0);
			},

			errorsContainer: function (field)
			{
				return field.$element.parents('.form-group').eq(0);
			}

		});
	}

	// ------------------------------------------------------------------------------------------------------
	// EQUAL HEIGHT GROUPS

	EqualHeightGroups = function(wrap, opts)
	{
		this.opts = $.extend({}, EqualHeightGroups.defaults, opts || {});
		this.wrap = wrap || $('body');
		
		if (!this.wrap.find('[data-height-group]').length) return;

		var groups = {};

		this.wrap.find('[data-height-group]').each(function() {

			var groupId = $(this).attr('data-height-group');
			if (groups.hasOwnProperty(groupId)) return;

			groups[groupId] = $('[data-height-group="'+groupId+'"]');

		});

		this.groups = groups;

		$(window).on('load resize', $.proxy(this.onResize, this));
		this.onResize();

	}

	EqualHeightGroups.defaults = {};

	EqualHeightGroups.prototype.processGroup = function(groupId)
	{
		var group = this.groups[groupId].height('auto'),
			lines = {};

		group.each(function(i, el) {
			var cPos = $(el).offset().top+'';
			if (!lines.hasOwnProperty(cPos)) lines[cPos] = [];
			lines[cPos].push(el);
		});

		for (var p in lines) {
			var line = $(lines[p]).map(function() { return $(this).toArray(); } );
			var maxHeight = Math.max.apply(null, line.map(function() {
				return $(this).height();
			}).get());
			line.height(maxHeight);
		}
	}

	EqualHeightGroups.prototype.onResize = function()
	{
		if (this.throttleResize == true) {
			this.resizeAttempts++;
			return;
		}

		for (var p in this.groups) {
			this.processGroup(p);
		}

		this.throttleResize = true;
		this.resizeAttempts = 0;
		clearTimeout(this.resizeTimeout || null);
		setTimeout($.proxy(function() {
			this.throttleResize = false;
			if (this.resizeAttempts > 0) {
				this.onResize();
			}
		}, this), 500);
	}

})(jQuery);
