$(function() {
	new_auth_id = $('#new_auth_id');
	if (new_auth_id.length) {
		new_auth_id.autocomplete(usersList,
		{
			delay: 1000,
			matchSubset: true,
			matchContains: true
		});
	}
	$('#new_cat').toggleWithLegend($('#new_cat').parent().children().not('#new_cat'), {
		// no cookie on new category as we don't use this every day
		legend_click: true
	});
});
