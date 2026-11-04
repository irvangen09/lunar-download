<?php

namespace LunarDownload\Core;

use LunarDownload\Services\DesignTokens;
use LunarDownload\Settings\Settings;
use LunarDownload\Settings\SettingsPage;

class Plugin {

	public function run(): void {
		$settings = new Settings();
		$settings->register();

		( new DesignTokens( $settings ) )->register();
		( new BlockRegistry() )->register();

		if ( is_admin() ) {
			( new SettingsPage() )->register();
		}
	}
}
