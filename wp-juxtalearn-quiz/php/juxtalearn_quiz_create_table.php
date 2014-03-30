<?php
/**
* Activate WP plugin - Create database table(s).
*
* @link https://codex.wordpress.org/Creating_Tables_with_Plugins
* create_score_table():
*   https://github.com/wp-plugins/slickquiz/blob/master/slickquiz.php#L234 
* activate():
*   http://github.com/mhawksey/wp-juxtalearn-hub/blob/master/shortcodes/shortcode.php#L140
*/
require_once 'juxtalearn_quiz_api_helper.php';


class JuxtaLearn_Quiz_Create_Table {

  const DB_VERSION = '1.0';
  const DB_PREFIX = '_juxtalearn_quiz__';


  protected function create_score_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'juxtalearn_quiz_scores';

    // scoreJson: [{"is_correct":false,"q_text":"1. What is 3 + 7?","q_num":0}]
    // score_id:  This links to `wp_plugin_slickquiz_scores`.`id`
    // endDate - startDate: How long has the attempt taken?
    $sql = "CREATE TABLE $table_name (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          scoreJson longtext NULL,
          score_id bigint(20) unsigned NOT NULL DEFAULT '0',
          startDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          endDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          createdDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          PRIMARY KEY  (id),
          KEY score_id_index (score_id)
          );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( self::DB_PREFIX . 'db_version', self::DB_VERSION );
  }
}
