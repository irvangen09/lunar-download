<?php

namespace LunarDownload\Core;

use LunarDownload\Blocks\DownloadInfo\EditorData;
use LunarDownload\Blocks\DownloadInfo\Renderer;
use LunarDownload\Blocks\DownloadInfo\SchemaOutput;
use LunarDownload\Services\DesignTokens;
use LunarDownload\Settings\Settings;
use LunarDownload\Settings\SettingsPage;

class Plugin {

	public function run(): void {
		$settings = new Settings();
		$settings->register();

		( new DesignTokens( $settings ) )->register();
		( new BlockRegistry() )->register();
		( new SchemaOutput() )->register();

		// An entry reads the settings once per request; a change starts over.
		foreach ( array( 'add', 'update', 'delete' ) as $action ) {
			add_action( $action . '_option_' . Settings::OPTION, array( Renderer::class, 'reset_context' ) );
		}

		if ( is_admin() ) {
			( new SettingsPage() )->register();
			( new EditorData( $settings ) )->register();
		}
	}
}
