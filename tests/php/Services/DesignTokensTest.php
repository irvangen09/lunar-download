<?php

namespace LunarDownload\Tests\Services;

use LunarDownload\Services\DesignTokens;
use LunarDownload\Settings\Settings;
use PHPUnit\Framework\TestCase;

class DesignTokensTest extends TestCase {

	private DesignTokens $tokens;

	protected function setUp(): void {
		$this->tokens = new DesignTokens( new Settings() );
	}

	public function test_no_colors_produce_no_css(): void {
		$this->assertSame( '', $this->tokens->build_override_css( array() ) );
	}

	public function test_surface_and_border_override_only_their_tokens(): void {
		$css = $this->tokens->build_override_css(
			array(
				'surface' => '#eeeeee',
				'border'  => '#ccc',
			)
		);

		$this->assertSame( ':root{--lunar-dl-color-surface:#eeeeee;--lunar-dl-color-border:#ccc;}', $css );
	}

	public function test_primary_turns_buttons_solid_with_white_text_on_a_dark_color(): void {
		$css = $this->tokens->build_override_css( array( 'primary' => '#336699' ) );

		$this->assertSame(
			':root{--lunar-dl-color-primary:#336699;'
			. '--lunar-dl-color-on-primary:#ffffff;'
			. '--lunar-dl-button-bg:var(--lunar-dl-color-primary);'
			. '--lunar-dl-button-color:var(--lunar-dl-color-on-primary);'
			. '--lunar-dl-button-border:var(--lunar-dl-color-primary);}',
			$css
		);
	}

	/**
	 * @dataProvider primary_colors
	 *
	 * @param string $primary  Primary color.
	 * @param string $expected Expected text color on Primary.
	 */
	public function test_text_color_has_the_higher_contrast( string $primary, string $expected ): void {
		$css = $this->tokens->build_override_css( array( 'primary' => $primary ) );

		$this->assertStringContainsString( '--lunar-dl-color-on-primary:' . $expected . ';', $css );
	}

	public static function primary_colors(): array {
		return array(
			'black'          => array( '#000000', '#ffffff' ),
			'white'          => array( '#ffffff', '#000000' ),
			'short white'    => array( '#fff', '#000000' ),
			'blue'           => array( '#0000ff', '#ffffff' ),
			'yellow'         => array( '#ffff00', '#000000' ),
			'uppercase blue' => array( '#0000FF', '#ffffff' ),
		);
	}

	public function test_invalid_values_and_unknown_names_are_ignored(): void {
		$css = $this->tokens->build_override_css(
			array(
				'primary' => 'red',
				'surface' => '#fff;}body{display:none',
				'border'  => "#fff\n",
				'accent'  => '#000000',
				'text'    => 123,
			)
		);

		$this->assertSame( '', $css );
	}
}
