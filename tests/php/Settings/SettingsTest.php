<?php

namespace LunarDownload\Tests\Settings;

use LunarDownload\Settings\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

	private Settings $settings;

	protected function setUp(): void {
		$this->settings = new Settings();
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

	/**
	 * @dataProvider non_array_values
	 *
	 * @param mixed $value Raw option value.
	 */
	public function test_non_array_value_returns_defaults( $value ): void {
		$this->assertSame( $this->defaults(), $this->settings->sanitize( $value ) );
	}

	public static function non_array_values(): array {
		return array(
			'null'   => array( null ),
			'string' => array( 'text' ),
			'number' => array( 42 ),
			'false'  => array( false ),
		);
	}

	public function test_valid_hex_colors_are_kept(): void {
		$result = $this->settings->sanitize(
			array(
				'appearance' => array(
					'primary' => '#fff',
					'surface' => '#A1b2C3',
					'border'  => '#000000',
				),
			)
		);

		$this->assertSame( '#fff', $result['appearance']['primary'] );
		$this->assertSame( '#A1b2C3', $result['appearance']['surface'] );
		$this->assertSame( '#000000', $result['appearance']['border'] );
	}

	/**
	 * @dataProvider invalid_colors
	 *
	 * @param mixed $color Raw color value.
	 */
	public function test_invalid_colors_are_cleared( $color ): void {
		$result = $this->settings->sanitize( array( 'appearance' => array( 'primary' => $color ) ) );

		$this->assertSame( '', $result['appearance']['primary'] );
	}

	public static function invalid_colors(): array {
		return array(
			'name'       => array( 'red' ),
			'short'      => array( '#ff' ),
			'bad digit'  => array( '#ggg' ),
			'no hash'    => array( 'ffffff' ),
			'functional' => array( 'rgb(0,0,0)' ),
			'injection'  => array( '#fff;}body{display:none' ),
			'number'     => array( 123 ),
			'array'      => array( array( '#fff' ) ),
			'null'       => array( null ),
		);
	}

	public function test_surrounding_whitespace_and_newline_are_removed(): void {
		$result = $this->settings->sanitize( array( 'appearance' => array( 'primary' => " #fff\n" ) ) );

		$this->assertSame( '#fff', $result['appearance']['primary'] );
	}

	public function test_unknown_keys_are_dropped(): void {
		$result = $this->settings->sanitize(
			array(
				'extra'      => 'value',
				'appearance' => array(
					'primary' => '#fff',
					'extra'   => '#000',
				),
			)
		);

		$this->assertSame( array( 'fields', 'own_domains', 'appearance' ), array_keys( $result ) );
		$this->assertSame( array( 'primary', 'surface', 'border' ), array_keys( $result['appearance'] ) );
	}

	public function test_appearance_that_is_not_an_array_returns_defaults(): void {
		$this->assertSame( $this->defaults(), $this->settings->sanitize( array( 'appearance' => '#fff' ) ) );
	}

	public function test_valid_fields_are_kept_and_normalized(): void {
		$result = $this->settings->sanitize(
			array(
				'fields' => array(
					array(
						'label' => 'Kredit',
						'type'  => 'text',
					),
					array( 'slug' => 'no-label' ),
				),
			)
		);

		$this->assertCount( 1, $result['fields'] );
		$this->assertSame( 'kredit', $result['fields'][0]['slug'] );
	}

	public function test_stored_fields_survive_a_save_that_omits_them(): void {
		$stored = array(
			'fields' => array(
				array(
					'slug'  => 'kredit',
					'label' => 'Kredit',
					'type'  => 'text',
				),
			),
		);

		$result = $this->settings->sanitize( array( 'fields' => array() ), $stored );

		$this->assertSame( array( 'kredit' ), array_column( $result['fields'], 'slug' ) );
	}

	public function test_previous_value_that_is_not_an_array_is_ignored(): void {
		$this->assertSame( $this->defaults(), $this->settings->sanitize( array(), 'junk' ) );
	}

	public function test_own_domains_stay_empty(): void {
		$result = $this->settings->sanitize( array( 'own_domains' => array( 'example.com' ) ) );

		$this->assertSame( array(), $result['own_domains'] );
	}
}
