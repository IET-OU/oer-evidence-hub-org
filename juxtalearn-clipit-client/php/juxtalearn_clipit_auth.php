<?php
/**
 * WordPress wrapper around JuxtaLearn Cookie Authentication.
 *
 * @copyright 2014 The Open University (IET).
 */
require_once 'juxtalearn_clipit_worker.php';
require_once 'juxtalearn-cookie-authentication/juxtalearn_cookie_authentication.php';


class JuxtaLearn_ClipIt_Auth extends JuxtaLearn_ClipIt_Worker {

  const EXCLUDE_PATH_RE = '@\/wp-login.php@';
  const AUTH_META = '_jxl_clipit_auth_method';

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


  /** WP action to add a message for users to the WP login page.
  */
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


  /** WP action to perform authentication via cookies.
  */
  public function init_authenticate() {
    if (!$this->auth) {

      try {
        $this->auth = new JuxtaLearn_Cookie_Authentication();
      } catch (Exception $ex) {
        $this->error( $ex->getMessage() );
        return;
      }

      if ($this->is_authenticated()) {
        $user = $this->get_cookie_auth_user();
        if (!$user) {
          return FALSE;
        }

        // WordPress, http://codex.wordpress.org/Function_Reference/wp_create_user
        $user_id = username_exists( $user->user_name );
        if ( !$user_id and email_exists($user->email) == false ) {
          $random_password = wp_generate_password( $length=12, $include_special_chars=false );
          $result = wp_create_user( $user->user_name, $random_password, $user->email );
          if (is_wp_error( $result )) {
            $this->error( 'ClipIt authentication: error, '. $result->get_error_message() );

            return FALSE;
          }
          // OK.
          $user_id = $result;
          $update_result = $this->update_new_user( $user_id, $user );
        } else {
          $this->message( 'ClipIt authentication: user already exists, '. $user->user_name );
        }

        $this->login_switch_user( $user_id );

      } else {
        //ERROR, maybe
        $this->debug( 'ClipIt authentication: no cookie auth. (OK, falls back to API)' );
      }
    }
  }


  protected function is_authenticated() {
    $auth_result = $this->auth->authenticate();
    $this->debug( $auth_result );
    return $this->auth->is_authenticated();
  }


  protected function update_new_user( $user_id, $user ) {
    $update_user = array(
      'ID' => $user_id,
      'display_name' => $user->display_name,
      'first_name' => $user->first_name,
      'last_name'  => $user->last_name,
    );
    if ($user->role) {
      $update_user[ 'role' ] = $user->role;
    }
    $update_result = wp_update_user( $update_user );
    $meta_res = add_user_meta($user_id, self::AUTH_META, 'ClipIt create');

    $this->message( 'ClipIt authentication: user created, '. $user->user_name );
    return $update_result;
  }


  /** Get sanitized user credentials.
   */
  protected function get_cookie_auth_user() {
    $auth = $this->auth;
    $user = (object) array(
      'user_name' => sanitize_user( $auth->get_user_login() ),
      'email' => sanitize_email($auth->get_property( 'user_mail' )),
      'role' => sanitize_text_field($auth->get_property( 'user_role' )),
      'display_name' => sanitize_text_field($auth->get_property('display_name')),
    );

    if (!$user->user_name OR !$user->email) {  //Was: OR !$user->role)
      $this->error( 'ClipIt authentication: missing or invalid sanitized field.' );
      return FALSE;
    }
    $user->role = isset(self::$roles_map[ $user->role ])
            ? self::$roles_map[ $user->role ] : NULL;
    $user->email_alt =
            $user->user_name . '+VIA+ClipIt@juxtalearn.net';
    $user->first_name = preg_replace(
            '/ .+?/', '', $user->display_name );
    $user->last_name => preg_replace(
            '/ .+/', '', $user->display_name );
    return $user;
  }

  /** Login the user.
  */
  protected function login_switch_user( $user_id ) {
    if (defined( 'JXL_CLIPIT_WP_LOGIN_USER' ) && !is_user_logged_in() && $user_id) {
      wp_clear_auth_cookie();
      wp_set_auth_cookie( $user_id );

      $this->auth_token = $this->auth->get_api_token();

      header('X-Jxl-Clipit-Auth: set-cookie; user_id=' . $user_id);
    } else {
      header('X-Jxl-Clipit-Auth: no-cookie; user_id=' . $user_id);
    }
  }

  /** TEST WP action.
  * wordpress/wp-admin/admin-ajax.php?action=clipit_cookie
  */
  public function clipit_cookie_test() {    
    $this->ajax_authenticate();

    require_once 'juxtalearn-cookie-authentication/test/test.php';
    print_r( $_COOKIE );
    exit;
  }

}
