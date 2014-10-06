<?php
/*
Plugin Name: IET WP Options
Plugin URI:  https://github.com/IET-OU/oer-evidence-hub-org
Description: Display PHP info and the WordPress options table to administrators [LACE].
Author:      Nick Freear [IET-OU]
Author URI:  https://github.com/nfreear
Version:     0.1
*/
// NDF, 6 Oct 2014.


class IET_WP_Options_Plugin {

    const MENU_SLUG = 'iet-wp-options';
	const CAPABILITY = 'manage_options';

    public function __construct() {

        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_options_pages' ));
        }
    }

    /** WP action: 'admin_menu' */
    public function add_options_pages() {

        // This page will be under "Settings"
        $hook_suffix = add_options_page(
            'PHP info',
            'PHP info',
            self::CAPABILITY,
            self::MENU_SLUG . '-phpinfo',
            array( &$this, 'create_phpinfo_page' )
        );
        $hook_suffix = add_options_page(
            'WP Options',
            'WP Options',
            self::CAPABILITY,
            self::MENU_SLUG,
            array( &$this, 'create_options_page' )
        );
    }

    /** Callback to create a phpinfo() page. */
    public function create_phpinfo_page() {
        ob_start();

        phpinfo();

        $page = ob_get_clean();
        $page = str_ireplace(array(
            '<html>','<head>','<body>', '</html>','</head>','</body>' ), '', $page );
        $page = str_replace( '<title>phpinfo()</title>', '', $page );
        $page = preg_replace( '#<!DOCTYPE.*?>#smi', '', $page );

        $this->phpinfo_style( $page );

        $page = preg_replace( '#(<style.*?\/style>)#smi', '', $page );

        echo '<div id="iet-phpinfo">'. $page .'</div>';
    }

    /** Callback to display the contents of the WP options table. */
    public function create_options_page() {

        // Cached (non-cached: get_alloptions() )
        $options = wp_load_alloptions();

        echo '<pre id="iet-wp-options">';
        print_r( $options );
        echo '</pre>';
    }

    public function phpinfo_style( $page ) {
        preg_match( '#<style.*?>(.+?)<\/style#smi', $page, $matches );
        $style = explode( '}', $matches[ 1 ] );
        $css_out = array();

        foreach ($style as $line) {
            if (trim( $line ) == '') continue;

            $css_out[] = $line . "}\n#iet-phpinfo ";
        }

        echo "\n<style id='iet-phpinfo-css'>\n#iet-phpinfo";
        echo implode( '', $css_out );
        echo " .center{font-size: 1.4em;}\n</style>\n";
    }
}
$iet_wp_options = new IET_WP_Options_Plugin();

