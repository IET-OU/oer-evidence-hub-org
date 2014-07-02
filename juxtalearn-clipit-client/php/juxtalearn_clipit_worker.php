<?php
/**
 * API worker methods for the JuxtaLearn ClipIt client.
 *
 * @author Nick Freear (IET), 2-22 May 2014.
 * @copyright 2014 The Open University (IET).
 */
require_once 'juxtalearn_clipit_http_lib.php';


class JuxtaLearn_ClipIt_Worker extends JuxtaLearn_ClipIt_HTTP_Lib {

  const HUB_SB_TAXONOMY = 'juxtalearn_hub_sb';
  const CLIPIT_QUESTION_TYPE = 'multiple choice';

  protected function request_quiz_questions( $clipit_id, $quiz ) {
    $question_ids = $questions = array();
    if ($clipit_id) {
      $this->debug( __FUNCTION__ . "; quiz editing" );

      // Editing a quiz - some questions may exist, some won't.
      $qq_resp = $this->api_request('quiz.get_quiz_questions', array('id' => $clipit_id));
      $question_clipit_ids = $qq_resp->success ? $qq_resp->obj->result : $question_ids;

      $questions_to_create = array();
      foreach ($question_clipit_ids as $qn_clipit_id) {
        $question_resp = $this->api_request('quiz_question.get_properties', array(
          'id' => $qn_clipit_id,
          'prop_array' => array( 'id', 'description', 'name' ),
        ));
        $this->debug( $question_resp );

        if ($question_resp->success) {
          $question_text = $question_resp->obj->result->description;
          $b_found = false;
          foreach ($quiz->stumbling_block_data as $idx => $question_sbs) {
            if ($question_sbs->q == $question_text) {
              $b_found = TRUE;
              break;
            }
          }
          if (!$b_found) {
            //$questions_to_create[ $idx ] = $question_sbs;
            $questions_to_create[] = $idx;
          }
        }
      }

      $questions = $this->create_quiz_questions( $quiz, $questions_to_create );

    } else {
      $this->debug( __FUNCTION__ . "; quiz create" );

      // Creating a quiz - no questions exist.
      $questions = $this->create_quiz_questions( $quiz );
    }
    return $questions;
  }

  protected function create_quiz_questions( $quiz, $question_idxs = NULL ) {
    $questions = array();

    if (!$question_idxs) {
      $question_idxs = range(0, count( $quiz->published_data ) - 1);
    }
    $this->debug( __FUNCTION__ );
    $this->debug( $question_idxs );

    //foreach ($quiz->stumbling_blocks_data as $idx => $question_sbs) {
    foreach ($question_idxs as $idx) {
        $question_tags = $quiz->stumbling_blocks_data[ $idx ];
        $question_text = $question_tags->q;
        $qn_data = $quiz->published_data->questions[ $idx ];
        $sb_tag_ids = $question_tags->s;

        $this->debug( $question_tags );
        $this->debug( $qn_data );

        $clipit_tags = $this->create_update_tags( $sb_tag_ids );
        $clipit_tag_ids = $clipit_tags[ 'tags' ];

        $question_resp = $this->api_request('quiz_question.create', array(
          'prop_value_array' => array(
            'name' => NULL,
            'description' => $question_text,
            'option_array' => NULL,
            'option_type' => self::CLIPIT_QUESTION_TYPE,
            'quiz_result_array' => NULL,
            'tag_array' => $clipit_tag_ids,
          ) //'quiz_question.add_tags'
        ));
        if ($question_resp->success) {
          $questions[ $question_text ] = $question_resp->clipit_id;
        }
    }
    return $questions;
  }


  /** Search for Stumbling Block tags and create those that don't exist - in ClipIt.
  * @param  array $wp_tags  Array of WP tags or tag IDs.
  * @return array $clipit_tags  Array of ClipIt tag IDs.
  */
  protected function create_update_tags( $wp_tags ) {  #array
    $clipit_tags = array();
    $about_tags = array();

    $this->debug( __FUNCTION__ );
    $this->debug( $wp_tags );

    //TODO: IF required, convert WP tag IDs to tag objects.
    if (isset($wp_tags[0]) && is_numeric($wp_tags[0])) {
      $this->debug( " is_int worked?! ");

      $terms = array();
      foreach ($wp_tags as $term_id) {
        $terms[] = get_term( $term_id, self::HUB_SB_TAXONOMY );
      }
      $wp_tags = $terms;
    }

    $this->debug( "WP term objects?" );
    $this->debug( $wp_tags );

    foreach ($wp_tags as $tag) {
      $search_rsp = $this->api_request( 'tag.get_from_search', array(
        'search_string' => $tag->name,
        'name_only' => true,
      ));
      if ($search_rsp->success && count( $search_rsp->obj->result ) > 0) {
        // Our tag exists - add it to the output array...
        foreach ($search_rsp->obj->result as $cid => $result) {
          $clipit_tags[] = $result->id;
          $about_tags[] = array('tag' => $tag->name, 'result' => $result, 'action' => 'search:exist');
          break;
        }
        continue;
      }

      $create_rsp = $this->api_request( 'tag.create', array(
        'prop_value_array' => array(
          'name' => $tag->name,
          'description' => $tag->description,  #Or, $tag->name,
          'url' => get_term_link( $tag ),  #.'#!ttt_term_id='. $tag->term_id,
        ),
      ));
      if ($create_rsp->success) {
        $clipit_tags[] = $create_rsp->clipit_id; #$create_rsp->obj->result;
        $about_tags[] = array('tag' => $tag->name, 'action' => 'search:not-exist,create');
      }
    }

    return array( 'tags' => $clipit_tags, 'about' => $about_tags );
  }

}

