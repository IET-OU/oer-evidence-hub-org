<?php
/**
 * Add JuxtaLearn scaffolding to the SlickQuiz quiz/ question editor.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */


class JuxtaLearn_Quiz_Scaffold extends JuxtaLearn_Quiz_Model {

  protected $quiz;

  public function __construct() {

    $AJAX_ACT = 'wp_ajax_juxtalearn_quiz_';
    add_action($AJAX_ACT . 'edit', array(&$this, 'ajax_post_quiz_edit'));
    add_action($AJAX_ACT . 'stumbling_blocks', array(&$this, 'ajax_get_stumbles'));
    add_action($AJAX_ACT . 'student_problems', array(&$this, 'ajax_get_student_problems'));
    //add_action('admin_init', array(&$this, 'admin_init'));

    if ($this->is_quiz_edit_page()) {
      //add_filter('slickquiz_admin_options', array(&$this, 'custom_admin_options'));

      add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

      add_action('admin_print_footer_scripts', array(&$this, 'admin_quiz_footer')); #, 50);
    }
  }

  protected function is_quiz_edit_page() {
    return isset($_GET['page']) &&
            preg_match('/slickquiz-(new|edit)/', $_GET['page']);
  }

  public function admin_init() {
    @header('X-JuxtaLearn-Quiz: admin_init');
    echo " admin_init ";
  }

  #wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_stumbling_blocks&tricky_topic=79
  public function ajax_get_stumbles() {
    $tricky_topic_id = isset($_GET['tricky_topic']) ? intval($_GET['tricky_topic']) : NULL;
    $quiz = $this->get_data('quiz');
    $post = get_post($tricky_topic_id);
    $stumbling_blocks = $this->get_data('sb', $tricky_topic_id);
    $html = '';
    foreach ($stumbling_blocks as $tm) {
      $html .= '<label><input type=checkbox value="'. $tm->term_id .'">'. $tm->name .'</label>';
    }
    $this->json_response(array(
      'quiz_id' => $quiz->id,
      'tricky_topic_id' => $post->ID,
      'tricky_topic_title' => $post->post_title,
      'tricky_topic_url' => $post->guid,
      #'tricky_topic_body' => $post->post_content,
      'post_type' => $post->post_type,
      'count' => count($stumbling_blocks),
      'stumbling_blocks' => $stumbling_blocks,
      'html' => $html,
    ));
  }

  public function ajax_get_student_problems() {
    $stumbling_block_ids = isset($_GET['stumbling_blocks']) ? $_GET['stumbling_blocks'] : NULL;
    $quiz = $this->get_data('quiz');
    $student_problems = $this->get_student_problems($stumbling_block_ids);
    $html = '<ul>';
    foreach ($student_problems as $post) {
      $html .= "<li data-sp='$post->ID'><a href='$post->guid'>$post->post_title</a>: $post->post_content</li>";
    }
    $this->json_response(array(
      'quiz_id' => $quiz->id,
      'stumbling_block_ids' => $stumbling_block_ids,
      'count' => count($student_problems),
      'student_problems' => $student_problems,
      'title' => 'Student Problems',
      'html' => $html . '</ul>',
    ));
  }

  # POST wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_edit&id=1
  public function ajax_post_quiz_edit() {
    $quiz = $this->get_data('quiz');

    $data = $this->check_post_json();

    $this->update_data('quiz_tt', array($quiz->id => $data->trickytopic_id));
    $this->update_data('quiz_sb', array($quiz->id => $data->stumbling_blocks));

    $this->json_response(array('quiz_id' => $quiz->id));
  }

  public function admin_enqueue_scripts() {
    wp_enqueue_script('quiz-scaffold', plugins_url(
      'js/juxtalearn-quiz-scaffold.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), array('jquery')); #, false, $in_footer = TRUE);
    wp_enqueue_style('quiz-scaffold', plugins_url(
      'css/juxtalearn-quiz-scaffold.css', JUXTALEARN_QUIZ_REGISTER_FILE
    ));
  }

  public function admin_quiz_footer() {
  //public function custom_admin_options( $options ) {
    //var_dump($GLOBALS['hook_suffix']); 'admin_page_slickquiz-edit'

    if (!$this->is_quiz_edit_page()) return;

    $tricky_topics = $this->get_data('tricky_topics');
    $quiz_tt = $this->get_data('quiz_tt');
    $quiz = $this->get_data('quiz');
?>
    <script type="text/template" class="jlq-template jlq-t-t" data-sel=".slickQuiz .QuizTitle">

    <div class="question JL-Quiz-TrickyTopic">
      <p class=jlq-loading ><span>Loading scaffolding...</span> <i></i></p>

      <label for=jlq-trickytopic >Trick topic</label>
      <small class=desc >What tricky topic should this quiz be linked to?</small>
      <select id=jlq-trickytopic name=jlq-trickytopic placeholder="Choose...">
        <option></option>
      <?php foreach ($tricky_topics as $post): #setup_postdata($topic); ?>
        <option value="<?php echo $post->ID ?>" <?php
          $this->form_selected($post, $quiz_tt, $quiz->id) ?>><?php
          echo $post->post_title ?></option>
      <?php endforeach; ?>
      </select>
    </div>

    </script>
    <script type="text/template" class="jlq-template jlq-t-s" data-sel=".question.actual">

    <div class="question JL-Quiz-Stumbles">
      <p class=jlq-loading ><span>Loading scaffolding...</span> <i></i></p>

      <label class=main >Stumbling blocks</label>
      <small class=desc >Which stumbling blocks should we test with this question?</small>
      <div class=jlq-stumbles-inner >
        <label><input type=checkbox name="jlq-s[]" class=dummy />[ Stumbling block ]</label>
      </div>

      <div class=jlq-scaffold-wrap >
      <h4>Student problems</h4>
      <div class=jlq-scaffold-inner ><p>[ TODO: More scaffolding -- display student problems for selected stumbling
       blocks? ]</div>
      </div>
    </div>
    <div class=jlq-clear ></div>

    </div>
<?php
  }

}
