<?php

namespace LunarDownload\Services;

use LunarDownload\Settings\Settings;

class DesignTokens {

	private const HANDLE = 'lunar-download-tokens';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'register_style' ) );
	}

	// Block stylesheets depend on this handle, so it is registered but never enqueued here.
	public function register_style(): void {
		wp_register_style( self::HANDLE, LUNAR_DOWNLOAD_URL . 'assets/css/tokens.css', array(), LUNAR_DOWNLOAD_VERSION );

		// The file is small, so WordPress prints it inline instead of as a separate request.
		wp_style_add_data( self::HANDLE, 'path', LUNAR_DOWNLOAD_DIR . 'assets/css/tokens.css' );

		$css = $this->build_override_css( $this->settings->get_color_overrides() );

		if ( '' !== $css ) {
			wp_add_inline_style( self::HANDLE, $css );
		}
	}

	/**
	 * Builds the :root block for the colors that are set. A valid Primary also
	 * turns the buttons solid, with the text color that contrasts best.
	 *
	 * @param array $colors Hex colors keyed by token name.
	 */
	public function build_override_css( array $colors ): string {
		$declarations = '';

		foreach ( array( 'primary', 'surface', 'border' ) as $name ) {
			$value = $colors[ $name ] ?? null;

			if ( is_string( $value ) && $this->is_hex_color( $value ) ) {
				$declarations .= '--lunar-dl-color-' . $name . ':' . $value . ';';
			}

			if ( 'primary' === $name && is_string( $value ) && $this->is_hex_color( $value ) ) {
				$declarations .= '--lunar-dl-color-on-primary:' . $this->contrast_text_color( $value ) . ';';
				$declarations .= '--lunar-dl-button-bg:var(--lunar-dl-color-primary);';
				$declarations .= '--lunar-dl-button-color:var(--lunar-dl-color-on-primary);';
				$declarations .= '--lunar-dl-button-border:var(--lunar-dl-color-primary);';
			}
		}

		return '' === $declarations ? '' : ':root{' . $declarations . '}';
	}

	private function is_hex_color( string $value ): bool {
		// The D modifier stops "$" from matching before a trailing newline.
		return 1 === preg_match( '/^#(?:[0-9a-fA-F]{3}){1,2}$/D', $value );
	}

	/**
	 * Returns white or black, whichever has the higher WCAG contrast ratio
	 * against the given hex color.
	 */
	private function contrast_text_color( string $hex ): string {
		$hex = substr( $hex, 1 );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$channels = array_map(
			static function ( string $pair ): float {
				$value = hexdec( $pair ) / 255;

				return $value <= 0.03928 ? $value / 12.92 : ( ( $value + 0.055 ) / 1.055 ) ** 2.4;
			},
			str_split( $hex, 2 )
		);

		$luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
		$on_white  = 1.05 / ( $luminance + 0.05 );
		$on_black  = ( $luminance + 0.05 ) / 0.05;

		return $on_white >= $on_black ? '#ffffff' : '#000000';
	}
}
