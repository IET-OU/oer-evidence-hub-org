<?php
/*
Plugin Name: JuxtaLearn ClipIt Client
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Push tricky topic data to the ClipIt API / Cookie authentication.
Author:      Nick Freear
Author URI:  https://github.com/nfreear
Version:     0.1
*/
define('JXL_CLIPIT_CLIENT_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

ini_set( 'display_errors', 1 );
error_reporting( E_ALL );


require_once 'php/juxtalearn_clipit_auth.php';


class JuxtaLearn_ClipIt_Client extends JuxtaLearn_ClipIt_Auth {

  const LOC_DOMAIN = 'juxtalearn-clipit-client';

  const CLIPIT_QUESTION_TYPE = 'multiple choice';


  public function __construct() {
    parent::__construct();

    add_action( 'save_post', array(&$this, 'save_post_to_clipit') );
    add_action( 'slickquiz_save_quiz', array(&$this, 'save_quiz_to_clipit') );
    add_action( 'juxtalearn_quiz_save_score', array(&$this, 'save_score_to_clipit') );

    add_action( 'wp_ajax_clipit_props_test', array(&$this, 'clipit_properties_test') );
    add_action( 'wp_ajax_clipit_quiz_test', array(&$this, 'clipit_quiz_test') );
  }


  public function save_score_to_clipit( $data ) {
    $quiz_id = $data->score->quiz_id;
    $score_id = $data->score_id;
    $quiz_responses = $data->score->responses;

    $this->debug( __FUNCTION__ .'. TODO: incomplete!!. Quiz ID: '. $quiz_id );

    $scaffold = $this->quiz_get_scaffold( $quiz_id );

    $clipit_id = $scaffold && $scaffold->clipit_id > 0 ? $scaffold->clipit_id : NULL;

    if (!$scaffold || !$scaffold->tricky_topic_id) {
      $this->debug( 'Not pushing Score to ClipIt: no linked Tricky Topic. Quiz ID: '. $quiz_id );
      return;
    }
    /* -- TODO: -- */
  }


  public function save_quiz_to_clipit( $quiz, $sub_action = 'create_draft' ) {
    $quiz_id = is_object( $quiz ) ? $quiz->id : intval( $quiz );

    // Get the most up-to-date Quiz data.
    #$scaffold = $this->quiz_get_scaffold( $quiz_id );
    $quiz = $this->get_quiz( $quiz_id );

    if (!$quiz->hasBeenPublished) {
      $this->debug( 'Not pushing Quiz to ClipIt: not published. Quiz ID: '. $quiz_id );
      return;
    }

    $clipit_id = $quiz && $quiz->clipit_id > 0 ? $quiz->clipit_id : NULL;
    if (!$quiz || !$quiz->tricky_topic_id) {
      $this->debug( 'Not pushing Quiz to ClipIt: no linked Tricky Topic. Quiz ID: '. $quiz_id );
      return;
    }
    $this->debug( __FUNCTION__ .". Sub-action: $sub_action. Clipit ID: $clipit_id. Quiz ID: $quiz_id" );

    if ($clipit_id) {
      $clipit_method = 'quiz.set_properties';
    } else {
      $clipit_method = 'quiz.create';
    }

    $questions = $this->request_quiz_questions( $clipit_id, $quiz );

    $quiz_data = $quiz->published_data;

    $quiz_resp = $this->api_request( $clipit_method, array(
      'id' => $clipit_id,
      'prop_value_array' => array(
        'name' => $quiz->name,
        'description' => $quiz_data->info->main,
        'quiz_question_array' => $questions,
        'public' => TRUE,   //?
        'tricky_topic' => NULL,
        'url' => site_url(sprintf( self::QUIZ_URL, $quiz_id )),
        'embed_url' => site_url(sprintf( self::EMBED_URL, $quiz_id )),
        'scores_url'=> site_url(sprintf( self::ALL_SCORES_URL, $quiz_id )),
      ),
    ));

    if ($quiz_resp->success) {
      $clipit_id = $quiz_resp->obj->result;
      $result = $this->quiz_set_clipit_id( $quiz_id, $clipit_id );

      /*$qq_resp = $this->api_request( 'quiz.set_quiz_questions', array(
        'id' => $clipit_id,
        'quiz_question_array' = array(),
      ));
      */

      $this->debug( "OK, $clipit_method | $clipit_id | $result" );
    } else {
      $this->error( "Error, $clipit_method" );
    }
    $this->debug_request_count();
  }


  protected function request_quiz_questions( $clipit_id, $quiz ) {
    $question_ids = $questions = array();
    if ($clipit_id) {
      // Editing a quiz - some questions may exist, some won't.
      $qq_resp = $this->api_request('quiz.get_quiz_questions', array('id' => $clipit_id));
      $question_clipit_ids = $qq_resp->success ? $qq_resp->obj->result : $question_ids;

      $questions_to_create = array();
      foreach ($question_clipit_ids as $qn_clipit_id) {
        $question_resp = $this->api_request('quiz_question.get_properties', array(
          'id' => $qn_clipit_id,
          'prop_array' => array( 'id', 'description', 'name' ),
        ));
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

      //TODO: ...

      /*if (count($question_ids) > 0) {
        //Was: 'quiz.question.get_by_id'
        $questions_resp = $this->api_request( 'quiz_question.get_by_id', array(
          'id_array' => $question_ids
        ));
        if ($questions_resp->success) {
          foreach ($questions_resp->obj->result as $clipit_question) {
            //TODO: iterate, create questions that don't exist; add tags..
            $b_found = FALSE;
            foreach ($quiz->stumbling_block_data as $question_sbs) {
              if ($question_sbs->q == $clipit_question->description) {
                $b_found = TRUE;
                // TODO: Test - a 'clipit ID'?!
                $questions[ $question_sbs->q ] = $clipit_question->id;
                break;
              }
            }
            if (!$b_found) {  }
          }
        /-/}
      }*/
    } else {
      // Creating a quiz - no questions exist.
      foreach ($quiz->stumbling_blocks_data as $idx => $question_sbs) {
        //$qz_data = $quiz->published_data[ $idx ];
        $question_text = $question_sbs->q;
        $sb_tag_ids = $question_sbs->s;
        $clipit_tags = $this->create_update_tags( $sb_tag_ids );

        $clipit_tag_ids = array_values($clipit_tags['tags']);

        $question_resp = $this->api_request('quiz_question.create', array(
          'prop_value_array' => array(
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
    }
    return $questions;
  }

  protected function create_quiz_questions( $questions_tags, $questions ) {
  
  }

  /** WordPress action to create or update an object in ClipIt.
  * @param int $post_id
  */
  public function save_post_to_clipit( $post_id ) {
    $post_type = get_post_type( $post_id );

    if ('publish' != get_post_status( $post_id )) {
      $this->debug( 'Not pushing post to ClipIt: not published. Post ID: '. $post_id );
      return;
    }

    // Is the post one of the Tricky Topic tool types? No, then return.
    if (!array_key_exists( $post_type, self::$types_map )) {
      $this->debug( 'Not pushing Post to ClipIt: not a JxL object. Post ID: '. $post_id );
      return;
    }

    $clipit_id = $this->post_get_clipit_id( $post_id );

    $this->debug( __FUNCTION__ .'. Clipit ID: '. $clipit_id );

    $clipit_type = strtolower(str_replace('Clipit', '', self::$types_map[ $post_type ]));
    $clipit_method = $clipit_type .'.';

    if ($clipit_id) {
      $clipit_method .= 'set_properties';
    } else {
      $clipit_method .= 'create';
    }

    // Make the initial API call.
    $response = $this->api_request( $clipit_method, array(
      'id' => $clipit_id,
      'prop_value_array' => $this->get_post_properties( $post_id ),
    ));

    // OK? Save the ClipIt ID locally.
    if ($response->success) {
      #$clipit_id = $response->obj->result;
      $meta_id = $this->post_set_clipit_id( $post_id, $response->clipit_id );

      $this->debug( "OK, $response->http_code: $clipit_method | $clipit_id" );
    } else {
      $this->error( "Error, $response->http_code: $clipit_method" );
    }
    $this->debug_request_count();
  }

  /** TEST.
  */
  public function clipit_properties_test() {
    $this->ajax_authenticate();

    $post_id = intval($this->_get( 'post_id', 84 )); //84: teaching actity.
    if (!$post_id) {
      die( "ERROR, invalid {post_id}, $post_id" );
    }
    $props = $this->get_post_properties( $post_id );
    var_dump( $props );
  }

  public function clipit_quiz_test() {
    $this->ajax_authenticate();

    $quiz_id = intval($this->_get( 'id', 2 ));
    //$result = $this->get_quiz( $quiz_id );
    $result = $this->save_quiz_to_clipit( $quiz_id );
    print_r( $result );
    print_r( $this->get_messages() );
  }


  /** Search for Stumbling Block tags and create those that don't exist - in ClipIt.
  * @param  array $wp_tags  Array of WP tags or tag IDs.
  * @return array $clipit_tags  Array of ClipIt tag IDs.
  */
  protected function create_update_tags( array $wp_tags ) {
    $clipit_tags = array();
    $about_tags = array();

    //TODO: IF required convert WP tag IDs to tag objects.

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
          'description' => $tag->description,
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
$clipit_client = new JuxtaLearn_ClipIt_Client();
