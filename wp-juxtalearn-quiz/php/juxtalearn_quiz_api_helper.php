<?php
/**
 * Helper functions for internal JSON API.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_API_Helper {

  const NONCE_ACTION = 'wp-admin/admin-ajax.php';



  protected function json_response($data, $success = TRUE) {
    $data = is_string($data) ? array('msg' => $data) : $data;
    $data['stat'] = $success ? 'ok' : 'fail';
    $quiz_id = isset($data['quiz_id']) ? $data['quiz_id'] : NULL;

    if (!$success) {
      header('HTTP/1.1 400');
    }
    @header('Content-Type: application/json; charset=utf-8');
    @header('X-JuxtaLearn-Quiz-Stat: '. $data['stat']);
    @header('X-JuxtaLearn-Quiz: ajax; quiz_id='. $quiz_id);
    // PHP 5.4+, JSON_PRETTY_PRINT.
    echo json_encode($data);
    die(0);
  }

  protected function error($msg) {
    return $this->json_response($msg, false);
  }

  protected function check_post_json() {
    if (!isset($_POST['json'])) {
      $this->error('Missing {json} in POST request.');
    }
    return json_decode(stripcslashes( $_POST['json'] ));
  }

  protected function check_ajax_referer() {
    $valid_ref = check_ajax_referer(self::NONCE_ACTION, false, $die = FALSE);
    $valid_nonce = wp_verify_nonce($_REQUEST['_wpnonce'], self::NONCE_ACTION);

    if (!$valid_ref) {
      $this->error('Invalid referer nonce.');
    }
    return $valid_ref;
  }

  protected function ajax_url() {
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


