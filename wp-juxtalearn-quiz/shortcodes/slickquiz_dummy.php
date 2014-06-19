<?php
/**
 * A pseudo-shortcode to inject code into SlickQuiz quizzes.
 *
 * Usage:
 *   [slickquiz id=url] - With `my-page/{QUIZ ID}/`
 *   [slickquiz id={QUIZ_ID}]
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_Shortcode_SlickQuiz_Dummy extends JuxtaLearn_Quiz_Shortcode {

  const SLICKQUIZ_SC_RE = '@\[slickquiz id=(\-?\d+|url|uri)\]@';
  const QUIZ_EDIT_URL = 'admin.php?page=slickquiz-edit&id=%d';

  protected $is_quiz_view_pg = FALSE;
  protected $quiz;


  public function __construct() {

    add_filter('the_content', array(&$this, 'slickquiz_view_filter'));
    add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'));

    $AJAX_ACT = 'wp_ajax_juxtalearn_quiz_';
    add_action($AJAX_ACT . 'scores', array(&$this, 'ajax_post_scores'));
  }

  public function ajax_post_scores() {
    $this->api_init();

    $is_valid_referer = $this->check_ajax_referer();

    $data = $this->check_post_json();

    $result = $this->save_score($data);
    //Was: $result[ jlq_score_id ]
    $url = site_url(sprintf(self::SCORE_URL, $result['sq_score_id']));
    $title = __('Visualize your score', self::LOC_DOMAIN);

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

    if (preg_match(self::SLICKQUIZ_SC_RE, $body, $matches)) {
      $sc_quiz_id = $matches[1];
      $quiz_id = $this->url_parse_id(array('id' => $sc_quiz_id));

      $model = new JuxtaLearn_Quiz_Model();
      $tricky_topic_id = $model->get_tricky_topic($quiz_id);

      $save_scores = $model->get_slickquiz_option('save_scores');
      if (!$save_scores) {
        return $this->console_message('SlickQuiz option "save scores" is not set') . $body;
      }

      if (!$tricky_topic_id) {
        return $this->console_message('Not a JuxtaLearn quiz') . $body;
      }

      $this->is_quiz_view_pg = TRUE;
      $this->quiz = (object) array('id' => $quiz_id);
      //$user = wp_get_current_user();

      $json = json_encode(array(
        'ajaxurl' => $model->ajax_url(),
        'tt_id'   => $tricky_topic_id,
        'quiz_id' => $quiz_id,
        //'user_name' => $user->user_login,
        //'user_email'=> $user->user_email,
      ));

      $quiz_edit_url = admin_url(sprintf( self::QUIZ_EDIT_URL, $quiz_id ));

      $edit_link = $quiz_edit_js = FALSE;
      if ($edit_link):
        $quiz_edit_js = <<<JS
      // Add an "Edit Quiz" link if appropriate.
      jQuery(function () {
        var url = "$quiz_edit_url";
        jQuery(".entry-meta .edit-link").after(
          ' <a class=jxl-quiz-edit href="' + url + '">Edit Quiz</a>');
      });
JS;
      endif;

      $body .= <<<HTML
      <script>
      var juxtalearn_quiz = $json;

      jQuery("title").html(jQuery("title").html().replace(/Page (\d+)/, "[Quiz ID: $1]"));

      $quiz_edit_js
      </script>
HTML;
      $this->end();
    }

    return $body;
  }

  public function front_enqueue_scripts() {
    wp_enqueue_script('jxl-quiz-response', plugins_url(
      'js/juxtalearn-quiz-response.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery', 'slickquiz_js'), false, $in_footer = TRUE);
    wp_enqueue_script('jxl-analytics', plugins_url(
      'js/juxtalearn-analytics.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery'), false, $in_footer = TRUE);
  }

}
