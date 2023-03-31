/**
 * Copyright (c) 2009 Dave Ross <dave@csixty4.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR 
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 **/
 
<?php

$pluginPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__FILE__)).'/';

?>

///////////////////////
// LiveSearch
///////////////////////

function LiveSearch() {}

/**
 * Initialization for the live search plugin.
 * Sets up the key handler and creates the search results list.
 */
LiveSearch.init = function() {
	
	// Add a ul for the results
	jQuery("body").append('<ul class="search_results"></ul>');
	jQuery("body").children("ul.search_results").hide();

	// Look for the search box. Exit if there isn't one.
	var searchBox = jQuery("input[name='s']");
	if(searchBox.size() === 0) {
		return false;
	}
		
	// Position the ul right under the search box	
	var searchBoxPosition = searchBox.offset();
	jQuery("body").children("ul.search_results").css('top', searchBoxPosition.top+searchBox.outerHeight()).css('left', searchBoxPosition.left);
		
	// Add the keypress handler
	// Using keyup because keypress doesn't recognize the backspace key
	// and that's kind of important.
	jQuery("input[name='s']").keyup(LiveSearch.handleKeypress);
	
	// Prevent browsers from doing autocomplete on the search field
	jQuery("input[name='s']").attr('autocomplete', 'off');
	
	// Hide the search results when the search box loses focus
	jQuery("*").click(LiveSearch.handleClicks);

};

LiveSearch.handleClicks = function(e) {
	
	var target = jQuery(this);
	
	if(this.tagName == "INPUT" && target.attr('name') == "s") {
		e.stopPropagation();
	}
	
	if(this.tagName == "UL" && target.hasClass('search_results')) {
		e.stopPropagation();
	}
			
	if(this.tagName == "HTML") {
		// If this is executing, the event has propagated all the way
		// to the <html> tag itself without being stopped,
		// so it must not have been the search box or the results that
		// were clicked.
		LiveSearch.hideResults();	
	}
};

/**
 * Process the search results that came back from the AJAX call
 */
LiveSearch.handleAJAXResults = function(e) {
	
	resultsSearchTerm = e.query_vars.s;
	if(resultsSearchTerm != jQuery("input[name='s']").val()) {
		return;
	}
	
	var searchResultsList = jQuery("ul.search_results");
	searchResultsList.empty();

	if(e.post_count === 0) {
		// Hide the search results, no results to show
		LiveSearch.hideResults();
	}
	else {
		// Show the search results
		LiveSearch.showResults();

		for(var postIndex = 0; postIndex < e.posts.length; postIndex++) {
			var searchResult = e.posts[postIndex];
			if(searchResult.post_title !== undefined) {
				searchResultsList.append('<li><a href="' + searchResult.guid + '">' + searchResult.post_title + '</a><p id="daves-wordpress-live-search_author">Posted by ' + searchResult.post_author_nicename + '</p><p id="daves-wordpress-live-search_date">' + searchResult.post_date + '</p></li>');
			}
		}
	}
	
	LiveSearch.removeIndicator();
};

/**
 * Keypress handler. Sets up a 1 sec. timeout which then
 * kicks off the actual query. The AJAX result handler checks
 * the search terms on the results and throws away anything
 * that doesn't match what's currently in the search field.
 * This way, we know that what's in the search box has been
 * there at least a second. The second delay also pretty much
 * guarantees that the key pressed has been added to the end
 * of the input box's value by the time we want to do something
 * with it.
 */
LiveSearch.handleKeypress = function(e) {
	var delayTime = 1000;
	setTimeout("LiveSearch.runQuery(" + e.which + ")", delayTime);
};

/**
 * Do the AJAX request for search results, or hide the search results
 * if the search box is empty.
 */
LiveSearch.runQuery = function(keyPressed) {
	
	//var searchResultsList = jQuery("ul.search_results");
	
	if(jQuery("input[name='s']").val() === "") {
		// Nothing entered. Hide the autocomplete.
		LiveSearch.hideResults();
		LiveSearch.removeIndicator();
	}
	else {
		// Do an autocomplete lookup
		LiveSearch.displayIndicator();
		
		// do AJAX query
		var currentSearch = jQuery("input[name='s']").val();
		
		jQuery.getJSON( "<?php print $pluginPath; ?>/daves-wordpress-live-search-ajax.php", {s: currentSearch}, LiveSearch.handleAJAXResults); 	
	}
};

LiveSearch.hideResults = function() {
	var searchResultsList = jQuery("ul.search_results");
	
	if(searchResultsList.css('display') == 'block') {
		searchResultsList.slideUp();	
	}
};

LiveSearch.showResults = function() {
	var searchResultsList = jQuery("ul.search_results");
	
	if(searchResultsList.css('display') != 'block') {
		searchResultsList.slideDown();	
	}
};

/**
 * Display the "spinning wheel" AJAX activity indicator
 */
LiveSearch.displayIndicator = function() {
	
	if(jQuery("#search_results_activity_indicator").size() === 0) {

		jQuery("body").append('<img id="search_results_activity_indicator" src="<?php print $pluginPath; ?>indicator.gif" />');
		
		var searchBox = jQuery("input[name='s']");

		var searchBoxPosition = searchBox.offset();

		jQuery("#search_results_activity_indicator").css('position', 'absolute');
		
		var indicatorY = (searchBoxPosition.top + ((searchBox.outerHeight() - searchBox.innerHeight()) / 2) + 'px');
		
		jQuery("#search_results_activity_indicator").css('top', indicatorY);

		var indicatorX = (searchBoxPosition.left + searchBox.outerWidth() - <?php $dimensions = getimagesize("indicator.gif"); print $dimensions[0]; ?> - 2) + 'px';
						
		jQuery("#search_results_activity_indicator").css('left', indicatorX);
	}
};

/**
 * Hide the "spinning wheel" AJAX activity indicator
 */
LiveSearch.removeIndicator = function() {
	jQuery("#search_results_activity_indicator").remove();
};

///////////////////
// Initialization
///////////////////

jQuery(document).ready( function() {
	LiveSearch.init();
});
