/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Load the graph AJAX request after the page loads
 */
google.load('visualization', '1', {'packages': ['corechart']});
//google.setOnLoadCallback(drawCharts);

$(document).ready(function() 
{
	var $form     = $('#form_collection_filter');
	var graph_url = $form.find('input[name="graph_url"]').val();
	var params = $('#form_collection_filter').serialize();
		
	$.get(graph_url, params, function(data) 
	{
		drawCharts(data);
	});
	
});
