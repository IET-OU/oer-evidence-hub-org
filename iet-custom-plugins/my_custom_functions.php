<?php
/*
Plugin Name: IET Custom Functions
Plugin URI:  https://gist.github.com/nfreear/9049532
Description: Search user-interface hack; admin UI simplifications for teachers/editors; print button... [JuxtaLearn]+.
Author:      Nick Freear [@IET-OU]
Author URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Version:     1.3
*/

// http://wphidedash.org/2011/04/best-practice-for-adding-custom-functions/
// http://wpmututorials.com/basics/what-is-the-mu-plugins-folder/


if (basename($_SERVER[ 'PHP_SELF' ]) == basename( __FILE__ )) {
    die( 'Sorry, but you cannot access this page directly.' );
}


define( 'IET_CUSTOM_FUNC_REGISTER_FILE',
  preg_replace( '@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace( '@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

//http://wordpress.org/support/topic/stop-wordpress-from-adding-p-tags-and-removing-line-break
//remove_filter ('the_content', 'wpautop');


class IET_Custom_Functions {

  const TEST_SERVER_REGEX = '@(test|approval|acct|dev|localhost)@';

  protected static $host;


  public function __construct() {
    self::$host = self::get_option( 'iet_custom_style_hostname', $_SERVER[ 'HTTP_HOST' ]);

    add_filter('admin_body_class', array(&$this, 'admin_body_class'));
    $this->add_action( 'admin_enqueue_scripts', 'admin_enqueue_scripts' );
    $this->add_action( 'wp_enqueue_scripts', 'front_enqueue_scripts' );

    //add_action('wp_head', array(&$this, 'head_custom_style'));
    $this->add_action( 'wp_footer', 'footer_browser_sniff' );

    if (self::is_juxtalearn()) {
      $this->add_action( 'admin_footer', 'admin_footer_javascript' );
    }

    if (self::is_test_site()) {
      add_filter( 'robots_txt', array(&$this, 'robots_txt'), 10, 2 );
    }

    add_filter('admin_body_class', array(&$this, 'body_class'));
    add_filter('body_class', array(&$this, 'body_class'));

    $this->security_remove_wp_links();
  }


  public function body_class( $classes ) {

    if (self::is_debug()) {
      if (is_array( $classes )) {
        $classes[] = 'debug';
      } else {
        $classes .= ' debug';  //'admin_body_class'
      }
    }

    if (self::is_test_site()) {
      if (is_array( $classes )) {
        $classes[] = 'test-site';
      } else {
        $classes .= ' test-site';
      }
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
    wp_enqueue_style( 'iet-custom-functions-admin', plugins_url(
      'css/custom-admin.css', IET_CUSTOM_FUNC_REGISTER_FILE
    ));
  }


  public function front_enqueue_scripts() {
    wp_enqueue_script( 'iet-custom-functions-front', plugins_url(
      'js/facetious-hack.js', IET_CUSTOM_FUNC_REGISTER_FILE
    ), array( 'jquery' ), false, $in_footer = TRUE );
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

    $this->print_button_javascript();
  }


  protected function print_button_javascript( $selector = NULL, $title='' ) {
    if (!self::is_juxtalearn()) return;

    if (!$selector) {
      $selector = self::get_option( 'iet_custom_fn_print_button_selector',
        '.entry-header, .wp-admin.post-php h2' );
    }

    $selector = json_encode( $selector );
    $title = json_encode(esc_attr( $title ));
    ?>

  <script id="iet-custom-functions-js">
  jQuery(function ($) {
    $(<?php echo $selector ?>).append(
      '<button class="jxl-print" onclick="window.print()" title=<?php
        echo $title ?>><i></i><span>Print</span></button>');
  });
  </script>
<?php
  }

  public function admin_footer_javascript() {
    $this->print_button_javascript( NULL, 'Try landscape to print "Edit" pages!' );
  }


  // Utilities.

  protected function add_action( $hook, $function, $priority = 10, $accepted_args = 1 ) {
    add_action( $hook, array( &$this, $function ), $priority, $accepted_args );
  }

  /** Get values for a named option from the options database table.
   * Uses WordPress `get_option()`. Falls back to a PHP defined() constant.
   *
   * @link https://codex.wordpress.org/Function_Reference/get_option
   */
  public static function get_option( $key, $default = NULL ) {
    $_KEY = strtoupper( $key );
    $default = /*!$default &&*/ defined( $_KEY ) ? constant( $_KEY ) : $default;
    return get_option( $key, $default );
  }

  public static function is_debug() {
    return isset($_GET[ 'debug' ]);
  }

  public static function is_test_site() {
    return preg_match( self::TEST_SERVER_REGEX, $_SERVER[ 'HTTP_HOST' ]);
  }

  public static function is_juxtalearn() {
    return 'trickytopic.juxtalearn.net' == self::$host;
  }

}
$iet_custom_functions = new IET_Custom_Functions();


#End.
