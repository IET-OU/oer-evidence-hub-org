<?php
/*
Plugin Name: OU Attribution
Plugin URI: https://gist.github.com/nfreear/56d6f4a76413fb7a4ae4
Description: Add "Developed by" and "Fork me" links to IET's sites (IET-OU)
Version: 0.1.0
Author: Nick Freear
Author URI: https://github.com/IET-OU/
License: GPL2+
*/


/**
* Configuration/ customization -- wp-config.php (or the DB `options` table):
*
* define( 'OU_ATTRIBUTION_DEVELOPED_BY', '[ CUSTOM HTML ]' );
* define( 'OU_ATTRIBUTION_STYLE', '[ CUSTOM CSS ]' );
*
*
* @link https://github.com/mhawksey/wp-evidence-hub/issues/12
* @copyright Nick Freear, 25 September 2014.
*/


class OU_Attribution_Links_Plugin {

  protected $already_called = FALSE;


  public function __construct() {
    add_action( 'wp_head', array( $this, 'style' ));
    add_action( 'wp_footer', array( $this, 'developed_by' ));
    add_action( 'tinyforge_credits', array( $this, 'developed_by' ));
  }


  /** Custom CSS styles.
  */
  public function style() { ?>

    <style id="ou-attribution-css">
    #ou-attribution {
      background:
        url(<?php $this->print_avatar_url( 'iet-webmaster@open.ac.uk' ) ?>) no-repeat right;
      padding-right: 36px;
      vertical-align: top;
    }
    #ou-fork-me img { position: absolute; top: 0; right: 0; border: 0; z-index: 100010; }
    <?php
    $this->print_option(
      'ou_attribution_style',
      '#site-generator { max-width: 200px; max-width: 26rem;  x-max: 14.286rem; }
    #site-info { max-width: 500px; max-width: 35rem;  x-max: 750px, 53.571rem; }
    #site-info, #site-generator { font-size: .77rem; }'
    ) ?>
    </style>
<?php
  }


  /** The "Developed by" link.
  */
  public function developed_by() {

    if ($this->already_called) {
      return;
    }
    $this->already_called = TRUE;

    ?>
    <div id="ou-attribution">
    <?php $this->print_option(
      'ou_attribution_developed_by',
      'Developed by <a href="http://mashe.hawksey.info/2013/10/building-an-evidence-hub-plugin-for-wordpress" title="Martin Hawksey">@mhawksey</a>
      and the <div><a href="http://iet.open.ac.uk/">Institute of Educational Technology</a> at The Open University</div>'
    ) ?>
    </div>

<?php
    $this->fork_me();
  }

  /** The "Fork me" ribbon link.
  * @link https://github.com/blog/273-github-ribbons
  */
  protected function fork_me() { ?>
    <a
      id="ou-fork-me"
      href="<?php $this->print_option(
        'ou_attribution_fork_me_url',
        'https://github.com/IET-OU/oer-evidence-hub-org' ) ?>"
      ><img src=
"https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67"
      alt="Fork me on GitHub"
      data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"
    ></a>
<?php
  }


  /** Get a WP configuration option from a PHP define() or the database.
  */
  protected function get_option( $option, $default = NULL ) {
    $KEY = strtoupper( $option );
    return defined( $KEY ) ? constant( $KEY ) : get_option( $option, $default );
  }

  protected function print_option( $option, $default = NULL ) {
    echo $this->get_option( $option, $default );
  }

  protected function print_avatar_url( $default_id_or_email ) {
    $id_or_email = $this->get_option( 'ou_attribution_avatar_id', $default_id_or_email );
    $avatar = get_avatar( $id_or_email, 32 );
    if (preg_match( "/src='([^']+)'/", $avatar, $matches )) {
      echo $matches[ 1 ];
    }
  }

}
$ou_attribution = new OU_Attribution_Links_Plugin();


#End.
