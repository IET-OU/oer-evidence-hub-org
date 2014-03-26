<?php
/*
Plugin Name: JuxtaLearn Quiz
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Add scaffolding and results visualization to Slick Quiz quizzes [JuxtaLearn].
Author:  Nick Freear
Author URI:  https://github.com/nfreear
*/
define('JUXTALEARN_QUIZ_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

require_once 'php/juxtalearn_quiz_model.php';


class Wp_JuxtaLearn_Quiz extends JuxtaLearn_Quiz_Model {

  protected $is_quiz_view_pg = FALSE;
  protected $quiz;


  public function __construct() {
    //TODO: check whether Slick Quiz is enabled -- is_plugin_active() ?

    require_once 'php/juxtalearn_quiz_scaffold.php';
    $editor_scaffold = new JuxtaLearn_Quiz_Scaffold();


    add_filter('the_content', array(&$this, 'slickquiz_view_filter'));
    add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'));

    $AJAX_ACT = 'wp_ajax_juxtalearn_quiz_';
    add_action($AJAX_ACT . 'scores', array(&$this, 'ajax_post_scores'));
  }

  public function ajax_post_scores() {
    $this->check_ajax_referer();

    $data = $this->check_post_json();

    $this->json_response(array(
      'ref_ok' => $valid_ref,
      'nonce_ok' => $valid_nonce,
      'input' => $data,
    ));
  }

  public function slickquiz_view_filter( $body ) {

    if (preg_match('@\[slickquiz id=(\d+)\]@', $body, $matches)) {
      $quiz_id = $matches[1];

      $this->is_quiz_view_pg = TRUE;
      $this->quiz = (object) array('id' => $quiz_id);

      $body .= '<script>juxtalearn_quiz = { ajaxurl: "'. $this->ajax_url()
          .'" };</script>';  //'id: '.$quiz_id
    }
    return $body;
  }

  public function front_enqueue_scripts() {
    wp_enqueue_script('quiz-response', plugins_url(
      'js/juxtalearn-quiz-response.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery', 'slickquiz_js'), false, $in_footer = TRUE);
  }

}
$juxtalearn_quiz = new Wp_JuxtaLearn_Quiz();

