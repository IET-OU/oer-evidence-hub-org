<?php
/*
Plugin Name: OU Attribution
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org
Description: Add copyright, Creative Commons, "Developed by" and "Fork me" links to WordPress sites run by IET at The Open University [LACE]
Version: 0.1.0
Author:  Nick Freear [@IET-OU]
Author URI: https://github.com/IET-OU/
License: GPL2+
*/


/**
* WordPress configuration/customization -- wp-config.php (or the DB `options` table):
*
* define( 'OU_ATTRIBUTION_DEVELOPED_BY_HTML', '[ custom HTML ]' );
* define( 'OU_ATTRIBUTION_AVATAR_ID, 'name@example' );
* define( 'OU_ATTRIBUTION_CSS', '[ custom CSS styles ]' );
* define( 'OU_ATTRIBUTION_FORK_ME_URL', 'https://github.com/example/project' );
* define( 'OU_ATTRIBUTION_LICENSE_CURIE', 'cc:by/4.0/88x31' );
* define( 'OU_ATTRIBUTION_COPYRIGHT_HTML', '[ custom HTML ]' );
*
*
* @link  https://github.com/mhawksey/wp-evidence-hub/issues/12
* @link  https://gist.github.com/nfreear/56d6f4a76413fb7a4ae4
* @copyright Nick Freear, 25 September 2014.
*/

define('OU_ATTRIBUTION_REGISTER_FILE', preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__));



class OU_Attribution_Plugin {

  protected $already_called = FALSE;


  public function __construct() {
    add_action( 'wp_head',   array( $this, 'wp_head_css_style' ));
    add_action( 'wp_footer', array( $this, 'wp_footer' ));
    add_action( 'tinyforge_credits', array( $this, 'wp_footer' ));
  }


  /** Custom CSS styles.
  */
  public function wp_head_css_style() { ?>

    <style id="ou-attribution-css">
    .ou-attribution-part { margin: .85em 0; line-height: 1.55em; }
    #ou-attribution {
      background:
        url(<?php $this->print_avatar_url( 'iet-webmaster@open.ac.uk' ) ?>) no-repeat bottom right;
      x-padding: 0 36px 1px 0;
      padding: 0 0 32px 0;
    }
    #ou-fork-me img { position: absolute; top: 0; right: 0; border: 0; z-index: 100010; }

    #site-generator a[href *= 'wordpress.org'], .el-icon-wordpress { display: none; }

    <?php $this->print_option(
      'ou_attribution_css',
      '#site-generator { max-width: 200px; max-width: 26rem;  x-max: 14.286rem; }
    #site-info { max-width: 500px; max-width: 35rem;  x-max: 750px, 53.571rem; }
    #site-info, #site-generator { font-size: .77rem; }'
    ) ?>

    </style>
<?php
  }


  /** Insert attribution-related links in the page footer.
  */
  public function wp_footer() {

    if ($this->already_called) {
      return;
    }
    $this->already_called = TRUE;

    $this->print_copyright_html();
    $this->print_license_html();
    $this->print_developed_by_html();
    $this->print_fork_me_html();
  }


  /** A copyright statement.
  * "Publication Disclaimer Policy" - maybe? http://www.laceproject.eu/publication-policy
  */
  protected function print_copyright_html() { ?>

    <div id="ou-copyright" class="ou-attribution-part">
    <?php $this->print_option(
     'ou_attribution_copyright_html',
     '© %s <a href="http://www.laceproject.eu/">LACE Consortium</a> and contributors.',
     date( 'Y' )
    ) ?>
    </div>

<?php
  }

  /** HTML+RDFa for a Creative Commons license.
  * @link https://creativecommons.org/choose/
  * @link http://embed.open.ac.uk/oembed?url=cc:by/4.0/88x31&title=[MY+WORK]
  */
  protected function print_license_html() {

    $license_compact_url = get_option( 'ou_attribution_license_curie', 'cc:by/4.0/88x31' );

    if (preg_match(
        '@cc:(?<license>[a-z\-]+)\/(?P<version>[\d\.]+)\/(?P<size>[\dx]+)@',
        $license_compact_url, $matches )):

      $license = $matches[ 'license' ];
      $version = $matches[ 'version' ];
      $size = $matches[ 'size' ];

      switch ($license) {
      case 'by':
        $label = sprintf( 'Creative Commons Attribution %s International License', $version );
      break;
      default:
        $label = sprintf( 'Creative Commons %s International License', $version );
      break;
      }
      ?>

  <div id="ou-license" class="ou-attribution-part">
    <a rel="license"
      href="http://creativecommons.org/licenses/<?php echo "$license/$version" ?>/"><img
      alt="Creative Commons License" style="border-width:0"
      src="https://i.creativecommons.org/l/<?php echo "$license/$version/$size" ?>.png" /></a>
    <br />This work is licensed under a <a rel="license"
      href="http://creativecommons.org/licenses/<?php echo "$license/$version" ?>/"
      ><?php echo $label ?></a>.
  </div>

<?php
    endif;
  }

  /** The "Developed by" link(s).
  */
  protected function print_developed_by_html() { ?>

    <div id="ou-attribution" class="ou-attribution-part">
    <?php $this->print_option(
      'ou_attribution_developed_by_html',
      'Developed by <a href="http://mashe.hawksey.info/2013/10/building-an-evidence-hub-plugin-for-wordpress" title="Martin Hawksey">@mhawksey</a>
      and the <div><a href="http://iet.open.ac.uk/">Institute of Educational Technology</a> at The Open University</div>'
    ) ?>
    </div>

<?php
  }


  /** The "Fork me" ribbon link (green, right-aligned).
  * @link https://github.com/blog/273-github-ribbons
  */
  protected function print_fork_me_html() { ?>
    <a
      id="ou-fork-me"
      href="<?php $this->print_option(
        'ou_attribution_fork_me_url',
        'https://github.com/IET-OU/oer-evidence-hub-org' ) ?>"
      ><img src=
"https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67"
      alt="Fork me on GitHub" title="Fork me on GitHub"
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

  /** Output a WP config. option through `printf()` in PHP core.
  */
  protected function print_option( $option, $default = NULL, $args = NULL ) {
    printf( $this->get_option( $option, $default ), $args );
  }

  /** Get the URL for an organization's icon/avatar.
  */
  protected function print_avatar_url( $default_id_or_email = NULL ) {
    $id_or_email = $this->get_option( 'ou_attribution_avatar_id', $default_id_or_email );

    # Option 1: a path to an image.
    if (preg_match( '/\.(png|jpe?g|svg)/', $id_or_email )) {
      echo plugins_url( $id_or_email, OU_ATTRIBUTION_REGISTER_FILE );
    }

    # Option 2: an email or user login.
    $avatar = get_avatar( $id_or_email, 32 );
    if (preg_match( "/src='([^']+)'/", $avatar, $matches )) {
      echo $matches[ 1 ];
    }
  }

}
$ou_attribution = new OU_Attribution_Plugin();


#End.
