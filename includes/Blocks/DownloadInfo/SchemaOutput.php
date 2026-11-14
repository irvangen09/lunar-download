<?php

namespace LunarDownload\Blocks\DownloadInfo;

/**
 * Prints the structured data of every entry on the page as one JSON-LD
 * script. The entries are collected while the page renders, and the script is
 * printed once in the footer.
 */
class SchemaOutput {

	/**
	 * Returns the collected CreativeWork nodes.
	 *
	 * @var callable
	 */
	private $nodes;

	/**
	 * @param callable|null $nodes Returns the nodes to print; the collected ones by default.
	 */
	public function __construct( ?callable $nodes = null ) {
		$this->nodes = $nodes ?? array( Renderer::class, 'schema_nodes' );
	}

	public function register(): void {
		add_action( 'wp_footer', array( $this, 'print_schema' ) );
	}

	public function print_schema(): void {
		$nodes = ( $this->nodes )();

		if ( array() === $nodes ) {
			return;
		}

		wp_print_inline_script_tag( self::json( $nodes ), array( 'type' => 'application/ld+json' ) );
	}

	/**
	 * Encodes the nodes. The flags keep a value from closing the script tag.
	 *
	 * @param array[] $nodes CreativeWork nodes.
	 */
	public static function json( array $nodes ): string {
		return (string) wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( $nodes ),
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES
		);
	}
}
