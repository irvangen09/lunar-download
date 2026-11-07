<?php

namespace LunarDownload\Tests\Blocks\DownloadInfo;

use LunarDownload\Blocks\DownloadInfo\Renderer;
use LunarDownload\Blocks\DownloadInfo\RenderContext;
use LunarDownload\Services\LinkPolicy;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase {

	private Renderer $renderer;

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
		);

		$context        = new RenderContext( $fields, new LinkPolicy( 'example.com', array( 'partner.net' ) ), 'Download', 'j F Y' );
		$this->renderer = new Renderer( $context );
	}

	private function render( array $attributes ): string {
		return $this->renderer->render( $attributes );
	}

	public function test_an_entry_has_rows_and_buttons_outside_the_list(): void {
		$html = $this->render(
			array(
				'name'  => 'Aneka Bintang',
				'rows'  => array(
					array(
						'field' => 'kredit',
						'value' => 'BlahBloh',
					),
					array(
						'label' => 'Catatan',
						'value' => 'Sekali pakai',
					),
				),
				'links' => array( array( 'url' => 'https://drive.google.com/file/d/1' ) ),
			)
		);

		$this->assertStringStartsWith( '<article class="wp-block-lunar-download-download-info lunar-dl-entry lunar-dl-entry--table">', $html );
		$this->assertStringContainsString( '<dl class="lunar-dl-entry__rows"><div class="lunar-dl-entry__row"><dt>Kredit</dt><dd>BlahBloh</dd></div><div class="lunar-dl-entry__row"><dt>Catatan</dt><dd>Sekali pakai</dd></div></dl>', $html );
		$this->assertGreaterThan( strpos( $html, '</dl>' ), strpos( $html, '<ul class="lunar-dl-entry__links">' ) );
		$this->assertStringEndsWith( '</ul></article>', $html );
	}

	public function test_an_entry_with_nothing_to_show_renders_nothing(): void {
		$this->assertSame( '', $this->render( array() ) );
		$this->assertSame(
			'',
			$this->render(
				array(
					'name'  => 'Only a name',
					'rows'  => array(
						array(
							'field' => 'kredit',
							'value' => '',
						),
					),
					'links' => array( array( 'url' => 'javascript:alert(1)' ) ),
				)
			)
		);
	}

	public function test_the_list_and_the_buttons_are_left_out_when_empty(): void {
		$rows_only  = $this->render(
			array(
				'rows' => array(
					array(
						'field' => 'kredit',
						'value' => 'X',
					),
				),
			)
		);
		$links_only = $this->render( array( 'links' => array( array( 'url' => '/a' ) ) ) );

		$this->assertStringNotContainsString( '<ul', $rows_only );
		$this->assertStringNotContainsString( '<dl', $links_only );
	}

	public function test_the_preset_sets_the_modifier_class_and_unknown_ones_fall_back(): void {
		$attributes = array( 'links' => array( array( 'url' => '/a' ) ) );

		$this->assertStringContainsString( 'lunar-dl-entry--detail"', $this->render( $attributes + array( 'preset' => 'detail' ) ) );
		$this->assertStringContainsString( 'lunar-dl-entry--table"', $this->render( $attributes + array( 'preset' => 'bogus' ) ) );
	}

	public function test_text_is_escaped(): void {
		$html = $this->render(
			array(
				'name'  => 'A "quoted" <b>name</b>',
				'rows'  => array(
					array(
						'label' => '<i>Label</i>',
						'value' => '<script>alert(1)</script>',
					),
				),
				'links' => array(
					array(
						'label' => 'Get <it>',
						'url'   => 'https://other.net/a?x=1&y="2"',
					),
				),
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<i>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
		$this->assertStringContainsString( 'aria-label="Get &lt;it&gt; A &quot;quoted&quot; &lt;b&gt;name&lt;/b&gt;"', $html );
		$this->assertStringContainsString( '>Get &lt;it&gt;</a>', $html );
		$this->assertStringContainsString( 'href="https://other.net/a?x=1&amp;y=&quot;2&quot;"', $html );
	}

	public function test_a_date_row_uses_the_site_format_inside_a_time_element(): void {
		$html = $this->render(
			array(
				'rows' => array(
					array(
						'field' => 'tanggal',
						'value' => '2026-10-30',
					),
				),
			)
		);

		$this->assertStringContainsString( '<dd><time datetime="2026-10-30">30 October 2026</time></dd>', $html );
	}

	/**
	 * @dataProvider invalid_dates
	 */
	public function test_a_value_that_is_not_a_real_date_is_shown_as_written( string $value ): void {
		$html = $this->render(
			array(
				'rows' => array(
					array(
						'field' => 'tanggal',
						'value' => $value,
					),
				),
			)
		);

		$this->assertStringContainsString( '<dd>' . $value . '</dd>', $html );
		$this->assertStringNotContainsString( '<time', $html );
	}

	public static function invalid_dates(): array {
		return array(
			'not a date'       => array( 'soon' ),
			'impossible day'   => array( '2026-02-30' ),
			'impossible month' => array( '2026-13-01' ),
			'wrong separators' => array( '30/10/2026' ),
			'missing zero'     => array( '2026-1-5' ),
		);
	}

	public function test_a_custom_row_that_looks_like_a_date_is_not_formatted(): void {
		$html = $this->render(
			array(
				'rows' => array(
					array(
						'label' => 'Tanggal',
						'value' => '2026-10-30',
					),
				),
			)
		);

		$this->assertStringNotContainsString( '<time', $html );
	}

	public function test_an_unknown_field_is_shown_under_its_slug(): void {
		$html = $this->render(
			array(
				'rows' => array(
					array(
						'field' => 'hilang',
						'value' => 'X',
					),
				),
			)
		);

		$this->assertStringContainsString( '<dt>hilang</dt><dd>X</dd>', $html );
	}

	public function test_an_external_link_gets_nofollow_and_an_own_link_does_not(): void {
		$html = $this->render(
			array(
				'links' => array(
					array( 'url' => 'https://drive.google.com/file/d/1' ),
					array( 'url' => 'https://files.example.com/a' ),
					array( 'url' => 'https://partner.net/a' ),
					array( 'url' => '/downloads/a.zip' ),
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, 'rel="nofollow"' ) );
		$this->assertStringContainsString( '<a class="lunar-dl-button" href="https://drive.google.com/file/d/1" rel="nofollow">Download</a>', $html );
		$this->assertStringContainsString( '<a class="lunar-dl-button" href="/downloads/a.zip">Download</a>', $html );
	}

	public function test_rel_is_not_written_when_it_is_empty(): void {
		$html = $this->render( array( 'links' => array( array( 'url' => '/a' ) ) ) );

		$this->assertStringNotContainsString( 'rel=', $html );
	}

	public function test_a_link_can_override_the_rel_rule(): void {
		$html = $this->render(
			array(
				'links' => array(
					array(
						'url'     => 'https://drive.google.com/file/d/1',
						'relMode' => 'follow',
					),
					array(
						'url'     => 'https://example.com/a',
						'relMode' => 'nofollow',
					),
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, 'rel="nofollow"' ) );
		$this->assertStringContainsString( 'href="https://example.com/a" rel="nofollow"', $html );
	}

	public function test_a_new_tab_link_gets_target_noopener_and_a_hidden_notice(): void {
		$html = $this->render(
			array(
				'links' => array(
					array(
						'url'    => 'https://drive.google.com/file/d/1',
						'newTab' => true,
					),
				),
			)
		);

		$this->assertStringContainsString( 'rel="nofollow noopener" target="_blank"', $html );
		$this->assertStringContainsString( '>Download<span class="lunar-dl-visually-hidden"> (opens in a new tab)</span></a>', $html );
	}

	public function test_the_accessible_name_starts_with_the_visible_text(): void {
		$html = $this->render(
			array(
				'name'  => 'Aneka Bintang',
				'links' => array(
					array( 'url' => '/a' ),
					array(
						'label'  => 'Bersih',
						'url'    => '/b',
						'newTab' => true,
					),
				),
			)
		);

		$this->assertStringContainsString( 'aria-label="Download Aneka Bintang">Download</a>', $html );
		$this->assertStringContainsString( 'aria-label="Bersih Aneka Bintang (opens in a new tab)"', $html );
	}

	public function test_there_is_no_aria_label_without_a_name(): void {
		$html = $this->render( array( 'links' => array( array( 'url' => '/a' ) ) ) );

		$this->assertStringNotContainsString( 'aria-label', $html );
	}

	public function test_unsafe_links_are_not_rendered(): void {
		$html = $this->render(
			array(
				'links' => array(
					array( 'url' => 'javascript:alert(1)' ),
					array( 'url' => 'data:text/html,x' ),
					array( 'url' => '//evil.net/a' ),
					array( 'url' => '/safe' ),
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, '<a ' ) );
		$this->assertStringNotContainsString( 'javascript', $html );
		$this->assertStringNotContainsString( 'evil', $html );
	}

	public function test_an_image_is_wrapped_in_a_figure(): void {
		$html = $this->render(
			array(
				'imageId' => 7,
				'links'   => array( array( 'url' => '/a' ) ),
			)
		);

		$this->assertStringContainsString( '<figure class="lunar-dl-entry__media"><img src="https://example.test/uploads/7.jpg" class="lunar-dl-entry__image" alt="attachment alt 7" data-size="large" loading="lazy" /></figure>', $html );
	}

	public function test_the_entry_alt_replaces_the_attachment_alt(): void {
		$html = $this->render(
			array(
				'imageId'  => 7,
				'imageAlt' => 'Livery tampak samping',
			)
		);

		$this->assertStringContainsString( 'alt="Livery tampak samping"', $html );
	}

	public function test_a_missing_image_leaves_out_the_figure(): void {
		$html = $this->render(
			array(
				'imageId' => 404,
				'links'   => array( array( 'url' => '/a' ) ),
			)
		);

		$this->assertStringNotContainsString( '<figure', $html );
		$this->assertStringContainsString( '<ul', $html );
	}

	public function test_an_entry_with_only_a_missing_image_renders_nothing(): void {
		$this->assertSame( '', $this->render( array( 'imageId' => 404 ) ) );
	}

	public function test_the_compact_style_uses_a_medium_image_with_a_small_sizes_hint(): void {
		$html = $this->render(
			array(
				'preset'  => 'compact',
				'imageId' => 7,
			)
		);

		$this->assertStringContainsString( 'data-size="medium" sizes="112px"', $html );
	}

	public function test_the_other_styles_use_a_large_image_without_a_sizes_hint(): void {
		foreach ( array( 'table', 'card', 'detail' ) as $preset ) {
			$html = $this->render(
				array(
					'preset'  => $preset,
					'imageId' => 7,
				)
			);

			$this->assertStringContainsString( 'data-size="large"', $html );
			$this->assertStringNotContainsString( 'sizes=', $html );
		}
	}

	public function test_the_compact_style_shows_two_rows_and_one_button(): void {
		$html = $this->render(
			array(
				'preset' => 'compact',
				'rows'   => array(
					array(
						'label' => 'Skipped',
						'value' => '',
					),
					array(
						'label' => 'One',
						'value' => '1',
					),
					array(
						'label' => 'Two',
						'value' => '2',
					),
					array(
						'label' => 'Three',
						'value' => '3',
					),
				),
				'links'  => array(
					array( 'url' => 'javascript:alert(1)' ),
					array(
						'label' => 'First',
						'url'   => '/a',
					),
					array(
						'label' => 'Second',
						'url'   => '/b',
					),
				),
			)
		);

		$this->assertSame( 2, substr_count( $html, '<dt>' ) );
		$this->assertStringContainsString( '<dt>One</dt><dd>1</dd>', $html );
		$this->assertStringContainsString( '<dt>Two</dt><dd>2</dd>', $html );
		$this->assertStringNotContainsString( 'Three', $html );
		$this->assertSame( 1, substr_count( $html, '<a ' ) );
		$this->assertStringContainsString( '>First</a>', $html );
		$this->assertStringNotContainsString( 'Second', $html );
	}

	public function test_the_other_styles_show_every_row_and_link(): void {
		foreach ( array( 'table', 'card', 'detail' ) as $preset ) {
			$html = $this->render(
				array(
					'preset' => $preset,
					'rows'   => array(
						array(
							'label' => 'One',
							'value' => '1',
						),
						array(
							'label' => 'Two',
							'value' => '2',
						),
						array(
							'label' => 'Three',
							'value' => '3',
						),
					),
					'links'  => array(
						array( 'url' => '/a' ),
						array( 'url' => '/b' ),
					),
				)
			);

			$this->assertSame( 3, substr_count( $html, '<dt>' ), $preset );
			$this->assertSame( 2, substr_count( $html, '<a ' ), $preset );
		}
	}
}
