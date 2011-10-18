/**
* JavaScript routines for Lib_Krumo
*
* @version $Id: Lib_Krumo.js 22 2007-12-02 07:38:18Z Mrasnika $
* @link http://sourceforge.net/projects/Lib_Krumo
*/

/////////////////////////////////////////////////////////////////////////////

/**
* Lib_Krumo JS Class
*/
function Lib_Krumo() {
	}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

/**
* Add a CSS class to an HTML element
*
* @param HtmlElement el 
* @param string className 
* @return void
*/
Lib_Krumo.reclass = function(el, className) {
	if (el.className.indexOf(className) < 0) {
		el.className += (' ' + className);
		}
	}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

/**
* Remove a CSS class to an HTML element
*
* @param HtmlElement el 
* @param string className 
* @return void
*/
Lib_Krumo.unclass = function(el, className) {
	if (el.className.indexOf(className) > -1) {
		el.className = el.className.replace(className, '');
		}
	}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

/**
* Toggle the nodes connected to an HTML element
*
* @param HtmlElement el 
* @return void
*/
Lib_Krumo.toggle = function(el) {
	var ul = el.parentNode.getElementsByTagName('ul');
	for (var i=0; i<ul.length; i++) {
		if (ul[i].parentNode.parentNode == el.parentNode) {
			ul[i].parentNode.style.display = (ul[i].parentNode.style.display == 'none')
				? 'block'
				: 'none';
			}
		}

	// toggle class
	//
	if (ul[0].parentNode.style.display == 'block') {
		Lib_Krumo.reclass(el, 'Lib_Krumo-opened');
		} else {
		Lib_Krumo.unclass(el, 'Lib_Krumo-opened');
		}
	}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

/**
* Hover over an HTML element
*
* @param HtmlElement el 
* @return void
*/
Lib_Krumo.over = function(el) {
	Lib_Krumo.reclass(el, 'Lib_Krumo-hover');
	}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

/**
* Hover out an HTML element
*
* @param HtmlElement el 
* @return void
*/

Lib_Krumo.out = function(el) {
	Lib_Krumo.unclass(el, 'Lib_Krumo-hover');
	}
	
/////////////////////////////////////////////////////////////////////////////