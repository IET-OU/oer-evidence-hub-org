<?php
/*
Plugin Name: IET WP tag cloud
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org
Description: Shortcode [tagcloud] wrapper around WordPress core function: `wp_tag_cloud` [LACE]
Author:      Nick Freear [@IET-OU]
Author URI:  https://github.com/IET-OU
Version:     0.1
*/


/**
* @link  http://codex.wordpress.org/Function_Reference/wp_tag_cloud
* @copyright Nick Freear, 23 October 2014.
*/


class IET_WP_Tag_Cloud_Plugin {

  const SHORTCODE = 'tagcloud';

  public function __construct() {
    add_shortcode( self::SHORTCODE, array( &$this, 'shortcode' ));
  }


  public function shortcode( $attrs, $content = '', $name ) {

    $classes = $this->get_classes( $attrs );

    ob_start(); ?>

    <div class="<?php echo $classes ?>">
    <?php wp_tag_cloud( $attrs ) ?>
    </div>

<?php
    return ob_get_clean();
  }


  protected function get_classes( $attrs ) {
    $classes[] = self::SHORTCODE;  //'shortcode-'..
    $classes[] = str_replace( '_', '-', strtolower( __CLASS__ )); 
    if ($attrs) {
      foreach ($attrs as $key => $value) {
        if (is_string( $value )) {
          $classes[] = $key .'-'. $value;
        }
      }
    }
    return esc_attr(implode( ' ', $classes ));
  }

}
$iet_wp_tag_cloud = new IET_WP_Tag_Cloud_Plugin();


#End.
