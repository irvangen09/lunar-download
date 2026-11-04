<?php

namespace LunarDownload\Core;

use LunarDownload\Settings\Settings;
use LunarDownload\Settings\SettingsPage;

class Plugin {

	public function run(): void {
		( new Settings() )->register();
		( new BlockRegistry() )->register();

		if ( is_admin() ) {
			( new SettingsPage() )->register();
		}
	}
}
