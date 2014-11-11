<?php
/*
Plugin Name: WP JuxtaLearn Quiz
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Add scaffolding and results visualization to Slick Quiz quizzes [Requires: SlickQuiz 1.3+, WP JuxtaLearn Hub].
Author:  Nick Freear [@IET-OU]
Author URI:  https://github.com/nfreear
*/

/**
* @copyright 2014 The Open University.
* @author Nick Freear, 19 March 2014.
* @package JuxtaLearn_Quiz
*/

define('JUXTALEARN_QUIZ_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

require_once 'php/juxtalearn_quiz_model.php';


class Wp_JuxtaLearn_Quiz_Plugin extends JuxtaLearn_Quiz_Model {

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

    require_once 'shortcodes/user_quiz_scores.php';
    $shortcode_user_scores = new JuxtaLearn_Quiz_Shortcode_User_Quiz_Scores();

    $this->plugin_name = basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );

    // Activate for New Installs
    register_activation_hook( $this->plugin_name, array( &$this, 'activate' ) );

    // Activate for Updates
    add_action( 'plugins_loaded', array( &$this, 'activate' ) );

    $this->add_ajax( 'redirect', 'ajax_redirect' );
  }


  // On Activation - Create JL Quiz database table.
  public function activate() {

    #$this->create_quiz_table();
    $this->create_scaffold_table();
    $this->create_score_table();
  }

#/wp-admin/admin-ajax.php?action=juxtalearn_quiz_redirect&s=user_quiz_scores&u=chem8+chem8
  public function ajax_redirect() {
    $shortcodes = array( 'all_quiz_scores', 'quiz_list', 'quiz_score', 'user_quiz_scores' );
    $shortcode = $this->_get( 's' );
    $user_name = explode( ' ', $this->_get( 'u' ));
    $url = $user = NULL;

    if (in_array( $shortcode, $shortcodes )) {

      if ('user_quiz_scores' == $shortcode) {

        $user = get_user_by( 'slug', $user_name[ 0 ] );

        if ($user) {
          $url = site_url( str_replace( '_', '-', $shortcode) . '/' . $user->ID );
          header( 'Location: ' . $url );
          exit;
        }
      }
    }
    var_dump( $shortcode, $user_name, $url, $user );
  }

}
$juxtalearn_quiz = new Wp_JuxtaLearn_Quiz_Plugin();

