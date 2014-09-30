<?php
/*
Plugin Name: IET Custom Style
Plugin URI:  https://github.com/IET-OU/wp-evidence-hub
Description: Custom CSS for WordPress sites run by IET at The Open University [LACE].
Author:      Nick Freear [@IET-OU]
Author URI:  https://github.com/IET-OU/
Version:     0.1
*/


/** See also:
* @link https://github.com/IET-OU/oer-evidence-hub-org/blob/juxtalearn/custom-functions/my_custom_functions.php#L57
* @copyright Nick Freear, 30 September 2014.
*/


class IET_Custom_Style_Plugin {

  public function __construct() {
    add_action( 'wp_head',   array( $this, 'wp_head_style' ));
    add_action( 'wp_footer', array( $this, 'wp_footer_javascript' ));
  }


  /** Custom CSS styles.
  */
  public function wp_head_style() {

    $css_selector = self::get_option( 'iet_custom_style_menu_special_selector',
        '.main-navigation ul > .menu-item-type-custom a[href *= clipit]' );
    ?>

  <style id="iet-custom-style-plugin">

  /* LACE menu link(s).
  */
  .evidence-laceproject-eu .main-navigation li a[href *= 'www.laceproject'],
  .evidence-laceproject-eu .main-navigation .menu-item-121 > a {
    x-display: inline-block;
    text-transform: none; /*uppercase*/
    background: #fcfcfc;
    color: #43673B;
    border: 1px solid #43672B;
    border-radius: 3px;
    line-height: 1.6em;
    padding: 3px 10px;
    font-weight: bold;
    font-size: 1.15em;
  }

  .evidence-laceproject-eu .main-navigation .menu-item-121 > a {
    border-style: dashed;
  }

  /* JuxtaLearn...
  */

  /* Custom.
  */
  <?php echo self::get_option( 'iet_custom_style_css' ) ?>
  </style>

<?php
  }


  /** Javascript to add a <html> class based on the hostname.
  */
  public function wp_footer_javascript() {
    $host = json_encode(self::get_option( 'iet_custom_style_hostname' ));
    ?>

  <script>
  document.documentElement.className += " " + (<?php echo $host ?> || document.location.hostname).replace(/\./g, "-");
  </script>

<?php
  }


  /** Get values for a named option from the options database table.
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
$iet_custom_style = new IET_Custom_Style_Plugin();

#End.
