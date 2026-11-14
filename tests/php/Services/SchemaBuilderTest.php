<?php

namespace LunarDownload\Tests\Services;

use LunarDownload\Services\SchemaBuilder;
use PHPUnit\Framework\TestCase;

class SchemaBuilderTest extends TestCase {

	private SchemaBuilder $builder;

	protected function setUp(): void {
		$this->builder = new SchemaBuilder();
	}

	private function row( string $role, string $value, bool $archived = false ): array {
		return array(
			'label'    => 'Row',
			'value'    => $value,
			'type'     => 'text',
			'role'     => $role,
			'archived' => $archived,
		);
	}

	public function test_a_full_entry_becomes_a_creative_work_with_media_objects(): void {
		$node = $this->builder->build(
			array(
				'name'      => 'Aneka Bintang',
				'image_url' => 'https://example.test/uploads/livery.jpg',
				'rows'      => array(
					$this->row( 'creator', 'BlahBloh' ),
					$this->row( 'date_modified', '2026-10-30' ),
					$this->row( 'date_published', '2026-09-01' ),
					$this->row( 'version', 'v2' ),
					$this->row( 'file_size', '2,4 MB' ),
				),
				'links'     => array(
					array(
						'label' => 'Bersih',
						'url'   => 'https://drive.google.com/file/d/1',
					),
				),
			)
		);

		$this->assertSame(
			array(
				'@type'           => 'CreativeWork',
				'name'            => 'Aneka Bintang',
				'image'           => 'https://example.test/uploads/livery.jpg',
				'creator'         => 'BlahBloh',
				'dateModified'    => '2026-10-30',
				'datePublished'   => '2026-09-01',
				'version'         => 'v2',
				'associatedMedia' => array(
					array(
						'@type'       => 'MediaObject',
						'name'        => 'Bersih',
						'url'         => 'https://drive.google.com/file/d/1',
						'contentSize' => '2,4 MB',
					),
				),
			),
			$node
		);
	}

	public function test_an_entry_without_a_name_has_no_structured_data(): void {
		$this->assertNull(
			$this->builder->build(
				array(
					'links' => array(
						array(
							'label' => 'A',
							'url'   => '/a',
						),
					),
				)
			)
		);
		$this->assertNull( $this->builder->build( array( 'name' => '' ) ) );
		$this->assertNull( $this->builder->build( array( 'name' => 12 ) ) );
	}

	public function test_only_what_the_entry_has_is_added(): void {
		$this->assertSame(
			array(
				'@type' => 'CreativeWork',
				'name'  => 'Only a name',
			),
			$this->builder->build( array( 'name' => 'Only a name' ) )
		);
	}

	public function test_every_link_becomes_a_media_object_in_order(): void {
		$node = $this->builder->build(
			array(
				'name'  => 'X',
				'links' => array(
					array(
						'label' => 'Bersih',
						'url'   => 'https://a.test/1',
					),
					array(
						'label' => 'Kotor',
						'url'   => 'https://a.test/2',
					),
				),
			)
		);

		$this->assertSame( array( 'Bersih', 'Kotor' ), array_column( $node['associatedMedia'], 'name' ) );
		$this->assertSame( array( 'https://a.test/1', 'https://a.test/2' ), array_column( $node['associatedMedia'], 'url' ) );
		$this->assertSame( array( 'MediaObject', 'MediaObject' ), array_column( $node['associatedMedia'], '@type' ) );
	}

	public function test_the_file_size_is_only_added_for_a_single_link(): void {
		$rows = array( $this->row( 'file_size', '2,4 MB' ) );
		$one  = $this->builder->build(
			array(
				'name'  => 'X',
				'rows'  => $rows,
				'links' => array(
					array(
						'label' => 'A',
						'url'   => '/a',
					),
				),
			)
		);
		$two  = $this->builder->build(
			array(
				'name'  => 'X',
				'rows'  => $rows,
				'links' => array(
					array(
						'label' => 'A',
						'url'   => '/a',
					),
					array(
						'label' => 'B',
						'url'   => '/b',
					),
				),
			)
		);
		$none = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => $rows,
			)
		);

		$this->assertSame( '2,4 MB', $one['associatedMedia'][0]['contentSize'] );
		$this->assertArrayNotHasKey( 'contentSize', $two['associatedMedia'][0] );
		$this->assertArrayNotHasKey( 'contentSize', $two['associatedMedia'][1] );
		$this->assertArrayNotHasKey( 'associatedMedia', $none );
	}

	public function test_a_single_link_without_a_size_row_has_no_content_size(): void {
		$node = $this->builder->build(
			array(
				'name'  => 'X',
				'links' => array(
					array(
						'label' => 'A',
						'url'   => '/a',
					),
				),
			)
		);

		$this->assertArrayNotHasKey( 'contentSize', $node['associatedMedia'][0] );
	}

	public function test_links_without_an_address_are_left_out(): void {
		$node = $this->builder->build(
			array(
				'name'  => 'X',
				'links' => array(
					array(
						'label' => 'A',
						'url'   => '',
					),
					array( 'label' => 'B' ),
					'text',
					array(
						'label' => 'C',
						'url'   => 12,
					),
					array(
						'label' => 'Kept',
						'url'   => '/k',
					),
				),
			)
		);

		$this->assertSame( array( 'Kept' ), array_column( $node['associatedMedia'], 'name' ) );
	}

	public function test_rows_without_a_role_or_with_an_empty_value_add_nothing(): void {
		$node = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array(
					array(
						'label'    => 'Note',
						'value'    => 'x',
						'type'     => 'custom',
						'role'     => null,
						'archived' => false,
					),
					$this->row( 'creator', '' ),
					'text',
					array( 'role' => 'creator' ),
					array(
						'value' => 12,
						'role'  => 'creator',
					),
				),
			)
		);

		$this->assertArrayNotHasKey( 'creator', $node );
	}

	public function test_a_row_that_does_not_say_whether_it_is_archived_counts_as_active(): void {
		$node = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array(
					array(
						'value' => 'y',
						'role'  => 'creator',
					),
				),
			)
		);

		$this->assertSame( 'y', $node['creator'] );
	}

	public function test_a_row_of_an_active_field_wins_over_one_of_an_archived_field(): void {
		$archived_first = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array( $this->row( 'creator', 'Old', true ), $this->row( 'creator', 'New' ) ),
			)
		);
		$only_archived  = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array( $this->row( 'creator', 'Old', true ) ),
			)
		);

		$this->assertSame( 'New', $archived_first['creator'] );
		$this->assertSame( 'Old', $only_archived['creator'] );
	}

	public function test_the_first_row_wins_among_equals(): void {
		$node = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array( $this->row( 'version', 'one' ), $this->row( 'version', 'two' ) ),
			)
		);

		$this->assertSame( 'one', $node['version'] );
	}

	/**
	 * @dataProvider invalid_dates
	 */
	public function test_a_date_role_only_takes_a_real_date( string $value ): void {
		$node = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array( $this->row( 'date_modified', $value ) ),
			)
		);

		$this->assertArrayNotHasKey( 'dateModified', $node );
	}

	public static function invalid_dates(): array {
		return array(
			'words'            => array( 'soon' ),
			'impossible day'   => array( '2026-02-30' ),
			'impossible month' => array( '2026-13-01' ),
			'other format'     => array( '30/10/2026' ),
			'missing zeros'    => array( '2026-1-5' ),
		);
	}

	public function test_a_real_date_is_used_even_when_an_active_row_has_an_invalid_one(): void {
		$node = $this->builder->build(
			array(
				'name' => 'X',
				'rows' => array( $this->row( 'date_published', 'soon' ), $this->row( 'date_published', '2025-01-02', true ) ),
			)
		);

		$this->assertSame( '2025-01-02', $node['datePublished'] );
	}

	public function test_an_empty_image_address_is_left_out(): void {
		$node = $this->builder->build(
			array(
				'name'      => 'X',
				'image_url' => '',
			)
		);

		$this->assertArrayNotHasKey( 'image', $node );
	}

	public function test_the_properties_come_in_a_fixed_order(): void {
		$node = $this->builder->build(
			array(
				'name'      => 'X',
				'image_url' => 'https://a.test/i.jpg',
				'rows'      => array( $this->row( 'version', '1' ), $this->row( 'creator', 'C' ), $this->row( 'date_published', '2026-01-01' ), $this->row( 'date_modified', '2026-02-02' ) ),
				'links'     => array(
					array(
						'label' => 'A',
						'url'   => '/a',
					),
				),
			)
		);

		$this->assertSame(
			array( '@type', 'name', 'image', 'creator', 'dateModified', 'datePublished', 'version', 'associatedMedia' ),
			array_keys( $node )
		);
	}
}
