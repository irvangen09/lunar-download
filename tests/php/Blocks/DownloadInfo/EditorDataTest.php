<?php

namespace LunarDownload\Tests\Blocks\DownloadInfo;

use LunarDownload\Blocks\DownloadInfo\EditorData;
use PHPUnit\Framework\TestCase;

class EditorDataTest extends TestCase {

	private function settings(): array {
		return array(
			'fields'      => array(
				array(
					'slug'        => 'kredit',
					'label'       => 'Kredit',
					'type'        => 'text',
					'schema_role' => 'creator',
					'archived'    => false,
				),
				array(
					'slug'        => 'tipe-bus',
					'label'       => 'Tipe Bus',
					'type'        => 'select',
					'options'     => array( 'Yudistira HD', 'Sadewa SHD' ),
					'schema_role' => null,
					'archived'    => false,
				),
				array(
					'slug'        => 'lama',
					'label'       => 'Lama',
					'type'        => 'date',
					'schema_role' => 'date_modified',
					'archived'    => true,
				),
			),
			'own_domains' => array( 'example.com' ),
			'appearance'  => array( 'primary' => '#336699' ),
		);
	}

	public function test_fields_are_listed_in_order_with_archived_ones_flagged(): void {
		$data = EditorData::build( $this->settings(), 'https://example.test/settings', true );

		$this->assertSame( array( 'kredit', 'tipe-bus', 'lama' ), array_column( $data['fields'], 'slug' ) );
		$this->assertSame( array( false, false, true ), array_column( $data['fields'], 'archived' ) );
	}

	public function test_only_select_fields_carry_options(): void {
		$fields = EditorData::build( $this->settings(), '', true )['fields'];

		$this->assertArrayNotHasKey( 'options', $fields[0] );
		$this->assertSame( array( 'Yudistira HD', 'Sadewa SHD' ), $fields[1]['options'] );
		$this->assertArrayNotHasKey( 'options', $fields[2] );
	}

	public function test_nothing_but_what_the_editor_needs_is_sent(): void {
		$data = EditorData::build( $this->settings(), 'https://example.test/settings', true );

		$this->assertSame( array( 'fields', 'settingsUrl' ), array_keys( $data ) );
		$this->assertSame( array( 'slug', 'label', 'type', 'archived' ), array_keys( $data['fields'][0] ) );
	}

	public function test_the_settings_link_is_only_for_users_who_can_manage(): void {
		$this->assertSame( 'https://example.test/settings', EditorData::build( $this->settings(), 'https://example.test/settings', true )['settingsUrl'] );
		$this->assertSame( '', EditorData::build( $this->settings(), 'https://example.test/settings', false )['settingsUrl'] );
	}

	public function test_no_fields_gives_an_empty_list(): void {
		$this->assertSame( array(), EditorData::build( array(), '', false )['fields'] );
		$this->assertSame( array(), EditorData::build( array( 'fields' => array() ), '', false )['fields'] );
	}
}
