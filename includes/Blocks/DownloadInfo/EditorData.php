<?php

namespace LunarDownload\Blocks\DownloadInfo;

use LunarDownload\Settings\Settings;
use LunarDownload\Settings\SettingsPage;

/**
 * Gives the block editor the field definitions it needs. The data travels with
 * the editor script, so authors who cannot open the settings screen can still
 * pick fields, and the editor makes no extra request.
 */
class EditorData {

	// Handle that WordPress gives the editorScript of the block in block.json.
	private const SCRIPT_HANDLE = 'lunar-download-download-info-editor-script';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'attach' ) );
	}

	public function attach(): void {
		$data = self::build(
			$this->settings->get(),
			admin_url( 'options-general.php?page=' . SettingsPage::SLUG ),
			current_user_can( 'manage_options' )
		);

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.lunarDownloadEditor = ' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . ';',
			'before'
		);
	}

	/**
	 * Builds what the editor needs: the fields, archived ones included so that
	 * old entries keep their labels, and a link to the settings screen for users
	 * who can open it.
	 *
	 * @param array  $settings     Clean plugin settings.
	 * @param string $settings_url Address of the settings screen.
	 * @param bool   $can_manage   Whether the current user can open it.
	 * @return array{fields: array[], settingsUrl: string}
	 */
	public static function build( array $settings, string $settings_url, bool $can_manage ): array {
		$fields = array();

		foreach ( $settings['fields'] ?? array() as $field ) {
			$item = array(
				'slug'     => $field['slug'],
				'label'    => $field['label'],
				'type'     => $field['type'],
				'archived' => $field['archived'],
			);

			if ( 'select' === $field['type'] ) {
				$item['options'] = $field['options'];
			}

			$fields[] = $item;
		}

		return array(
			'fields'      => $fields,
			'settingsUrl' => $can_manage ? $settings_url : '',
		);
	}
}
