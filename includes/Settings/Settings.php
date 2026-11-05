<?php

namespace LunarDownload\Settings;

use LunarDownload\Services\FieldRegistry;

class Settings {

	public const OPTION = 'lunar_download_settings';

	private FieldRegistry $registry;

	public function __construct( ?FieldRegistry $registry = null ) {
		$this->registry = $registry ?? new FieldRegistry();
	}

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
				'sanitize_callback' => array( $this, 'sanitize_for_save' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'fields'      => array(
								'type'  => 'array',
								'items' => $this->field_schema(),
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
	 * Sanitizes a value that is about to be stored, keeping the fields that are already stored.
	 *
	 * @param mixed $value Raw option value.
	 */
	public function sanitize_for_save( $value ): array {
		return $this->sanitize( $value, get_option( self::OPTION, array() ) );
	}

	/**
	 * Returns a value containing only known keys and valid entries.
	 *
	 * The own_domains list stays empty: its entries are not accepted without
	 * validation.
	 *
	 * @param mixed $value    Raw option value.
	 * @param mixed $previous Raw value that is currently stored.
	 */
	public function sanitize( $value, $previous = array() ): array {
		$value      = is_array( $value ) ? $value : array();
		$previous   = is_array( $previous ) ? $previous : array();
		$settings   = $this->defaults();
		$appearance = is_array( $value['appearance'] ?? null ) ? $value['appearance'] : array();

		$settings['fields'] = $this->registry->sanitize( $value['fields'] ?? array(), $previous['fields'] ?? array() );

		foreach ( array_keys( $settings['appearance'] ) as $key ) {
			$color = $appearance[ $key ] ?? '';

			// trim() because sanitize_hex_color() lets a trailing newline through.
			$settings['appearance'][ $key ] = is_string( $color ) ? (string) sanitize_hex_color( trim( $color ) ) : '';
		}

		return $settings;
	}

	public function get(): array {
		return $this->sanitize( get_option( self::OPTION, array() ) );
	}

	/**
	 * Returns the color overrides that are set, keyed by token name (primary, surface, border).
	 */
	public function get_color_overrides(): array {
		return array_filter( $this->get()['appearance'] );
	}

	// WordPress rejects any property that the REST schema does not declare.
	private function field_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'slug'        => array( 'type' => 'string' ),
				'label'       => array( 'type' => 'string' ),
				'type'        => array( 'type' => 'string' ),
				'options'     => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'schema_role' => array( 'type' => array( 'string', 'null' ) ),
				'archived'    => array( 'type' => 'boolean' ),
			),
		);
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
