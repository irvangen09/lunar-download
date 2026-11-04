<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// The plugin is not loaded during uninstall, so the option name is repeated here.
function lunar_download_delete_site_data(): void {
	delete_option( 'lunar_download_settings' );
}

if ( is_multisite() ) {
	// WordPress runs this file once per network, and the data is stored per site.
	$lunar_download_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $lunar_download_site_ids as $lunar_download_site_id ) {
		switch_to_blog( $lunar_download_site_id );
		lunar_download_delete_site_data();
		restore_current_blog();
	}
} else {
	lunar_download_delete_site_data();
}
