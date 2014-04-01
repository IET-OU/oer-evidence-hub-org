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

abstract class JuxtaLearn_Quiz_Shortcode {

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
    status_header(403);
  ?>
    <script> document.documentElement.className += " jlq-error 403 "; </script>
    <p class=error >SORRY! (403) You don't have permission to access this page.</p>
    <?php 
    return ! $b_continue;
  }

  protected function error_404($reason = NULL) {
    @header("X-JuxtaLearn-Error: missing or invalid score ID.");
    status_header(404);
    nocache_headers();

    if (!$reason) $reason = 'missing score ID.';
  ?>
    <script> document.documentElement.className += " jlq-error 404 "; </script>
    <p class=error >ERROR (404). Reason: <?php echo $reason;
    include( get_404_template() );
    exit;
  }

}
