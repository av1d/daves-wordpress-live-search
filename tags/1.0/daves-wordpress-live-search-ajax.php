<?php

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

include_once("../../../wp-config.php");

$wp_query = new WP_Query(array('s' => $_GET['s'], 'showposts' => 100));

// Add author names

$authorCache = array();
foreach($wp_query->posts as $index=>$post)
{
	$authorID = $post->post_author;
	if(array_key_exists($authorID, $authorCache))
	{
		$authorName = $authorCache[$authorID];
	}
	else
	{
		$authorData = get_userdata($authorID);
		$authorName = $authorData->user_nicename;
		$authorCache[$authorID] = $authorData->user_nicename;
	}
	
	$post->post_author_nicename = $authorName;
	
	unset($post->post_content);
}

$json = json_encode($wp_query);

print $json;

?>