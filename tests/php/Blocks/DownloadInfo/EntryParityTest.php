<?php

namespace LunarDownload\Tests\Blocks\DownloadInfo;

use LunarDownload\Blocks\DownloadInfo\EntryData;
use LunarDownload\Blocks\DownloadInfo\RenderContext;
use LunarDownload\Services\LinkPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Checks EntryData against the cases that the editor preview is also tested
 * against, so that the page and the preview cannot drift apart.
 */
class EntryParityTest extends TestCase {

	private static function fixture(): array {
		// A test fixture on disk, not a remote file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json = file_get_contents( dirname( __DIR__, 3 ) . '/fixtures/entry-cases.json' );

		return json_decode( (string) $json, true );
	}

	public static function case_provider(): array {
		$provided = array();

		foreach ( self::fixture()['cases'] as $case ) {
			$provided[ $case['name'] ] = array( $case['attributes'], $case['expected'] );
		}

		return $provided;
	}

	/**
	 * @dataProvider case_provider
	 */
	public function test_entry_data_gives_the_expected_entry( array $attributes, array $expected ): void {
		$fixture = self::fixture();
		$context = new RenderContext(
			$fixture['fields'],
			new LinkPolicy( $fixture['siteHost'], $fixture['ownDomains'] ),
			$fixture['defaultLinkLabel'],
			'Y-m-d'
		);

		$entry = EntryData::from_attributes( $attributes, $context );

		$this->assertSame(
			$expected,
			array(
				'name'     => $entry->name,
				'imageId'  => $entry->image_id,
				'imageAlt' => $entry->image_alt,
				'preset'   => $entry->preset,
				'rows'     => array_map(
					static fn( $row ) => array(
						'slug'  => $row['slug'],
						'label' => $row['label'],
						'value' => $row['value'],
						'type'  => $row['type'],
					),
					$entry->rows
				),
				'links'    => array_map(
					static fn( $link ) => array(
						'label'  => $link['label'],
						'url'    => $link['url'],
						'newTab' => $link['new_tab'],
					),
					$entry->links
				),
			)
		);
	}
}
