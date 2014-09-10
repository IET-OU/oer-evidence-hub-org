<?php
/**
 * Wordpress shortcode to visualize all scores for a JuxtaLearn Quiz.
 *
 * Usage:
 *   [all_quiz_scores] - With `my-page/{QUIZ ID}/`
 *   [all_quiz_scores id={QUIZ_ID}]
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_Shortcode_All_Quiz_Scores extends JuxtaLearn_Quiz_Shortcode_Score {

  const SHORTCODE = 'all_quiz_scores';

  public function __construct() {
    add_shortcode(self::SHORTCODE, array(&$this, 'all_scores_shortcode'));
  }


  public function all_scores_shortcode($attrs, $content = '', $name) {
    $quiz_id = $this->url_parse_id($attrs);
    $this->set_score_options();

    $score_user_id = $score_perm = NULL;

    // Check if the user is a teacher ...
    $b_continue = $this->auth_permitted( $score_user_id, $score_perm, $auth_reason );
    if (!$b_continue) {
      return;
    }

    $model = new JuxtaLearn_Quiz_Model();

    $tricky_topic_id = $model->get_tricky_topic($quiz_id);
    $warn = __('not a JuxtaLearn quiz (no linked tricky topic).', self::LOC_DOMAIN);
    ?>
 
    <!--JLQ AUTH: <?php echo $auth_reason ?> -->
    <?php if (!$tricky_topic_id): ?>
      <p class="jl-error-msg no-tt"><?php echo sprintf(
        __('Warning: %s', self::LOC_DOMAIN), $warn) ?>
        <?php echo sprintf(__('Quiz ID: %d', self::LOC_DOMAIN), $quiz_id) ?></p>
      <?php return; ?>
    <?php endif;

    $all_scores = $model->get_all_scores($quiz_id, $this->offset, $most_recent);

    $warn = sprintf(__('no scores yet for this quiz, ID: %d', self::LOC_DOMAIN), $quiz_id);
    if (count($all_scores) < 1): ?>
      <p class="jl-error-msg no-sc"><?php echo sprintf(
        __('Warning: %s', self::LOC_DOMAIN), $warn) ?></p>
      <?php return; ?>
    <?php endif;

    ob_start();

    $notes = sprintf(
      __('%d students have attempted the quiz.', self::LOC_DOMAIN), count($all_scores))
      .' '. __('(Only the most recent attempt is shown per student.)', self::LOC_DOMAIN);


    // Extra links.
    $this->links_individual_user_scores( $all_scores );

    $this->links_recent_oldest_switch();


    $this->print_score_markup($all_scores, $notes);
    ?>

    <script src=
    "<?php echo plugins_url('js/radar-charts-d3.js', JUXTALEARN_QUIZ_REGISTER_FILE) ?>"
    ></script>
    <script>
    <?php $this->print_spider_chart_javascript($all_scores, $is_personal = FALSE) ?>
    </script>

<?php    
    $this->print_utility_javascripts($all_scores);
    $this->end();

    return ob_get_clean();
  }


  // ======================================================

  protected function is_most_recent() {
    return ! $this->_get('oldest');
  }

  /** Links to individual score visualizations...
  */
  protected function links_individual_user_scores($all_scores) {

    ?><p id="jlq-links-user-scores" title="Individual scores"><?php
    $oldest_param = '';   //Not required:  $most_recent ? '' : '?oldest=1';

    foreach ($all_scores as $score): ?>
      <a href="<?php echo site_url(sprintf(self::SCORE_URL, $score->score_id))
        . $oldest_param ?>"><?php echo $score->user_name ?></a> |
<?php
    endforeach;
    ?></p><?php
  }

  /** Links to switch between most recent and oldest (A HACK).
  */
  protected function links_recent_oldest_switch() {

    ?><p id="jlq-links-recent-switch"><?php
    if ($this->is_most_recent()): ?>
      <em class=r >Most recent</em> | <a class=o href="?oldest=1">Oldest</a>
    <?php else: ?>
      <a class=r href="?#">Most recent</a> | <em class=o >Oldest</em>
<?php
    endif;
    ?></p><?php
  }

}
