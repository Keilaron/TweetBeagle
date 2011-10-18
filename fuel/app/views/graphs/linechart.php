<div id="canvases">
<?php
foreach ($charts as $graph)
{
	$graph_id = $graph['id'].'_canvas';
	
	if (empty($graph['message']))
	{
		// Create the legend
		echo '<table id="',$graph_id,'_legend" class="graph-legend">',"\n";
		echo '<tr><th></th><th><h3>Top Ten Terms</h3></th><th>',Html::help('Weight', $help, 'weight'),'</th><th>Options</th></tr>',"\n";
		
		foreach ($graph['data'] as $termId => $termData)
		{
			echo '<tr><td><div class="graph-legend-colour" style="background-color: '.$termData['colour'].'">&nbsp;</td>';
			echo '<td><a href="#" class="term_filter" rel="',$termId,'">',$termData['content'],'</a></td>';
			echo '<td class="weight">',$termData['weight'],'</td>';
			echo '<td class="options"><a href="#" class="term_filter" rel="',$termId,'">',Html::help('Filter&nbsp;by', $help, 'filter'),'</a>';
			if (!empty($hasEditAccess))
				echo '&nbsp;|&nbsp;<a href="#" class="term_hide" rel="',$termId,'">',Html::help('Hide', $help, 'hide'),'</a>';
			echo '</td></tr>',"\n";
		}
		
		echo '</table>',"\n";
	}
	// Create the containing DIV -- Warning: Google Vis API will overwrite the innerHTML.
	echo '<div id="',$graph_id,'"></div>',"\n";
}
?>
</div>
