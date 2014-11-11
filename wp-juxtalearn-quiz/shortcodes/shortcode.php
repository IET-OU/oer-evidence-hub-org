<?php
/**
 * Abstract class used to construct shortcodes
 *
 * Based on shortcode class construction used in Conferencer
 * @link http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

abstract class JuxtaLearn_Quiz_Shortcode extends JuxtaLearn_Quiz_Model {
  const SHORTCODE = 'jl-quiz-shortcode';


  protected function add_shortcode( $func ) {
    add_shortcode( static::SHORTCODE, array( &$this, $func ));
  }


  protected function amend_title_javascript( $replacement, $page_replace = 'ID: $1' ) {
    $replacement = json_encode( $replacement );
    $page_replace = json_encode( $page_replace ); ?>
  <script>
  jQuery(function ($) {
    var
      $post_title = $(".entry-title:first"),
      $page_title = $("title:first"),
      text = $post_title.html(),
      new_val = <?php echo $replacement ?>,
      replace = text.match(/%s/) ? text.replace("%s", new_val) : new_val;

    $post_title.html(replace);
    $page_title.html($page_title.html()
        .replace(text, replace).replace(/Page (\d+)/, <?php echo $page_replace ?>));
  });
  </script>
<?php
  }


  protected function end($shortcode = NULL) {
    $shortcode = $shortcode ? $shortcode : get_class($this) .' jxl-'. static::SHORTCODE;
    ?>
  <script>
  document.documentElement.className += " jxl-shortcode <?php echo $shortcode ?>";
  </script>
<?php
  }

  protected function url_parse_id($attrs = array()) {
    #var_dump($sc_name, $sc_content, $attrs, get_the_ID());

    $the_id = isset($attrs['id']) ? $attrs['id'] : NULL;

    $regex = '@'. basename(get_permalink()) .'\/(\d+)@';

    if (preg_match($regex, $_SERVER['REQUEST_URI'], $matches)) {
      $the_id = $matches[1];
    }
    if (!$the_id) {
      //die("Error, missing score ID.");
      $this->error_404();
    }
    return $the_id;
  }

  /** Was: authenticate()
  */
  protected function auth_permitted($user_id, $permission = NULL, &$reason = NULL) {
    $b_continue = TRUE;
    $current_user = wp_get_current_user();

    if (!$this->user_exists( $user_id )) {
      return ! $b_continue;
    }
    if ('public' == $permission) {
      $reason = 'permission public';
      return $b_continue;
    }
    if ($user_id && $current_user->ID == $user_id) {
      $reason = 'current user';
      return $b_continue;
    }
    // Admin or editor (teacher).
    if (current_user_can('edit_pages')) {
      $reason = 'is editor';
      return $b_continue;
    }
    $this->error_message(__( "You don't have permission to access this page." ), 403 );
    return ! $b_continue;
  }


  protected function user_exists( $user_id ) {
    $user_exists = is_user_member_of_blog( $user_id );
    if (!$user_exists) {
      $this->error_message(sprintf(__( 'This user does not exist, %s', self::LOC_DOMAIN ), $user_id ), 404);
    }
    return $user_exists;
  }


  protected function error_message( $reason, $http_code = 400, $template = NULL ) {
    $template = $template ? $template : __( 'SORRY! (%2$s) %1$s', self::LOC_DOMAIN );
    @header( 'X-JuxtaLearn-Error: '. $reason );
    status_header( $http_code ); ?>
    <script> document.documentElement.className += " jl-q-error $http_code "; </script>
    <p class="jl-error-msg"><i class="el-icon-warning-sign"></i> <?php
      echo sprintf( $template, $reason, $http_code )?></p><?php
  }


  protected function error_404($reason = NULL) {
    if (!$reason) $reason = __( 'Missing or invalid score ID.', self::LOC_DOMAIN );

    $this->error_message( $reason, 404 );
    include( get_404_template() );
    exit;
  }

  protected function console_message($message) {
    $message = json_encode($message);
    return <<<HTML
    <!--JLQ: $message -->
    <script>
    window.console && console.log($message);
    </script>
HTML;
  }

}
