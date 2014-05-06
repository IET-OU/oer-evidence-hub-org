<?php
/**
 * Add JuxtaLearn scaffolding to the SlickQuiz quiz/ question editor.
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 *
 * SB:32:logic ; TT:545:Logical Planning.. ; TT:79:?
/wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_student_problems&id=1&tricky_topic=79&stumbling_blocks=323&pretty=1
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
      add_action('admin_notices', array(&$this, 'admin_notices'));
    }

    if ($this->is_quiz_edit_page()) {
      //add_filter('slickquiz_admin_options', array(&$this, 'custom_admin_options'));

      add_action('admin_print_footer_scripts', array(&$this, 'admin_footer_templates'));
    }
  }

  protected function is_quiz_admin_page() {
    return preg_match('/^slickquiz/', $this->_get('page'));
  }

  protected function is_quiz_edit_page() {
    return preg_match('/^slickquiz-(new|edit)/', $this->_get('page'));
  }

  public function admin_notices() {
    $save_scores = $this->get_slickquiz_option('save_scores');
    if (!$save_scores):
      $admin_url = admin_url(
        'admin.php?page=slickquiz-options&SCROLL=save_scores#wpbody-content');
    ?>
    <div class=error ><p><?php echo sprintf( __(
      'Error: the SlickQuiz option "<a %s>Save user scores</a>", required by JuxtaLearn is NOT set to "Yes". Please fix me!',
      self::LOC_DOMAIN), ' href="'. $admin_url .'"') ?></div>
    <?php
    endif;
  }

  #wordpress/wp-admin/admin-ajax.php?action=juxtalearn_quiz_stumbling_blocks&tricky_topic=79
  public function ajax_get_stumbles() {
    $this->api_init();

    $tricky_topic_id = intval($this->_get('tricky_topic'));
    // Note, new quizzes won't have a quiz ID.
    $quiz = $this->get_data('quiz');
    if (!$tricky_topic_id) {
      $this->error('Missing tricky topic ID');
    }
    $post = get_post($tricky_topic_id);
    $stumbling_blocks = $this->get_data('sb', $tricky_topic_id);
    $country = $this->get_data('country', $tricky_topic_id);
    $location = $this->get_data('location', $tricky_topic_id);
    $html = '';
    foreach ($stumbling_blocks as $tm) {
      $html .= '<label><input type=checkbox value="'. $tm->term_id .'">'. $tm->name .'</label>';
    }
    $this->json_response(array(
      'quiz_id' => $quiz->id,
      'tricky_topic_id' => $post->ID,
      'tricky_topic_title' => $post->post_title,
      'tricky_topic_url' => get_permalink($post->ID),  #Was: $post->guid
      #'post_type' => $post->post_type,
      'count' => count($stumbling_blocks),
      'stumbling_blocks' => $stumbling_blocks,
      'quiz_sbs' => $this->get_stumbling_blocks($quiz->id),
      #'education_level' => $this->get_data('edu', $post->ID),
      'country_name' => $country->name,
      'country_id' => $country->id,
      'location' => $location->name,
      'html' => $html,
    ));
  }

  public function ajax_get_student_problems() {
    $this->api_init();

    $stumbling_block_ids = $this->_get('stumbling_blocks');
    // Note, new quizzes won't have a quiz ID.
    $quiz = $this->get_data('quiz');
    if (!$stumbling_block_ids) {
      $this->error('Missing stumbling block ID(s)');
    }
    $student_problems = $this->get_student_problems($stumbling_block_ids);
    $sp_html = '';
    foreach ($student_problems as $post) {
      $url = site_url($post->post_type .'/'. $post->post_name);
      $sp_html .= "<li data-sp='$post->ID'><a href='$url'>$post->post_title</a>: $post->post_content</li>";
    }
    if (!$student_problems || 0 == count($student_problems)) {
      $sp_html = '<li class="dummy no-sp">'.
          __('[ No linked student problems found ]', self::LOC_DOMAIN) .'</li>';
    }

    $tax = $this->process_taxonomy( $student_problems );

    $sp_label  = __('Student Problems', self::LOC_DOMAIN);
    $tax_label = __('Taxonomy: Why do students have this problem?', self::LOC_DOMAIN);
    $html = <<<HTML
    <div class=sp ><h3>$sp_label</h3>
      <ul>$sp_html</ul>
    </div>
    <div class=tax ><h3>$tax_label</h3>
      <ul>$tax->html</ul>
    </div>
HTML;

    $this->json_response(array(
      'quiz_id' => $quiz->id,
      'stumbling_block_ids' => $stumbling_block_ids,
      'count' => count($student_problems),
      'student_problems' => $student_problems,
      'title' => 'Student Problems',
      'html' => $html,
      'tax_data' => $tax->data,
      'meta' => $tax->meta,
    ));
  }

  protected function process_taxonomy($student_problems) {
    $tax_tool = $this->get_hub_tax_tool( $student_problems, $as_html = FALSE );
    $meta = $this->get_posts_meta($student_problems);

    $tax_data = array();
    $tax_html = '';
    $count = 0;
    foreach ($meta as $m) {
      if (preg_match('/_(term|pre|esn|bel)(\d+)/', $m->meta_key, $matches)) {
        $count++;

        $tax_id = $matches[1];
        $tax_key = $tax_id . $matches[2];

        $label = $tax_tool['labels'][$tax_key]['label'];

        foreach ($tax_tool['tabs'] as $tab) {
          if ($tax_id == $tab['id']) {
            $name = $tab['name'];
            $desc = $tab['description'];
            $prompt = $tab['prompt'][$tax_key];
          }
        }
        $tax_data[ $tax_key ] = array(
          'name' => $name,
          'label' => $label,
          'desc' => $desc,
          'prompt' => $prompt,
          'id'  => $tax_id,
          'key' => $tax_key,
        );
        $tax_html .= <<<HTML
  <li data-tx=$tax_key ><h4>$name</h4><span class=lbl >$label</span>
    <span class=dsc >$desc</span> <span class=prmt >$prompt</span></li>

HTML;
      }
    }
    if (!$count || 0 == count($meta)) {
      $tax_html = '<li class="dummy no-tx">'.
          __('[ No taxonomy items selected in the student problems ]', self::LOC_DOMAIN) .'</li>';
    }
    return (object) array('data' => $tax_data, 'html' => $tax_html, 'meta' => $meta);
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
    $this->api_init();

    $quiz = $this->get_data('quiz');

    $data = $this->check_post_json();

    $result = $this->update_scaffold($quiz->id, $data);

    // Legacy.
    $this->update_data('quiz_tt', array($quiz->id => $data->trickytopic_id));
    $this->update_data('quiz_sb', array($quiz->id => $data->stumbling_blocks));

    $this->json_response(array('quiz_id' => $quiz->id, 'result' => $result ));
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

  public function admin_footer_templates() {
  //public function custom_admin_options( $options ) {
    //var_dump($GLOBALS['hook_suffix']); 'admin_page_slickquiz-edit'

    if (!$this->is_quiz_edit_page()) return;

    $quiz = $this->get_data('quiz');
    $tricky_topics = $this->get_data('tricky_topics');

    $tt_id = $this->get_tricky_topic($quiz->id);

    // Legacy.
    #$quiz_tt = $this->get_data('quiz_tt');

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
          $this->form_selected($post, $tt_id) ?>><?php
          echo $post->post_title ?></option>
      <?php endforeach; ?>
      </select>
    </div>

    </script>
    <script type="text/html" class="jlq-template jlq-t-s" data-sel=".question.actual">

    <div class="question JL-Quiz-Stumbles" aria-live="polite" aria-relevant="additions"
        aria-label="<?php echo __('Question stumbling blocks and scaffolding') ?>">
      <p class=jlq-loading ><span><?php echo __('Loading scaffolding...', self::LOC_DOMAIN)
          ?></span> <i></i></p>

      <div class=jlq-stumbles-wrap >
      <label class=main ><?php echo __('Stumbling blocks', self::LOC_DOMAIN) ?></label>
      <small class=desc ><?php echo
      __('Which stumbling blocks should we test with this question?', self::LOC_DOMAIN) ?></small>
      <div class=jlq-stumbles-inner >
        <label class=dummy ><input type=checkbox disabled /> [ Stumbling block ]</label>
      </div>
      </div>

      <div class=jlq-scaffold-wrap >
      <div class=jlq-scaffold-inner ><p class=dummy >[ Main scaffolding ]</div>
      </div>
    </div>
    <div class=jlq-clear ></div>

    </script>
    <script type="text/html" class="jlq-template jlq-t-dummy scaffold" defer=defer >

    <p class=dummy >[ Main scaffolding ]</p>

    </script>
    <script type="text/html" class="jlq-template jlq-t-dummy stumbles" defer=defer >

    <label class=dummy ><input type=checkbox disabled /> [ Stumbling block ]</label>

    </script>
<?php
  }

}
