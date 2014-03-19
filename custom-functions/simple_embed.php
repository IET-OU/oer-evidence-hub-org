<?php
/*
Plugin Name: Simple Embed
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Description: A plugin to remove the "chrome" from any Wordpress page, making it embeddable '/page?embed=1' [JuxtaLearn].
Author:  Nick Freear
Author URI:  https://github.com/nfreear
*/
define('SIMPLE_EMBED_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));


class Simple_Embed {

  protected $is_embed = FALSE;

  public function __construct() {

    $this->is_embed = isset($_GET['embed']);

    if ($this->is_embed) {
      add_filter('body_class', array(&$this, 'body_class'));
      add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));

      # http://www.youtube.com/embed/vvEmahPNEcI
      header('X-Frame-Options: ALLOWALL');
      @header('X-Content-Type-Options: nosniff');
    }
  }

  public function body_class( $classes ) {
    if (is_array($classes)) {
      // Yes.
      $classes[] = 'simple-embed';
    } else {
      // 'admin_body_class'
      $classes .= ' simple-embed';
    }
    return $classes;
  }

  public function enqueue_scripts() {
    wp_enqueue_style('simple-embed', plugins_url(
      'css/simple-embed.css', SIMPLE_EMBED_REGISTER_FILE
    ));
  }

}
$simple_embed = new Simple_Embed();

