<?php
/**
 * Database model for JuxtaLearn Quiz plugin.
 *
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

  const PREFIX = '_juxtalearn_quiz__';


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
        $terms = wp_get_post_terms($id, 'juxtalearn_hub_sb',
          array('fields' => 'all'));
        /*$filt = array_filter($terms, function ($term) {
        return array('id' => $term->term_id, 'name' => $term->name);
        });*/
        // Best do a deep clone.
        $result = $terms;
        #$filter_terms = clone_array($terms);
        $b_ok = array_walk($result, function ($values, $idx) {
          unset($values->slug);
          unset($values->description);
          unset($values->taxonomy);
          unset($values->term_taxonomy_id);
          unset($values->term_group);
          unset($values->parent);
        });
      break;
      case 'quiz':
        //$quiz = $this->get_last_quiz_by_user( get_current_user_id() );
        $quiz_id = isset($_GET['id']) ? intval($_GET['id']) : NULL;
        $result = (object) array('id' => $quiz_id);
      break;
      case 'quiz_tt':
        $result = get_option(self::PREFIX .'tt', array());
      break;
      case 'quiz_sb':
        $result = get_option(self::PREFIX .'sb', array());
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
        update_option(self::PREFIX . 'tt', $result);
      break;
      case 'quiz_sb':
        $result = array_merge($result, $new_values);
        update_option(self::PREFIX .'sb', $result);
      break;
      default:
        die("Unexpected 'update_data' call.");
      break;
    }
    return $result;
  }

}
