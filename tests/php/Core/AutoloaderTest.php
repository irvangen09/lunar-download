<?php

namespace LunarDownload\Tests\Core;

use LunarDownload\Core\Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase {

	private Autoloader $autoloader;

	protected function setUp(): void {
		$this->autoloader = new Autoloader( 'LunarDownloadFixtures\\', dirname( __DIR__ ) . '/fixtures' );
		$this->autoloader->register();
	}

	protected function tearDown(): void {
		spl_autoload_unregister( array( $this->autoloader, 'load' ) );
	}

	public function test_loads_class_from_mapped_path(): void {
		$this->assertTrue( class_exists( 'LunarDownloadFixtures\Core\Marker' ) );
	}

	public function test_ignores_classes_outside_the_prefix(): void {
		$this->assertFalse( class_exists( 'OtherVendor\Core\Marker' ) );
	}

	public function test_prefix_must_match_a_whole_namespace_segment(): void {
		$this->assertFalse( class_exists( 'LunarDownloadFixturesExtra\Core\Marker' ) );
	}

	public function test_missing_file_leaves_class_unloaded(): void {
		$this->assertFalse( class_exists( 'LunarDownloadFixtures\Core\Missing' ) );
	}

	public function test_accepts_prefix_and_directory_with_trailing_separators(): void {
		$other = new Autoloader( 'LunarDownloadFixtures', dirname( __DIR__ ) . '/fixtures/' );
		$other->register();

		$this->assertTrue( class_exists( 'LunarDownloadFixtures\Services\Helper' ) );

		spl_autoload_unregister( array( $other, 'load' ) );
	}
}
