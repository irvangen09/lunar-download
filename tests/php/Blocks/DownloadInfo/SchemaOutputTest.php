<?php

namespace LunarDownload\Tests\Blocks\DownloadInfo;

use LunarDownload\Blocks\DownloadInfo\SchemaOutput;
use PHPUnit\Framework\TestCase;

class SchemaOutputTest extends TestCase {

	private function node( string $name = 'Aneka Bintang' ): array {
		return array(
			'@type' => 'CreativeWork',
			'name'  => $name,
		);
	}

	public function test_the_nodes_are_wrapped_in_one_graph(): void {
		$decoded = json_decode( SchemaOutput::json( array( $this->node( 'A' ), $this->node( 'B' ) ) ), true );

		$this->assertSame( 'https://schema.org', $decoded['@context'] );
		$this->assertSame( array( 'A', 'B' ), array_column( $decoded['@graph'], 'name' ) );
	}

	public function test_a_value_cannot_close_the_script_tag(): void {
		$json = SchemaOutput::json( array( $this->node( '</script><script>alert(1)</script> & "quoted"' ) ) );

		$this->assertStringNotContainsString( '</script', $json );
		$this->assertStringNotContainsString( '<', $json );
		$this->assertStringNotContainsString( '&', $json );
		$this->assertSame( '</script><script>alert(1)</script> & "quoted"', json_decode( $json, true )['@graph'][0]['name'] );
	}

	public function test_addresses_keep_their_slashes(): void {
		$json = SchemaOutput::json(
			array(
				array(
					'@type' => 'MediaObject',
					'url'   => 'https://drive.google.com/file/d/1',
				),
			)
		);

		$this->assertStringContainsString( 'https://drive.google.com/file/d/1', $json );
	}

	public function test_the_script_is_printed_once_with_the_ld_json_type(): void {
		$output = new SchemaOutput( fn() => array( $this->node( 'A' ), $this->node( 'B' ) ) );

		ob_start();
		$output->print_schema();
		$html = (string) ob_get_clean();

		$this->assertSame( 1, substr_count( $html, '<script' ) );
		$this->assertStringStartsWith( '<script type="application/ld+json">', $html );
		$this->assertStringContainsString( '"name":"A"', $html );
		$this->assertStringContainsString( '"name":"B"', $html );
	}

	public function test_nothing_is_printed_without_entries(): void {
		$output = new SchemaOutput( fn() => array() );

		ob_start();
		$output->print_schema();

		$this->assertSame( '', ob_get_clean() );
	}

	public function test_it_prints_in_the_footer(): void {
		$GLOBALS['lunar_download_test_actions'] = array();

		( new SchemaOutput() )->register();

		$this->assertArrayHasKey( 'wp_footer', $GLOBALS['lunar_download_test_actions'] );
	}
}
