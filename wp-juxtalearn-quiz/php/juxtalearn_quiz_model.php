<?php
/**
 * Database and API models for JuxtaLearn Quiz plugin.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */
require_once 'juxtalearn_quiz_create_table.php';


class JuxtaLearn_Quiz_Model extends JuxtaLearn_Quiz_Create_Table  {

  const HUB_SB_TAXONOMY = 'juxtalearn_hub_sb';

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

      $score->offset = $offset;
      $score->_scores = json_decode($score->scoreJson);
      $score->_quiz = json_decode($score->publishedJson);

      $score->tricky_topic_id = $this->get_tricky_topic($score->quiz_id);
      $score->stumbling_block_ids = $this->get_stumbling_blocks($score->quiz_id);

      $post = get_post($score->tricky_topic_id);
      $score->tricky_topic_title = $post->post_title;
      $score->tricky_topic_url = site_url('trickytopic/'. $post->post_name);

      $stumbles = array();
      $max_score = $offset;
      foreach ($score->stumbling_block_ids as $sb) {
        $sb_ids = $sb->s;
        // TODO: are there multiple questions potentially?
        $the_question = $sb->q;

        foreach ($sb_ids as $sb_id) {
          $the_sb = get_term( $sb_id, self::HUB_SB_TAXONOMY );
          $stumbles[$sb_id] = array(
            'score' => $offset, 'qs' => $the_question, 'sb_id' => $sb_id,
            'sb' => isset($the_sb->name) ? $the_sb->name : '[unknown]',
          );
          foreach ($score->_scores as $qs) {
            $cand_question = preg_replace('/^\d+\. /', '', $qs->q_text);
            if ($cand_question == $the_question && $qs->is_correct) {
              $stumbles[$sb_id]['score'] += 1;
              $max_score += $stumbles[$sb_id]['score'] > $max ? 1 : 0;
            }
          }
        }
      }
      $score->stumbling_blocks = $stumbles;
      $score->maximum_score = $max_score;

      return $score;
    }

// BUG: This doesn't appear to filter based on stumbling blocks?!
  protected function get_student_problems($stumbling_blocks) {
    #$sb = is_array($stumbling_blocks) ? $stumbling_blocks : array($stumbling_blocks);
    $sb = is_array($stumbling_blocks) ? intval($stumbling_blocks[ 0 ]) : $stumbling_blocks;

    $posts = get_posts(array(
      'post_type' => 'student_problem',
      #self::HUB_SB_TAXONOMY => $sb,
      'tax_query' => array(
        'taxonomy' => self::HUB_SB_TAXONOMY,
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
        $terms = wp_get_post_terms($id, self::HUB_SB_TAXONOMY,
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

}
