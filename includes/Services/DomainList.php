<?php

namespace LunarDownload\Services;

/**
 * Cleans the list of domains that count as the site's own. It makes no
 * WordPress calls, so it can be tested directly.
 */
class DomainList {

	private const MAX_DOMAINS = 100;
	private const MAX_LENGTH  = 253;

	/**
	 * Returns unique, normalized domains in the order they were written. Lines
	 * that are not a valid domain are dropped.
	 *
	 * @param mixed $domains Raw list of domains or URLs.
	 * @return string[]
	 */
	public function sanitize( $domains ): array {
		$clean = array();

		foreach ( is_array( $domains ) ? $domains : array() as $domain ) {
			$host = is_string( $domain ) ? $this->normalize( $domain ) : '';

			if ( '' !== $host && ! in_array( $host, $clean, true ) ) {
				$clean[] = $host;
			}

			if ( count( $clean ) >= self::MAX_DOMAINS ) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Reduces a domain or URL to its lowercase host without a leading www.
	 * Returns '' when the result is not a valid domain name.
	 *
	 * @param string $input A domain such as example.com, or a URL.
	 */
	public function normalize( string $input ): string {
		$host = strtolower( trim( $input ) );

		// Drop the scheme, then keep only the part before the path, query or fragment.
		$host = (string) preg_replace( '#^(?:[a-z][a-z0-9+.-]*:)?//#', '', $host );
		$host = (string) preg_replace( '#[/?\#].*$#', '', $host );

		// Drop a port. A user name or an IP literal is not a domain.
		$host = (string) preg_replace( '#:\d*$#', '', $host );

		// Drop a wildcard or leading dot, a trailing dot, and www.
		$host = ltrim( $host, '*.' );
		$host = rtrim( $host, '.' );
		$host = str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;

		return $this->is_valid( $host ) ? $host : '';
	}

	private function is_valid( string $host ): bool {
		if ( '' === $host || strlen( $host ) > self::MAX_LENGTH || false === strpos( $host, '.' ) ) {
			return false;
		}

		foreach ( explode( '.', $host ) as $label ) {
			if ( 1 !== preg_match( '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label ) ) {
				return false;
			}
		}

		return true;
	}
}
