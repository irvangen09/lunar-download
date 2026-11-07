<?php

namespace LunarDownload\Blocks\DownloadInfo;

use LunarDownload\Services\TextCleaner;

/**
 * The clean form of one entry's attributes. Attributes can be edited in the
 * code editor, so every value is checked here before anything is rendered.
 */
class EntryData {

	public const PRESETS = array( 'table', 'card', 'compact', 'detail' );

	private const NAME_MAX  = 200;
	private const LABEL_MAX = 60;
	private const VALUE_MAX = 200;
	private const SLUG_MAX  = 40;

	// The compact style shows the first rows and one button.
	private const COMPACT_ROWS  = 2;
	private const COMPACT_LINKS = 1;

	public string $name;

	public int $image_id;

	public string $image_alt;

	public string $preset;

	/**
	 * Each row has slug (null for a custom label), label, value, type and role.
	 * The type is a field type, or 'custom'.
	 *
	 * @var array[]
	 */
	public array $rows;

	/**
	 * Each link has label, url, rel and new_tab.
	 *
	 * @var array[]
	 */
	public array $links;

	private function __construct() {
		$this->name      = '';
		$this->image_id  = 0;
		$this->image_alt = '';
		$this->preset    = self::PRESETS[0];
		$this->rows      = array();
		$this->links     = array();
	}

	/**
	 * @param array         $attributes Block attributes.
	 * @param RenderContext $context    Shared render data.
	 */
	public static function from_attributes( array $attributes, RenderContext $context ): self {
		$entry = new self();

		$entry->name      = TextCleaner::single_line( $attributes['name'] ?? '', self::NAME_MAX );
		$entry->image_alt = TextCleaner::single_line( $attributes['imageAlt'] ?? '', self::NAME_MAX );
		$entry->image_id  = self::to_id( $attributes['imageId'] ?? 0 );
		$preset           = $attributes['preset'] ?? null;

		if ( is_string( $preset ) && in_array( $preset, self::PRESETS, true ) ) {
			$entry->preset = $preset;
		}

		$entry->rows  = self::clean_rows( $attributes['rows'] ?? array(), $context );
		$entry->links = self::clean_links( $attributes['links'] ?? array(), $context );

		// What the style does not show is dropped here, so that the structured data matches the page.
		if ( 'compact' === $entry->preset ) {
			$entry->rows  = array_slice( $entry->rows, 0, self::COMPACT_ROWS );
			$entry->links = array_slice( $entry->links, 0, self::COMPACT_LINKS );
		}

		return $entry;
	}

	/**
	 * @param mixed $value Raw attachment ID.
	 */
	private static function to_id( $value ): int {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * @param mixed         $rows    Raw rows.
	 * @param RenderContext $context Shared render data.
	 * @return array[]
	 */
	private static function clean_rows( $rows, RenderContext $context ): array {
		$clean = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$value = TextCleaner::single_line( $row['value'] ?? '', self::VALUE_MAX );
			$slug  = TextCleaner::single_line( $row['field'] ?? '', self::SLUG_MAX );

			if ( '' === $value ) {
				continue;
			}

			if ( '' !== $slug ) {
				// A field that is no longer in the registry still shows, under its slug.
				$field   = $context->field( $slug );
				$label   = null === $field ? $slug : TextCleaner::single_line( $field['label'] ?? '', self::LABEL_MAX );
				$clean[] = array(
					'slug'  => $slug,
					'label' => '' === $label ? $slug : $label,
					'value' => $value,
					'type'  => $field['type'] ?? 'text',
					'role'  => $field['schema_role'] ?? null,
				);

				continue;
			}

			$label = TextCleaner::single_line( $row['label'] ?? '', self::LABEL_MAX );

			if ( '' !== $label ) {
				$clean[] = array(
					'slug'  => null,
					'label' => $label,
					'value' => $value,
					'type'  => 'custom',
					'role'  => null,
				);
			}
		}

		return $clean;
	}

	/**
	 * @param mixed         $links   Raw links.
	 * @param RenderContext $context Shared render data.
	 * @return array[]
	 */
	private static function clean_links( $links, RenderContext $context ): array {
		$clean = array();

		foreach ( is_array( $links ) ? $links : array() as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$mode     = is_string( $link['relMode'] ?? null ) ? $link['relMode'] : 'auto';
			$new_tab  = isset( $link['newTab'] ) && true === $link['newTab'];
			$resolved = $context->policy()->resolve( is_string( $link['url'] ?? null ) ? $link['url'] : '', $mode, $new_tab );

			if ( null === $resolved ) {
				continue;
			}

			$label = TextCleaner::single_line( $link['label'] ?? '', self::LABEL_MAX );

			$clean[] = array(
				'label'   => '' === $label ? $context->default_link_label() : $label,
				'url'     => $resolved['url'],
				'rel'     => $resolved['rel'],
				'new_tab' => $resolved['new_tab'],
			);
		}

		return $clean;
	}
}
