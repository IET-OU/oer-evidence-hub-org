<?php
/**
 * Wordpress shortcode to list JuxtaLearn quizzes. Basic implementation.
 *
 * Usage:
 *   [quiz_list]
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear, 8 May 2014.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_Shortcode_List extends JuxtaLearn_Quiz_Shortcode {

  const SHORTCODE = 'quiz_list';

  public function __construct() {
    $this->add_shortcode( 'quiz_list_shortcode' );
  }


  public function quiz_list_shortcode($attrs, $content = '', $name) {
    //$sq_quiz_id = $this->url_parse_id($attrs);

    $quizzes_list = $this->model_get_quizzes();

    ob_start();
    ?><ul id=quiz-list >

  <?php
    foreach ($quizzes_list as $qz): ?>
      <li><a href="<?php echo $qz->url ?>"><?php echo $qz->name ?></a>
        <div><?php echo $qz->main_text ?></div>

  <?php
    endforeach;
    ?></ul>

  <?php
    $this->print_utility_javascripts( $quizzes_list );
    $this->end();

    return ob_get_clean();
  }


  protected function model_get_quizzes( $juxtalearn_only = TRUE, $published = TRUE ) {
    global $wpdb;
    $db_name = $wpdb->prefix . 'plugin_slickquiz';
    $join_scaffold = $wpdb->prefix . self::DB_SCAFFOLD;
    $quizzes = $wpdb->get_results( "SELECT *
        FROM $db_name
        INNER JOIN $join_scaffold ON $join_scaffold.quiz_id = $db_name.id
        WHERE hasBeenPublished = 1
        GROUP BY $join_scaffold.quiz_id" );  // Defensive - 'group by'

    #$allow_r = wp_kses_allowed_html( 'post' );
    foreach ($quizzes as $qz) {
      $qz->data = json_decode(
          $qz->hasBeenPublished ? $qz->publishedJson : $qz->workingJson );
      $qz->questions = $qz->data->questions;
      $qz->main_text = wp_kses_post( $qz->data->info->main );  // WordPress filter.
      $qz->url = site_url(sprintf( self::QUIZ_URL, $qz->quiz_id ));
      $qz->score_url = site_url(sprintf( self::SCORE_URL, $qz->quiz_id ));
    }
    return $quizzes;
  }


  protected function print_utility_javascripts( $quizzes ) {
    if ($this->_get( 'debug' )): ?>
      <pre id=quiz-list-debug ><?php print_r( $quizzes ) ?></pre>

    <script>
    var JLQ_quizzes = <?php echo json_encode( $quizzes ) ?>;
    window.console && console.log(">> Score data:", JLQ_quizzes);
    </script>
    <?php endif; ?>
<?php
  }

}
