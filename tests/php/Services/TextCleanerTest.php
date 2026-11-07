<?php

namespace LunarDownload\Tests\Services;

use LunarDownload\Services\TextCleaner;
use PHPUnit\Framework\TestCase;

class TextCleanerTest extends TestCase {

	public function test_text_is_trimmed_and_made_single_line(): void {
		$this->assertSame( 'Line one line two', TextCleaner::single_line( "  Line one\nline two\t", 60 ) );
	}

	public function test_text_is_cut_by_characters_not_bytes(): void {
		$this->assertSame( str_repeat( 'é', 5 ), TextCleaner::single_line( str_repeat( 'é', 9 ), 5 ) );
	}

	public function test_trailing_space_left_by_the_cut_is_removed(): void {
		$this->assertSame( 'ab', TextCleaner::single_line( 'ab cd', 3 ) );
	}

	public function test_invalid_utf8_gives_an_empty_string(): void {
		$this->assertSame( '', TextCleaner::single_line( "Kre\xffdit", 60 ) );
	}

	/**
	 * @dataProvider non_string_values
	 *
	 * @param mixed $value Raw value.
	 */
	public function test_anything_but_a_string_gives_an_empty_string( $value ): void {
		$this->assertSame( '', TextCleaner::single_line( $value, 60 ) );
	}

	public static function non_string_values(): array {
		return array(
			'null'   => array( null ),
			'number' => array( 12 ),
			'array'  => array( array( 'a' ) ),
			'bool'   => array( true ),
		);
	}
}
