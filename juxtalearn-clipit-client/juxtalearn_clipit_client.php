<?php
/*
Plugin Name: JuxtaLearn ClipIt Client
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Push tricky topic data to the ClipIt API / Cookie authentication.
Author:      Nick Freear
Author URI:  https://github.com/nfreear
*/
define('JXL_CLIPIT_CLIENT_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

require_once 'php/juxtalearn_clipit_http_lib.php';
require_once 'lib/juxtalearn-cookie-authentication/juxtalearn_cookie_authentication.php';


class JuxtaLearn_ClipIt_Client extends JuxtaLearn_ClipIt_HTTP_Lib {

  const LOC_DOMAIN = 'juxtalearn-clipit-client';

  // Cookie authentication object.
  private $auth;


  public function __construct() {

    add_action('init', array(&$this, 'init_authenticate'));
    add_action('admin_init', array(&$this, 'init_authenticate'));

    add_action('wp_ajax_clipit_cookie', array(&$this, 'clipit_cookie_test'));
  }


  public function clipit_cookie_test() {    
    require_once 'lib/juxtalearn-cookie-authentication/test.php';
    exit;

    header( 'Content-Type: text/plain' );
    $this->auth = new JuxtaLearn_Cookie_Authentication();
    $result = $this->auth->authenticate();
    print_r( $result );
    print_r( $_COOKIE );
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

}
$clipit_client = new JuxtaLearn_ClipIt_Client();
