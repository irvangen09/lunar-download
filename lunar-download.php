<?php
/**
 * Plugin Name:       Lunar Download
 * Plugin URI:        https://github.com/irvangen09/lunar-download
 * Description:       A Gutenberg block for presenting download information, with correct link handling and structured data.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Irvan Noerfazri
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lunar-download
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// A pre-release such as 6.9-RC1 sorts below 6.9, so only the release part is compared.
$lunar_download_wp_version = (string) strtok( get_bloginfo( 'version' ), '-' );

// Checked before any class is loaded: the classes use syntax that older PHP cannot parse.
if ( version_compare( PHP_VERSION, '8.0', '<' ) || version_compare( $lunar_download_wp_version, '6.9', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'Lunar Download requires PHP 8.0 or later and WordPress 6.9 or later.', 'lunar-download' )
				);
			}
		}
	);
	return;
}

define( 'LUNAR_DOWNLOAD_VERSION', '0.1.0' );
define( 'LUNAR_DOWNLOAD_DIR', plugin_dir_path( __FILE__ ) );
define( 'LUNAR_DOWNLOAD_URL', plugin_dir_url( __FILE__ ) );

require_once LUNAR_DOWNLOAD_DIR . 'includes/Core/Autoloader.php';

( new \LunarDownload\Core\Autoloader( 'LunarDownload\\', LUNAR_DOWNLOAD_DIR . 'includes/' ) )->register();

add_action(
	'plugins_loaded',
	function () {
		( new \LunarDownload\Core\Plugin() )->run();
	}
);
