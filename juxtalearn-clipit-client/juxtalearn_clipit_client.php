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
    #add_action( 'slickquiz_save_quiz', array(&$this, 'ajax_save_quiz') );
    #add_action( 'wp_ajax_export_quiz', array(&$this, 'ajax_save_quiz') );

    add_action( 'wp_ajax_clipit_props', array(&$this, 'clipit_properties_test') );
  }

  /** WordPress action to create or update an object in ClipIt.
  * @param int $post_id
  */
  public function save_post_to_clipit( $post_id ) {
    $post_type = get_post_type( $post_id );

    // Is the post one of the Tricky Topic tool types? No, then return.
    #if (!preg_match( self::RE_POST_TYPES, $post_type )) return;
    if (!array_key_exists( $post_type, self::$types_map )) return;

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
      'prop_value_array' => $this->get_post_properties( $post_id ),
    ));

    // OK? Save the ClipIt ID locally.
    if ($response->success) {
      $this->debug( ">> OK, $clipit_method | $clipit_id" );

      $clipit_id = $response->obj->result;
      $meta_id = update_post_meta( $post_id, self::META_CLIPIT, $clipit_id );
    } else {
      $this->error( ">> Error, $clipit_method" );
    }
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

  /** Prepare ClipIt request properties, based on WP post data.
  * @param int $post_id
  * @return array
  */
  protected function get_post_properties( $post_id ) {
    // WordPress post.
    $post = get_post( $post_id );
    $meta_data = $this->get_posts_meta( $post_id );
    $tax_terms = wp_get_object_terms( $post_id, array() );
    $stumbling_blocks = wp_get_object_terms( $post_id, array( 'juxtalearn_hub_sb' ));

    $clipit_tags = $this->create_update_tags( $stumbling_blocks );

    $properties = array(
        'name' => $post->post_title,
        'description' => $post->post_content,
        'url' => get_permalink( $post_id ),  #.'#!ttt_post_id='. $post_id,
        'tag_array' => $clipit_tags[ 'tags' ],
        #'_ttt_tags_about_' => $clipit_tags[ 'about' ],
        #'_ttt_type_' => $post->post_type,
        #'_ttt_post_id_' => $post->ID,
    );

    $problem_tax = array();
    foreach ($meta_data as $meta) {
      switch ($meta->meta_key) {
        case 'juxtalearn_hub_link':
          $properties[ 'resource_url' ] = $meta->meta_value ? $meta->meta_value : NULL;
          break;
        case 'juxtalearn_hub_trickytopic_id':
          $properties[ 'tricky_topic' ] =
              get_post_meta( $meta->meta_value, self::META_CLIPIT, $single=true );
          break;
        case 'juxtalearn_hub_location_id': //TODO: Not used.
        default:
          break;
      }
      if (preg_match('@juxtalearn_hub_((bel|esn|pre|term)\d+)@', $meta->meta_key, $m)) {
        $problem_tax[] = 'jxl_hub_' . $m[ 1 ];
      }
    }
    #$properties[ '_ttt_problem_tax_' ] = $problem_tax;

    // Process taxonomy terms, like 'subject' and country.
    foreach ($tax_terms as $tag) {
      switch ($tag->taxonomy) {
        case 'juxtalearn_hub_subject':
          $properties[ 'subject' ] = $tag->name;
        break;
        case 'juxtalearn_hub_country':
          $properties[ 'country' ] = $tag->slug;  //ISO: 'gb', etc.
          break;
        case 'juxtalearn_hub_education_level': //TODO: Not used.
          #$properties[ 'education_level' ] = $tag->name;
          break;
      }
    }

    return $properties;
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

  /**
  * Source: .../juxtalearn_quiz_model.php
  */
  protected function get_posts_meta( $post_id, $like = 'juxtalearn_hub_%' ) {
    global $wpdb;
    $post_ids = array( $post_id );

    return $wpdb->get_results( "SELECT * FROM $wpdb->postmeta
      WHERE post_id IN (". implode(',', $post_ids) .")
      AND meta_key LIKE '$like'
      GROUP BY meta_key" );
  }

}
$clipit_client = new JuxtaLearn_ClipIt_Client();
