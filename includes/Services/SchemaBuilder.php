<?php

namespace LunarDownload\Services;

/**
 * Builds the structured data (schema.org JSON-LD) of one download entry from
 * what the entry shows. It makes no WordPress calls and never adds a value
 * that the author did not enter.
 */
class SchemaBuilder {

	// Schema role of a field => property of the CreativeWork.
	private const PROPERTIES = array(
		'creator'        => 'creator',
		'date_modified'  => 'dateModified',
		'date_published' => 'datePublished',
		'version'        => 'version',
	);

	private const DATE_ROLES = array( 'date_modified', 'date_published' );

	/**
	 * Returns the CreativeWork of an entry, or null when the entry has no name.
	 *
	 * @param array $entry {
	 *     What the entry shows.
	 *
	 *     @type string  $name      Name of the entry.
	 *     @type string  $image_url Address of the image, or ''.
	 *     @type array[] $rows      Rows with value, type, role and archived.
	 *     @type array[] $links     Links with label and an absolute url.
	 * }
	 * @return array|null
	 */
	public function build( array $entry ): ?array {
		$name = $entry['name'] ?? '';

		if ( ! is_string( $name ) || '' === $name ) {
			return null;
		}

		$node = array(
			'@type' => 'CreativeWork',
			'name'  => $name,
		);

		if ( ! empty( $entry['image_url'] ) && is_string( $entry['image_url'] ) ) {
			$node['image'] = $entry['image_url'];
		}

		$rows = is_array( $entry['rows'] ?? null ) ? $entry['rows'] : array();

		foreach ( self::PROPERTIES as $role => $property ) {
			$value = $this->value_for_role( $rows, $role );

			if ( null !== $value ) {
				$node[ $property ] = $value;
			}
		}

		$media = $this->media( is_array( $entry['links'] ?? null ) ? $entry['links'] : array(), $this->value_for_role( $rows, 'file_size' ) );

		if ( array() !== $media ) {
			$node['associatedMedia'] = $media;
		}

		return $node;
	}

	/**
	 * One MediaObject per link. The size is added only when there is a single
	 * link, because one size cannot be known to fit each of several files.
	 *
	 * @param array[]     $links Links with label and url.
	 * @param string|null $size  Value of the file size field.
	 * @return array[]
	 */
	private function media( array $links, ?string $size ): array {
		$media = array();

		foreach ( $links as $link ) {
			if ( ! is_array( $link ) || ! is_string( $link['url'] ?? null ) || '' === $link['url'] ) {
				continue;
			}

			$media[] = array(
				'@type' => 'MediaObject',
				'name'  => is_string( $link['label'] ?? null ) ? $link['label'] : '',
				'url'   => $link['url'],
			);
		}

		if ( 1 === count( $media ) && null !== $size ) {
			$media[0]['contentSize'] = $size;
		}

		return $media;
	}

	/**
	 * The value of the row that carries a role. A row of an active field wins
	 * over one of an archived field, and the first of them wins among equals.
	 * A date role only takes a real date.
	 *
	 * @param array[] $rows Rows with value, role and archived.
	 * @param string  $role Schema role.
	 */
	private function value_for_role( array $rows, string $role ): ?string {
		$active   = null;
		$archived = null;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ( $row['role'] ?? null ) !== $role || ! is_string( $row['value'] ?? null ) || '' === $row['value'] ) {
				continue;
			}

			if ( in_array( $role, self::DATE_ROLES, true ) && ! self::is_real_date( $row['value'] ) ) {
				continue;
			}

			if ( true === ( $row['archived'] ?? false ) ) {
				$archived = $archived ?? $row['value'];
			} else {
				$active = $active ?? $row['value'];
			}
		}

		return $active ?? $archived;
	}

	private static function is_real_date( string $value ): bool {
		return 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) && checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
	}
}
