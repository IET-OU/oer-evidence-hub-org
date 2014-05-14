<?php
/**
 * Clip-It API model for JuxtaLearn.
 *
 * @author Nick Freear, 2 May 2014.
 * @copyright 2014 The Open University.
 */

class JuxtaLearn_ClipIt_Model {


  protected function quiz_get_scaffold( $quiz_id ) {
    global $wpdb;
    $db_name = $wpdb->prefix . 'juxtalearn_quiz_scaffold';
    $scaffold = $wpdb->get_row( "SELECT * FROM $db_name WHERE quiz_id = $quiz_id" );
    if ($scaffold) {
      $scaffold->stumbling_blocks_data = json_decode( $scaffold->stumbling_blocks );
    }
    return $scaffold;
  }

  protected function quiz_set_clipit_id( $quiz_id, $clipit_id ) {
    global $wpdb;
    $db_name = $wpdb->prefix . 'juxtalearn_quiz_scaffold';
    return $wpdb->update( $db_name, array(
        'clipit_id' => intval($clipit_id)
      ), array('quiz_id' => intval($quiz_id) ));
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

