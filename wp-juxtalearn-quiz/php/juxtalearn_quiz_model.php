<?php
/**
 * Database and API models for JuxtaLearn Quiz plugin.
 *
 * NOTE: public methods are used in JuxtaLearn ClipIt Client plugin.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */
require_once 'juxtalearn_quiz_create_table.php';


class JuxtaLearn_Quiz_Model extends JuxtaLearn_Quiz_Create_Table  {

  const SAVE_SCORE_ACTION = 'juxtalearn_quiz_save_score';

  const HUB_SB_TAXONOMY = 'juxtalearn_hub_sb';
  const HUB_EDU_TAXONOMY = 'juxtalearn_hub_education_level';
  const HUB_CNY_TAXONOMY = 'juxtalearn_hub_country';
  const HUB_LOC_META = 'juxtalearn_hub_location_id';

  const DB_SQ_QUIZ = 'plugin_slickquiz';
  const DB_SQ_SCORES = 'plugin_slickquiz_scores';

  // Was: 'juxtalearn-quiz-score/'
  const SCORE_URL = 'quiz-score/%d/';
  const QUIZ_URL  = 'juxtalearn-quiz/%d/';
  const TT_URL    = 'trickytopic/%s/';


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
        if ($success) {
          // Bug #8,
          do_action( self::SAVE_SCORE_ACTION, (object) array(
              'score' => $data, 'score_id' => $set['score_id'] ));
        }
        else {
          $this->error('Failed to submit score');
        }

        return array('jlq_score_id' => $wpdb->insert_id,
                'sq_score_id' => $slickquiz_score->id,
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

    protected function get_slickquiz_option($option = 'save_scores') {
      $all = get_option('slick_quiz_options');
      return $option && isset($all[$option]) ? $all[$option] : $all;
    }

    public function get_quiz( $quiz_id ) {
      global $wpdb;
      $db_name = $wpdb->prefix . self::DB_SQ_QUIZ;
      $join_quiz = $wpdb->prefix . self::DB_SCAFFOLD;
      $quiz_all = $wpdb->get_row( "SELECT * FROM $db_name
          JOIN $join_quiz ON $join_quiz.quiz_id = $db_name.id
          WHERE $db_name.id =". intval($quiz_id) );
      if ($quiz_all) {
        $quiz_all->stumbling_blocks_data = json_decode( $quiz_all->stumbling_blocks );
        $quiz_all->published_data = json_decode( $quiz_all->publishedJson );
        $quiz_all->working_data = json_decode( $quiz_all->workingJson );
      }
      return $quiz_all;
    }

    public function quiz_get_scaffold( $quiz_id ) {
      global $wpdb;
      $db_name = $wpdb->prefix . self::DB_SCAFFOLD;
      $scaffold = $wpdb->get_row( "SELECT * FROM $db_name WHERE quiz_id = $quiz_id" );
      if ($scaffold) {
        $scaffold->stumbling_blocks_data = json_decode( $scaffold->stumbling_blocks );
      }
      return $scaffold;
    }

    public function get_score($sq_score_id, $offset = 0) {
      global $wpdb;
      $db_name = $wpdb->prefix . 'juxtalearn_quiz_scores';
      $join_scores = $wpdb->prefix . 'plugin_slickquiz_scores';
      $join_quiz = $wpdb->prefix . 'plugin_slickquiz';

      $score = $wpdb->get_row( "SELECT *, $join_scores.name AS user_name,
            $join_quiz.name AS quiz_name, $db_name.id AS jlq_score_id,
            $join_scores.createdBy AS score_user_id
          FROM $db_name
          JOIN $join_scores ON $join_scores.id = $db_name.score_id
          JOIN $join_quiz ON $join_quiz.id = $join_scores.quiz_id
          WHERE $join_scores.id = ". intval($sq_score_id) );
          //-- WHERE $db_name.id = ". intval($jlq_score_id)

    /* SELECT *
      FROM `wp_4_juxtalearn_quiz_scores` jqs
      JOIN wp_4_plugin_slickquiz_scores  pss ON pss.id = jqs.score_id
      JOIN wp_4_plugin_slickquiz  ps ON ps.id = pss.quiz_id;
      WHERE jqs.id = 10
      LIMIT 1;
    */
      return $this->process_score($score, $offset);
    }

    public function get_all_scores($quiz_id, $offset = 0) {
      global $wpdb;
      $db_name = $wpdb->prefix . 'juxtalearn_quiz_scores';
      $join_scores = $wpdb->prefix . 'plugin_slickquiz_scores';
      $join_quiz = $wpdb->prefix . 'plugin_slickquiz';

      $all_scores = $wpdb->get_results( "SELECT *, $join_scores.name AS user_name,
            $join_quiz.name AS quiz_name, $db_name.id AS jlq_score_id,
            $join_scores.createdBy AS user_id
          FROM $db_name
          JOIN $join_scores ON $join_scores.id = $db_name.score_id
          JOIN $join_quiz ON $join_quiz.id = $join_scores.quiz_id
          WHERE $join_quiz.id = ". intval( $quiz_id ) ."
            GROUP BY $join_scores.name
            ORDER BY $db_name.endDate ASC" );

      foreach ($all_scores as $j => $score) {
        $all_scores[$j] = $this->process_score( $score, $offset );
      }
      return $all_scores;
    }

    protected function process_score($score, $offset = 0) {
      if (!is_object($score)) return $score;

      $score->offset = $offset;
      $score->tricky_topic_id = $this->get_tricky_topic($score->quiz_id);
      if (!$score->tricky_topic_id) {
        $score->warning =
          __('not a JuxtaLearn quiz (no linked tricky topic).', self::LOC_DOMAIN);
        return $score;
      }

      $score->_scores = json_decode($score->scoreJson);
      $score->_quiz = json_decode($score->publishedJson);

      $score->stumbling_block_ids = $this->get_stumbling_blocks($score->quiz_id);

      $post = get_post($score->tricky_topic_id);
      $score->tricky_topic_title = $post->post_title;
      $score->tricky_topic_url = get_permalink($post->ID);
      $score->quiz_url = site_url(sprintf(self::QUIZ_URL, $score->quiz_id));

      $stumbles = array();
      $max_score = $offset;
      // Iterate through each question (with associated Stumbling Blocks)
      foreach ($score->stumbling_block_ids as $question_sbs) {  #Was: $sb
        $sb_ids = $question_sbs->s;
        if (0 == count($sb_ids)) {
          $score->warning =
            __('at least one question has no stumbling blocks', self::LOC_DOMAIN);
        }
        // WordPress filter - no HTML <el>.
        $the_question = wp_filter_nohtml_kses( $question_sbs->q );

        foreach ($sb_ids as $sb_id) {
          $the_sb = get_term( $sb_id, self::HUB_SB_TAXONOMY );
          // Handle multiple questions.
          if (isset($stumbles[$sb_id])) {
            $stumbles[$sb_id]['qs'][] = $the_question;
          } else {
            $stumbles[$sb_id] = array(
              'score' => $offset, 'qs' => array($the_question), 'sb_id' => $sb_id,
              'sb' => isset($the_sb->name) ? $the_sb->name : '[unknown]',
            );
          }
          // Iterate through the scores - crude question-text matching.
          foreach ($score->_scores as $qz_score) {
            $candidate_question = preg_replace('/^\d+\. /', '', $qz_score->q_text);
            if ($candidate_question == $the_question && $qz_score->is_correct) {
              $stumbles[$sb_id]['score'] += 1;
              $max_score += $stumbles[$sb_id]['score'] > $max_score ? 1 : 0;
            }
          }
        }
      }
      $score->stumbling_blocks = $stumbles;
      $score->maximum_score = $max_score;

      return $score;
    }

  protected function get_student_problems($stumbling_blocks) {
    global $wpdb;
    $sb = is_array($stumbling_blocks) ? implode(',', $stumbling_blocks) : $stumbling_blocks;

    /*// BUG: This doesn't appear to filter based on stumbling blocks?!
    $posts = get_posts(array(
      'post_type' => 'student_problem',
      #self::HUB_SB_TAXONOMY => $sb,
      'tax_query' => array(
        'taxonomy' => self::HUB_SB_TAXONOMY,
        #'field' => 'id', #'slug',
        'terms' => $sb,
      ),
    ));*/
    $fields = 'p.ID,p.post_title,p.post_name,p.post_type,p.post_content';
    $posts = $wpdb->get_results("SELECT $fields FROM `$wpdb->posts` p
      JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID
      JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
      WHERE p.post_type = 'student_problem'
      AND tt.taxonomy = 'juxtalearn_hub_sb'
      AND tt.term_id IN ( $sb )
      GROUP BY p.ID"); //Test 297; Diffusion 282;
    /*// Remove unnecessary fields.
      $b_ok = array_walk($posts, function ($post, $idx) {
          unset($post->post_date);
          unset($post->comment_status);
          //...
      });
    */
    return $posts;
  }

  protected function get_posts_meta($posts) {
    global $wpdb;
    $post_ids = array();
    foreach ($posts as $post) {
      $post_ids[] = $post->ID;
    }
    $like = 'juxtalearn_hub_%';
    return $wpdb->get_results( "SELECT * FROM $wpdb->postmeta
      WHERE post_id IN (". implode(',', $post_ids) .")
      AND meta_key LIKE '$like'
      GROUP BY meta_key" );
  }

  protected function get_data($key, $id = NULL) {
    $result = array();
    switch ($key) {
      case 'tricky_topics':
      case 'tt':
        $result = get_posts(array(
          'post_type' => 'tricky_topic',
          'posts_per_page' => -1, //100,
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
          unset($term->description);
          unset($term->taxonomy);
          unset($term->term_taxonomy_id);
          unset($term->term_group);
          unset($term->parent);
          unset($term->count);
        });
      break;
      case self::HUB_CNY_TAXONOMY:
      case 'country':
        $country = wp_get_post_terms($id, self::HUB_CNY_TAXONOMY);
        $result = (object) array(
          'id' => isset($country[0]) ? $country[0]->slug : null,
          'name' => isset($country[0]) ? $country[0]->name : null,
        );
      break;
      case self::HUB_LOC_META:
      case 'location':
        $location_id = get_post_meta($id, self::HUB_LOC_META, true);
        $location = get_post($location_id);
        $result = (object) array(
          'id' => $location ? $location->ID : null,
          'name' => $location ? $location->post_name : null,
          'title'=> $location ? $location->post_title : null,
        );
      break;
      case self::HUB_EDU_TAXONOMY:  // Not for Tricky Topics!
      case 'edu':
        $result = wp_get_post_terms($id, self::HUB_EDU_TAXONOMY);
      break;
      case 'quiz':
        //$quiz = $this->get_last_quiz_by_user( get_current_user_id() );
        $quiz_id = isset($_GET['id']) ? intval($_GET['id']) : NULL;
        $result = (object) array('id' => $quiz_id);
      break;
      // Legacy.
      case 'X_quiz_tt':
        $result = get_option(self::DB_PREFIX .'tt', array());
      break;
      case 'X_quiz_sb':
        $result = get_option(self::DB_PREFIX .'sb', array());
      break;
      default:
        die("Unexpected 'get_data' call.");
      break;
    }
    return $result;
  }

  public function get_tricky_topic($quiz_id) {
    global $wpdb;
    $db_name = $wpdb->prefix . self::DB_SCAFFOLD;
    return $wpdb->get_var( "SELECT tricky_topic_id FROM $db_name
        WHERE quiz_id = ". intval($quiz_id) );
    // Legacy.
    $quiz_tt = $this->get_data('quiz_tt');
    return isset($quiz_tt['x'. $quiz_id]) ? $quiz_tt['x' . $quiz_id] : NULL;
  }

  protected function get_stumbling_blocks($quiz_id) {
    global $wpdb;
    $db_name = $wpdb->prefix . self::DB_SCAFFOLD;
    return json_decode($wpdb->get_var( "SELECT stumbling_blocks FROM $db_name
        WHERE quiz_id = ". intval($quiz_id) ));
    // Legacy.
    $quiz_sb = $this->get_data('quiz_sb');
    return isset($quiz_sb['x'. $quiz_id]) ? $quiz_sb['x' . $quiz_id] : NULL;
  }

  protected function update_scaffold($quiz_id, $data) {
    global $wpdb;
    $db_name = $wpdb->prefix . self::DB_SCAFFOLD;
    $tt_id = $this->get_tricky_topic($quiz_id);
    if ($tt_id) {
      return $wpdb->update( $db_name, array(
        'tricky_topic_id' => $data->trickytopic_id, // No '_'
        'stumbling_blocks'=> json_encode($data->stumbling_blocks),
      ), array('quiz_id' => intval($quiz_id) ));
    }
    //ELSE insert..
    return $wpdb->insert( $db_name, array(
      'quiz_id' => $quiz_id,
      'tricky_topic_id' => $data->trickytopic_id,
      'stumbling_blocks'=> json_encode($data->stumbling_blocks),
    ) );
  }

  // Legacy.
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

  protected function form_selected($post, $tt_id) {
    echo $post->ID == $tt_id ? 'selected' : '';
  }

  /*// Legacy.
  protected function form_selected($post, $quiz_tt, $quiz_id) {
    echo isset($quiz_tt['x'. $quiz_id]) &&
        $post->ID == $quiz_tt['x'. $quiz_id] ? 'selected' : '';
  }*/

}
