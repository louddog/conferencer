jQuery(function($) {
	$('#conferencer_settings .levels tbody').sortable({
		items: 'tr',
		handle: '.ui-icon',
		axis: 'y',
		containment: '.post_types',
		revert: 50,
		tolerance: 'pointer'
	});
	
	$('#conference_options .add-another').click(function() {
		var list = $(this).prev();
		var item = $($('li:first', list).clone()).appendTo(list);
		$('select', item).val('');
		
		return false;
	});
});