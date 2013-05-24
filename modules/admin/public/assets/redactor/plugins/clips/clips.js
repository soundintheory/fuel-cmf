if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {};

RedactorPlugins.clips = {

	init: function()
	{
		var clipLinks = $('#redactor_modal .redactor_clip_link');
		if (clipLinks.length == 0) return;
		
		var callback = $.proxy(function()
		{
			clipLinks.each($.proxy(function(i,s)
			{
				$(s).click($.proxy(function()
				{
					this.insertClip($(s).next().html());
					
					return false;
					
				}, this));
					
			}, this));
			
			
			this.saveSelection();
			this.setBuffer();
			
		}, this);
	
		this.addBtn('clips', 'Clips', function(obj)
		{
			obj.modalInit('Clips', '#clipsmodal', 500, callback);
			
		});		

	},
	insertClip: function(html)
	{
		this.restoreSelection();
		this.execCommand('inserthtml', html);
		this.modalClose();
	}

}

