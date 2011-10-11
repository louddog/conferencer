jQuery(function($) {
	// Sponsors Slideshow
	$('.widget_conferencer_sponsors_widget .sponsors').fadeshow();
	
	$('.conferencer_tabs').each(function() {
		var options = {
			selected: 0
		};
		
		var tabs = $('.tabs li', this);
		var allContent = $([]);
		
		tabs.each(function() {
			var tab = $(this);
			var content = $($('a', this).attr('href'));
			allContent = $(allContent).add(content);
			
			$('a', tab).click(function() {
				tabs.removeClass('current');
				tab.addClass('current');
				
				allContent.hide();
				content.show();
				
				return false;
			});
		});
		
		$('a', tabs[options.selected]).click();
	});
	
	// TODO: session tooltip helper, keep them in the viewport
});