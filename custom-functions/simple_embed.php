<?php  #if (!defined('DB_NAME')) die('No direct script access allowed');
/*
Plugin Name: Simple Embed
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Remove the "chrome" from any Wordpress page, making it embeddable '/page?embed=1&comments=0&target=_blank' [JuxtaLearn].
Author:  Nick Freear
Author URI:  https://github.com/nfreear
Version:  1.3
*/
define('SIMPLE_EMBED_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));


class Simple_Embed {

  // http://w3.org/TR/html-markup/a.html#a.attrs.target
  const TARGET_RE = '/^(_blank|_self|_parent|_top)$/';

  protected $is_embed = FALSE;
  protected $has_comments = TRUE;
  protected $target = '_top';


  public function __construct() {

    $this->is_embed = $this->_get( 'embed' );
    if ($this->_get( 'comments' ) < 1) {
      $this->has_comments = FALSE;
    }
    if ($this->_get('target') && preg_match(self::TARGET_RE, $_GET['target'], $m)) {
      $this->target = $m[1];
    }

    if ($this->is_embed || !$this->has_comments) {
      add_filter('body_class', array(&$this, 'body_class'));
      add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }
    if ($this->is_embed) {
      add_action('wp_head', array(&$this, 'head_target'), 1);
      add_action('wp_footer', array(&$this, 'footer_script')); //, 500);

      # http://www.youtube.com/embed/vvEmahPNEcI
      header('X-Frame-Options: ALLOWALL');
      @header('X-Content-Type-Options: nosniff');
    }
  }


  /** WP action: body_class.
  */
  public function body_class( $classes ) {
    if (!$this->has_comments) {
      if (is_array($classes)) {
        // 'body_class' action.
        $classes[] = 'se-no-comments';
      } else {
        // 'admin_body_class' action.
        $classes .= ' se-no-comments';
      }
    }

    if (!$this->is_embed) return $classes;

    if (is_array($classes)) {
      $classes[] = is_user_logged_in() ? 'se-login-yes' : 'se-login-no';
    } else {
      $classes .= ' simple-embed ';
      $classes .= is_user_logged_in() ? 'se-login-yes' : 'se-login-no';
    }
    return $classes;
  }

  /** WP action. */
  public function enqueue_scripts() {
    wp_enqueue_style('simple-embed', plugins_url(
      'css/simple-embed.css', SIMPLE_EMBED_REGISTER_FILE
    ));
  }

  /** WP action. */
  public function head_target() {
    $title = ('_blank' == $this->target) ? __('Opens in new window') : '';
    ?>
    <base id="se-bt" target="<?php echo $this->target ?>" title="<?php echo $title ?>" />
  <?php
  }

  /** WP action. */
  public function footer_script() {

    // Display basic user info on button click.
    $user = wp_get_current_user();
    $info = 'Logged in as: [unknown]';
    if ($user instanceof WP_User) {
      $info = sprintf('Logged in as: %s (role: %s, via: %s)',
          $user->user_login, $this->get_user_role(), $this->get_user_auth_method());

      if (0 == $user->ID) {
        $info = __('Not logged in.');
      }
    }

    ?>
    <script> document.documentElement.className += " simple-embed"; </script>
    <script id="se-scr">
    jQuery(function ($) {
      // Set link titles when target='_blank'
      $("a:not( [title] )").attr("title", $("base").attr("title"));

      // User info widget.
      ///$(".entry-content:first").append(
      $("body").prepend(
        "<button id='se-info-btn' title='User information'><i>i</i></button>"
      )
      $("#se-info-btn").on("click", function () {
        alert("User Information:\n\n" + <?php echo json_encode( $info ) ?>);
      });
    });
    </script>
  <?php
  }


  /** Utilities.
  */
  protected function _get( $key, $default = NULL ) {
    return isset($_GET[ $key ]) ? $_GET[ $key ] : $default;
  }

  protected function get_user_role() {
	global $current_user;

	$user_roles = $current_user->roles;
	$user_role = array_shift($user_roles);
	return $user_role;
  }

  protected function get_user_auth_method() {
    global $current_user;
    $via = get_user_meta( $current_user->ID, '_jxl_clipit_auth_method', $single = true );
    return $via ? $via : '- ';
  }

}
$simple_embed = new Simple_Embed();

