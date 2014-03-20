<?php
/*
Plugin Name: Simple Embed
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: Remove the "chrome" from any Wordpress page, making it embeddable '/page?embed=1&comments=0' [JuxtaLearn].
Author:  Nick Freear
Author URI:  https://github.com/nfreear
*/
define('SIMPLE_EMBED_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));


class Simple_Embed {

  protected $is_embed = FALSE;
  protected $has_comments = TRUE;


  public function __construct() {

    $this->is_embed = isset($_GET['embed']);
    if (isset($_GET['comments']) && $_GET['comments'] < 1) {
      $this->has_comments = FALSE;
    }

    if ($this->is_embed || !$this->has_comments) {
      add_filter('body_class', array(&$this, 'body_class'));
      add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    }
    if ($this->is_embed) {
      add_action('wp_footer', array(&$this, 'footer_script')); //, 500);

      # http://www.youtube.com/embed/vvEmahPNEcI
      header('X-Frame-Options: ALLOWALL');
      @header('X-Content-Type-Options: nosniff');
    }
  }

  public function body_class( $classes ) {
    if (!$this->has_comments) {
      if (is_array($classes)) {
        // 'body_class' action.
        $classes[] = 'se-no-comments';
      } else {
        // 'admin_body_class' action.
        $classes .= ' se-no-comments';
      }
    }

    if (!$this->is_embed) return $classes;

    if (is_array($classes)) {
      $classes[] = 'simple-embed';
    } else {
      $classes .= ' simple-embed';
    }
    return $classes;
  }

  public function enqueue_scripts() {
    wp_enqueue_style('simple-embed', plugins_url(
      'css/simple-embed.css', SIMPLE_EMBED_REGISTER_FILE
    ));
  }

  public function footer_script() {
    ?>
    <script> document.documentElement.className += " simple-embed"; </script>
  <?php
  }

}
$simple_embed = new Simple_Embed();

