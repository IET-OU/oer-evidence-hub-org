<?php
/**
 * Helper functions for internal JSON API.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_API_Helper {

  const LOC_DOMAIN = Wp_JuxtaLearn_Quiz::LOC_DOMAIN;
  const NONCE_ACTION = 'wp-admin/admin-ajax.php';

  protected $note;


  protected function api_init() {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
  }

  protected function json_response($data, $success = TRUE) {
    $data = is_string($data) ? array('msg' => $data) : $data;
    $data['stat'] = $success ? 'ok' : 'fail';
    $quiz_id = isset($data['quiz_id']) ? $data['quiz_id'] : NULL;

    $data['_request'] = array(
      'request' => $_REQUEST,
      'method' => $_SERVER['REQUEST_METHOD'],
      'note' => $this->note,
    );
    if (!$success) {
      header('HTTP/1.1 400');
    }
    @header('Content-Type: application/json; charset=utf-8');
    @header('X-JuxtaLearn-Quiz-Stat: '. $data['stat']);
    @header('X-JuxtaLearn-Quiz: ajax; quiz_id='. $quiz_id);
    // PHP 5.4+, JSON_PRETTY_PRINT.
    if (isset($_GET['pretty'])) {
      echo preg_replace('/,(["\{\[])/', ",\n$1", json_encode($data));
    } else {
      echo json_encode($data);
    }
    die(0);
  }

  protected function error($msg) {
    return $this->json_response($msg, false);
  }

  protected function check_post_json() {
    if ('POST' != $_SERVER['REQUEST_METHOD']) {
      $this->note = 'Expecting a POST request';
    }
    if (!isset($_POST['json'])) {
      $this->error('Missing {json} in POST request.');
    }
    return json_decode(stripcslashes( $_POST['json'] ));
  }

  protected function check_ajax_referer() {
    $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : NULL;
    $valid_ref = check_ajax_referer(self::NONCE_ACTION, false, $die = FALSE);
    $valid_nonce = wp_verify_nonce($nonce, self::NONCE_ACTION);

    if (!$valid_ref) {
      $this->error('Invalid referer nonce.');
    }
    return $valid_ref;
  }

  public function ajax_url() {
    return esc_url(wp_nonce_url(
        site_url('wp-admin/admin-ajax.php'), self::NONCE_ACTION)
    );
  }
}


/* =========== clone_array() ============= */

//http://stackoverflow.com/questions/1532618/is-there-a-function-to-make-a-copy-of-a-php-array-to-another
function clone_array($copied_array) {
    return array_map(function($element) {
        return (
            ((is_array($element))
                ? call_user_func(__FUNCTION__, $element)
                : ((is_object($element))
                    ? clone $element
                    : $element
                )
            )
        );
    }, $copied_array);
}


