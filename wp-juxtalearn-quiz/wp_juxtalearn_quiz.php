<?php
/*
Plugin Name: WP JuxtaLearn Quiz
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

  // Was: 'juxtalearn-quiz-score/'
  const SCORE_URL = 'quiz-score/%d/';

  protected $is_quiz_view_pg = FALSE;
  protected $quiz;


  public function __construct() {
    //TODO: check whether Slick Quiz is enabled -- is_plugin_active() ?

    require_once 'php/juxtalearn_quiz_scaffold.php';
    $editor_scaffold = new JuxtaLearn_Quiz_Scaffold();

    // Shortcodes.
    require_once 'shortcodes/shortcode.php';

    require_once 'shortcodes/quiz_score.php';
    $shortcode_score = new JuxtaLearn_Quiz_Shortcode_Score();

    #require_once 'shortcodes/juxtalearn_quiz.php';
    #$shortcode_quiz = new JuxtaLearn_Quiz_Shortcode_Quiz();

    $this->plugin_name = basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );

    // Activate for New Installs
    register_activation_hook( $this->plugin_name, array( &$this, 'activate' ) );

    // Activate for Updates
    add_action( 'plugins_loaded', array( &$this, 'activate' ) );


    add_filter('the_content', array(&$this, 'slickquiz_view_filter'));
    add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'));

    $AJAX_ACT = 'wp_ajax_juxtalearn_quiz_';
    add_action($AJAX_ACT . 'scores', array(&$this, 'ajax_post_scores'));
  }

  public function ajax_post_scores() {
    $is_valid_referer = $this->check_ajax_referer();

    $data = $this->check_post_json();

    $result = $this->save_score($data);
    $url = site_url(sprintf(self::SCORE_URL, $result['jlq_score_id']));
    $title = 'Visualize your score';

    $this->json_response(array(
      'referer_ok' => $is_valid_referer,
      'input' => $data,
      'slickquiz_score' => $result['parent_score'],
      'jlq_score_id' => $result['jlq_score_id'],
      'url'  => $url,
      'title' => $title,
      'html' => "<p class='jlq-visualize'><a href='$url'>$title</a></p>",
    ));
  }

  public function slickquiz_view_filter( $body ) {

    if (preg_match('@\[slickquiz id=(\-?\d+|url|uri)\]@', $body, $matches)) {
      $quiz_id = $matches[1];

      $this->is_quiz_view_pg = TRUE;
      $this->quiz = (object) array('id' => $quiz_id);

      $body .= '<script>juxtalearn_quiz = { ajaxurl: "'. $this->ajax_url()
          .'" };</script>';  //'id: '.$quiz_id
      $body .= <<<HTML
      <script>
      document.documentElement.className += " shortcode-juxtalearn_quiz";
      </script>
HTML;
    }

    return $body;
  }

  public function front_enqueue_scripts() {
    wp_enqueue_script('quiz-response', plugins_url(
      'js/juxtalearn-quiz-response.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery', 'slickquiz_js'), false, $in_footer = TRUE);
  }


  // On Activation - Create JL Quiz database table.
  public function activate() {

    #$this->create_quiz_table();
    $this->create_score_table();
  }

}
$juxtalearn_quiz = new Wp_JuxtaLearn_Quiz();

