<?php
/*
Plugin Name: IET Attribution
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org
Description: Add copyright, Creative Commons, "Developed by" and "Fork me" links to WordPress sites run by IET at The Open University [LACE]
Version:     0.1
Author:      Nick Freear [@IET-OU]
Author URI:  https://github.com/IET-OU/
License: GPL2+
*/


/**
* WordPress configuration/customization -- wp-config.php (or the DB `options` table):
*
* define( 'IET_ATTRIBUTION_DEVELOPED_BY_HTML', '[ © %s <a href>PROJECT</a> - custom HTML ]' );
* define( 'IET_ATTRIBUTION_AVATAR_ID', 'name@example' );
* define( 'IET_ATTRIBUTION_URL', 'http://example.org' );
* define( 'IET_ATTRIBUTION_NAME', '[ Example Organization Name ]' );
* define( 'IET_ATTRIBUTION_CSS', '[ custom CSS styles ]' );
* define( 'IET_ATTRIBUTION_FORK_ME_URL', 'https://github.com/example/project' );
* define( 'IET_ATTRIBUTION_LICENSE_CURIE', 'cc:by/4.0/88x31' );
* define( 'IET_ATTRIBUTION_COPYRIGHT_HTML', '[ custom HTML ]' );
*
*
* @link  https://github.com/mhawksey/wp-evidence-hub/issues/12
* @link  https://gist.github.com/nfreear/56d6f4a76413fb7a4ae4
* @copyright Nick Freear, 25 September 2014.
*/

define('IET_ATTRIBUTION_REGISTER_FILE', preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__));



class IET_Attribution_Plugin {

  const LICENSE_CURIE_REGEX = '@cc:(?<license>[a-z\-]+)\/(?P<version>[\d\.]+)\/(?P<size>[\dx]+)@';

  protected $already_called = FALSE;


  public function __construct() {
    add_action( 'wp_head',   array( $this, 'wp_head_css_style' ));
    add_action( 'wp_footer', array( $this, 'wp_footer' ));
    add_action( 'tinyforge_credits', array( $this, 'wp_footer' ));
  }


  /** CSS styles for IET attribution in page footer.
  */
  public function wp_head_css_style() { ?>

    <style id="iet-attribution-css">
    .iet-attribution-part { margin: .85em 0; line-height: 1.55em; }
    #iet-attribution-logo { display: block; height: 35px; }
    #iet-attribution-logo:hover svg { outline: 1px solid #bbb; }
    #iet-attribution-logo svg { width: 44px; height: 44px; } /*Was: 32px*/
    #X--iet-attribution {
      background:
        url(<?php echo $this->get_avatar_url( 'iet-webmaster@open.ac.uk' ) ?>) no-repeat bottom right;
      padding: 0 0 32px 0;
    }
    #iet-fork-me img { position: absolute; top: 0; right: 0; border: 0; z-index: 100010; }

    #site-generator a[href *= 'wordpress.org'], .el-icon-wordpress { display: none; }
    #site-generator { max-width: 200px; max-width: 30rem;  x-max: 14.286rem; }
    #site-info { max-width: 500px; max-width: 35rem;  x-max: 750px, 53.571rem; }
    #site-info, #site-generator { font-size: .77rem; }
    <?php $this->print_option( 'iet_attribution_css', '' ) ?>

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
    $this->print_view_code_fork_me_html();
  }


  /** A copyright statement.
  * "Publication Disclaimer Policy" - maybe? http://www.laceproject.eu/publication-policy
  */
  protected function print_copyright_html() { ?>

    <div id="iet-copyright" class="iet-attribution-part">
    <?php $this->print_option(
     'iet_attribution_copyright_html',
     '© %s <a href="#Project-URL">PROJECT</a> partners and contributors.',
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
    $license_compact_url = get_option( 'iet_attribution_license_curie', NULL );  //'cc:by/4.0/88x31'
    if ( ! $license_compact_url ) {
      return;
    }

    if (preg_match( self::LICENSE_CURIE_REGEX, $license_compact_url, $matches )):

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

  <div id="iet-license" class="iet-attribution-part">
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

    <div id="iet-attribution" class="iet-attribution-part">
    <?php $this->print_option(
      'iet_attribution_developed_by_html',
      'Based on free/open source code developed for <a href=
      "http://oerresearchhub.org/">OER Research Hub</a> at the
      <div><a href="http://iet.open.ac.uk/">Institute of Educational Technology</a> at The Open University</div>'
    ) ?>
    <?php $this->svg_load_javascript() ?>
    </div>

<?php
  }

  /** Embed SVG logo via Javascript.
  * @link http://stackoverflow.com/questions/14068031/embedding-external-svg-in-html-for-javascript-manipulation#14070928
  */
  protected function svg_load_javascript( $url = null, $id = 'iet-attribution-logo' ) {
    $url = $url ? $url : $this->get_avatar_url( 'images/iet-ou-logo-400px.svg' ); #plugins_url('images/..svg', __FILE__ );
    $name = $this->get_option( 'iet_attribution_name', 'Institute of Educational Technology' );
     ?>
  <a id="<?php echo $id ?>"
    href="<?php $this->print_option( 'iet_attribution_url', 'http://iet.open.ac.uk/' ) ?>"
    title="<?php echo $name ?>" aria-label="<?php echo $name ?>" ></a>
  <script>
  (function (url, id) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", url, false);

    if (!xhr.overrideMimeType) { return; } // MSIE 7/8 returns here..gracefully!

        // Following line is just to be on the safe side;
        // not needed if your server delivers SVG with correct MIME type
        xhr.overrideMimeType("image/svg+xml");
        xhr.send("");
        document.getElementById(id)
                .appendChild(xhr.responseXML.documentElement);

  })("<?php echo $url ?>", "<?php echo $id ?>");
  </script>
<?php
  }


  /** A discreet "View code" footer link or a "Fork me" ribbon link (green, right-aligned).
  * @link https://github.com/blog/273-github-ribbons
  * @link https://github.com/petethepig/github-ribbons-css -- Mobile friendly CSS?!
  */
  protected function print_view_code_fork_me_html() {
    if ($this->get_option( 'iet_attribution_as_fork_me' )): ?>
    <a
      id="iet-fork-me"
      href="<?php $this->print_option(
        'iet_attribution_fork_me_url',
        'https://github.com/IET-OU/oer-evidence-hub-org#contributors' ) ?>"
      ><img src=
"https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67"
      alt="Fork me on GitHub" title="Fork me on GitHub"
      data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"
    ></a>
<?php
    else: ?>
    <div id="iet-view-code" class="iet-attribution-part"><a
      href="<?php $this->print_option(
        'iet_attribution_fork_me_url',
        'https://github.com/IET-OU/oer-evidence-hub-org#contributors' ) ?>"
      >View source code &amp; contributors on GitHub</a></div>
<?php
    endif;
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
  protected function get_avatar_url( $default_id_or_email = NULL ) {
    $id_or_email = $this->get_option( 'iet_attribution_avatar_id', $default_id_or_email );

    # Option 1: parameter is a path to an image.
    if (preg_match( '/\.(png|jpe?g|svg)/', $id_or_email )) {
      return plugins_url( $id_or_email, IET_ATTRIBUTION_REGISTER_FILE );
    }

    # Option 2: parameter is an email or user login.
    $avatar = get_avatar( $id_or_email, 32 );
    if (preg_match( "/src='([^']+)'/", $avatar, $matches )) {
      return $matches[ 1 ];
    }
  }

}
$iet_attribution = new IET_Attribution_Plugin();


#End.
