
var collection = {};
// NOTE: One setting, collection.autoFilters, must be at the end of the file
collection.activeFilters = '.dyn_filters';
collection.activeHides = '.dyn_hides';
collection.filterClassSuffix = 'filter'; // Don't style these classes globally: They're used by a *and* input.
collection.hiderClassSuffix = 'hide'; // Don't style these classes globally: They're used by a *and* input.
collection.filterLinks = '.filter_trigger_container' // Where to look for links that add filters
collection.filterForm = '#form_collection_filter';
collection.filterResetButton = '#s_filters_off';
collection.infoId = 'selectionInfo';

/**
 * Adds a filter to the current set of filters, and fires refreshFilters().
 * @param string type term|mention|hashtag|link
 * @param string content The data to be filtered out (e.g. #gadgets, @foo)
 * @param string label Optional. Label to show on filter link. Defaults to content.
 * @see refreshFilters
 */
collection.addFilter = function (id, type, content, label) {
	if (!label) label = content;
	console.log('Adding filter '+type+' for '+id+' ('+content+') labelled '+label);
	
	var newItem = document.createElement('li');
	var newFilter = document.createElement('a');
	$(newFilter)
		.text(type+' "'+label+'"')
		.click(collection.removeThisFilter)
		.addClass(type+'_'+collection.filterClassSuffix)
		.attr('rel', id);
	$(collection.activeFilters).append(newItem);
	$(newItem).append(newFilter);
	
	var newInput = document.createElement('input');
	$(newInput)
		.attr('value', id)
		.addClass(type+'_'+collection.filterClassSuffix)
		.attr('name', 'filters[]')
		.attr('type', 'hidden');
	$(collection.filterForm).append(newInput);
	
	collection.refreshFilters();
}

collection.addHide = function(id, type, content, label) {
	if (!label) label = content;
	console.log('Adding hide '+type+' for '+id+' ('+content+') labelled '+label);
	
	var newInput = document.createElement('input');
	$(newInput)
		.attr('value', id)
		.attr('name', 'hide[]')
		.attr('type', 'hidden');
	$(collection.filterForm).append(newInput);
	
	collection.refreshFilters();
}

/**
 * Event handler. A variant of addThisFilter for links.
 * This function gives the option to either add the link as a (regex? domain?) filter,
 * or to go to the URL in a new window.
 * @deprecated
 */
collection.addOrGo = function (e) {
	collection.addThisFilter.apply(this, [e]);
}

/**
 * Event handler. When fired, adds the event target's contents as a filter.
 * Expects the contents (.text()) to be the whole tag content,
 * and the only class to be the filter type (term|mention|hashtag|link)
 * (along with a suffix separated with an underscore).
 * Or, expects the parent().parent()'s first link to be the above.
 */
collection.addThisFilter = function (e) {
	e.preventDefault();
	e.stopPropagation();
	if (!$(this).attr('rel'))
	{
		// Locate the actual link to find the actual content
		arguments.callee.apply($(this).parent().parent().find('a:first')[0], [e]);
		return;
	}
	var id = $(this).attr('rel');
	var content = $(this).text();
	var type = $(this).attr('class');
	type = type.substring(0, type.indexOf('_'));
	//collection.showFilterOptions(e.layerY, e.layerX, id, type, content);
	collection.addFilter(id, type, content);
}

/**
 * Event handler. When fired, adds the event target's contents as a hidden tag.
 * Expects the contents (.text()) to be the whole tag content,
 * and the only class to be the filter type (term|mention|hashtag|link)
 * (along with a suffix separated with an underscore).
 * Or, expects the parent().parent()'s first link to be the above.
 */
collection.addThisHide = function (e) {
	e.preventDefault();
	e.stopPropagation();
	if (
		$(this).hasClass('mention_hide') ||
		$(this).hasClass('hashtag_hide') ||
		$(this).hasClass('link_hide')
		)
	{
		// Locate the actual link to find the actual content
		arguments.callee.apply($(this).parent().parent().find('a:first')[0], [e]);
		return;
	}
	var id = $(this).attr('rel');
	var content = $(this).text();
	var type = $(this).attr('class');
	type = type.substring(0, type.indexOf('_'));
	collection.addHide(id, type, content);
}

/**
 * Callback function so chart calls collection.filterChart().
 */
collection.filterChart_createCB = function (id, graph) {
	return function () { collection.filterChart(id, graph); };
}

/**
 * Function called by chart for filtering/presenting data.
 */
collection.filterChart = function (graph_id, graph) {
	try {
	var i = 0;
	console.log('Graph '+graph_id+' clicked.');
	
	var selected = graph.getSelection()[0];
	// Don't filter if they clicked a graph dot.
	if (typeof selected.row != 'undefined')
		return;
	console.log('Column '+selected.column+' is the target.');
	
	// Get the column's content/label
	var thisChart = null;
	var filterContent = null;
	var filterId = null;
	for (i = 0; i < charts.length; i++)
	{
		if (charts[i].id == graph_id)
		{
			thisChart = charts[i];
			break;
		}
	}
	if (!thisChart)
		throw 'No such chart!'
	
	if (thisChart.data.cols[selected.column])
	{
		filterId = thisChart.data.cols[selected.column].id; // This has the form type_id
		filterId = filterId.substring(filterId.indexOf('_') + 1, filterId.length);
		filterContent = thisChart.data.cols[selected.column].label;
	}
	if (filterContent)
		collection.showFilterOptions(null, null, filterId, graph_id, filterContent);
	else
		throw 'No such column!'
	
	} catch (ex) { console.log(ex); }
}

/**
 * TODO: document me .. or not:
 * @deprecated
 */
collection.showFilterOptions = function (top, left, id, type, content) {
	var infoDiv = $('#'+collection.infoId);
	if (!infoDiv.length)
	{
		var infoDiv = document.createElement('div');
		infoDiv.id = collection.infoId;
		document.getElementById('content').appendChild(infoDiv);
		infoDiv = $(infoDiv);
		infoDiv.css('position', 'absolute');
		infoDiv.css('width', '25em');
		infoDiv.css('height', '3em');
		infoDiv.addClass('fancybox');
		infoDiv.html(
			'<div id="'+collection.infoId+'_actions"><a onclick="$(this).parent().parent().hide()">x</a></div>'+
			'<div><span id="'+collection.infoId+'_target"></span> tag options:</div>'+
			'<div id="'+collection.infoId+'_opts"></div>'
			);
		infoDiv.hide();
		$(document.body).click(function() {
			infoDiv.hide();
		});
	}
	else
	{
		infoDiv.children('#'+collection.infoId+'_opts').empty();
	}
	
	if (!top || !left)
	{
		// Assume it's the graph
		top = $('#canvases').position().top - infoDiv.height();
		left = $(document).width() - infoDiv.width() - 200;
	}
	infoDiv.css('top', top+'px');
	infoDiv.css('left', left+'px');
	
	// Insert content
	var trimContent = content;
	if (content.length > 30)
		trimContent = content.substring(0, 30) + '...';
	$('#'+collection.infoId+'_target').text(trimContent);
	$('#'+collection.infoId+'_opts').append('<a>Filter by it</a>').children().click(function() {
		collection.addFilter(id, type, content);
	});
	$('#'+collection.infoId+'_opts').append(' | <a>Hide it</a>').children().click(function() {
		collection.addHide(id, type, content);
	});
	if (type == 'link')
		$('#'+collection.infoId+'_opts').append(' | <a href="'+content+'" target="_blank">Go to URL</a> (potentially unsafe)');
	
	infoDiv.show();
}

/**
 * Adds/removes a piece of text depending on if there are filters applied.
 * @deprecated
 */
collection.refreshFilterCount = function () {
	/*
	var filters = $(collection.activeFilters);
	var par = $(collection.activeFilters+' p');
	var showPar = false;
	if ((filters.children().length == 0) || ((filters.children().length == 1) && ($(collection.activeFilters+' a').length == 0)))
		showPar = true;
	
	if (showPar && (par.length == 0))
	{
		var item = document.createElement('li');
		var par = document.createElement('p');
		$(par).text('No filters currently applied.');
		filters.append(item);
		$(item).append(par);
	}
	else if (!showPar && (par.length == 1))
		par.parent().remove();
	*/
}

/**
 * Causes filters to be sent to the server and the resulting data to be retrieved.
 * At the moment, this is done without AJAX and thus reloads the page immediately.
 */
collection.refreshFilters = function () {
	// TODO: AJAX call(s): refresh graph & top ten X widgets
	collection.refreshFilterCount();
	// For now, instead...
	$(collection.filterForm).submit();
}

/**
 * Removes a filter from the current set of filters, and fires refreshFilters().
 * @param string type term|mention|hashtag|link
 * @param string content The data to be filtered out (e.g. #gadgets, @foo)
 * @see refreshFilters
 */
collection.removeFilter = function (id, type, content, massClear) {
	console.log('Removing filter '+type+' for '+id+' ('+content+')');
	
	// Remove list item & link
	var selector = collection.activeFilters+' a.'+type+'_'+collection.filterClassSuffix+'[rel="'+id+'"]';
	if ($(selector).length > 1)
		console.log('Warning: link selector matched more than one element!');
	$(selector).parent().remove();
	// Remove input
	var selector = collection.filterForm+' input.'+type+'_'+collection.filterClassSuffix+'[value="'+id+'"]';
	if ($(selector).length > 1)
		console.log('Warning: input selector matched more than one element!');
	$(selector).remove();
	
	if (!massClear)
		collection.refreshFilters();
}

collection.removeHide = function (id, type) {
	console.log('Removing hide '+type+' for '+id);
	
	var newInput = document.createElement('input');
	$(newInput)
		.attr('value', id)
		.attr('name', 'unhide[]')
		.attr('type', 'hidden');
	$(collection.filterForm).append(newInput);
	
	collection.refreshFilters();
}

/**
 * Event handler. When fired, removes all current filters, and fires refreshFilters().
 * @see refreshFilters
 */
collection.removeAllFilters = function (e) {
	console.log('Removing all filters!');
	if (e)
	{
		e.preventDefault();
		e.stopPropagation();
	}
	
	var selector = '';
	for (type in collection.autoFilters)
		selector +=
		// Remove links
			collection.activeFilters+' a.'+type+'_'+collection.filterClassSuffix+', '
		// Remove inputs
			+ collection.filterForm+' input.'+type+'_'+collection.filterClassSuffix+', '
			;
	// Remove last comma from selector before passing to jQuery
	$(selector.substring(0, selector.length - 2)).remove();
	
	collection.refreshFilters();
}

/**
 * Event handler. When fired, removes the event target's rel from the filters.
 * Expects the rel attribute to be the whole filter content,
 * and the only class to be the filter type (term|mention|hashtag|link).
 * (along with a suffix separated with an underscore).
 */
collection.removeThisFilter = function (e, massClear) {
	e.preventDefault();
	e.stopPropagation();
	var id = $(this).attr('rel');
	var content = '(not collected)';
	var type = $(this).attr('class');
	type = type.substring(0, type.indexOf('_'));
	var isClear = $(this).attr('class');
	isClear = (isClear.indexOf('_', (isClear.indexOf('_') + 1)) != -1);
	if ((!isClear) || massClear)
		collection.removeFilter(id, type, content, massClear);
	else
	{
		// This is a link which clears all filters after this particular link
		var ignore = true;
		var ignore_until_id   = id;
		var ignore_until_type = type;
		// Go through all currently applied filters .. if there are any
		if ($(this).parent().parent().children().length < 2)
			return; // TODO: alert() or remove this one
		$(this).parent().parent().children().each(function () {
			if (ignore) // We have to locate the current link first
			{
				var current = $(this).children().first();
				if (current.length) // Ignore collection name
				{
					if ((current.attr('rel') == ignore_until_id) &&
						(current.attr('class').indexOf(ignore_until_type) != -1))
						ignore = false;
				}
			}
			else // We've found the current link, now remove all of the next ones
				collection.removeThisFilter.apply($(this).children().first()[0], [e,true]);
		});
		collection.refreshFilters();
	}
}

collection.removeThisHide = function (e) {
	e.preventDefault();
	e.stopPropagation();
	var id = $(this).attr('rel');
	var type = $(this).attr('class');
	type = type.substring(0, type.indexOf('_'));
	collection.removeHide(id, type);
}

// Register filters for the event triggers to be added.
collection.autoFilters = {
	term: collection.addThisFilter,
	mention: collection.addThisFilter,
	hashtag: collection.addThisFilter,
	link: collection.addThisFilter
};
collection.autoHiders = {
	term: collection.addThisHide,
	mention: collection.addThisHide,
	hashtag: collection.addThisHide,
	link: collection.addThisHide
};

// Register the chart filter handler
if (typeof chartsCB == 'undefined') chartsCB = {};
chartsCB.term = collection.filterChart_createCB;

// Add the event handlers above when document is ready
$(document).ready(function () {
	// Add handler so time drop-down auto-submits
	$('#filter_last_x_days').change(collection.refreshFilters);
	// Add add-filter handlers and hide handlers
	for (type in collection.autoFilters)
		if (collection.autoFilters[type])
			$(collection.filterLinks+' .'+type+'_'+collection.filterClassSuffix).click(collection.autoFilters[type]);
	for (type in collection.autoHiders)
		if (collection.autoHiders[type])
			$(collection.filterLinks+' .'+type+'_'+collection.hiderClassSuffix).click(collection.autoHiders[type]);
	// Add removal handlers
	$(collection.activeFilters+' a').click(collection.removeThisFilter);
	$(collection.filterResetButton).click(collection.removeAllFilters);
	$(collection.activeHides+' a').click(collection.removeThisHide);
	// Check if there are active filters
	collection.refreshFilterCount();
});
