<?php
/**
 * WordPress wrapper around JuxtaLearn Cookie Authentication.
 *
 * @copyright 2014 The Open University.
 */
require_once 'juxtalearn_clipit_http_lib.php';
require_once 'juxtalearn-cookie-authentication/juxtalearn_cookie_authentication.php';


class JuxtaLearn_ClipIt_Auth extends JuxtaLearn_ClipIt_HTTP_Lib {

  // Cookie authentication object.
  private $auth;


  public function __construct() {
    parent::__construct();

    add_action('init', array(&$this, 'init_authenticate'));
    add_action('admin_init', array(&$this, 'init_authenticate'));

    add_action('wp_ajax_clipit_cookie', array(&$this, 'clipit_cookie_test'));
  }


  public function init_authenticate() {
    if (!$this->auth) {

      $this->auth = new JuxtaLearn_Cookie_Authentication();

      $result = $this->auth->authenticate();

      if ($this->auth->is_authenticated()) {
        $this->auth_token = $this->auth->get_api_token();
        $user_name = $this->auth->get_user_login();

        $user_email = $user_name . '+VIA+ClipIt@juxtalearn.net';

        // WordPress.
        $user_id = username_exists( $login );
        if ( !$user_id and email_exists($user_email) == false ) {
          $random_password = wp_generate_password( $length=12, $include_special_chars=false );
          $result = wp_create_user( $user_name, $random_password, $user_email );
          if (is_wp_error( $result )) {  //is_numeric( $user_id )) {
            $this->error( 'ClipIt authentication: error, '. $result->get_error_message() );
          } else {
            // OK. TODO: Switch user?
            $this->message( 'ClipIt authentication: user created, '. $user_name );
          }
        } else {
          $this->message( 'ClipIt authentication: user already exists, '. $user_name );
        }
      } else {
        //ERROR, maybe
      }
    }
  }


  /** TEST.
  * wordpress/wp-admin/admin-ajax.php?action=clipit_cookie
  */
  public function clipit_cookie_test() {    
    $this->ajax_authenticate();

    require_once 'juxtalearn-cookie-authentication/test.php';
    print_r( $_COOKIE );
    exit;
  }

}
