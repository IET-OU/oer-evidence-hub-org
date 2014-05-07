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
  #const RE_POST_TYPES = '/(student_problem|teaching_activity|tricky_topic)/'; //'location'?

  // Map: WordPress TTT post-type => ClipIt API.
  protected static $types_map = array(
    'student_problem'  => 'ClipitStudentProblem', //?
    'teaching_activity'=> 'ClipitActivity',
    'tricky_topic'     => 'ClipitTrickyTopic',
    'stumbling_block'  => 'ClipitTag',
    'X_slickquiz'      => 'ClipitQuiz',
    'X_learning_analytics' => 'ClipitLA',
  );


  public function __construct() {
    parent::__construct();

    add_action( 'save_post', array(&$this, 'save_post') );
    #add_action( 'slickquiz_save_quiz', array(&$this, 'ajax_save_quiz') );
    #add_action( 'wp_ajax_export_quiz', array(&$this, 'ajax_save_quiz') );
  }


  public function save_post( $post_id ) {
    $post_type = get_post_type( $post_id );

    // Is the post one of the Tricky Topic tool types? No, then return.
    #if (!preg_match( self::RE_POST_TYPES, $post_type )) return;
    if (!array_key_exists( $post_type, self::$types_map )) return;

    // WordPress post.
    $post = get_post( $post_id );
    $clipit_id = get_post_meta( $post_id, self::META_CLIPIT );

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
      'prop_value_array' => array(
        'name' => $post->post_title,
        'description' => $post->post_content,
      ),
    ));

    if ($response->success) {
      $this->debug( ">> OK, $clipit_method | $clipit_id" );

      $clipit_id = $response->obj->result;
      $meta_id = update_post_meta( $post_id, self::META_CLIPIT, $clipit_id );
    } else {
      $this->error( ">> Error, $clipit_method" );
    }
  }


}
$clipit_client = new JuxtaLearn_ClipIt_Client();
