<?php
/**
 * Clip-It API client library for JuxtaLearn.
 *
 * @author Nick Freear, 13 March, 2 May 2014.
 *
 * Chrome add-on:  Postman API client;
 * Chrome add-on:  XML Viewer;
 * Twitter app.:   Twitter caster;
 */
require_once 'http.php';


class JuxtaLearn_ClipIt_HTTP_Lib {

  const TIMEOUT = 1000000;

  // ClipIt API token.
  private $auth_token;
  private $messages = array();


  protected function __construct() {
    add_action('admin_notices', array(&$this, 'admin_notices'));

    add_action('wp_ajax_clipit', array(&$this, 'clipit_api_test'));
  }

  public function admin_notices() {
    foreach ($this->messages as $msg):
        ?>
    <div class="clipit-msg <?php echo $msg['type'] ?>"><p><?php echo $msg['msg'] ?></div>
    <?php
    endforeach;
  }

  //wordpress/wp-admin/admin-ajax.php?action=clipit_test&method=site.api_list
  public function clipit_api_test() {
    header( 'Content-Type: text/plain' );
    $method = isset($_GET['method']) ? $_GET['method'] : NULL;
    $result = $this->request( $method );
    echo "HTTP status: $result->http_code | $result->http_method $result->url\n";
    print_r( $result->obj );
  }

  protected function request( $method = NULL, $input = array() ) {
    $method = $method ? $method : 'site.api_list';
    if (!$this->auth_token) {
      $resp = $this->do_request( 'site.get_token', array(
        'login'   => constant( 'JXL_CLIPIT_LOGIN' ),
        'password'=> constant( 'JXL_CLIPIT_PASSWORD' ),
        'timeout' => self::TIMEOUT,
      ));

      if ($resp->success) {
        $this->auth_token = $resp->obj->result;
      }
    }
    if ('site.get_token' == $method) {
      return $resp;
    }

    return $this->do_request( $method, $input );
  }

  protected function do_request( $method, $input ) {
    $is_get = preg_match( '/\.(get_|api_list)/', $method );

    if ($this->auth_token) {
      $input[ 'auth_token' ] = $this->auth_token;
    }

    $url = sprintf(constant( 'JXL_CLIPIT_API_URL' ), 'json') .'?method=clipit.'. $method .'&';
    $payload = NULL;
    if ($is_get) {
      $url .= http_build_query( $input );
    } else {
      $payload = http_build_query( $input );
    }

    //require_once './php/http.php';

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

        $this->error( 'ClipIt API: error, '. $resp->curl_errno .', '. $url );
      }
    }
    $resp->url = $url;
    $resp->http_method = $is_get ? 'GET' : 'POST';
    return $resp;
  }


  protected function error( $text ) {
    return $this->message( $text, 'error' );
  }
  protected function message( $text, $type = 'ok' ) {
    $this->messages[] = array( 'type' => $type, 'msg' => $text );
  }
}
