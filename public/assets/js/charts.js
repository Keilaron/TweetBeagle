
/**
 * Add callbacks here as chart_id => callback creator function.
 * The CC function must return a function, and will be passed the chart id along with the chart object.
 * The function it returns will be used as an event function in which the this variable will be unreliable.
 */
if (typeof chartsCB == 'undefined') chartsCB = {};

/**
 * Function to be called by google.setOnLoadCallback().
 * Expects:
 * - A div (or other block container) with the id canvases.
 * - A global array "charts", with the requirements below.
 * - That https://www.google.com/jsapi or the graph scripts and so on are loaded.
 *
 * Each graph is expected to have:
 *  - id: the ID of the graph
 * and either:
 *  - message: A message to display instead of the graph
 * or:
 *  - type: The type of graph (currently ignored, all are line graphs)
 *  - tagType: The type tag that was used to create the graph (gets placed in the title)
 *  - data: The graph data
 */
function drawCharts(charts)
{
	if (!charts)
		throw 'No charts to draw!';
	
	for (var i = 0; i < charts.length; i++)
	{
		var grph = charts[i];
		var chartDiv = document.getElementById(grph.id+'_canvas');
		
		if (typeof grph.message != 'undefined' && grph.message)
		{
			chartDiv.innerHTML = '<div class="message">'+grph.message+'</div>';
			continue;
		}
		
		// // // Line Chart // // //
		var data = new google.visualization.DataTable(grph.data);
		
		colours = [];
		for (col in grph.data.cols)
		{
			if (col == 0) continue; // Skip definition column
			colours[colours.length] = grph.data.cols[col].colour;
		}
		
		var options = {
			title: 'Trending '+grph.tagType+'s',
			width:  673, // max is ~1010px, we want 2/3 now. Also, this width should sync with the legend width in main.css.
			height: 420,
			legend: 'none',
			pointSize: 5,
			precision: 3,
			chartArea: {
				top: 'auto',
				left: 50,
				width: 673, // should sync with the above width
				height: 'auto'
			},
			colors: colours
		};
		
		// change the way labels are displayed on the X-axis depending on the number
		// of labels (rows)
		if (data.getNumberOfRows() > 4)
		{
			options.hAxis = {
				slantedText: true,
				showTextEvery: 2,
				maxAlternation: 1
			};
		}
		
		var chart = new google.visualization.LineChart(chartDiv);
		chart.draw(data, options);
		for (CB in chartsCB)
			google.visualization.events.addListener(chart, 'select', chartsCB[CB](grph.id, chart));
		chartDiv.style.position = null; // Why do you do this, Google Vis?
		drawLegend(chart, grph, data, options);
	}
}

function drawLegend(chart, graph, data_table, options)
{
	/* Drawn on server-side for now
	// Create legend container
	var graphDiv = $('#'+graph.id+'_canvas');
	var fieldset = $(document.createElement('fieldset'));
	//var legend   = $(document.createElement('legend'));
	//legend.text('Legend');
	//fieldset.append(legend);
	fieldset.attr('id', graph.id+'_legend');
	fieldset.addClass('graph-legend');
	
	for (col in graph.data.cols)
	{
		if (col == 0) continue; // Skip definition column
		var colLegend = $(document.createElement('div'));
		var colColour = $(document.createElement('div'));
		var colName   = $(document.createElement('p'));
		colLegend.attr('id', graph.id+'_legend_'+graph.data.cols[col].id);
		colLegend.addClass('graph-legend-item');
		colColour.css('background-color', graph.data.cols[col].colour);
		colName.text(graph.data.cols[col].label);
		
		colLegend.append(colColour);
		colLegend.append(colName);
		fieldset.append(colLegend);
	}
	
	graphDiv.append(fieldset);
	*/
}
