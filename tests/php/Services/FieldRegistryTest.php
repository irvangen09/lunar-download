<?php

namespace LunarDownload\Tests\Services;

use LunarDownload\Services\FieldRegistry;
use PHPUnit\Framework\TestCase;

class FieldRegistryTest extends TestCase {

	private FieldRegistry $registry;

	protected function setUp(): void {
		$this->registry = new FieldRegistry();
	}

	private function text_field( string $slug, string $label = 'Kredit' ): array {
		return array(
			'slug'        => $slug,
			'label'       => $label,
			'type'        => 'text',
			'schema_role' => null,
			'archived'    => false,
		);
	}

	/**
	 * @dataProvider non_array_values
	 *
	 * @param mixed $value Raw value.
	 */
	public function test_non_array_input_gives_an_empty_list( $value ): void {
		$this->assertSame( array(), $this->registry->sanitize( $value, $value ) );
	}

	public static function non_array_values(): array {
		return array(
			'null'   => array( null ),
			'string' => array( 'text' ),
			'number' => array( 42 ),
			'false'  => array( false ),
		);
	}

	public function test_valid_text_field_is_normalized(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label'       => 'Kredit',
					'type'        => 'text',
					'schema_role' => 'creator',
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'slug'        => 'kredit',
					'label'       => 'Kredit',
					'type'        => 'text',
					'schema_role' => 'creator',
					'archived'    => false,
				),
			),
			$result
		);
	}

	public function test_select_field_keeps_clean_unique_options_in_order(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label'   => 'Tipe Bus',
					'type'    => 'select',
					'options' => array( ' Yudistira HD ', 'Sadewa SHD', '', '   ', 5, array( 'x' ), 'Sadewa SHD', "Srikandi\nSHD" ),
				),
			)
		);

		$this->assertSame( array( 'Yudistira HD', 'Sadewa SHD', 'Srikandi SHD' ), $result[0]['options'] );
	}

	public function test_select_without_options_gets_an_empty_list_and_other_types_get_none(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label' => 'Tipe Bus',
					'type'  => 'select',
				),
				array(
					'label'   => 'Kredit',
					'type'    => 'text',
					'options' => array( 'ignored' ),
				),
			)
		);

		$this->assertSame( array(), $result[0]['options'] );
		$this->assertArrayNotHasKey( 'options', $result[1] );
	}

	public function test_options_are_limited_in_count_and_length(): void {
		$options   = array_map( static fn( $number ) => 'Option ' . $number, range( 1, 130 ) );
		$options[] = str_repeat( 'é', 250 );

		$result = $this->registry->sanitize(
			array(
				array(
					'label'   => 'Many',
					'type'    => 'select',
					'options' => array_merge( array( str_repeat( 'é', 250 ) ), $options ),
				),
			)
		);

		$this->assertCount( 100, $result[0]['options'] );
		$this->assertSame( str_repeat( 'é', 200 ), $result[0]['options'][0] );
	}

	/**
	 * @dataProvider invalid_entries
	 *
	 * @param mixed $entry Raw definition that cannot be repaired.
	 */
	public function test_unrepairable_entries_are_dropped( $entry ): void {
		$this->assertSame( array(), $this->registry->sanitize( array( $entry ) ) );
	}

	public static function invalid_entries(): array {
		return array(
			'not an array'      => array( 'kredit' ),
			'no label'          => array( array( 'type' => 'text' ) ),
			'blank label'       => array(
				array(
					'label' => '   ',
					'type'  => 'text',
				),
			),
			'label not string'  => array(
				array(
					'label' => 12,
					'type'  => 'text',
				),
			),
			'invalid utf-8'     => array(
				array(
					'label' => "Kre\xffdit",
					'type'  => 'text',
				),
			),
			'no type'           => array( array( 'label' => 'Kredit' ) ),
			'unknown type'      => array(
				array(
					'label' => 'Kredit',
					'type'  => 'number',
				),
			),
			'type not a string' => array(
				array(
					'label' => 'Kredit',
					'type'  => array( 'text' ),
				),
			),
		);
	}

	public function test_label_is_single_line_and_cut_to_sixty_characters(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label' => "  Line one\nline two  ",
					'type'  => 'text',
				),
				array(
					'label' => str_repeat( 'é', 70 ),
					'type'  => 'text',
				),
			)
		);

		$this->assertSame( 'Line one line two', $result[0]['label'] );
		$this->assertSame( str_repeat( 'é', 60 ), $result[1]['label'] );
	}

	/**
	 * @dataProvider role_cases
	 */
	public function test_schema_role_must_match_the_field_type( string $type, $role, ?string $expected ): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label'       => 'Field',
					'type'        => $type,
					'schema_role' => $role,
				),
			)
		);

		$this->assertSame( $expected, $result[0]['schema_role'] );
	}

	public static function role_cases(): array {
		return array(
			'creator on text'          => array( 'text', 'creator', 'creator' ),
			'creator on select'        => array( 'select', 'creator', 'creator' ),
			'creator on date'          => array( 'date', 'creator', null ),
			'file size on text'        => array( 'text', 'file_size', 'file_size' ),
			'file size on select'      => array( 'select', 'file_size', null ),
			'version on select'        => array( 'select', 'version', 'version' ),
			'date modified on date'    => array( 'date', 'date_modified', 'date_modified' ),
			'date modified on text'    => array( 'text', 'date_modified', null ),
			'date published on date'   => array( 'date', 'date_published', 'date_published' ),
			'date published on select' => array( 'select', 'date_published', null ),
			'unknown role'             => array( 'text', 'price', null ),
			'role not a string'        => array( 'text', array( 'creator' ), null ),
			'null role'                => array( 'text', null, null ),
		);
	}

	public function test_only_the_first_active_field_keeps_a_role(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label'       => 'Archived credit',
					'type'        => 'text',
					'schema_role' => 'creator',
					'archived'    => true,
				),
				array(
					'label'       => 'Credit',
					'type'        => 'text',
					'schema_role' => 'creator',
				),
				array(
					'label'       => 'Author',
					'type'        => 'text',
					'schema_role' => 'creator',
				),
			)
		);

		$this->assertSame( 'creator', $result[0]['schema_role'] );
		$this->assertSame( 'creator', $result[1]['schema_role'] );
		$this->assertNull( $result[2]['schema_role'] );
	}

	public function test_archived_is_true_only_for_a_real_boolean(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'label'    => 'One',
					'type'     => 'text',
					'archived' => true,
				),
				array(
					'label'    => 'Two',
					'type'     => 'text',
					'archived' => 'true',
				),
				array(
					'label'    => 'Three',
					'type'     => 'text',
					'archived' => 1,
				),
			)
		);

		$this->assertSame( array( true, false, false ), array_column( $result, 'archived' ) );
	}

	public function test_a_valid_unused_slug_is_kept_and_anything_else_is_regenerated(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'slug'  => 'kredit',
					'label' => 'Credit',
					'type'  => 'text',
				),
				array(
					'slug'  => 'kredit',
					'label' => 'Credit again',
					'type'  => 'text',
				),
				array(
					'slug'  => 'Bad Slug!',
					'label' => 'Ukuran',
					'type'  => 'text',
				),
				array(
					'slug'  => str_repeat( 'a', 41 ),
					'label' => 'Long',
					'type'  => 'text',
				),
				array(
					'slug'  => 42,
					'label' => 'Ukuran',
					'type'  => 'text',
				),
			)
		);

		$this->assertSame( array( 'kredit', 'credit-again', 'ukuran', 'long', 'ukuran-2' ), array_column( $result, 'slug' ) );
	}

	/**
	 * @dataProvider slug_cases
	 */
	public function test_slug_is_built_from_the_label( string $label, string $expected ): void {
		$this->assertSame( $expected, $this->registry->make_slug( $label ) );
	}

	public static function slug_cases(): array {
		return array(
			'plain word'        => array( 'Kredit', 'kredit' ),
			'two words'         => array( 'Tipe Bus', 'tipe-bus' ),
			'punctuation'       => array( 'Ukuran File (MB)', 'ukuran-file-mb' ),
			'edge separators'   => array( '  --Hello__World--  ', 'hello-world' ),
			'digits'            => array( 'Version 2', 'version-2' ),
			'lowercase accents' => array( 'Crème Brûlée', 'creme-brulee' ),
			'uppercase accents' => array( 'ÄRGER ÖL', 'arger-ol' ),
			'sharp s'           => array( 'Straße', 'strasse' ),
			'ligature'          => array( 'Æon', 'aeon' ),
			'non latin only'    => array( '日本語', 'field' ),
			'empty'             => array( '', 'field' ),
			'symbols only'      => array( '!!!', 'field' ),
			'long label'        => array( str_repeat( 'a', 60 ), str_repeat( 'a', 40 ) ),
			'cut at a hyphen'   => array( str_repeat( 'a', 39 ) . ' b', str_repeat( 'a', 39 ) ),
		);
	}

	public function test_taken_slugs_get_a_numbered_suffix_within_the_length_limit(): void {
		$this->assertSame( 'tipe-bus-2', $this->registry->make_slug( 'Tipe Bus', array( 'tipe-bus' ) ) );
		$this->assertSame( 'tipe-bus-3', $this->registry->make_slug( 'Tipe Bus', array( 'tipe-bus', 'tipe-bus-2' ) ) );
		$this->assertSame( 'field-2', $this->registry->make_slug( '日本語', array( 'field' ) ) );
		$this->assertSame( str_repeat( 'a', 38 ) . '-2', $this->registry->make_slug( str_repeat( 'a', 60 ), array( str_repeat( 'a', 40 ) ) ) );
	}

	public function test_a_known_field_keeps_its_slug_and_type_but_can_change_label_and_role(): void {
		$previous = array( $this->text_field( 'kredit' ) );

		$result = $this->registry->sanitize(
			array(
				array(
					'slug'        => 'kredit',
					'label'       => 'Pembuat',
					'type'        => 'select',
					'schema_role' => 'creator',
					'archived'    => true,
				),
			),
			$previous
		);

		$this->assertSame(
			array(
				array(
					'slug'        => 'kredit',
					'label'       => 'Pembuat',
					'type'        => 'text',
					'schema_role' => 'creator',
					'archived'    => true,
				),
			),
			$result
		);
	}

	public function test_a_known_field_with_a_blank_label_keeps_the_stored_label(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'slug'  => 'kredit',
					'label' => '  ',
					'type'  => 'text',
				),
			),
			array( $this->text_field( 'kredit', 'Kredit' ) )
		);

		$this->assertSame( 'Kredit', $result[0]['label'] );
	}

	public function test_known_fields_missing_from_the_new_list_are_kept_after_it(): void {
		$previous = array( $this->text_field( 'kredit' ), $this->text_field( 'ukuran', 'Ukuran' ), $this->text_field( 'versi', 'Versi' ) );

		$result = $this->registry->sanitize(
			array(
				array(
					'label' => 'Tanggal',
					'type'  => 'date',
				),
				array(
					'slug'  => 'ukuran',
					'label' => 'Ukuran',
					'type'  => 'text',
				),
			),
			$previous
		);

		$this->assertSame( array( 'tanggal', 'ukuran', 'kredit', 'versi' ), array_column( $result, 'slug' ) );
	}

	public function test_an_empty_or_invalid_new_list_keeps_every_known_field(): void {
		$previous = array( $this->text_field( 'kredit' ), $this->text_field( 'ukuran', 'Ukuran' ) );

		$this->assertSame( array( 'kredit', 'ukuran' ), array_column( $this->registry->sanitize( array(), $previous ), 'slug' ) );
		$this->assertSame( array( 'kredit', 'ukuran' ), array_column( $this->registry->sanitize( null, $previous ), 'slug' ) );
	}

	public function test_renaming_a_slug_adds_a_new_field_and_keeps_the_old_one(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'slug'  => 'credit',
					'label' => 'Kredit',
					'type'  => 'text',
				),
			),
			array( $this->text_field( 'kredit' ) )
		);

		$this->assertSame( array( 'credit', 'kredit' ), array_column( $result, 'slug' ) );
	}

	public function test_a_repeated_known_slug_in_the_new_list_is_used_once(): void {
		$entry = array(
			'slug'  => 'kredit',
			'label' => 'Kredit',
			'type'  => 'text',
		);

		$result = $this->registry->sanitize( array( $entry, $entry ), array( $this->text_field( 'kredit' ) ) );

		$this->assertCount( 1, $result );
	}

	public function test_known_select_keeps_stored_options_when_none_are_sent(): void {
		$previous = array(
			array(
				'slug'        => 'tipe-bus',
				'label'       => 'Tipe Bus',
				'type'        => 'select',
				'options'     => array( 'Yudistira HD' ),
				'schema_role' => null,
				'archived'    => false,
			),
		);

		$kept = $this->registry->sanitize(
			array(
				array(
					'slug'  => 'tipe-bus',
					'label' => 'Tipe Bus',
				),
			),
			$previous
		);
		$cut  = $this->registry->sanitize(
			array(
				array(
					'slug'    => 'tipe-bus',
					'label'   => 'Tipe Bus',
					'options' => array( 'Sadewa SHD' ),
				),
			),
			$previous
		);

		$this->assertSame( array( 'Yudistira HD' ), $kept[0]['options'] );
		$this->assertSame( array( 'Sadewa SHD' ), $cut[0]['options'] );
	}

	public function test_invalid_stored_fields_do_not_lock_anything(): void {
		$result = $this->registry->sanitize(
			array(
				array(
					'slug'  => 'kredit',
					'label' => 'Kredit',
					'type'  => 'text',
				),
			),
			array( 'junk', array( 'slug' => 'kredit' ) )
		);

		$this->assertSame( array( 'kredit' ), array_column( $result, 'slug' ) );
	}
}
