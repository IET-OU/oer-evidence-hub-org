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

  protected $is_quiz_edit_pg = FALSE;
  protected $is_quiz_view_pg = FALSE;
  protected $quiz;


  public function __construct() {
    //TODO: check whether Slick Quiz is enabled -- is_plugin_active() ?

    $this->is_quiz_edit_pg = isset($_GET['page']) &&
        preg_match('/slickquiz-(new|edit)/', $_GET['page']);

    add_filter('the_content', array(&$this, 'slickquiz_view_filter'));
    add_action('wp_enqueue_scripts', array(&$this, 'front_enqueue_scripts'));

    $AJAX_ACT = 'wp_ajax_juxtalearn_quiz_';
    add_action($AJAX_ACT . 'edit', array(&$this, 'ajax_post_quiz_edit'));
    add_action($AJAX_ACT . 'stumbling_blocks', array(&$this, 'ajax_get_stumbles'));
    //add_action($AJAX_ACT . 'scores', array(&$this, 'ajax_post_scores'));
    //add_action('admin_init', array(&$this, 'admin_init'));

    if ($this->is_quiz_edit_pg) {
      //add_filter('slickquiz_admin_options', array(&$this, 'custom_admin_options'));

      add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

      add_action('admin_print_footer_scripts', array(&$this, 'admin_quiz_footer')); #, 50);
    }
  }

  public function slickquiz_view_filter( $body ) {

    if (preg_match('@\[slickquiz id=(\d+)\]@', $body, $matches)) {
      $quiz_id = $matches[1];

      $this->is_quiz_view_pg = TRUE;
      $this->quiz = (object) array('id' => $quiz_id);

      $body .= '<script>juxtalearn_quiz = { id: '. $quiz_id .' };</script>';
    }
    return $body;
  }

  public function admin_init() {
    @header('X-JuxtaLearn-Quiz: admin_init');
    echo " admin_init ";
  }

  #wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_stumbling_blocks&tricky_topic=79
  public function ajax_get_stumbles() {
    $tricky_topic_id = isset($_GET['tricky_topic']) ? intval($_GET['tricky_topic']) : NULL;
    $post = get_post($tricky_topic_id);
    $stumbling_blocks = $this->get_data('sb', $tricky_topic_id);
    $html = '';
    foreach ($stumbling_blocks as $tm) {
      $html .= '<label><input type=checkbox value="'. $tm->term_id .'">'. $tm->name .'</label>';
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
      'tricky_topic_id' => $post->ID,
      'tricky_topic_title' => $post->post_title,
      #'tricky_topic_body' => $post->body,
      'post_type' => $post->post_type,
      'stumbling_blocks' => $stumbling_blocks,
      'html' => $html,
    ));
    die(0);
  }

  # POST wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_edit&id=1
  public function ajax_post_quiz_edit() {

    $quiz = $this->get_data('quiz');

    header('X-JuxtaLearn-Quiz: ajax; quiz_id='. $quiz->id);

    $data = json_decode(stripcslashes( $_POST['json'] ));

    var_dump($quiz_id);

    $this->update_data('quiz_tt', array($quiz->id => $data->trickytopic_id));
    $this->update_data('quiz_sb', array($quiz->id => $data->stumbling_blocks));

    die('Yes');
  }

  public function admin_enqueue_scripts() {
    wp_enqueue_script('quiz-scaffold', plugins_url(
      'js/juxtalearn-quiz-scaffold.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery')); #, false, $in_footer = TRUE);
  }

  public function front_enqueue_scripts() {
    wp_enqueue_script('quiz-response', plugins_url(
      'js/juxtalearn-quiz-response.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery'));
  }

  public function admin_quiz_footer() {
  //public function custom_admin_options( $options ) {
    //var_dump($GLOBALS['hook_suffix']); 'admin_page_slickquiz-edit'

    if (!$this->is_quiz_edit_pg) return;

    $tricky_topics = $this->get_data('tricky_topics');
    $quiz_tt = $this->get_data('quiz_tt');
    $quiz = $this->get_data('quiz');
?>
    <script type="text/template" class="jlq-template jlq-t-t" data-sel=".slickQuiz .QuizTitle">

    <div class="question JL-Quiz-TrickyTopic">
      <label for=jlq-trickytopic >Trick topic</label>
      <small class=desc >What tricky topic should this quiz be linked to?</small>
      <select id=jlq-trickytopic name=jlq-trickytopic placeholder="Choose...">
        <option></option>
      <?php foreach ($tricky_topics as $post): #setup_postdata($topic); ?>
        <option value="<?php echo $post->ID ?>"
          <?php echo isset($quiz_tt['x'. $quiz->id])
             && $post->ID == $quiz_tt['x'. $quiz->id] ? 'selected' : '' ?>
          ><?php echo $post->post_title ?></option>
      <?php endforeach; ?>
      </select>
    </div>

    </script>
    <script type="text/template" class="jlq-template jlq-t-s" data-sel=".question.actual">

    <div class="question JL-Quiz-Stumbles">
      <label class=main >Stumbling blocks</label>
      <small class=desc >Choose some stumbling blocks.</small>
      <label ><input type=checkbox name="jlq-stumble[]" value=1 />Stumbling block 1</label>
      <label ><input type=checkbox name="jlq-stumble[]" value=2 />Stumbling block 2</label>
      <label ><input type=checkbox name="jlq-stumble[]" value=3 />Stumbling block 3 ...</label>
      <p>[ TODO: More scaffolding -- display student problems for selected stumbling blocks?]
    </div>

    </div>
<?php
  }

}
$juxtalearn_quiz = new Wp_JuxtaLearn_Quiz();

