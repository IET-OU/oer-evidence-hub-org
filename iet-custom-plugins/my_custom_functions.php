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

    add_action('wp_head', array(&$this, 'head_custom_style'));
    add_action('wp_footer', array(&$this, 'footer_browser_sniff'));

    if (preg_match(self::DEV_SERVER_REGEX, $_SERVER['HTTP_HOST'])) {
      add_filter( 'robots_txt', array(&$this, 'robots_txt'), 10, 2 );
    }

    add_filter('admin_body_class', array(&$this, 'debug_body_class'));
    add_filter('body_class', array(&$this, 'debug_body_class'));

    $this->security_remove_wp_links();
  }


  public function head_custom_style() {
    $css_selector = self::get_option( 'jxl_menu_clipit_selector',
        '.main-navigation ul > .menu-item-type-custom a[href *= clipit]' );
        //'.main-navigation ul #menu-item-609 a' );
    $css_color    = self::get_option( 'jxl_menu_clipit_color', '#32b4e5' );
    $css_custom   = self::get_option( 'jxl_custom_style', '/* Option:jxl_custom_style */' );
      ?>
  <style id=jxl-custom-style >
  /* ClipIt menu link.
  */
  body .main-navigation li {
    margin: 0 1.7rem 0 0;  /*right: 2.85rem, 2rem*/
  }
  body .main-navigation li:last-child {
    margin: 0;
  }
  /* Option:jxl_menu_clipit_selector */
  body <?php echo $css_selector ?> {
    text-transform: none;  /*uppercase*/
    color: <?php echo $css_color ?>;  /* Option:jxl_menu_clipit_color */
    background: #fbfbfb;
    border: 1px solid <?php echo $css_color ?>; /*#0066cc, #32b4e5, #f7931e*/
    border-radius: 3px;
    line-height: 1.6em;
    padding: 3px 11px;
    font-weight: bold;
    font-size: 1.15em;
  }
  body <?php echo $css_selector ?>:hover {
    background: #f6f6f6;
    border-color: #fc9f00;
    color: #fc9f00;  /*orange*/
  }
  <?php echo $css_custom ?>
  </style>
    <?php
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


  // Utility.

  /**
   * Get values for a named option from the options database table.
   * Uses WordPress `get_option()`. Falls back to a PHP defined() constant.
   *
   * @link https://codex.wordpress.org/Function_Reference/get_option
   */
  public static function get_option( $key, $default = NULL ) {
    $_KEY = strtoupper( $key );
    $default = !$default && defined( $_KEY ) ? constant( $_KEY ) : $default;
    return get_option( $key, $default );
  }

}
$jxl_custom_functions = new JxL_Custom_Functions();


/** STOP ADDING CODE NOW**/

/* That's all folks! */
