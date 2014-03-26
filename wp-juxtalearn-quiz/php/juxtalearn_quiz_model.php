<?php
/**
 * Database and API models for JuxtaLearn Quiz plugin.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

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


class JuxtaLearn_Quiz_Model {

  const DB_PREFIX = '_juxtalearn_quiz__';
  const HUB_TAXONOMY = 'juxtalearn_hub_sb';
  const NONCE_ACTION = 'wp-admin/admin-ajax.php';


// BUG: This doesn't appear to filter based on stumbling blocks?!
  protected function get_student_problems($stumbling_blocks) {
    #$sb = is_array($stumbling_blocks) ? $stumbling_blocks : array($stumbling_blocks);
    $sb = is_array($stumbling_blocks) ? intval($stumbling_blocks[ 0 ]) : $stumbling_blocks;

    $posts = get_posts(array(
      'post_type' => 'student_problem',
      #self::HUB_TAXONOMY => $sb,
      'tax_query' => array(
        'taxonomy' => self::HUB_TAXONOMY,
        #'field' => 'id', #'slug',
        'terms' => $sb,
      ),
    ));
    // Remove unnecessary fields.
    $b_ok = array_walk($posts, function ($post, $idx) {
          unset($post->post_date);
          unset($post->comment_status);
          unset($post->ping_status);
          unset($post->to_ping);
          unset($post->pinged);
          unset($post->post_modified);
          unset($post->menu_order);
          unset($post->post_mime_type);
          unset($post->post_parent);
          unset($post->post_password);
          unset($post->comment_count);
    });
    return $posts;
  }

  protected function get_data($key, $id = NULL) {
    $result = array();
    switch ($key) {
      case 'tricky_topics':
      case 'tt':
        $result = get_posts(array(
          'post_type' => 'tricky_topic',
          'post_per_page' => 10, //100,
          'orderby' => 'title',
          'order' => 'ASC',
        ));
      break;
      case 'stumbling_blocks':
      case 'sb':
        $terms = wp_get_post_terms($id, self::HUB_TAXONOMY,
          array('fields' => 'all'));
        $result = $terms;
        // Maybe, best do a deep clone.
        #$filter_terms = clone_array($terms);
        $b_ok = array_walk($result, function ($term, $idx) {
          unset($term->slug);
          unset($term->description);
          unset($term->taxonomy);
          unset($term->term_taxonomy_id);
          unset($term->term_group);
          unset($term->parent);
          unset($term->count);
        });
      break;
      case 'quiz':
        //$quiz = $this->get_last_quiz_by_user( get_current_user_id() );
        $quiz_id = isset($_GET['id']) ? intval($_GET['id']) : NULL;
        $result = (object) array('id' => $quiz_id);
      break;
      case 'quiz_tt':
        $result = get_option(self::DB_PREFIX .'tt', array());
      break;
      case 'quiz_sb':
        $result = get_option(self::DB_PREFIX .'sb', array());
      break;
      default:
        die("Unexpected 'get_data' call.");
      break;
    }
    return $result;
  }

  protected function update_data($key, $values) {
    $result = $this->get_data($key);
    $new_values = array();
    foreach ($values as $id => $value) {
      if (is_numeric($id)) {
        $new_values['x'. $id] = $value;
      } else {
        $new_values[$id] = $value;
      }
    }
    switch ($key) {
      case 'quiz_tt':
        $result = array_merge($result, $new_values);
        update_option(self::DB_PREFIX . 'tt', $result);
      break;
      case 'quiz_sb':
        $result = array_merge($result, $new_values);
        update_option(self::DB_PREFIX .'sb', $result);
      break;
      default:
        die("Unexpected 'update_data' call.");
      break;
    }
    return $result;
  }


  /* =========== JSON API ============= */

  protected function json_response($data, $success = TRUE) {
    $data = is_string($data) ? array('msg' => $data) : $data;
    $data['stat'] = $success ? 'ok' : 'fail';
    $quiz_id = isset($data['quiz_id']) ? $data['quiz_id'] : NULL;

    @header('Content-Type: application/json; charset=utf-8');
    @header('X-JuxtaLearn-Quiz-Stat: '. $data['stat']);
    @header('X-JuxtaLearn-Quiz: ajax; quiz_id='. $quiz_id);
    // PHP 5.4+, JSON_PRETTY_PRINT.
    echo json_encode($data);
    die(0);
  }

  protected function check_post_json() {
    if (!isset($_POST['json'])) {
      $this->json_response('Missing {json}.', false);
    }
    return json_decode(stripcslashes( $_POST['json'] ));
  }

  protected function check_ajax_referer() {
    $valid_ref = check_ajax_referer(self::NONCE_ACTION, false, $die = FALSE);
    $valid_nonce = wp_verify_nonce($_REQUEST['_wpnonce'], self::NONCE_ACTION);

    if (!$valid_ref) {
      $this->json_response('Invalid referer nonce.', false);
    }
  }

  protected function ajax_url() {
    return esc_url(wp_nonce_url(
        site_url('wp-admin/admin-ajax.php'), self::NONCE_ACTION)
    );
  }
}
