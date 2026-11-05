<?php

namespace LunarDownload\Tests\Services;

use LunarDownload\Services\LinkPolicy;
use PHPUnit\Framework\TestCase;

class LinkPolicyTest extends TestCase {

	private LinkPolicy $policy;

	protected function setUp(): void {
		$this->policy = new LinkPolicy( 'example.com', array( 'partner.net' ) );
	}

	public function test_external_link_gets_nofollow(): void {
		$result = $this->policy->resolve( 'https://drive.google.com/file/d/1' );

		$this->assertSame( 'nofollow', $result['rel'] );
		$this->assertFalse( $result['new_tab'] );
	}

	/**
	 * @dataProvider own_url_provider
	 */
	public function test_own_destinations_get_no_nofollow( string $url ): void {
		$result = $this->policy->resolve( $url );

		$this->assertNotNull( $result );
		$this->assertSame( '', $result['rel'] );
	}

	public static function own_url_provider(): array {
		return array(
			'site host'           => array( 'https://example.com/a' ),
			'www prefix'          => array( 'https://www.example.com/a' ),
			'subdomain'           => array( 'https://files.example.com/a' ),
			'uppercase'           => array( 'HTTPS://EXAMPLE.COM/A' ),
			'port'                => array( 'https://example.com:8080/a' ),
			'trailing dot'        => array( 'https://example.com./a' ),
			'extra own domain'    => array( 'https://partner.net/a' ),
			'extra own subdomain' => array( 'http://cdn.partner.net/a' ),
			'relative path'       => array( '/downloads/file.zip' ),
			'root'                => array( '/' ),
		);
	}

	/**
	 * @dataProvider lookalike_url_provider
	 */
	public function test_lookalike_hosts_are_external( string $url ): void {
		$result = $this->policy->resolve( $url );

		$this->assertSame( 'nofollow', $result['rel'] );
	}

	public static function lookalike_url_provider(): array {
		return array(
			'shared suffix'    => array( 'https://notexample.com/a' ),
			'own host as sub'  => array( 'https://example.com.evil.net/a' ),
			'own host in path' => array( 'https://evil.net/example.com' ),
		);
	}

	public function test_follow_mode_removes_nofollow_from_an_external_link(): void {
		$result = $this->policy->resolve( 'https://drive.google.com/file/d/1', 'follow' );

		$this->assertSame( '', $result['rel'] );
	}

	public function test_nofollow_mode_applies_to_an_own_link(): void {
		$result = $this->policy->resolve( 'https://example.com/a', 'nofollow' );

		$this->assertSame( 'nofollow', $result['rel'] );
	}

	public function test_unknown_mode_acts_as_auto(): void {
		$this->assertSame( 'nofollow', $this->policy->resolve( 'https://drive.google.com/a', 'bogus' )['rel'] );
		$this->assertSame( '', $this->policy->resolve( 'https://example.com/a', 'bogus' )['rel'] );
	}

	public function test_new_tab_adds_noopener_for_every_destination(): void {
		$external = $this->policy->resolve( 'https://drive.google.com/a', 'auto', true );
		$own      = $this->policy->resolve( 'https://example.com/a', 'auto', true );
		$relative = $this->policy->resolve( '/a', 'auto', true );
		$followed = $this->policy->resolve( 'https://drive.google.com/a', 'follow', true );

		$this->assertSame( 'nofollow noopener', $external['rel'] );
		$this->assertSame( 'noopener', $own['rel'] );
		$this->assertSame( 'noopener', $relative['rel'] );
		$this->assertSame( 'noopener', $followed['rel'] );
		$this->assertTrue( $external['new_tab'] );
	}

	/**
	 * @dataProvider rejected_url_provider
	 */
	public function test_unsafe_or_unsupported_urls_are_rejected( string $url ): void {
		$this->assertNull( $this->policy->resolve( $url ) );
	}

	public static function rejected_url_provider(): array {
		return array(
			'empty'                  => array( '' ),
			'blank'                  => array( '   ' ),
			'javascript'             => array( 'javascript:alert(1)' ),
			'mixed case javascript'  => array( 'JaVaScRiPt:alert(1)' ),
			'tab inside scheme'      => array( "java\tscript:alert(1)" ),
			'newline in host'        => array( "https://exam\nple.com/a" ),
			'data'                   => array( 'data:text/html;base64,AAAA' ),
			'ftp'                    => array( 'ftp://example.com/a' ),
			'mailto'                 => array( 'mailto:someone@example.com' ),
			'protocol relative'      => array( '//evil.net/a' ),
			'backslash after slash'  => array( '/\\evil.net/a' ),
			'backslash in url'       => array( 'https://evil.net\\@example.com/a' ),
			'no scheme'              => array( 'example.com/file.zip' ),
			'bare file name'         => array( 'file.zip' ),
			'fragment only'          => array( '#top' ),
			'query only'             => array( '?a=1' ),
			'scheme without slashes' => array( 'https:example.com/a' ),
			'no host'                => array( 'https://' ),
			'empty authority'        => array( 'http:///path' ),
			'user info'              => array( 'https://user@example.com/a' ),
			'user and password'      => array( 'https://user:pass@example.com/a' ),
			'space in host'          => array( 'https://exa mple.com/a' ),
		);
	}

	public function test_url_is_trimmed_but_otherwise_kept_as_written(): void {
		$result = $this->policy->resolve( "  HTTPS://Drive.Google.com/A?x=1#y \n" );

		$this->assertSame( 'HTTPS://Drive.Google.com/A?x=1#y', $result['url'] );
	}

	public function test_own_domains_are_normalized_and_invalid_entries_ignored(): void {
		$policy = new LinkPolicy( 'WWW.Example.com', array( ' WWW.Partner.NET ', '', 123, null, array( 'x' ) ) );

		$this->assertSame( '', $policy->resolve( 'https://example.com/a' )['rel'] );
		$this->assertSame( '', $policy->resolve( 'https://partner.net/a' )['rel'] );
		$this->assertSame( 'nofollow', $policy->resolve( 'https://123/a' )['rel'] );
	}

	public function test_empty_site_host_makes_every_absolute_link_external(): void {
		$policy = new LinkPolicy( '' );

		$this->assertSame( 'nofollow', $policy->resolve( 'https://example.com/a' )['rel'] );
		$this->assertSame( '', $policy->resolve( '/a' )['rel'] );
	}
}
