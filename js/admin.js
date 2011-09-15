jQuery(function($) {
	
	// all for post sorting on reordering admin page
	
	$('#conferencer_reordering .items').sortable({
		axis: 'y',
		tolerance: 'pointer',
		containment: 'parent',
		opacity: 0.8
	});
	
	// all for addition of options for multiple select
	
	$('#conference_options .add-another').click(function() {
		var list = $(this).prev();
		var item = $($('li:first', list).clone()).appendTo(list);
		$('select', item).val('');
		
		return false;
	});
	
	// date picker for post admin options
	
	$('#conference_options .date').datepicker({
		showOtherMonths: true,
		onSelect: function(selectedDate) {
			var api = $(this).data('datepicker');
			var date = $.datepicker.parseDate(
				api.settings.dateFormat || $.datepicker._defaults.dateFormat,
				selectedDate,
				api.settings
			);
			
			if (this.id == 'conferencer_starts_date') {
				$('#conferencer_ends_date').datepicker('option', 'minDate', date);
			} else {
				$('#conferencer_starts_date').datepicker('option', 'maxDate', date);
			}
		}
	});
	
	// regenerate logos
	
	var logos = {
		ids: typeof(conferencer_logo_regeneration_ids) != 'undefined' ? conferencer_logo_regeneration_ids : [],
		ndx: 0
	};

	$('#conferencer_regenerate_logos_button').click(function() {
		$('#conferencer_regenerate_logos_console').html("<p>regenerating logos</p>");
		logos.ndx = 0;
		regenerateNextLogo();
	});

	function regenerateNextLogo() {
		var id = logos.ids[logos.ndx++];
		if (id) {
			$.post(ajaxurl, { action: "conferencer_logo_regenerate", id: id }, function(response) {
				$('#conferencer_regenerate_logos_console').append("<p>" + (response.success ? response.success : response.error) + "</p>");
				
				regenerateNextLogo();
			});
		} else {
			$.get(ajaxurl, { action: 'conferencer_logo_regeneration_done' }, function(response) {
				$('#conferencer_logo_regeneration_needed').slideUp();
				$('#conferencer_regenerate_logos_console').append("<p>complete</p>");
			});
		}
	}
	
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