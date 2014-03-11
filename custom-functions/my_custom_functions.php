<?php
/*
Plugin Name: My Custom Functions
Plugin URI:  https://gist.github.com/nfreear/9049532
Description: A simple plugin that contains all the awesome little functions that
    I use to make my site work <a href=
"http://wphidedash.org/2011/04/best-practice-for-adding-custom-functions/">my way.</a>
Author:  Nick Freear
Author URI:  https://github.com/IET-OU/oer-evidence-hub-org/#Juxtalearn
Version:  0.1
*/
// FILE: juxtalearn_hub/wordpress/wp-content/plugins/my-custom-functions.php

// http://wphidedash.org/2011/04/best-practice-for-adding-custom-functions/
// http://wpmututorials.com/basics/what-is-the-mu-plugins-folder/


/* Disallow direct access to the plugin file */

if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
        die('Sorry, but you cannot access this page directly.');
}

/** START ADDING CODE BELOW THIS LINE **/

define('CUSTOM_FUNC_REGISTER_FILE',
  preg_replace('@/Users/[^\/]+/[^\/]+/[^\/]+@', '',    # Mac OS X
    preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__) # Linux
));

//http://wordpress.org/support/topic/stop-wordpress-from-adding-p-tags-and-removing-line-break
//remove_filter ('the_content', 'wpautop');


//http://wordpress.stackexchange.com/questions/66834/how-to-target-with-css-admin-elements-according-to-user-role-level
function wpa66834_role_admin_body_class( $classes ) {
    global $current_user;
    foreach( $current_user->roles as $role )
        $classes .= ' role-' . $role;
    return trim( $classes );
}
add_filter( 'admin_body_class', 'wpa66834_role_admin_body_class' );


class My_Custom_Functions {

  public function __construct() {
    add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
  }


  public function admin_enqueue_scripts() {
    wp_enqueue_style('my-custom-functions-admin', plugins_url(
      'css/custom-admin.css', CUSTOM_FUNC_REGISTER_FILE
    ));
  }
}

$custom_func = new My_Custom_Functions();


/** STOP ADDING CODE NOW**/

/* That's all folks! */
