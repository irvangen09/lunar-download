<?php

namespace LunarDownload\Tests\Services;

use LunarDownload\Services\DomainList;
use PHPUnit\Framework\TestCase;

class DomainListTest extends TestCase {

	private DomainList $list;

	protected function setUp(): void {
		$this->list = new DomainList();
	}

	/**
	 * @dataProvider valid_input_provider
	 */
	public function test_valid_input_is_reduced_to_a_host( string $input, string $expected ): void {
		$this->assertSame( $expected, $this->list->normalize( $input ) );
	}

	public static function valid_input_provider(): array {
		return array(
			'plain domain'       => array( 'example.com', 'example.com' ),
			'uppercase'          => array( 'Example.COM', 'example.com' ),
			'surrounding spaces' => array( '  example.com  ', 'example.com' ),
			'www prefix'         => array( 'www.example.com', 'example.com' ),
			'full url'           => array( 'https://www.example.com/path/page.html?x=1#top', 'example.com' ),
			'http with port'     => array( 'http://example.com:8080/', 'example.com' ),
			'port only'          => array( 'example.com:8080', 'example.com' ),
			'protocol relative'  => array( '//example.com/a', 'example.com' ),
			'wildcard'           => array( '*.example.com', 'example.com' ),
			'leading dot'        => array( '.example.com', 'example.com' ),
			'trailing dot'       => array( 'example.com.', 'example.com' ),
			'trailing slash'     => array( 'example.com/', 'example.com' ),
			'query on a domain'  => array( 'example.com?x=1', 'example.com' ),
			'subdomain'          => array( 'files.example.co.id', 'files.example.co.id' ),
			'punycode'           => array( 'xn--bcher-kva.example', 'xn--bcher-kva.example' ),
			'numeric label'      => array( '123.example.com', '123.example.com' ),
			'hyphen inside'      => array( 'my-site.example.com', 'my-site.example.com' ),
		);
	}

	/**
	 * @dataProvider invalid_input_provider
	 */
	public function test_invalid_input_gives_an_empty_string( string $input ): void {
		$this->assertSame( '', $this->list->normalize( $input ) );
	}

	public static function invalid_input_provider(): array {
		return array(
			'empty'             => array( '' ),
			'blank'             => array( '   ' ),
			'no dot'            => array( 'localhost' ),
			'top level only'    => array( 'com' ),
			'scheme only'       => array( 'https://' ),
			'www only'          => array( 'www.' ),
			'space inside'      => array( 'exa mple.com' ),
			'tab inside'        => array( "exa\tmple.com" ),
			'user info'         => array( 'user@example.com' ),
			'user and password' => array( 'https://user:pass@example.com' ),
			'ip literal'        => array( '[::1]' ),
			'non ascii'         => array( 'bücher.de' ),
			'underscore'        => array( 'exa_mple.com' ),
			'leading hyphen'    => array( '-example.com' ),
			'trailing hyphen'   => array( 'example-.com' ),
			'empty label'       => array( 'example..com' ),
			'long label'        => array( str_repeat( 'a', 64 ) . '.com' ),
			'too long overall'  => array( implode( '.', array_fill( 0, 30, str_repeat( 'a', 9 ) ) ) . '.com' ),
			'mailto'            => array( 'mailto:someone@example.com' ),
			'javascript'        => array( 'javascript:alert(1)' ),
			'path only'         => array( '/downloads' ),
		);
	}

	public function test_sanitize_keeps_order_and_removes_repeats_after_normalizing(): void {
		$result = $this->list->sanitize(
			array(
				'files.example.com',
				'https://www.Example.com/a',
				'example.com',
				'WWW.EXAMPLE.COM',
				'other.net',
			)
		);

		$this->assertSame( array( 'files.example.com', 'example.com', 'other.net' ), $result );
	}

	public function test_sanitize_drops_invalid_lines_and_non_strings(): void {
		$result = $this->list->sanitize( array( 'example.com', 'nope', '', 12, null, array( 'x.com' ), 'other.net' ) );

		$this->assertSame( array( 'example.com', 'other.net' ), $result );
	}

	/**
	 * @dataProvider non_array_values
	 *
	 * @param mixed $value Raw value.
	 */
	public function test_sanitize_gives_an_empty_list_for_anything_but_an_array( $value ): void {
		$this->assertSame( array(), $this->list->sanitize( $value ) );
	}

	public static function non_array_values(): array {
		return array(
			'null'   => array( null ),
			'string' => array( "example.com\nother.net" ),
			'number' => array( 5 ),
		);
	}

	public function test_sanitize_keeps_at_most_one_hundred_domains(): void {
		$domains = array_map( static fn( $number ) => 'site' . $number . '.example.com', range( 1, 120 ) );

		$result = $this->list->sanitize( $domains );

		$this->assertCount( 100, $result );
		$this->assertSame( 'site1.example.com', $result[0] );
		$this->assertSame( 'site100.example.com', $result[99] );
	}
}
