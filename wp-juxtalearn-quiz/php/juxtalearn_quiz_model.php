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

  const DB_VERSION = '1.0';
  const DB_PREFIX = '_juxtalearn_quiz__';
  const HUB_TAXONOMY = 'juxtalearn_hub_sb';
  const NONCE_ACTION = 'wp-admin/admin-ajax.php';

  /**
  * Docs: https://codex.wordpress.org/Creating_Tables_with_Plugins
  * create_score_table():
  *   https://github.com/wp-plugins/slickquiz/blob/master/slickquiz.php#L234
  * activate():
  *   http://github.com/mhawksey/wp-juxtalearn-hub/blob/master/shortcodes/shortcode.php#L140
  */
  protected function create_score_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'juxtalearn_quiz_scores';

    $sql = "CREATE TABLE $table_name (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          scoreJson longtext NULL,
          score_id bigint(20) unsigned NOT NULL DEFAULT '0',
          startDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          endDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          createdDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          PRIMARY KEY  (id),
          KEY score_id_index (score_id)
          );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( self::DB_PREFIX . 'db_version', self::DB_VERSION );
  }

  /**
  * save_score()
  *   https://github.com/wp-plugins/slickquiz/blob/master/php/slickquiz-model.php#L255
  */
    protected function save_score( $data, $user_id = null ) {
        global $wpdb;
        $db_name = $wpdb->prefix . 'juxtalearn_quiz_scores';

        $set     = array();
        #$user_id = $user_id ? $user_id : get_current_user_id();
        $quiz_id = intval($data->quiz_id);
        $user_name = sanitize_text_field($data->user_name);

        $slickquiz_score = $this->get_slickquiz_score($quiz_id, $user_name);
        if (!$slickquiz_score) {
          $this->error('No SlickQuiz score (race condition?)');
        }
        if (time() - strtotime($slickquiz_score->createdDate) > 30) {
          $this->error('SlickQuiz score too old (race condition?): '
              . $slickquiz_score->createdDate);
        }

        $set['scoreJson']   = json_encode($data->responses);
        $set['score_id']    = intval($slickquiz_score->id);
        #$set['quiz_id']     = $quiz_id;
        $set['startDate']   = $this->toSQLDatetime($data->time_start);
        $set['endDate']     = $this->toSQLDatetime($data->time_end);
        $set['createdDate'] = $slickquiz_score->createdDate;

        $success = $wpdb->insert( $db_name, $set );
        if (!$success) {
          $this->error('Failed to submit score');
        }
        return array('jlq_score_id' => $wpdb->insert_id,
                'parent_score' => $slickquiz_score);
    }

    protected function toSQLDatetime( $str = NULL ) {
      $timestamp = $str ? strtotime( $str ) : time();
      return date( 'Y-m-d H:i:s', $timestamp );
    }

    /**
    * get_all_scores()
    * https://github.com/wp-plugins/slickquiz/blob/master/php/slickquiz-model.php#L47
    */
    protected function get_slickquiz_score($quiz_id, $name, $order_by = '') {
        global $wpdb;
        $db_name = $wpdb->prefix . 'plugin_slickquiz_scores';

        $order_by = $order_by ? $order_by : 'createdDate DESC';

        //Was: $wpdb->get_results();
        return $wpdb->get_row( "SELECT * FROM $db_name WHERE quiz_id = ".
           $quiz_id . " AND name = '". esc_sql($name) ."' ORDER BY $order_by" );
    }

    public function get_score($jl_score_id, $offset = 0) {
      global $wpdb;
      $db_name = $wpdb->prefix . 'juxtalearn_quiz_scores';
      $join_scores = $wpdb->prefix . 'plugin_slickquiz_scores';
      $join_quiz = $wpdb->prefix . 'plugin_slickquiz';

      $score = $wpdb->get_row( "SELECT *, $join_scores.name AS user_name,
            $join_quiz.name AS quiz_name, $db_name.id AS jl_score_id,
            $join_scores.createdBy AS user_id
          FROM $db_name
          JOIN $join_scores ON $join_scores.id = $db_name.score_id
          JOIN $join_quiz ON $join_quiz.id = $join_scores.quiz_id
          WHERE $db_name.id = ". intval($jl_score_id) );
    /* SELECT *
      FROM `wp_4_juxtalearn_quiz_scores` jqs
      JOIN wp_4_plugin_slickquiz_scores  pss ON pss.id = jqs.score_id
      JOIN wp_4_plugin_slickquiz  ps ON ps.id = pss.quiz_id;
      WHERE jqs.id = 10
      LIMIT 1;
    */
      return $this->process_score($score, $offset);
    }

    protected function process_score($score, $offset = 0) {
      if (!is_object($score)) return $score;

      $score->_scores = json_decode($score->scoreJson);
      $score->_quiz = json_decode($score->publishedJson);

      $score->tricky_topic_id = $this->get_tricky_topic($score->quiz_id);
      $score->stumbling_block_ids = $this->get_stumbling_blocks($score->quiz_id);

      $stumbles = array();
      foreach ($score->stumbling_block_ids as $sb) {
        $sb_ids = $sb->s;
        $the_question = $sb->q;

        foreach ($sb_ids as $sb_id) {
          $the_sb = $this->get_data('sb', $sb_id); # TODO / BUG ?
          $stumbles[$sb_id] = array(
            'score' => $offset, 'qs' => $the_question, 'sb' => 'TODO' #$the_sb
          );
          foreach ($score->_scores as $qs) {
            $cand_question = preg_replace('/^\d+\. /', '', $qs->q_text);
            if ($cand_question == $the_question && $qs->is_correct) {
              $stumbles[$sb_id]['score'] += 1;
            }
          }
        }
      }
      $score->stumbling_blocks = $stumbles;

      return $score;
    }

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
      case 'stumbling_block':
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

  protected function get_tricky_topic($quiz_id) {
    $quiz_tt = $this->get_data('quiz_tt');
    return isset($quiz_tt['x'. $quiz_id]) ? $quiz_tt['x' . $quiz_id] : NULL;
  }
  protected function get_stumbling_blocks($quiz_id) {
    $quiz_sb = $this->get_data('quiz_sb');
    return isset($quiz_sb['x'. $quiz_id]) ? $quiz_sb['x' . $quiz_id] : NULL;
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

  protected function form_selected($post, $quiz_tt, $quiz_id) {
    echo isset($quiz_tt['x'. $quiz_id]) &&
        $post->ID == $quiz_tt['x'. $quiz_id] ? 'selected' : '';
  }


  /* =========== JSON API ============= */

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
