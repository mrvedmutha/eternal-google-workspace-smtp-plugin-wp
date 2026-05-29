<?php
/**
 * Plugin Name: Eternal Google Workspace SMTP
 * Plugin URI:  https://github.com/mrvedmutha/eternal-google-workspace-smtp-plugin-wp
 * Description: Configure WordPress to send email via Google Workspace SMTP using PHPMailer. No third-party dependencies.
 * Version:     1.0.0
 * Author:      Eternal
 * License:     GPL-2.0-or-later
 * Text Domain: eternal-gws-smtp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EGWS_VERSION', '1.0.0' );
define( 'EGWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EGWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EGWS_OPTION_KEY', 'egws_settings' );

require_once EGWS_PLUGIN_DIR . 'includes/class-crypto.php';
require_once EGWS_PLUGIN_DIR . 'includes/class-settings.php';
require_once EGWS_PLUGIN_DIR . 'includes/class-mailer.php';

new EGWS_Settings();
new EGWS_Mailer();
