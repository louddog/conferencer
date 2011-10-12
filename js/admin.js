jQuery(function($) {
	// alert user when they change logo sizes to regenerate
	
	var sponsor_slideshow_logo_size_changed = false;

	$('body').delegate('.conferencer_widget_logo_size', 'change', function() {
		sponsor_slideshow_logo_size_changed = true;
		$.getJSON(ajaxurl, { action: 'conferencer_logo_regeneration_needed' });
	});
	
	$('body').ajaxSuccess(function(e, request, options) {
		if (
			options.data.search('action=save-widget') != -1 &&
			options.data.search('widget-conferencer_sponsors_widget') != -1 &&
			sponsor_slideshow_logo_size_changed
		) {
			$('#conferencer_logo_regeneration_needed').slideDown();
		}
	});
});