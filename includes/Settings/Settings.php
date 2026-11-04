<?php

namespace LunarDownload\Settings;

class Settings {

	public const OPTION = 'lunar_download_settings';

	public function register(): void {
		add_action( 'init', array( $this, 'register_option' ) );
	}

	// Runs on init so the REST settings endpoint can see the option.
	public function register_option(): void {
		$defaults = $this->defaults();

		$color_properties = array();
		foreach ( array_keys( $defaults['appearance'] ) as $key ) {
			// An empty string clears the override.
			$color_properties[ $key ] = array(
				'type'    => 'string',
				'pattern' => '^(#([0-9a-fA-F]{3}){1,2})?$',
			);
		}

		register_setting(
			'lunar_download',
			self::OPTION,
			array(
				'type'              => 'object',
				'default'           => $defaults,
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'fields'      => array(
								'type'  => 'array',
								'items' => array( 'type' => 'object' ),
							),
							'own_domains' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'appearance'  => array(
								'type'                 => 'object',
								'additionalProperties' => false,
								'properties'           => $color_properties,
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Returns a value containing only known keys and valid entries.
	 *
	 * The fields and own_domains lists stay empty: their entries are not
	 * accepted without validation.
	 *
	 * @param mixed $value Raw option value.
	 */
	public function sanitize( $value ): array {
		$value      = is_array( $value ) ? $value : array();
		$settings   = $this->defaults();
		$appearance = is_array( $value['appearance'] ?? null ) ? $value['appearance'] : array();

		foreach ( array_keys( $settings['appearance'] ) as $key ) {
			$color = $appearance[ $key ] ?? '';

			// trim() because sanitize_hex_color() lets a trailing newline through.
			$settings['appearance'][ $key ] = is_string( $color ) ? (string) sanitize_hex_color( trim( $color ) ) : '';
		}

		return $settings;
	}

	private function defaults(): array {
		return array(
			'fields'      => array(),
			'own_domains' => array(),
			'appearance'  => array(
				'primary' => '',
				'surface' => '',
				'border'  => '',
			),
		);
	}
}
