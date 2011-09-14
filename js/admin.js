jQuery(function($) {
	$('#conferencer_reordering .items').sortable({
		axis: 'y',
		tolerance: 'pointer',
		containment: 'parent',
		opacity: 0.8
	});
	
	$('#conference_options .add-another').click(function() {
		var list = $(this).prev();
		var item = $($('li:first', list).clone()).appendTo(list);
		$('select', item).val('');
		
		return false;
	});
	
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
});