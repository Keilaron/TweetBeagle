
if (DEBUG_MODE) // TODO: Export system dev|prod setting.
{
	// If Firebug/Inspector are not available, replace with something, even if it isn't terribly useful
	if (typeof console == 'undefined')
	{
		// I tried adding the tag via jQuery,
		// $('head').append('<script type="text/javascript"></script>').attr('src', 'https://getfirebug.com/firebug-lite.js');
		// but that didn't work for some reason.
		// Simply appending the whole script does make it load, but it seems to do so prematurely as Firebug gets confused and fails to load.
		// This method seems to work fine, so I'm sticking to that.
		var script = document.createElement('script');
		document.getElementsByTagName('head')[0].appendChild(script);
		$(script).attr('type', "text/javascript");
		$(script).attr('src', "https://getfirebug.com/firebug-lite.js");
	}
	
	// General error handler
	window.onerror = function (errorMsg, url, lineNumber)
	{
		// TODO: Trim URL using the base URL
		//if (url.indexOf(BASE_URL) !== -1)
		//	url = url.substring(url.indexOf(BASE_URL) + BASE_URL.length - 1);
		if (typeof console != 'undefined') // Is it too soon?
			console.log('JS error: ln '+lineNumber+' of '+url+': '+errorMsg);
		else
			setTimeout(function () { window.onerror(errorMsg, url, lineNumber); }, 1000);
		return false; // return true to suppress normal browser error handling
	}
}
else
{
	console = {};
	console.log = function () {};
	console.debug = console.log;
}
