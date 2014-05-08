<?php
/*
Plugin Name: WP JuxtaLearn Quiz
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Add scaffolding and results visualization to Slick Quiz quizzes [Requires: SlickQuiz 1.3+, WP JuxtaLearn Hub].
Author:  Nick Freear
Author URI:  https://github.com/nfreear
*/
define('JUXTALEARN_QUIZ_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

require_once 'php/juxtalearn_quiz_model.php';


class Wp_JuxtaLearn_Quiz extends JuxtaLearn_Quiz_Model {

  const LOC_DOMAIN = 'juxtalearn-quiz';


  public function __construct() {
    //TODO: check whether Slick Quiz is enabled -- is_plugin_active() ?

    require_once 'php/juxtalearn_quiz_scaffold.php';
    $editor_scaffold = new JuxtaLearn_Quiz_Scaffold();

    // Shortcodes.
    require_once 'shortcodes/shortcode.php';

    require_once 'shortcodes/quiz_score.php';
    $shortcode_score = new JuxtaLearn_Quiz_Shortcode_Score();

    require_once 'shortcodes/all_quiz_scores.php';
    $shortcode_all_scores = new JuxtaLearn_Quiz_Shortcode_All_Quiz_Scores();

    require_once 'shortcodes/slickquiz_dummy.php';
    $shortcode_sq = new JuxtaLearn_Quiz_Shortcode_SlickQuiz_Dummy();

    require_once 'shortcodes/quiz_list.php';
    $shortcode_quiz_list = new JuxtaLearn_Quiz_Shortcode_List();

    $this->plugin_name = basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );

    // Activate for New Installs
    register_activation_hook( $this->plugin_name, array( &$this, 'activate' ) );

    // Activate for Updates
    add_action( 'plugins_loaded', array( &$this, 'activate' ) );

  }


  // On Activation - Create JL Quiz database table.
  public function activate() {

    #$this->create_quiz_table();
    $this->create_scaffold_table();
    $this->create_score_table();
  }

}
$juxtalearn_quiz = new Wp_JuxtaLearn_Quiz();

