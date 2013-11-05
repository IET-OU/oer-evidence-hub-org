<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: (WCM) PHPMailer SMTP Settings
 * Description: Enables SMTP servers, SSL/TSL authentication and SMTP settings.
 */

/*
  http://wordpress.stackexchange.com/questions/75956/whats-the-easiest-way-to-setup-smtp-settings-programmatically
  https://gist.github.com/franz-josef-kaiser/5840282#file-wpmail_smtp-php
*/


add_action( 'phpmailer_init', 'phpmailerSMTP' );
function phpmailerSMTP( $phpmailer )
{
    $smtp_host = get_option('smtp_host', defined('SMTP_HOST') ? SMTP_HOST : NULL);

    if ($smtp_host) {
        $phpmailer->IsSMTP();
        # $phpmailer->SMTPAuth   = true;  // Authentication
	 $phpmailer->Host       = $smtp_host;
    }
	# $phpmailer->Username   = '';
	# $phpmailer->Password   = '';
	# $phpmailer->SMTPSecure = 'ssl'; // enable if required, 'tls' is another possible value
	# $phpmailer->Host       = '';    // SMTP Host
	# $phpmailer->Port       = 26;    // SMTP Port
}

