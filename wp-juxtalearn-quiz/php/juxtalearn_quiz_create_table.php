<?php
/**
* Activate WP plugin - Create database table(s).
*
* @link https://codex.wordpress.org/Creating_Tables_with_Plugins
* create_score_table():
*   https://github.com/wp-plugins/slickquiz/blob/master/slickquiz.php#L234 
* activate():
*   http://github.com/mhawksey/wp-juxtalearn-hub/blob/master/shortcodes/shortcode.php#L140
*
*
* ALTER TABLE `wp_4_juxtalearn_quiz_scores` ADD COLUMN `permission` varchar(16) NULL;
*/
require_once 'juxtalearn_quiz_api_helper.php';


class JuxtaLearn_Quiz_Create_Table extends JuxtaLearn_Quiz_API_Helper {

  const DB_VERSION = '1.2';
  const DB_PREFIX = '_juxtalearn_quiz__';
  const DB_SCAFFOLD = 'juxtalearn_quiz_scaffold';
  const DB_SCORES   = 'juxtalearn_quiz_scores';


  /**
  * Replaces '_juxtalearn_quiz__tt' & '_juxtalearn_quiz__sb' in `wp_options` option_names.
  */
  protected function create_scaffold_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . self::DB_SCAFFOLD;

    // quiz_id: Links to `wp_plugin_slickquiz`.`id`
    // tricky_topic_id:  Links to `wp_posts`.id WHERE post_type=tricky_topic
    // stumbling_blocks: JSON:  [{"q":"What is 33 + 72?","s":["278","281"]}]
    $sql = "CREATE TABLE $table_name (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            quiz_id bigint(20) unsigned NOT NULL DEFAULT '0',
            tricky_topic_id bigint(20) unsigned NOT NULL DEFAULT '0',
            stumbling_blocks longtext NULL,
            PRIMARY KEY  (meta_id),
            KEY quiz_id_index (quiz_id)
        );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option( self::DB_PREFIX . 'db_version', self::DB_VERSION );
  }

  protected function create_score_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . self::DB_SCORES;

    // scoreJson: [{"is_correct":false,"q_text":"1. What is 3 + 7?","q_num":0}]
    // score_id:  This links to `wp_plugin_slickquiz_scores`.`id`
    // endDate - startDate: How long has the attempt taken?
    $sql = "CREATE TABLE $table_name (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          scoreJson longtext NULL,
          score_id bigint(20) unsigned NOT NULL DEFAULT '0',
          startDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          endDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          createdDate datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          permission varchar(16) NULL,
          PRIMARY KEY  (id),
          KEY score_id_index (score_id)
          );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option( self::DB_PREFIX . 'db_version', self::DB_VERSION );
  }

}
