<?php
/**
 * Wordpress shortcode to visualize JuxtaLearn quiz scores.
 *
 * Usage:
 *   [quiz_score] - With `my-page/{SCORE ID}/`
 *   [quiz_score id={SCORE_ID}]
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_Shortcode_Score { #extends JuxtaLearn_Hub_Shortcode {

  public function __construct() {
    add_shortcode('quiz_score', array(&$this, 'quiz_score_shortcode'));
  }

  public function quiz_score_shortcode($attrs, $content = '', $name) {
    $jlq_score_id = $this->url_parse_score_id($attrs);

    $score = $this->model_get_score($jlq_score_id);

    $b_continue = $this->authenticate($score->createdBy);
    if (!$b_continue) {
      return;
    }
  ?>
    <ul>
    <li> Quiz title:  <?php echo $score->quiz_name ?>
    <li> Quiz completed: <?php echo $score->endDate ?>
    <li> User name:  <?php echo $score->user_name ?>
    </ul>
  <?php
    var_dump($score->stumbling_blocks);
  }


  protected function url_parse_score_id($attrs) {
    #var_dump($sc_name, $sc_content, $attrs, get_the_ID());

    $jlq_score_id = isset($attrs['id']) ? $attrs['id'] : NULL;

    $regex = '@'. basename(get_permalink()) .'\/(\d+)@';

    if (preg_match($regex, $_SERVER['REQUEST_URI'], $matches)) {
      $jlq_score_id = $matches[1];
    }
    if (!$jlq_score_id) {
      //die("Error, missing score ID.");
      $this->error_404();
    }
    return $jlq_score_id;
  }

  protected function model_get_score($score_id) {
    $model = new JuxtaLearn_Quiz_Model();
    $score = $model->get_score($score_id);
    if (!$score) {
      $this->error_404('Invalid score ID: '. $score_id);
    }
    return $score;
  }

  protected function authenticate($user_id) {
    $b_continue = TRUE;
    $current_user = wp_get_current_user();

    if ($user_id && $current_user->ID == $user_id) {
      return $b_continue;
    }
    // Admin or editor (teacher).
    if (current_user_can('edit_pages')) {
      return $b_continue;
    }
  ?>
    <script> document.documentElement.className += " jlq-error 403 "; </script>
    <p class=error >SORRY! (403) You don't have permission to access this page.</p>
    <?php 
    return ! $b_continue;
  }

  protected function error_404($reason = NULL) {
    @header("X-JuxtaLearn-Error: invalid score ID.");
    status_header(404);
    nocache_headers();

    if (!$reason) $reason = 'missing score ID.';
  ?>
    <script> document.documentElement.className += " jlq-error 404 "; </script>
    <p class=error >ERROR (404). Reason: <?php echo $reason;
    include( get_404_template() );
    exit;
  }
 
}
