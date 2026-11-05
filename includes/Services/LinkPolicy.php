<?php

namespace LunarDownload\Services;

/**
 * Decides whether a download link can be rendered and which rel and target
 * values it needs. It makes no WordPress calls: the hosts are passed in.
 */
class LinkPolicy {

	/**
	 * Hosts that count as the site's own, normalized and unique.
	 *
	 * @var string[]
	 */
	private array $own_hosts = array();

	/**
	 * @param string $site_host   Host of the site, without scheme or port.
	 * @param array  $own_domains Extra hosts that count as the site's own.
	 */
	public function __construct( string $site_host, array $own_domains = array() ) {
		$hosts = array();

		foreach ( array_merge( array( $site_host ), $own_domains ) as $domain ) {
			if ( ! is_string( $domain ) ) {
				continue;
			}

			$host = self::normalize_host( $domain );

			if ( '' !== $host ) {
				$hosts[] = $host;
			}
		}

		$this->own_hosts = array_values( array_unique( $hosts ) );
	}

	/**
	 * Validates a link and computes its rel and target values.
	 *
	 * @param string $url      Destination as written by the author.
	 * @param string $rel_mode auto, follow or nofollow. Any other value acts as auto.
	 * @param bool   $new_tab  Whether the link opens in a new tab.
	 * @return array{url: string, rel: string, new_tab: bool}|null Null when the link must not be rendered.
	 */
	public function resolve( string $url, string $rel_mode = 'auto', bool $new_tab = false ): ?array {
		$url      = trim( $url );
		$external = $this->is_external( $url );

		if ( null === $external ) {
			return null;
		}

		$tokens = array();

		if ( 'nofollow' === $rel_mode || ( 'follow' !== $rel_mode && $external ) ) {
			$tokens[] = 'nofollow';
		}

		if ( $new_tab ) {
			$tokens[] = 'noopener';
		}

		return array(
			'url'     => $url,
			'rel'     => implode( ' ', $tokens ),
			'new_tab' => $new_tab,
		);
	}

	/**
	 * Returns null when the URL is not allowed, otherwise whether it leaves the site.
	 */
	private function is_external( string $url ): ?bool {
		// Browsers drop control characters (java<TAB>script:) and read a backslash as a slash, which can hide a scheme or change the host.
		if ( '' === $url || 1 === preg_match( '/[\x00-\x1f\x7f\\\\]/', $url ) ) {
			return null;
		}

		if ( '/' === $url[0] ) {
			// A second slash makes the URL protocol-relative, not a local path.
			return isset( $url[1] ) && '/' === $url[1] ? null : false;
		}

		if ( 1 !== preg_match( '#^https?://#i', $url ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- This class stays free of WordPress calls so it can be tested directly.
		$parts = parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || 1 === preg_match( '/\s/', $parts['host'] ) ) {
			return null;
		}

		return ! $this->is_own_host( self::normalize_host( $parts['host'] ) );
	}

	/**
	 * A host matches itself and its subdomains.
	 */
	private function is_own_host( string $host ): bool {
		foreach ( $this->own_hosts as $own_host ) {
			if ( $host === $own_host || str_ends_with( $host, '.' . $own_host ) ) {
				return true;
			}
		}

		return false;
	}

	private static function normalize_host( string $host ): string {
		$host = rtrim( strtolower( trim( $host ) ), '.' );

		return str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}
}
