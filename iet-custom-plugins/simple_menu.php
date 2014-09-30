<?php
/*
Plugin Name: Simple Menu
Plugin URI:  
Description: Display a menu or sub-menu based on the shortcode `[simplemenu menu=Main sub=Help]` (JuxtaLearn)
Author:      Nick Freear
Author URI:  https://github.com/nfreear
Version:     1.2-alpha
*/
// NDF, 21 May 2014.

/*
  Shortcode:  [simplemenu menu=Main sub=Help]
*/


class Simple_Menu {

  const SHORTCODE = 'simple_menu';

  public function __construct() {
	add_shortcode( self::SHORTCODE, array(&$this, 'shortcode_simplemenu') );
  }


  public function shortcode_simplemenu( $attr ) {
    extract( shortcode_atts( array(
        'menu' => 'Main',
        'sub' => NULL,
    ), $attr ) );

    $menu_obj = wp_get_nav_menu_object( $menu ); //232 ); //'Help' ); //'Main' );
    $menu_items = wp_get_nav_menu_items( $menu_obj );

    if (!$menu_items) return;

    $parent_id = NULL;
    $sub_menu = array();
    if ($sub) {
      foreach ($menu_items as $item) {
        if ($sub == $item->title) {
          $parent_id = $item->ID;
        }
        elseif ($parent_id == $item->menu_item_parent) {
          $sub_menu[] = $item;
        }
      }
    } else {
      $parent_id = $sub = 'Z';
      $sub_menu = $menu_items;
    }

    $classes = self::SHORTCODE . " menu-$menu sub-$sub";

    return $this->display_menu( $sub_menu, $parent_id, $classes );
  }


  protected function display_menu( $sub_menu, $menu_id, $classes ) {
    ob_start();

    ?><ul class="<?php echo $classes ?>" id="sm-menu-<?php echo $menu_id ?>">
  <?php foreach ($sub_menu as $it): ?>
    <li id="menu-item-<?php echo $it->ID ?>" class="menu-item"
      ><a href="<?php echo $it->url ?>"><?php echo $it->title ?></a>
  <?php endforeach; ?>
    </ul><?php

    $this->end();

    return ob_get_clean();
  }


  protected function end($shortcode = NULL) {
    $shortcode = $shortcode ? $shortcode : get_class($this);
    ?>
  <script>
  document.documentElement.className += " jxl-shortcode <?php echo $shortcode ?>";
  </script>
<?php
  }

}
$simple_menu = new Simple_Menu();
