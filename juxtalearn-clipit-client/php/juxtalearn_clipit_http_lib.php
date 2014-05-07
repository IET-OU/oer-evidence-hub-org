<?php
/**
 * Clip-It API client library for JuxtaLearn.
 *
 * @author Nick Freear, 13 March, 2 May 2014.
 * @copyright 2014 The Open University.
 *
 * Chrome add-on:  Postman API client;
 * Chrome add-on:  XML Viewer;
 * Twitter app.:   Twitter caster;
 */
require_once 'http.php';


class JuxtaLearn_ClipIt_HTTP_Lib {

  const API_TIMEOUT = 1000000;

  // ClipIt API token.
  private $auth_token;
  protected $messages = array();


  public function __construct() {
    add_action('admin_notices', array(&$this, 'admin_notices'));

    add_action( 'wp_ajax_clipit_test', array(&$this, 'clipit_api_test') );
  }


  public function admin_notices() {
    foreach ($this->messages as $msg):
        ?>
    <div class="clipit-msg <?php echo $msg['type'] ?>"><p><?php echo $msg['msg'] ?></div>
    <?php
    endforeach;
  }

  /** TEST.
  * wordpress/wp-admin/admin-ajax.php?action=clipit_test&method=site.api_list
  */
  public function clipit_api_test() {
    @header( 'Content-Type: text/plain' );
    $api_method = isset($_GET['method']) ? $_GET['method'] : NULL;
    $input = array(
      'id_array' => isset($_GET['id']) && is_array($_GET['id']) ? $_GET['id'] : NULL,
      'id' =>  isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : NULL,
    );
    $result = $this->api_request( $api_method, $input );
    echo "$result->http_method $result->url \nHTTP status: $result->http_code".PHP_EOL;
    if ($result->success) {
      print_r( $result->obj );
    } else {
      echo 'ERROR: '. $result->curl_error;
    }
    die();
  }


  protected function api_request( $api_method = NULL, $input = array() ) {
    $api_method = $api_method ? $api_method : 'site.api_list';
    if (!$this->get_token()) {
      $resp = $this->do_request( 'site.get_token', array(
        'login'   => constant( 'JXL_CLIPIT_LOGIN' ),
        'password'=> constant( 'JXL_CLIPIT_PASSWORD' ),
        'timeout' => self::API_TIMEOUT,
      ));

      if ($resp->success) {
        $this->auth_token = $resp->obj->result;
      }
    }
    if ('site.get_token' == $api_method) {
      return $resp;
    }

    return $this->do_request( $api_method, $input );
  }


  protected function do_request( $api_method, $input ) {
    $is_get = preg_match( '/\.(get_|api_list)/', $api_method );

    $this->debug( 'API request: '. $api_method );

    if ($this->get_token()) {
      $input[ 'auth_token' ] = $this->get_token();
    }

    $url = sprintf(constant( 'JXL_CLIPIT_API_URL' ), 'json')
        .'?method=clipit.'. $api_method .'&';
    $payload = NULL;
    if ($is_get) {
      $url .= http_build_query( $input );
    } else {
      //$payload = http_build_query( $input );
      $payload = $input;
    }

    $http = new Http();
    $resp = $http->request( $url, $spoof = FALSE, array(
      'method' => $is_get ? 'GET' : 'POST',
      'ua' => 'Tricky Topic tool (WP/PHP/cURL) (+http://juxtalearn.net)',
      'data' => $payload,
    ));

    if ($resp->success) {
      $resp->obj = json_decode($resp->data);

      if (0 === $resp->obj->status) {
        $this->message( 'ClipIt API: OK, '. $url );
      } else {
        $resp->success = FALSE;
        $resp->curl_errno = $resp->obj->status;
        $resp->curl_error = $resp->obj->message;

        $resp->error_source = 'clipit';

        $this->error( 'ClipIt API: error '.
            $resp->curl_errno .': '. $resp->curl_error .' | '. $url );
      }
    }
    $resp->url = $url;
    $resp->http_method = $is_get ? 'GET' : 'POST';
    return $resp;
  }

  /** Utilities.
  */
  protected function get_token() {
    return $this->auth_token;
  }
  protected function error( $text ) {
    return $this->message( $text, 'error' );
  }
  protected function debug( $text ) {
    return $this->message( $text, 'debug' );
  }
  protected function message( $text, $type = 'ok' ) {
    $this->messages[] = array( 'type' => $type, 'msg' => $text );
  }
}
