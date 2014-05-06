<?php
/*
Plugin Name: JuxtaLearn Custom Functions
Plugin URI:  https://gist.github.com/nfreear/9049532
Description: Search user-interface hack; admin UI simplifications for teachers/editors [JuxtaLearn].
Author:  Nick Freear
Author URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Version:  1.2
*/
// FILE: juxtalearn_hub/wordpress/wp-content/plugins/my-custom-functions.php

// http://wphidedash.org/2011/04/best-practice-for-adding-custom-functions/
// http://wpmututorials.com/basics/what-is-the-mu-plugins-folder/


/* Disallow direct access to the plugin file */

if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
        die('Sorry, but you cannot access this page directly.');
}

/** START ADDING CODE BELOW THIS LINE **/

define('JXL_CUSTOM_FUNC_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

//http://wordpress.org/support/topic/stop-wordpress-from-adding-p-tags-and-removing-line-break
//remove_filter ('the_content', 'wpautop');


class JxL_Custom_Functions {

  const DEV_SERVER_REGEX = '@(test|approval|acct|dev)@';


  public function __construct() {
    add_filter('admin_body_class', array(&$this, 'admin_body_class'));
    add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
    add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'));

    add_action('wp_footer', array(&$this, 'footer_browser_sniff'));

    if (preg_match(self::DEV_SERVER_REGEX, $_SERVER['HTTP_HOST'])) {
      add_filter( 'robots_txt', array(&$this, 'robots_txt'), 10, 2 );
    }

    add_filter('admin_body_class', array(&$this, 'debug_body_class'));
    add_filter('body_class', array(&$this, 'debug_body_class'));

    $this->security_remove_wp_links();
  }

  public function debug_body_class( $classes ) {
    if (!isset($_GET['debug'])) return $classes;

    if (is_array($classes)) {
      $classes[] = 'debug';
    } else {
      $classes .= ' debug';  //'admin_body_class'
    }
    return $classes;
  }

  // http://wordpress.stackexchange.com/questions/66834/how-to-target-with-css-admin-elem-
  public function admin_body_class( $classes ) {
    global $current_user;
    foreach( $current_user->roles as $role )
        $classes .= ' role-' . $role;
    return trim( $classes );
  }


  public function admin_enqueue_scripts() {
    wp_enqueue_style('my-custom-functions-admin', plugins_url(
      'css/custom-admin.css', JXL_CUSTOM_FUNC_REGISTER_FILE
    ));
  }


  public function front_enqueue_scripts() {
    wp_enqueue_script('my-custom-functions-front', plugins_url(
      'js/facetious-hack.js', JXL_CUSTOM_FUNC_REGISTER_FILE
    ), array('jquery'), false, $in_footer = TRUE);
  }


  protected function security_remove_wp_links() {
    remove_action('wp_head', 'rsd_link');  #'EditURL' /xmlrpc.php?rsd
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'wp_generator'); # <meta name="generator" content="WordPress 3.8.1">

    #? remove_action('wp_head', 'pingback_link');
  }

  /** http://trepmal.com/2011/04/03/change-the-virtual-robots-txt-file
  */
  public function robots_txt( $output, $public ) {
    $output .= 'Disallow: /' . PHP_EOL;
    return $output;
  }

  /** Some crude browser sniffing for SVG/ fullscreen API.
  */
  public function footer_browser_sniff() {
    /* ?>
<!--[if gte IE 9]>
    <script> document.documentElement.className += " ie ie9p "; </script>
<![endif]-->  */ ?>

<!--[if !IE]>-->
    <script> document.documentElement.className += " not-ie "; </script>
<!--<![endif]-->
    <script>
    document.documentElement.setAttribute('data-ua', navigator.userAgent);
    </script>
    <?php /*
    <script src="//cdn.jsdelivr.net/modernizr/2.7.1/modernizr.min.js"></script>
    <?php */
  }
}

$jxl_custom_functions = new JxL_Custom_Functions();


/** STOP ADDING CODE NOW**/

/* That's all folks! */
