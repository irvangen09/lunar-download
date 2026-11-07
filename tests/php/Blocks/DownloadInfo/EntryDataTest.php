<?php

namespace LunarDownload\Tests\Blocks\DownloadInfo;

use LunarDownload\Blocks\DownloadInfo\EntryData;
use LunarDownload\Blocks\DownloadInfo\RenderContext;
use LunarDownload\Services\LinkPolicy;
use PHPUnit\Framework\TestCase;

class EntryDataTest extends TestCase {

	private RenderContext $context;

	protected function setUp(): void {
		$fields = array(
			array(
				'slug'        => 'kredit',
				'label'       => 'Kredit',
				'type'        => 'text',
				'schema_role' => 'creator',
				'archived'    => false,
			),
			array(
				'slug'        => 'tanggal',
				'label'       => 'Tanggal',
				'type'        => 'date',
				'schema_role' => 'date_modified',
				'archived'    => false,
			),
			array(
				'slug'        => 'lama',
				'label'       => 'Lama',
				'type'        => 'select',
				'schema_role' => null,
				'archived'    => true,
			),
		);

		$this->context = new RenderContext( $fields, new LinkPolicy( 'example.com' ), 'Download', 'Y-m-d' );
	}

	private function entry( array $attributes ): EntryData {
		return EntryData::from_attributes( $attributes, $this->context );
	}

	public function test_empty_attributes_give_an_empty_entry(): void {
		$entry = $this->entry( array() );

		$this->assertSame( '', $entry->name );
		$this->assertSame( 0, $entry->image_id );
		$this->assertSame( '', $entry->image_alt );
		$this->assertSame( 'table', $entry->preset );
		$this->assertSame( array(), $entry->rows );
		$this->assertSame( array(), $entry->links );
	}

	public function test_name_and_alt_are_cleaned_and_limited(): void {
		$entry = $this->entry(
			array(
				'name'     => "  Aneka\nBintang  ",
				'imageAlt' => str_repeat( 'a', 300 ),
			)
		);

		$this->assertSame( 'Aneka Bintang', $entry->name );
		$this->assertSame( str_repeat( 'a', 200 ), $entry->image_alt );
	}

	/**
	 * @dataProvider image_id_provider
	 *
	 * @param mixed $value    Raw attachment ID.
	 * @param int   $expected Clean ID.
	 */
	public function test_image_id_is_a_non_negative_integer( $value, int $expected ): void {
		$this->assertSame( $expected, $this->entry( array( 'imageId' => $value ) )->image_id );
	}

	public static function image_id_provider(): array {
		return array(
			'integer'       => array( 12, 12 ),
			'digit string'  => array( '12', 12 ),
			'zero'          => array( 0, 0 ),
			'negative'      => array( -5, 0 ),
			'negative text' => array( '-5', 0 ),
			'text'          => array( 'abc', 0 ),
			'float'         => array( 1.5, 0 ),
			'array'         => array( array( 1 ), 0 ),
			'null'          => array( null, 0 ),
		);
	}

	public function test_known_presets_are_kept_and_anything_else_is_table(): void {
		foreach ( array( 'table', 'card', 'compact', 'detail' ) as $preset ) {
			$this->assertSame( $preset, $this->entry( array( 'preset' => $preset ) )->preset );
		}

		foreach ( array( 'Card', 'list', '', null, 5, array( 'card' ) ) as $preset ) {
			$this->assertSame( 'table', $this->entry( array( 'preset' => $preset ) )->preset );
		}
	}

	public function test_a_field_row_takes_label_type_and_role_from_the_registry(): void {
		$entry = $this->entry(
			array(
				'rows' => array(
					array(
						'field' => 'kredit',
						'value' => ' BlahBloh ',
					),
					array(
						'field' => 'tanggal',
						'value' => '2026-10-30',
					),
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'slug'  => 'kredit',
					'label' => 'Kredit',
					'value' => 'BlahBloh',
					'type'  => 'text',
					'role'  => 'creator',
				),
				array(
					'slug'  => 'tanggal',
					'label' => 'Tanggal',
					'value' => '2026-10-30',
					'type'  => 'date',
					'role'  => 'date_modified',
				),
			),
			$entry->rows
		);
	}

	public function test_an_archived_field_still_resolves(): void {
		$row = $this->entry(
			array(
				'rows' => array(
					array(
						'field' => 'lama',
						'value' => 'X',
					),
				),
			)
		)->rows[0];

		$this->assertSame( 'Lama', $row['label'] );
		$this->assertSame( 'select', $row['type'] );
	}

	public function test_an_unknown_field_is_shown_under_its_slug_without_a_role(): void {
		$row = $this->entry(
			array(
				'rows' => array(
					array(
						'field' => 'hilang',
						'value' => 'X',
					),
				),
			)
		)->rows[0];

		$this->assertSame( 'hilang', $row['label'] );
		$this->assertSame( 'text', $row['type'] );
		$this->assertNull( $row['role'] );
	}

	public function test_a_custom_row_has_no_slug_type_or_role(): void {
		$row = $this->entry(
			array(
				'rows' => array(
					array(
						'label' => ' Catatan ',
						'value' => 'Hanya sekali',
					),
				),
			)
		)->rows[0];

		$this->assertSame(
			array(
				'slug'  => null,
				'label' => 'Catatan',
				'value' => 'Hanya sekali',
				'type'  => 'custom',
				'role'  => null,
			),
			$row
		);
	}

	public function test_the_field_wins_when_a_row_has_both_a_field_and_a_label(): void {
		$row = $this->entry(
			array(
				'rows' => array(
					array(
						'field' => 'kredit',
						'label' => 'Other',
						'value' => 'X',
					),
				),
			)
		)->rows[0];

		$this->assertSame( 'Kredit', $row['label'] );
	}

	public function test_rows_that_cannot_be_shown_are_dropped(): void {
		$entry = $this->entry(
			array(
				'rows' => array(
					'text',
					array(
						'field' => 'kredit',
						'value' => '   ',
					),
					array(
						'field' => 'kredit',
					),
					array( 'value' => 'No label' ),
					array(
						'label' => '  ',
						'value' => 'Blank label',
					),
					array(
						'field' => 'kredit',
						'value' => 12,
					),
					array(
						'field' => 'kredit',
						'value' => 'Kept',
					),
				),
			)
		);

		$this->assertCount( 1, $entry->rows );
		$this->assertSame( 'Kept', $entry->rows[0]['value'] );
	}

	public function test_row_text_is_limited(): void {
		$entry = $this->entry(
			array(
				'rows' => array(
					array(
						'label' => str_repeat( 'l', 80 ),
						'value' => str_repeat( 'v', 300 ),
					),
				),
			)
		);

		$this->assertSame( str_repeat( 'l', 60 ), $entry->rows[0]['label'] );
		$this->assertSame( str_repeat( 'v', 200 ), $entry->rows[0]['value'] );
	}

	/**
	 * @dataProvider not_a_list
	 *
	 * @param mixed $value Raw value.
	 */
	public function test_rows_and_links_that_are_not_lists_give_empty_lists( $value ): void {
		$entry = $this->entry(
			array(
				'rows'  => $value,
				'links' => $value,
			)
		);

		$this->assertSame( array(), $entry->rows );
		$this->assertSame( array(), $entry->links );
	}

	public static function not_a_list(): array {
		return array(
			'string' => array( 'text' ),
			'null'   => array( null ),
			'number' => array( 3 ),
		);
	}

	public function test_links_get_rel_and_the_default_label(): void {
		$entry = $this->entry(
			array(
				'links' => array(
					array( 'url' => 'https://drive.google.com/file/d/1' ),
					array(
						'label'  => ' Bersih ',
						'url'    => 'https://example.com/a',
						'newTab' => true,
					),
					array(
						'label'   => 'Kotor',
						'url'     => 'https://drive.google.com/file/d/2',
						'relMode' => 'follow',
					),
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'label'   => 'Download',
					'url'     => 'https://drive.google.com/file/d/1',
					'rel'     => 'nofollow',
					'new_tab' => false,
				),
				array(
					'label'   => 'Bersih',
					'url'     => 'https://example.com/a',
					'rel'     => 'noopener',
					'new_tab' => true,
				),
				array(
					'label'   => 'Kotor',
					'url'     => 'https://drive.google.com/file/d/2',
					'rel'     => '',
					'new_tab' => false,
				),
			),
			$entry->links
		);
	}

	public function test_new_tab_needs_a_real_boolean(): void {
		$links = $this->entry(
			array(
				'links' => array(
					array(
						'url'    => '/a',
						'newTab' => 'true',
					),
					array(
						'url'    => '/b',
						'newTab' => 1,
					),
				),
			)
		)->links;

		$this->assertFalse( $links[0]['new_tab'] );
		$this->assertFalse( $links[1]['new_tab'] );
	}

	public function test_links_that_cannot_be_shown_are_dropped(): void {
		$entry = $this->entry(
			array(
				'links' => array(
					'text',
					array( 'label' => 'No url' ),
					array( 'url' => '' ),
					array( 'url' => 'javascript:alert(1)' ),
					array( 'url' => 12 ),
					array( 'url' => '//evil.net/a' ),
					array( 'url' => '/kept' ),
				),
			)
		);

		$this->assertCount( 1, $entry->links );
		$this->assertSame( '/kept', $entry->links[0]['url'] );
	}

	public function test_a_rel_mode_that_is_not_a_string_acts_as_auto(): void {
		$link = $this->entry(
			array(
				'links' => array(
					array(
						'url'     => 'https://other.net/a',
						'relMode' => array( 'follow' ),
					),
				),
			)
		)->links[0];

		$this->assertSame( 'nofollow', $link['rel'] );
	}
}
