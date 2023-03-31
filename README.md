The plugin was deleted from wordpress.org as of March 27, 2023.  
This is a checkout of the SVN (https://plugins.svn.wordpress.org/daves-wordpress-live-search/)  

See also:  
https://plugins.trac.wordpress.org/browser/daves-wordpress-live-search/  
https://wordpress.org/plugins/daves-wordpress-live-search/#developers  
  
  
When installing you want the directory named "2.x" inside the branch/ directory.  
Rename it daves-wordpress-live-search then place it in your Wordpress directory like:  
example.com/wp-content/plugins/daves-wordpress-live-search  
or wherever your plugin directory is.  

Notes: if you get ""syntax error, unexpected 'new' (T_NEW)"" when installing,  
change line 335 of DavesWordPressLiveSearchResults.php from:  
`$wp =& new WP();`  
to:  
`$wp = new WP();`  
Then you should be good to go.  
  
These files have been left unmodified to maintain the integrity of the archive.  
