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

class Wp_JuxtaLearn_Quiz {

  const PREFIX = '_juxtalearn_quiz__';

  protected $is_quiz_edit_pg = FALSE;


  public function __construct() {
    //TODO: check whether Slick Quiz is enabled -- is_plugin_active() ?

    $this->is_quiz_edit_pg = isset($_GET['page']) &&
        preg_match('/slickquiz-(new|edit)/', $_GET['page']);

    add_action('wp_ajax_juxtalearn_quiz_edit', array(&$this, 'ajax_juxtalearn_quiz_edit'));
    //add_action('admin_init', array(&$this, 'admin_init'));

    if ($this->is_quiz_edit_pg) {
      //add_filter('slickquiz_admin_options', array(&$this, 'custom_admin_options'));

      add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

      add_action('admin_print_footer_scripts', array(&$this, 'admin_quiz_footer')); #, 50);
    }
  }

  public function admin_init() {
    @header('X-JuxtaLearn-Quiz: admin_init');
    echo " admin_init ";
  }

  public function ajax_juxtalearn_quiz_edit() {
    $action = isset($_POST['action']) ? $_POST['action'] : NULL;
    if ('juxtalearn_quiz_edit' != $action) {
      header('X-JuxtaLearn-Quiz: no-ajax');
      die('No');
    }

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
      <p>[ MORE SCAFFOLDING..? ]
    </div>

    </div>
<?php
  }


  /* ========== Data / model functions ============ */

  protected function get_data($key) {
    $result = array();
    switch ($key) {
      case 'tricky_topics':
        $result = get_posts(array(
          'post_type' => 'tricky_topic',
          'post_per_page' => 10, //100,
          'orderby' => 'title',
          'order' => 'ASC',
        ));
      break;
      case 'quiz':
        //$quiz = $this->get_last_quiz_by_user( get_current_user_id() );
        $quiz_id = isset($_GET['id']) ? intval($_GET['id']) : NULL;
        $result = (object) array('id' => $quiz_id);
      break;
      case 'quiz_tt':
        $result = get_option(self::PREFIX .'tt', array());
      break;
      case 'quiz_sb':
        $result = get_option(self::PREFIX .'sb', array());
      break;
      default:
        die("Unexpected 'get_data' call.");
      break;
    }
    return $result;
  }

  protected function update_data($key, $values) {
    $result = $this->get_data($key);
    $new_values = array();
    foreach ($values as $id => $value) {
      if (is_numeric($id)) {
        $new_values['x'. $id] = $value;
      } else {
        $new_values[$id] = $value;
      }
    }
    switch ($key) {
      case 'quiz_tt':
        $result = array_merge($result, $new_values);
        update_option(self::PREFIX . 'tt', $result);
      break;
      case 'quiz_sb':
        $result = array_merge($result, $new_values);
        update_option(self::PREFIX .'sb', $result);
      break;
      default:
        die("Unexpected 'update_data' call.");
      break;
    }
    return $result;
  }

}
$juxtalearn_quiz = new Wp_JuxtaLearn_Quiz();

