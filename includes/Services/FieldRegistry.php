<?php

namespace LunarDownload\Services;

/**
 * Validates Field Registry definitions and builds their slugs. It makes no
 * WordPress calls, so it can be tested directly.
 */
class FieldRegistry {

	private const TYPES = array( 'text', 'select', 'date' );

	// Schema roles and the field types each one accepts.
	private const ROLES = array(
		'creator'        => array( 'text', 'select' ),
		'file_size'      => array( 'text' ),
		'date_modified'  => array( 'date' ),
		'date_published' => array( 'date' ),
		'version'        => array( 'text', 'select' ),
	);

	private const SLUG_MAX        = 40;
	private const LABEL_MAX       = 60;
	private const OPTIONS_MAX     = 100;
	private const OPTION_TEXT_MAX = 200;
	private const FALLBACK_SLUG   = 'field';

	// ASCII replacement => accented characters (both cases) that fold to it.
	private const ACCENTS = array(
		'a'  => 'àáâãäåāăąÀÁÂÃÄÅĀĂĄ',
		'c'  => 'çćčÇĆČ',
		'd'  => 'ďđĎĐ',
		'e'  => 'èéêëēėęěÈÉÊËĒĖĘĚ',
		'g'  => 'ğĞ',
		'i'  => 'ìíîïīįıÌÍÎÏĪĮİ',
		'l'  => 'łŁ',
		'n'  => 'ńñňŃÑŇ',
		'o'  => 'òóôõöøōőÒÓÔÕÖØŌŐ',
		'r'  => 'ŕřŔŘ',
		's'  => 'śšşŚŠŞ',
		't'  => 'ťŤ',
		'u'  => 'ùúûüūůűųÙÚÛÜŪŮŰŲ',
		'y'  => 'ýÿÝŸ',
		'z'  => 'źžżŹŽŻ',
		'ae' => 'æÆ',
		'oe' => 'œŒ',
		'ss' => 'ß',
	);

	/**
	 * Returns a clean list of field definitions.
	 *
	 * Entries that cannot be repaired are dropped. A field that exists in
	 * $previous keeps its slug and type and is never removed, because entries
	 * store the slug and fields are archived rather than deleted.
	 *
	 * @param mixed $fields   Raw list of field definitions.
	 * @param mixed $previous Raw list that is currently stored.
	 * @return array[] Definitions with slug, label, type, options (select only), schema_role and archived.
	 */
	public function sanitize( $fields, $previous = array() ): array {
		$locked = array();

		foreach ( $this->normalize( $previous, array() ) as $field ) {
			$locked[ $field['slug'] ] = $field;
		}

		$result = $this->normalize( $fields, $locked );
		$slugs  = array_column( $result, 'slug' );

		foreach ( $locked as $slug => $field ) {
			if ( ! in_array( (string) $slug, $slugs, true ) ) {
				$result[] = $field;
			}
		}

		return $this->enforce_unique_roles( $result );
	}

	/**
	 * Builds a slug from a label: lowercase ASCII letters, digits and hyphens,
	 * at most 40 characters, unique among $taken.
	 *
	 * @param string   $label Field label.
	 * @param string[] $taken Slugs that are already in use.
	 */
	public function make_slug( string $label, array $taken = array() ): string {
		$slug = $label;

		foreach ( self::ACCENTS as $ascii => $accented ) {
			$characters = preg_split( '//u', $accented, -1, PREG_SPLIT_NO_EMPTY );
			$slug       = str_replace( $characters, $ascii, $slug );
		}

		$slug = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $slug ) );
		$slug = trim( substr( (string) $slug, 0, self::SLUG_MAX ), '-' );

		if ( '' === $slug ) {
			$slug = self::FALLBACK_SLUG;
		}

		$candidate = $slug;
		$number    = 2;

		while ( in_array( $candidate, $taken, true ) ) {
			$suffix    = '-' . $number;
			$candidate = rtrim( substr( $slug, 0, self::SLUG_MAX - strlen( $suffix ) ), '-' ) . $suffix;
			++$number;
		}

		return $candidate;
	}

	/**
	 * @param mixed $raw    Raw list of definitions.
	 * @param array $locked Known fields keyed by slug; their slug and type cannot change.
	 * @return array[]
	 */
	private function normalize( $raw, array $locked ): array {
		$result = array();
		$slugs  = array_map( 'strval', array_keys( $locked ) );

		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = $this->clean_text( $item['label'] ?? null, self::LABEL_MAX );
			$slug  = is_string( $item['slug'] ?? null ) ? $item['slug'] : '';

			if ( isset( $locked[ $slug ] ) ) {
				// Known field: slug and type stay as stored.
				$known = $locked[ $slug ];

				if ( in_array( $slug, array_column( $result, 'slug' ), true ) ) {
					continue;
				}

				$field = $this->build( $slug, '' === $label ? $known['label'] : $label, $known['type'], $item, $known['options'] ?? array() );
			} else {
				$type = $item['type'] ?? null;

				if ( '' === $label || ! is_string( $type ) || ! in_array( $type, self::TYPES, true ) ) {
					continue;
				}

				$slugs_in_use = array_merge( $slugs, array_column( $result, 'slug' ) );

				if ( ! $this->is_valid_slug( $slug ) || in_array( $slug, $slugs_in_use, true ) ) {
					$slug = $this->make_slug( $label, $slugs_in_use );
				}

				$field = $this->build( $slug, $label, $type, $item, array() );
			}

			$result[] = $field;
		}

		return $result;
	}

	/**
	 * @param string   $slug     Final slug.
	 * @param string   $label    Final label.
	 * @param string   $type     Final type.
	 * @param array    $item     Raw definition.
	 * @param string[] $fallback Options to keep when the raw definition has none.
	 */
	private function build( string $slug, string $label, string $type, array $item, array $fallback ): array {
		$field = array(
			'slug'  => $slug,
			'label' => $label,
			'type'  => $type,
		);

		if ( 'select' === $type ) {
			$field['options'] = $this->clean_options( $item['options'] ?? $fallback );
		}

		$role = $item['schema_role'] ?? null;

		$field['schema_role'] = is_string( $role ) && isset( self::ROLES[ $role ] ) && in_array( $type, self::ROLES[ $role ], true ) ? $role : null;
		$field['archived']    = true === ( $item['archived'] ?? false );

		return $field;
	}

	/**
	 * Keeps at most one active field per schema role; the first one wins.
	 *
	 * @param array[] $fields Clean definitions.
	 * @return array[]
	 */
	private function enforce_unique_roles( array $fields ): array {
		$used = array();

		foreach ( $fields as $index => $field ) {
			if ( $field['archived'] || null === $field['schema_role'] ) {
				continue;
			}

			if ( isset( $used[ $field['schema_role'] ] ) ) {
				$fields[ $index ]['schema_role'] = null;
				continue;
			}

			$used[ $field['schema_role'] ] = true;
		}

		return $fields;
	}

	/**
	 * @param mixed $options Raw option list.
	 * @return string[]
	 */
	private function clean_options( $options ): array {
		$clean = array();

		foreach ( is_array( $options ) ? $options : array() as $option ) {
			$text = $this->clean_text( $option, self::OPTION_TEXT_MAX );

			if ( '' !== $text && ! in_array( $text, $clean, true ) ) {
				$clean[] = $text;
			}

			if ( count( $clean ) >= self::OPTIONS_MAX ) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Returns single-line text cut to $max characters, or '' when the value is
	 * not a string or is not valid UTF-8.
	 *
	 * @param mixed $text Raw value.
	 * @param int   $max  Maximum number of characters.
	 */
	private function clean_text( $text, int $max ): string {
		if ( ! is_string( $text ) ) {
			return '';
		}

		// Control characters are ASCII bytes, so this is safe on UTF-8 text.
		$text = trim( (string) preg_replace( '/[\x00-\x1f\x7f]+/', ' ', $text ) );

		// Counts characters without needing mbstring; fails on invalid UTF-8.
		return 1 === preg_match( '/^.{0,' . $max . '}/us', $text, $match ) ? rtrim( $match[0] ) : '';
	}

	private function is_valid_slug( string $slug ): bool {
		return 1 === preg_match( '/^[a-z0-9-]+$/', $slug ) && strlen( $slug ) <= self::SLUG_MAX;
	}
}
