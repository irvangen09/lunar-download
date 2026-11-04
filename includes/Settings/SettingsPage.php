<?php

namespace LunarDownload\Settings;

class SettingsPage {

	private const SLUG   = 'lunar-download';
	private const HANDLE = 'lunar-download-admin';

	private string $hook_suffix = '';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_page(): void {
		$this->hook_suffix = (string) add_options_page(
			__( 'Lunar Download', 'lunar-download' ),
			__( 'Lunar Download', 'lunar-download' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! is_readable( $this->asset_file() ) ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'The Lunar Download settings interface could not be loaded. Please reinstall the plugin.', 'lunar-download' )
			);
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="lunar-download-settings"></div>
		</div>
		<?php
	}

	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook_suffix || ! is_readable( $this->asset_file() ) ) {
			return;
		}

		$asset = include $this->asset_file();

		wp_enqueue_style(
			self::HANDLE,
			LUNAR_DOWNLOAD_URL . 'build/admin/style-index.css',
			array( 'wp-components' ),
			$asset['version']
		);
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );

		wp_enqueue_script(
			self::HANDLE,
			LUNAR_DOWNLOAD_URL . 'build/admin/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( self::HANDLE, 'lunar-download', LUNAR_DOWNLOAD_DIR . 'languages' );
	}

	private function asset_file(): string {
		return LUNAR_DOWNLOAD_DIR . 'build/admin/index.asset.php';
	}
}
