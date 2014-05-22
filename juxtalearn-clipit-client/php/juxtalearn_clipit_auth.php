<?php
/**
 * WordPress wrapper around JuxtaLearn Cookie Authentication.
 *
 * @copyright 2014 The Open University (IET).
 */
require_once 'juxtalearn_clipit_http_lib.php';
require_once 'juxtalearn-cookie-authentication/juxtalearn_cookie_authentication.php';


class JuxtaLearn_ClipIt_Auth extends JuxtaLearn_ClipIt_HTTP_Lib {

  const EXCLUDE_PATH_RE = '@\/wp-login.php@';

  // Cookie authentication object.
  private $auth;

  // Map: ClipIt roles => WordPress roles.
  // wp_juxtalearn_hub.php : init() : $wp_roles
  protected static $roles_map = array(
    'student' => 'subscriber',
    'teacher' => 'editor',
    'admin'   => 'editor',
  );


  public function __construct() {
    parent::__construct();

    add_filter( 'login_message', array(&$this, 'login_message_filter') );

    if (preg_match( self::EXCLUDE_PATH_RE, $_SERVER['REQUEST_URI'] )) {
      // Allow users to login if they need to!!
      $this->debug( 'Cookie auth: exclude login/logout/register pages' );
    } else {
      add_action('init', array(&$this, 'init_authenticate'));
      add_action('admin_init', array(&$this, 'init_authenticate'));
    }

    add_action('wp_ajax_clipit_cookie', array(&$this, 'clipit_cookie_test'));
  }


  public function login_message_filter( $body ) {
    ob_start(); ?>

    <style>.login #jxl-login-msg p { font-size: 1.15em; margin: 1em 0; } </style>
    <div id=jxl-login-msg ><p>
    <?php echo sprintf(__(
    'Students and teachers should <a %s>login via ClipIt</a>. <p>System administrators, log in below.',
      self::LOC_DOMAIN ), 'href="'.
      constant( self::DEF_BASE_URL ) .'/login?url='. urlencode(site_url()) .'"'
    ) ?></div>

  <?php return ob_get_clean() . $body;
  }


  public function init_authenticate() {
    if (!$this->auth) {

      try {
        $this->auth = new JuxtaLearn_Cookie_Authentication();
      } catch (Exception $ex) {
        $this->error( $ex->getMessage() );
        return;
      }

      $result = $this->auth->authenticate();
      $this->debug( $result );

      if ($this->auth->is_authenticated()) {
        $user_name = sanitize_user($this->auth->get_user_login());
        $user_email= sanitize_email($this->auth->get_property( 'user_mail' ));
        $user_role = sanitize_text_field($this->auth->get_property( 'user_role' ));
        $user_role = isset(self::$roles_map[$user_role]) ? self::$roles_map[$user_role] : NULL;

        $_AN_email = $user_name . '+VIA+ClipIt@juxtalearn.net';

        // WordPress, http://codex.wordpress.org/Function_Reference/wp_create_user
        $user_id = username_exists( $user_name );
        if ( !$user_id and email_exists($user_email) == false ) {
          $random_password = wp_generate_password( $length=12, $include_special_chars=false );
          $result = wp_create_user( $user_name, $random_password, $user_email );
          if (is_wp_error( $result )) {
            $this->error( 'ClipIt authentication: error, '. $result->get_error_message() );

            return FALSE;
          }
          // OK.
          $user_id = $result;

          if ($user_role) {
            $up_result = wp_update_user(array( 'ID' => $user_id, 'role' => $user_role ));
          }
          $this->message( 'ClipIt authentication: user created, '. $user_name );
        } else {
          $this->message( 'ClipIt authentication: user already exists, '. $user_name );
        }

        $this->login_switch_user( $user_id );

      } else {
        //ERROR, maybe
        $this->debug( 'ClipIt authentication: no cookie auth. (OK, falls back to API)' );
      }
    }
  }


  protected function login_switch_user( $user_id ) {
    if (defined( 'JXL_CLIPIT_WP_SET_AUTH_COOKIE' )) {
      wp_clear_auth_cookie();
      wp_set_auth_cookie( $user_id );

      $this->auth_token = $this->auth->get_api_token();

      header('X-Jxl-Clipit-Auth: set-cookie; user_id=' . $user_id);
    } else {
      header('X-Jxl-Clipit-Auth: no-cookie; user_id=' . $user_id);
    }
  }

  /** TEST.
  * wordpress/wp-admin/admin-ajax.php?action=clipit_cookie
  */
  public function clipit_cookie_test() {    
    $this->ajax_authenticate();

    require_once 'juxtalearn-cookie-authentication/test/test.php';
    print_r( $_COOKIE );
    exit;
  }

}
