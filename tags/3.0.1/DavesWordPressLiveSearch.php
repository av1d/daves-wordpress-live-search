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
 * */
class DavesWordPressLiveSearch {
  ///////////////////
  // Initialization
  ///////////////////

  /**
   * Initialize the live search object & enqueuing scripts
   * @return void
   */
  public static function advanced_search_init() {
    load_plugin_textdomain('dwls', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    if (self::isSearchablePage()) {
      wp_enqueue_script('daves-wordpress-live-search', plugin_dir_url(__FILE__) . 'js/daves-wordpress-live-search.js', array('jquery'));
      wp_enqueue_script('excanvas', plugin_dir_url(__FILE__) . 'js/excanvas.compiled.js', 'jquery');
      wp_enqueue_script('spinners', plugin_dir_url(__FILE__) . 'js/spinners.min.js', 'explorercanvas');
      self::inlineSettings();
    }
  }

  public static function head() {

    if (self::isSearchablePage()) {
      $cssOption = get_option('daves-wordpress-live-search_css_option');

      $themeDir = get_bloginfo("stylesheet_directory");

      switch ($cssOption) {
        case 'theme':
          $style = $themeDir . '/daves-wordpress-live-search.css';
          break;
          break;
        case 'default_red':
        case 'default_blue':
        case 'default_gray':
          $style = plugin_dir_url(__FILE__) . 'css/daves-wordpress-live-search_' . $cssOption . '.css';
          break;
        case 'notheme':
        default:
          $style = false;
      }

      if ($style) {
        if (function_exists('wp_register_style') && function_exists('wp_enqueue_style')) {
          // WordPress >= 2.6
          wp_register_style('daves-wordpress-live-search', $style);
          wp_enqueue_style('daves-wordpress-live-search');
          wp_print_styles();
        } else {
          // WordPress < 2.6
          echo('<link rel="stylesheet" href="' . $style . '" type="text/css" media="screen" />');
        }
      }
    }
  }

  private static function inlineSettings() {
    global $wps_subdomains;

    $resultsDirection = stripslashes(get_option('daves-wordpress-live-search_results_direction'));
    $showThumbs = intval(("true" == get_option('daves-wordpress-live-search_thumbs')));
    $showExcerpt = intval(("true" == get_option('daves-wordpress-live-search_excerpt')));
    $showMoreResultsLink = intval(("true" == get_option('daves-wordpress-live-search_more_results', true)));
    $minCharsToSearch = intval(get_option('daves-wordpress-live-search_minchars'));
    $xOffset = intval(get_option('daves-wordpress-live-search_xoffset'));
    $yOffset = intval(get_option('daves-wordpress-live-search_yoffset'));

    // Translations
    $moreResultsText = __('View more results', 'dwls');
    $outdatedJQueryText = __("Dave's WordPress Live Search requires jQuery 1.2.6 or higher. WordPress ships with current jQuery versions. But if you are seeing this message, it's likely that another plugin is including an earlier version.", 'dwls');

    // Neat trick: use wp_localize_script to generate the config object
    // "This way, you won’t have to use PHP to print out JavaScript code,
    // which is both ugly and non-cacheable."
    // @see http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/#js-global
    wp_localize_script('daves-wordpress-live-search', 'DavesWordPressLiveSearchConfig', array(
        'resultsDirection' => $resultsDirection,
        'showThumbs' => ($showThumbs == 1) ? 'true' : 'false',
        'showExcerpt' => ($showExcerpt == 1) ? 'true' : 'false',
        'showMoreResultsLink' => ($showMoreResultsLink == 1) ? 'true' : 'false',
        'minCharsToSearch' => $minCharsToSearch,
        'xOffset' => $xOffset,
        'yOffset' => $yOffset,
        'blogURL' => get_bloginfo('url'),
        'ajaxURL' => admin_url('admin-ajax.php', is_ssl()),
        'viewMoreText' => $moreResultsText,
        'outdatedJQuery' => $outdatedJQueryText,
    ));
  }

  ///////////////
  // Admin Pages
  ///////////////

  /**
   * Include the Live Search options page in the admin menu
   * @return void
   */
  public static function admin_menu() {
    add_options_page("Dave's WordPress Live Search Options", __('Live Search', 'dwls'), 'manage_options', __FILE__, array('DavesWordPressLiveSearch', 'plugin_options'));
  }

  /**
   * Display & process the Live Search admin options
   * @return void
   */
  public static function plugin_options() {
    $tab = $_REQUEST['tab'];
    switch ($tab) {
      case 'advanced':
        return self::plugin_options_advanced();
      case 'debug':
        return self::plugin_options_debug();
      case 'settings':
      default:
        return self::plugin_options_settings();
    }
  }

  private static function plugin_options_settings() {
    $thisPluginsDirectory = dirname(__FILE__);
    $enableDebugger = (bool) get_option('daves-wordpress-live-search_debug');

    if (array_key_exists('daves-wordpress-live-search_submit', $_POST) && current_user_can('manage_options')) {
      check_admin_referer('daves-wordpress-live-search-config');

      // Read their posted value
      $maxResults = max(intval($_POST['daves-wordpress-live-search_max_results']), 0);
      $resultsDirection = $_POST['daves-wordpress-live-search_results_direction'];
      $displayPostMeta = ("true" == $_POST['daves-wordpress-live-search_display_post_meta']);
      $cssOption = $_POST['daves-wordpress-live-search_css'];
      $showThumbs = $_POST['daves-wordpress-live-search_thumbs'];
      $showExcerpt = $_POST['daves-wordpress-live-search_excerpt'];
      $excerptLength = $_POST['daves-wordpress-live-search_excerpt_length'];
      $showMoreResultsLink = $_POST['daves-wordpress-live-search_more_results'];
      $minCharsToSearch = intval($_POST['daves-wordpress-live-search_minchars']);
      $searchSource = intval($_POST['daves-wordpress-live-search_source']);

      // Save the posted value in the database
      update_option('daves-wordpress-live-search_max_results', $maxResults);
      update_option('daves-wordpress-live-search_results_direction', $resultsDirection);
      update_option('daves-wordpress-live-search_display_post_meta', (string) $displayPostMeta);
      update_option('daves-wordpress-live-search_css_option', $cssOption);
      update_option('daves-wordpress-live-search_thumbs', $showThumbs);
      update_option('daves-wordpress-live-search_excerpt', $showExcerpt);
      update_option('daves-wordpress-live-search_excerpt_length', $excerptLength);
      update_option('daves-wordpress-live-search_more_results', $showMoreResultsLink);
      update_option('daves-wordpress-live-search_minchars', $minCharsToSearch);
      update_option('daves-wordpress-live-search_source', $searchSource);

      // Translate the "Options saved" message...just in case.
      // You know...the code I was copying for this does it, thought it might be a good idea to leave it
      $updateMessage = __('Options saved.', 'dwls');

      echo "<div class=\"updated fade\"><p><strong>$updateMessage</strong></p></div>";
    } else {
      $maxResults = intval(get_option('daves-wordpress-live-search_max_results'));
      $resultsDirection = stripslashes(get_option('daves-wordpress-live-search_results_direction'));
      $displayPostMeta = (bool) get_option('daves-wordpress-live-search_display_post_meta');
      $cssOption = get_option('daves-wordpress-live-search_css_option');
      $showThumbs = (bool) get_option('daves-wordpress-live-search_thumbs');
      $showExcerpt = (bool) get_option('daves-wordpress-live-search_excerpt');
      $excerptLength = intval(get_option('daves-wordpress-live-search_excerpt_length'));
      $showMoreResultsLink = intval("true" == get_option('daves-wordpress-live-search_more_results'));
      $minCharsToSearch = intval(get_option('daves-wordpress-live-search_minchars'));
      $searchSource = intval(get_option('daves-wordpress-live-search_source'));
    }

    include("$thisPluginsDirectory/admin/daves-wordpress-live-search-admin.tpl");
  }

  private static function plugin_options_advanced() {
    $thisPluginsDirectory = dirname(__FILE__);
    if (array_key_exists('daves-wordpress-live-search_submit', $_POST) && current_user_can('manage_options')) {
      check_admin_referer('daves-wordpress-live-search-config');

      // Read their posted value
      $xOffset = intval($_POST['daves-wordpress-live-search_xoffset']);
      $yOffset = intval($_POST['daves-wordpress-live-search_yoffset']);
      $exceptions = $_POST['daves-wordpress-live-search_exceptions'];
      $cacheLifetime = $_POST['daves-wordpress-live-search_cache_lifetime'];
      if ("" == trim($cacheLifetime)) {
        $cacheLifetime = 3600;
      }
      $applyContentFilter = ("true" == $_POST['daves-wordpress-live-search_apply_content_filter']);
      $enableDebugger = ("true" == $_POST['daves-wordpress-live-search_debug']);

      if (array_key_exists('daves-wordpress-live-search_submit', $_POST) && "Clear Cache" == $_POST['daves-wordpress-live-search_submit'] && current_user_can('manage_options')) {
        // Clear the cache
        DWLSTransients::clear();
        $clearedMessage = __('Cache cleared.', 'mt_trans_domain');
        echo "<div class=\"updated fade\"><p><strong>$clearedMessage</strong></p></div>";
      }

      update_option('daves-wordpress-live-search_exceptions', $exceptions);
      update_option('daves-wordpress-live-search_xoffset', intval($xOffset));
      update_option('daves-wordpress-live-search_yoffset', intval($yOffset));
      update_option('daves-wordpress-live-search_cache_lifetime', intval($cacheLifetime));
      update_option('daves-wordpress-live-search_apply_content_filter', $applyContentFilter);
      update_option('daves-wordpress-live-search_debug', $enableDebugger);

      // Translate the "Options saved" message...just in case.
      // You know...the code I was copying for this does it, thought it might be a good idea to leave it
      $updateMessage = __('Options saved.', 'dwls');

      echo "<div class=\"updated fade\"><p><strong>$updateMessage</strong></p></div>";
    } else {
      
      $exceptions = get_option('daves-wordpress-live-search_exceptions');
      $xOffset = intval(get_option('daves-wordpress-live-search_xoffset'));
      $yOffset = intval(get_option('daves-wordpress-live-search_yoffset'));
      $cacheLifetime = get_option('daves-wordpress-live-search_cache_lifetime');
      if ("" == trim($cacheLifetime)) {
        $cacheLifetime = 3600;
      } else {
        $cacheLifetime = intval($cacheLifetime);
      }

      $applyContentFilter = (bool) get_option('daves-wordpress-live-search_apply_content_filter');
      $enableDebugger = (bool) get_option('daves-wordpress-live-search_debug');
    }

    include("$thisPluginsDirectory/admin/daves-wordpress-live-search-admin-advanced.tpl");
  }

  private static function plugin_options_debug() {
    $thisPluginsDirectory = dirname(__FILE__);
    $enableDebugger = (bool) get_option('daves-wordpress-live-search_debug');

    $debug_output = array();

    $debug_output[] = "Cache contents:";
    $cache_indexes = DWLSTransients::indexes();

    // Output the cache indexes
    foreach ($cache_indexes as $cache_index) {
      $debug_output[] = $cache_index;
    }

    $debug_output[] = "----------";

    foreach ($cache_indexes as $cache_index) {
      $debug_output[] = "{$cache_index}:";
      $hashes = get_transient($cache_index);
      foreach ($hashes as $hash) {
        $debug_output[] = $hash;
      }
    }

    $debug_output[] = "----------";

    // Output the cache contents for each index
    foreach ($cache_indexes as $cache_index) {
      $hashes = get_transient($cache_index);
      foreach ($hashes as $hash) {
        $contents = get_transient("dwls_result_{$hash}");
        $debug_output[] = "dwls_result_{$hash}:";
        $debug_output[] = var_export($contents, TRUE);
      }
    }

    $debug_output = implode("<br><br>", $debug_output);

    include("$thisPluginsDirectory/admin/daves-wordpress-live-search-admin-debug.tpl");
  }

  public static function admin_notices() {
    $cssOption = get_option('daves-wordpress-live-search_css_option');
    if ('theme' == $cssOption) {
      $themeDir = get_theme_root() . '/' . get_stylesheet();

      // Make sure there's a daves-wordpress-live-search.css file in the theme
      if (!file_exists($themeDir . "/daves-wordpress-live-search.css")) {
        $alertMessage = sprintf(__("The %sDave's WordPress Live Search%s plugin is configured to use a theme-specific CSS file, but the current theme does not contain a daves-wordpress-live-search.css file."), '<em>', '</em>');
        echo "<div class=\"updated fade\"><p><strong>$alertMessage</strong></p></div>";
      }
    }
  }

  private static function isSearchablePage() {
    if (is_admin())
      return false;

    $searchable = true;
    $exceptions = explode("\n", get_option('daves-wordpress-live-search_exceptions'));

    foreach ($exceptions as $exception) {

      $regexp = trim($exception);

      // Blank paths were slipping through. Ignore them.
      if (empty($regexp)) {
        continue;
      }

      if ('<front>' == $regexp) {
        $regexp = '';
      }

      $regexp = str_replace('?', '[?]', $regexp);
      $regexp = str_replace('|', '[|]', $regexp);

      // These checks can probably be turned into regexps themselves,
      // but it's too early in the morning to be writing regexps
      if ('*' == substr($regexp, 0, 1)) {
        $regexp = substr($regexp, 1);
      } else {
        $regexp = '^' . $regexp;
      }

      if ('*' == substr($regexp, -1)) {
        $regexp = substr($regexp, 0, -1);
      } else {
        $regexp = $regexp . '$';
      }

      $regexp = '|' . $regexp . '|';

      if (preg_match($regexp, substr($_SERVER['REQUEST_URI'], 1)) > 0) {
        return false;
      }
    }

    // Fall-through, search everything by default
    return true;
  }

  /**
   * Set some decent defaults
   */
  public static function activate() {

    add_option('daves-wordpress-live-search_max_results', 10);
    add_option('daves-wordpress-live-search_results_direction', 'down');
    add_option('daves-wordpress-live-search_display_post_meta', 'true');
    add_option('daves-wordpress-live-search_css_option', 'default_gray');
    add_option('daves-wordpress-live-search_thumbs', 'true');
    add_option('daves-wordpress-live-search_excerpt', 'true');
    add_option('daves-wordpress-live-search_excerpt_length', 100);
    add_option('daves-wordpress-live-search_more_results', 'true');
    add_option('daves-wordpress-live-search_minchars', 3);
    add_option('daves-wordpress-live-search_source', DavesWordPressLiveSearchResults::SEARCH_CONTENT);
  }

}

// Set up hooks to clear the cache when a post is
// created, deleted, or edited
$dwls_update_hooks = array(
    'delete_post',
    'edit_post',
    'save_post',
    'trash_post',
    'untrash_post',
    'update_postmeta',
    'xmlrpc_publish_post',
);
foreach ($dwls_update_hooks as $dwls_update_hook) {
  add_action($dwls_update_hook, array("DWLSTransients", "clear"));
}
