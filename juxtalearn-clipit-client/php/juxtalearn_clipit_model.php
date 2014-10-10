<?php
/**
 * Database model for JuxtaLearn ClipIt client.
 *
 * @author Nick Freear (IET), 2 May 2014.
 * @copyright 2014 The Open University (IET).
 */

class JuxtaLearn_ClipIt_Model {

  const META_CLIPIT = 'juxtalearn_clipit_id';
  const DB_SCAFFOLD = 'juxtalearn_quiz_scaffold';
  const JXL_QUIZ_MODEL_PATH = '/../../wp-juxtalearn-quiz/php/juxtalearn_quiz_model.php';

  const QUIZ_URL  = 'juxtalearn-quiz/%d/';
  const EMBED_URL = 'juxtalearn-quiz/%d/?embed=1';
  const ALL_SCORES_URL = 'all-quiz-scores/%d/';

  // Map: WordPress TTT post-type => ClipIt API.
  protected static $types_map = array(
    'student_problem'  => 'ClipitExample',
    'teaching_activity'=> 'ClipitSTA',  //Extends 'ClipitFile' (not 'ClipitActivity')
    'tricky_topic'     => 'ClipitTricky_Topic',
    'stumbling_block'  => 'ClipitTag',  //'ClipitStumblingBlock'
    'X_slickquiz'      => 'ClipitQuiz',
    'X_learning_analytics' => 'ClipitLA',
  );


  protected function quiz_get_scaffold( $quiz_id ) {
    require_once __DIR__ . self::JXL_QUIZ_MODEL_PATH;
    $quiz_model = new JuxtaLearn_Quiz_Model();
    return $quiz_model->quiz_get_scaffold( $quiz_id );
  }

  protected function get_quiz( $quiz_id ) {
    require_once __DIR__ . self::JXL_QUIZ_MODEL_PATH;
    $quiz_model = new JuxtaLearn_Quiz_Model();
    return $quiz_model->get_quiz( $quiz_id );
  }

  protected function quiz_get_clipit_id( $quiz ) {
    return $quiz && $quiz->clipit_id > 0 ? $quiz->clipit_id : NULL;
  }

  protected function quiz_set_clipit_id( $quiz_id, $clipit_id ) {
    global $wpdb;
    $db_name = $wpdb->prefix . self::DB_SCAFFOLD;
    return $wpdb->update( $db_name, array(
        'clipit_id' => intval($clipit_id)
      ), array('quiz_id' => intval($quiz_id) ));
  }

  protected function post_get_clipit_id( $post_id ) {
    return get_post_meta( $post_id, self::META_CLIPIT, $single = TRUE );
  }

  protected function post_set_clipit_id( $post_id, $clipit_id ) {
    return update_post_meta( $post_id, self::META_CLIPIT, $clipit_id );
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
          $properties[ 'tricky_topic' ] = $this->post_get_clipit_id( $meta->meta_value );
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

  /** Get a WP configuration option from a PHP define() or the database.
  */
  protected function get_option( $option, $default = NULL ) {
    $KEY = strtoupper( $option );
    return defined( $KEY ) ? constant( $KEY ) : get_option( $option, $default );
  }
}

