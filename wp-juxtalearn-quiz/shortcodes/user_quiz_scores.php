<?php
/**
 * Wordpress shortcode to list JuxtaLearn (SlickQuiz) quiz scores for a user.
 *
 * Usage:
 *   [user_quiz_scores] - With `my-page/{USER ID}/`
 *   [user_quiz_scores id={USER ID}]
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear, 10 November 2014.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_Shortcode_User_Quiz_Scores extends JuxtaLearn_Quiz_Shortcode {

  const SHORTCODE = 'user_quiz_scores';

  public function __construct() {
    $this->add_shortcode( 'user_scores_shortcode' );

    add_action( 'show_user_profile', array( &$this, 'show_user_profile' ));
    add_action( 'edit_user_profile', array( &$this, 'show_user_profile' ));
    
    add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), 100);
  }

  /** WP user_profile action.
  */
  public function show_user_profile( $user ) { ?>
    <p id="jlq-user-scores-link"><a href="<?php echo $this->user_scores_url( $user ) ?>">JuxtaLearn quiz scores for user</a></p>
<?php
  }

  public function admin_bar_menu( $wp_admin_bar ) {
    $wp_admin_bar->add_node(array(
      'id' => 'jlq-user-scores',
      'parent' => 'user-actions', //'my-account',
      'title' => 'View my quiz scores',
      'href' => $this->user_scores_url(),
    ));
  }

  /** WP shortcode action.
  */
  public function user_scores_shortcode( $attrs, $content = '', $name ) {
    $user_id = $this->url_parse_id( $attrs );

    $user_scores = $this->model_get_user_scores( $user_id );

    $score = isset($user_scores[ 0 ]) ? $user_scores[ 0 ] : NULL;
    $permission = isset($score->permission) ? $score->permission : NULL;

    $b_continue = $this->auth_permitted($user_id, $permission, $auth_reason);
    if (!$b_continue) {
      return;
    }

    $user = get_user_by( 'id', $user_id );
    ?>

    <!--JLQ AUTH: <?php echo $auth_reason ?> -->

    <?php
    $warn = sprintf(__('no scores yet for this user, ID: %d', self::LOC_DOMAIN), $user_id);
    if (count($user_scores) < 1): ?>
      <p class="jl-error-msg no-sc"><?php echo sprintf(
        __('Warning: %s', self::LOC_DOMAIN), $warn) ?></p>
      <?php return; ?>
    <?php endif;

    ob_start();
    ?>
    <div id="jlq-score"><table id="score-list">
    <tr><th> Score </th><th> Date </th><th> Quiz </th></tr>

  <?php
    foreach ($user_scores as $sc): ?>
      <tr><td><a href="<?php echo $sc->score_url ?>"><?php echo $sc->score ?></a>
        </td><td><span><?php echo $sc->createdDate ?></span></td><td>
        <a href="<?php echo $sc->quiz_url ?>"><?php echo $sc->name ?></a></td></tr>

  <?php
    endforeach;
    ?></table></div>

  <?php
    $this->print_utility_javascripts( $user_scores );
    $this->amend_title_javascript( $user->user_nicename );
    $this->end();

    return ob_get_clean();
  }


  protected function model_get_user_scores( $user_id, $order = 'DESC', $limit = 30, $is_juxtalearn = TRUE) {
    global $wpdb;
    $db_name = $wpdb->prefix . 'plugin_slickquiz_scores';  //.._Model::DB_SQ_SCORES
    $join_quiz = $wpdb->prefix . 'plugin_slickquiz';
    $jl_scores = $wpdb->prefix . 'juxtalearn_quiz_scores';
    $order = 'ASC' == $order ? 'ASC' : 'DESC';
    $scores = $wpdb->get_results( "SELECT *, $db_name.id AS score_id
        FROM $db_name
        INNER JOIN $join_quiz ON $join_quiz.id = $db_name.quiz_id
        ". ($is_juxtalearn ? "INNER JOIN $jl_scores ON $jl_scores.score_id = $db_name.id" : '') ."
        WHERE $db_name.createdBy = ". intval( $user_id ) ."
        ORDER BY $db_name.createdDate $order
        LIMIT ". intval( $limit ));

    #$allow_r = wp_kses_allowed_html( 'post' );
    foreach ($scores as $sc) {
      $sc->quiz_url = site_url(sprintf( self::QUIZ_URL, $sc->quiz_id ));
      $sc->score_url = site_url(sprintf( self::SCORE_URL, $sc->score_id ));
    }
    return $scores;
  }


  protected function print_utility_javascripts( $scores ) {
    if ($this->_get( 'debug' )): ?>
      <pre id=user-scores-debug ><?php print_r( $scores ) ?></pre>

    <script>
    var JLQ_scores = <?php echo json_encode( $scores ) ?>;
    window.console && console.log(">> Score data:", JLQ_scores);
    </script>
    <?php endif; ?>
<?php
  }


  protected function user_scores_url( $user = NULL ) {
    $user_id = isset( $user->ID ) ? $user->ID : get_current_user_id();
    return site_url(sprintf( self::USER_SCORES_URL, $user_id ));
  }
}
