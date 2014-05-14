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
  const META_CLIPIT = 'juxtalearn_clipit_id';
  const STUMBLING_BLOCK_URL = 'block/%s';

  // Map: WordPress TTT post-type => ClipIt API.
  protected static $types_map = array(
    'student_problem'  => 'ClipitExample',
    'teaching_activity'=> 'ClipitSTA',  //Extends 'ClipitFile' (not 'ClipitActivity')
    'tricky_topic'     => 'ClipitTricky_Topic',
    'stumbling_block'  => 'ClipitTag',  //'ClipitStumblingBlock'
    'X_slickquiz'      => 'ClipitQuiz',
    'X_learning_analytics' => 'ClipitLA',
  );


  public function __construct() {
    parent::__construct();

    add_action( 'save_post', array(&$this, 'save_post_to_clipit') );
    add_action( 'slickquiz_save_quiz', array(&$this, 'save_quiz_to_clipit') );
    #add_action( 'wp_ajax_export_quiz', array(&$this, 'ajax_save_quiz') );

    add_action( 'wp_ajax_clipit_props', array(&$this, 'clipit_properties_test') );
  }


  public function save_quiz_to_clipit( $quiz, $sub_action = 'create_draft' ) {
    $quiz_id = $quiz->id;

    if (!$quiz->hasBeenPublished) return;

    $scaffold = $this->quiz_get_scaffold( $quiz_id );

    $clipit_id = $scaffold && $scaffold->clipit_id > 0 ? $scaffold->clipit_id : NULL;

    if (!$scaffold || !$scaffold->tricky_topic_id) return;

    $this->debug( __FUNCTION__ .'; clipit ID: '. $clipit_id );

    if ($clipit_id) {
      $clipit_method = 'quiz.set_properties';
    } else {
      $clipit_method = 'quiz.create';
    }

    $questions = $this->request_quiz_questions( $clipit_id );

    $quiz_data = json_decode( $quiz->publishedJson );

    $quiz_resp = $this->api_request( $clipit_method, array(
      'id' => $clipit_id,
      'prop_value_array' => array(
        'name' => $quiz->name,
        'description' => $quiz_data->info->main,
        'url' => NULL,
      ),
    ));

    if ($quiz_resp->success) {
      $clipit_id = $response->obj->result;
      $result = $this->quiz_set_clipit_id( $quiz_id, $clipit_id );

      /*$qq_resp = $this->api_request( 'quiz.set_quiz_questions', array(
        'id' => $clipit_id,
        'quiz_question_array' = array(),
      ));
      */

      $this->debug( "OK, $clipit_method | $clipit_id" );
    } else {
      $this->error( "Error, $clipit_method" );
    }
    $this->debug_request_count();
  }

  protected function request_quiz_questions( $clipit_id ) {
    $question_ids = $questions = array();
    if ($clipit_id) {
      $qq_resp = $this->api_request( 'quiz.get_questions', array( 'id' => $clipit_id ));
      $question_ids = $qq_resp->success ? $qq_resp->obj->result : $question_ids;

      if (count($question_ids) > 0) {
        //Was: 'quiz.question.get_by_id'
        $question_resp = $this->api_request( 'quiz_question.get_by_id', array(
          'id_array' => $question_ids
        ));
        if ($question_resp->success) {
          $question_data = $question_resp->obj->result;

          //TODO: create questions that don't exist; add tags..

        }
      }
    }
    return $questions;
  }

  /** WordPress action to create or update an object in ClipIt.
  * @param int $post_id
  */
  public function save_post_to_clipit( $post_id ) {
    $post_type = get_post_type( $post_id );

    if ('publish' != get_post_status( $post_id )) return;

    // Is the post one of the Tricky Topic tool types? No, then return.
    if (!array_key_exists( $post_type, self::$types_map )) return;

    $clipit_id = get_post_meta( $post_id, self::META_CLIPIT );

    $this->debug( __FUNCTION__ .'; clipit ID: '. $clipit_id );

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
      $clipit_id = $response->obj->result;
      $meta_id = update_post_meta( $post_id, self::META_CLIPIT, $clipit_id );

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

  /** Search for Stumbling Block tags and create those that don't exist - in ClipIt.
  * @param  array $wp_tags  Array of WP tag/ tag IDs.
  * @return array $clipit_tags  Array of ClipIt tag IDs.
  */
  protected function create_update_tags( array $wp_tags ) {
    $clipit_tags = array();
    $about_tags = array();

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
          #'url' => site_url(sprintf(self::STUMBLING_BLOCK_URL, $tag->slug )),
        ),
      ));
      if ($create_rsp->success) {
        $clipit_tags[] = $create_rsp->obj->result;
        $about_tags[] = array('tag' => $tag->name, 'action' => 'search:not-exist,create');
      }
    }

    return array( 'tags' => $clipit_tags, 'about' => $about_tags );
  }

}
$clipit_client = new JuxtaLearn_ClipIt_Client();
