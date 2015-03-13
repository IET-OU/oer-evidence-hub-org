<?php
/*
Plugin Name: IET Custom Style
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org
Description: Custom CSS for WordPress sites run by IET at The Open University [LACE]+
Author:      Nick Freear [@IET-OU]
Author URI:  https://github.com/IET-OU
Version:     0.2
*/


/** See also:
* @link https://github.com/IET-OU/oer-evidence-hub-org/blob/juxtalearn/custom-functions/my_custom_functions.php#L57
* @copyright Nick Freear, 30 September 2014.
*/
define('IET_CUSTOM_STYLE_REGISTER_FILE', preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__));


class IET_Custom_Style_Plugin {

  protected $host;

  public function __construct() {
    $this->host = self::get_option( 'iet_custom_style_hostname', $_SERVER[ 'HTTP_HOST' ]);

    if ($this->is_juxtalearn()) {
      #$this->add_action( 'admin_enqueue_scripts', .. );
      $this->add_action( 'admin_head', 'wp_head_style' );
    }
    if ($this->get_option( 'iet_custom_style_no_google_font' )) {
      $this->add_action( 'wp_enqueue_scripts', 'wp_enqueue_scripts', 20 );
    }
    $this->add_action( 'wp_head', 'wp_head_style', 999 );
    $this->add_action( 'wp_footer', 'wp_footer_javascript' );
    $this->add_action( 'init', 'init_rewrites' );
  }


  /** De-register remote Google Font styles for local devs.
  */
  public function wp_enqueue_scripts() {
    wp_deregister_style( 'tinyforge-fonts' );
    wp_deregister_style( 'open-sans' );
  }

  /** Custom CSS styles.
  */
  public function wp_head_style() {

    $css_selector = self::get_option( 'iet_custom_style_menu_special_selector',
        '.main-navigation ul > .menu-item-type-custom a[href *= clipit]' );
    $custom_css = self::get_option( 'iet_custom_style_css' );
    ?>

  <link id="iet-custom-style-link" rel="stylesheet" href="<?php echo plugins_url(
        'css/iet-custom-style.css', IET_CUSTOM_STYLE_REGISTER_FILE ) ?>" />
<?php if ($custom_css): ?>
  <style id="iet-custom-style-css">
  <?php echo $custom_css ?>
  </style>

<?php
    endif;

    $this->msie_print_shiv();
  }


  /** Javascript to add a <html> class based on the hostname.
  */
  public function wp_footer_javascript() {
    $host = json_encode( $this->host );
    ?>

  <script>
  //
  document.documentElement.className += " " + (<?php echo $host ?> || document.location.hostname).replace(/\./g, "-");
  </script>

<?php
  }

  /** Clean URLs [LACE]
  */
  public function init_rewrites() {
    add_rewrite_rule( "^(post-type|list)/([^/]+)/?",
        'index.php?post_type=$matches[2]', 'top' );
    add_rewrite_rule( "^([^/]+)/(full)?-?list/?",
	    'index.php?post_type=$matches[1]', 'top' );
  }


  /** HTML5 print shiv/shim for Internet Explorer [Bug: #31].
  */
  protected function msie_print_shiv() {
    if (!$this->is_juxtalearn()) return; ?>
<!--[if lt IE 9]>
  <script src="<?php echo plugins_url(
      'html5shiv/dist/html5shiv-printshiv.min.js',
      IET_CUSTOM_STYLE_REGISTER_FILE ) ?>"></script>
<![endif]-->

<?php
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

  protected function add_action( $hook, $function = '', $priority = 10 ) {
    add_action( $hook, array( &$this, $function ), $priority );
  }

  protected function is_juxtalearn() {
    return 'trickytopic.juxtalearn.net' == $this->host;
  }

}
$iet_custom_style = new IET_Custom_Style_Plugin();

#End.
