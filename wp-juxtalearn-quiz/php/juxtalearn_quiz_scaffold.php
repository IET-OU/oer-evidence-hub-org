<?php
/**
 * Add JuxtaLearn scaffolding to the SlickQuiz quiz/ question editor.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */


class JuxtaLearn_Quiz_Scaffold extends JuxtaLearn_Quiz_Model {

  const LOC_DOMAIN = Wp_JuxtaLearn_Quiz::LOC_DOMAIN;

  protected $quiz;

  public function __construct() {

    $AJAX_ACT = 'wp_ajax_juxtalearn_quiz_';
    add_action($AJAX_ACT . 'edit', array(&$this, 'ajax_post_quiz_edit'));
    add_action($AJAX_ACT . 'stumbling_blocks', array(&$this, 'ajax_get_stumbles'));
    add_action($AJAX_ACT . 'student_problems', array(&$this, 'ajax_get_student_problems'));
    //add_action('admin_init', array(&$this, 'admin_init'));

    if ($this->is_quiz_admin_page()) {
      add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
    }

    if ($this->is_quiz_edit_page()) {
      //add_filter('slickquiz_admin_options', array(&$this, 'custom_admin_options'));

      add_action('admin_print_footer_scripts', array(&$this, 'admin_quiz_footer')); #, 50);
    }
  }

  protected function is_quiz_admin_page() {
    return isset($_GET['page']) && preg_match('/^slickquiz/', $_GET['page']);
  }

  protected function is_quiz_edit_page() {
    return isset($_GET['page']) &&
            preg_match('/^slickquiz-(new|edit)/', $_GET['page']);
  }

  public function admin_init() {
    @header('X-JuxtaLearn-Quiz: admin_init');
    echo " admin_init ";
  }

  #wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_stumbling_blocks&tricky_topic=79
  public function ajax_get_stumbles() {
    $tricky_topic_id = isset($_GET['tricky_topic']) ? intval($_GET['tricky_topic']) : NULL;
    $quiz = $this->get_data('quiz');
    if (!$tricky_topic_id) {
      $this->error('Missing tricky topic ID');
    }
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
    if (!$stumbling_block_ids) {  #|| !$quiz->id) {
      $this->error('Missing stumbling block ID(s)');
    }
    $inc_tax_tool = TRUE;
    $student_problems = $this->get_student_problems($stumbling_block_ids);
    $html = '<ul>';
    foreach ($student_problems as $post) {
      $url = site_url($post->post_type .'/'. $post->post_name);
      $html .= "<li data-sp='$post->ID'><a href='$url'>$post->post_title</a>: $post->post_content</li>";
    }
    $tax_tool = $inc_tax_tool ? $this->get_hub_tax_tool( $student_problems, $as_html = FALSE ) : '';

    $this->json_response(array(
      'quiz_id' => $quiz->id,
      'stumbling_block_ids' => $stumbling_block_ids,
      'count' => count($student_problems),
      'student_problems' => $student_problems,
      'title' => 'Student Problems',
      'html' => $html . '</ul> ',
      'tax_tool' => $tax_tool,
      'activate_tax_tool' => $inc_tax_tool ? TRUE : FALSE,
    ));
  }

  /**
    TODO: enqueue :: jquery-ui, tabs...
    TODO: multiple student problems?!
  */
  protected function get_hub_tax_tool( $posts, $as_html = TRUE ) {
	if (!$posts) return '';

	$post = $posts[0];
	$path = '../../wp-juxtalearn-hub/';

    require_once $path . 'post-types/class-custom_post_type.php';
    require_once $path . 'post-types/student_problem.php';
    #$cpt = new Juxtalearn_Hub_CustomPostType();
    $sp = new Student_Problem_Template( $as_wp_plugin = false );
    $sp->set_options();

    if ($as_html) {
      ob_start();
      $sp->add_inner_meta_boxes_tax_tool( $post );
      #require_once "../../wp-juxtalearn-hub/post-types/taxonomy-tool.php";
      return ob_get_clean();
    }

    $sp_options = $sp->get_options();
    $sp_c_tax = $sp_options['country']['options'][0]->taxonomy;  #'juxtalearn_hub_country'
    unset($sp_options['country']['options']);

    return array(
      'labels' => $sp_options,
      'tabs' => $sp->get_tax_tool_tabs(),
    );
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
    $scripts = array('jquery', 'post', 'jquery-ui-core', 'jquery-ui-tabs'); #'jquery-ui-autocomplete');
    wp_enqueue_script('quiz-scaffold', plugins_url(
      'js/juxtalearn-quiz-scaffold.js', JUXTALEARN_QUIZ_REGISTER_FILE
    ), $scripts, false, $in_footer = TRUE);
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
    <!-- JuxtaLearn Quiz templates -->
    <script type="text/html" class="jlq-template jlq-t-t" data-sel=".slickQuiz .QuizTitle">

    <div class="question JL-Quiz-TrickyTopic">
      <p class=jlq-loading ><span><?php echo __('Loading scaffolding...', self::LOC_DOMAIN)
          ?></span> <i></i></p>

      <label for=jlq-trickytopic ><?php echo __('Tricky topic', self::LOC_DOMAIN) ?></label>
      <small class=desc ><?php echo
      __('What tricky topic should this quiz be linked to?', self::LOC_DOMAIN) ?></small>
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
      <p class=jlq-loading ><span><?php echo __('Loading scaffolding...', self::LOC_DOMAIN)
          ?></span> <i></i></p>

      <label class=main ><?php echo __('Stumbling blocks', self::LOC_DOMAIN) ?></label>
      <small class=desc ><?php echo
      __('Which stumbling blocks should we test with this question?', self::LOC_DOMAIN) ?></small>
      <div class=jlq-stumbles-inner >
        <label><input type=checkbox name="jlq-s[]" class=dummy />[ Stumbling block ]</label>
      </div>

      <div class=jlq-scaffold-wrap >
      <h4><?php echo __('Student problems', self::LOC_DOMAIN) ?></h4>
      <div class=jlq-scaffold-inner ><p>[ TODO: More scaffolding -- display student problems for selected stumbling
       blocks? ]</div>
      </div>
    </div>
    <div class=jlq-clear ></div>

    </div>
<?php
  }

}
