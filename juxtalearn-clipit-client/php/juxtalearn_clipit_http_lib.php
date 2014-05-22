<?php
/**
 * Clip-It API HTTP client library for JuxtaLearn.
 *
 * @author Nick Freear, 13 March, 2 May 2014.
 * @copyright 2014 The Open University (IET).
 *
 * Chrome add-on:  Postman API client;
 * Chrome add-on:  XML Viewer;
 * Twitter app.:   Twitter caster;
 */
require_once 'http.php';
require_once 'juxtalearn_clipit_model.php';


class JuxtaLearn_ClipIt_HTTP_Lib extends JuxtaLearn_ClipIt_Model {

  const LOC_DOMAIN = JuxtaLearn_ClipIt_Client::LOC_DOMAIN;
  const CLIPIT_TIMEOUT = 1000000;
  const CLIPIT_API = '/services/api/rest/json?method=clipit.%s&';
  const DEF_BASE_URL = 'JXL_CLIPIT_BASE_URL';

  // ClipIt API token.
  private $auth_token;
  protected $messages = array();
  protected $request_count = 0;


  public function __construct() {
    if (!defined( self::DEF_BASE_URL )) {
      $this->error( 'Error. Missing PHP define(): '. self::DEF_BASE_URL );
      //throw new Exception(..)
    }

    add_action('admin_notices', array(&$this, 'admin_notices'));
    add_action( 'admin_init', array(&$this, 'ajax_authenticate') );

    add_action( 'wp_ajax_clipit_test', array(&$this, 'clipit_api_test') );
  }


  public function admin_notices() {
    foreach ($this->get_messages() as $msg):
      if (!is_string($msg['msg'])) {
        continue;
      }
      $classes = $msg['type'] . ('debug' == $msg['type'] ? ' warn update-nag': ' updated');
        ?>
    <div class="message clipit-msg <?php echo $classes ?>"><p><?php echo $msg['msg'] ?></div>
    <?php
    endforeach;
  }


  public function ajax_authenticate() {
    $regex = '@admin-ajax.php.+action=clipit_@';
    if (preg_match($regex, $_SERVER['REQUEST_URI']) && !is_user_logged_in()) {
      die( "Warning, not logged in" );
    }
    @header( 'Content-Type: text/plain' );
  }

  /** TEST.
  * wordpress/wp-admin/admin-ajax.php?action=clipit_test&method=site.api_list
  */
  public function clipit_api_test() {
    $this->ajax_authenticate();

    $api_method = isset($_GET['method']) ? $_GET['method'] : NULL;
    $ids = $this->_get( 'id' );
    $input = array(
      'id_array' => is_array( $ids ) ? $ids : NULL,
      'id' =>  is_numeric( $ids ) ? $ids : NULL,
    );
    foreach ($_GET as $key => $value) {
      if (preg_match('/(id|method|token)/', $key)) continue;
      $input[ $key ] = $value;
    }
    $resp = $this->api_request( $api_method, $input );
    $resp->obj->http_code = $resp->http_code;
    $resp->obj->url = $resp->url;
    $this->debug_request_count();
    echo "$resp->http_method $resp->url \nHTTP status: $resp->http_code".PHP_EOL;
    if ($resp->success) {
      print_r( $resp->obj );
      if ($this->_get('debug')) {
        print_r( $this->get_messages() );
      }
    } else {
      echo 'ERROR: '. $resp->curl_error;
    }
    die();
  }


  /** Main method to make a request to the ClipIt API. Handles authentication.
  * @param string $api_method  Examples 'site.get_token', 'quiz.create'
  * @param array  $input
  * @return object Response, with the result in $resp->obj->result. Also, $resp->success.
  */
  protected function api_request( $api_method = NULL, $input = array() ) {
    $api_method = $api_method ? $api_method : 'site.api_list';
    if (!$this->get_token()) {
      $resp = $this->do_request( 'site.get_token', array(
        'login'   => constant( 'JXL_CLIPIT_LOGIN' ),
        'password'=> constant( 'JXL_CLIPIT_PASSWORD' ),
        'timeout' => self::CLIPIT_TIMEOUT,
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
    $this->request_count++;

    $is_get = preg_match( '/\.(get_|api_list|list_prop)/', $api_method );

    $this->debug( 'API request: '. $api_method );

    if ($this->get_token()) {
      $input[ 'auth_token' ] = $this->get_token();
    }

    $url = constant( self::DEF_BASE_URL ) . sprintf( self::CLIPIT_API, $api_method );
    //WAS: $url = sprintf(constant( 'JXL_CLIPIT_API_URL' ), 'json')
    //    .'?method=clipit.'. $api_method .'&';
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
        $this->debug( "ClipIt API: OK, $resp->http_code: $url" );

        $resp->clipit_id = is_int($resp->obj->result) ? $resp->obj->result : NULL;
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
  protected function debug_request_count() {
    return $this->debug( 'ClipIt API, request count: '. $this->request_count );
  }

  protected function message( $text, $type = 'ok' ) {
    $message_r = array( 'type' => $type, 'msg' => $text );
    $this->messages[] = $message_r;
    @header('X-Jxl-Clipit-Msg-'. count($this->messages) .': '. json_encode($message_r));
  }
  protected function get_messages() {
    return $this->messages;
  }

  protected function _get( $key, $default = NULL ) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
  }

}
