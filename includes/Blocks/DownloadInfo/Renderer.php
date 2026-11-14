<?php

namespace LunarDownload\Blocks\DownloadInfo;

use DateTimeZone;
use LunarDownload\Services\LinkPolicy;
use LunarDownload\Services\SchemaBuilder;
use LunarDownload\Settings\Settings;

class Renderer {

	/**
	 * Render data shared by every entry of the request.
	 */
	private static ?RenderContext $shared = null;

	private RenderContext $context;

	public function __construct( ?RenderContext $context = null ) {
		$this->context = $context ?? self::shared_context();
	}

	/**
	 * Forgets the shared render data, so the next entry reads the settings again.
	 */
	public static function reset_context(): void {
		self::$shared = null;
	}

	/**
	 * The structured data of the entries rendered in this request.
	 *
	 * @return array[]
	 */
	public static function schema_nodes(): array {
		return null === self::$shared ? array() : self::$shared->schema_nodes();
	}

	/**
	 * Returns the markup of one download entry, or '' when there is nothing to show.
	 *
	 * @param array $attributes Block attributes.
	 */
	public function render( array $attributes ): string {
		$entry = EntryData::from_attributes( $attributes, $this->context );
		$media = $this->media( $entry );
		$rows  = $this->rows( $entry );
		$links = $this->links( $entry );

		if ( '' === $media . $rows . $links ) {
			return '';
		}

		$this->collect_schema( $entry, '' !== $media );

		return sprintf(
			'<article %s>%s%s%s</article>',
			get_block_wrapper_attributes( array( 'class' => 'lunar-dl-entry lunar-dl-entry--' . $entry->preset ) ),
			$media,
			$rows,
			$links
		);
	}

	private function media( EntryData $entry ): string {
		if ( $entry->image_id < 1 ) {
			return '';
		}

		$attributes = array( 'class' => 'lunar-dl-entry__image' );

		// Without an alt here, WordPress uses the alt of the attachment.
		if ( '' !== $entry->image_alt ) {
			$attributes['alt'] = $entry->image_alt;
		}

		$size = 'large';

		// A small thumbnail needs no large image; the sizes hint lets the browser pick a small one.
		if ( 'compact' === $entry->preset ) {
			$size                = 'medium';
			$attributes['sizes'] = '112px';
		}

		$image = wp_get_attachment_image( $entry->image_id, $size, false, $attributes );

		return '' === $image ? '' : '<figure class="lunar-dl-entry__media">' . $image . '</figure>';
	}

	private function rows( EntryData $entry ): string {
		$html = '';

		foreach ( $entry->rows as $row ) {
			$html .= '<div class="lunar-dl-entry__row"><dt>' . esc_html( $row['label'] ) . '</dt><dd>' . $this->value( $row ) . '</dd></div>';
		}

		return '' === $html ? '' : '<dl class="lunar-dl-entry__rows">' . $html . '</dl>';
	}

	/**
	 * @param array $row A row of EntryData.
	 */
	private function value( array $row ): string {
		$timestamp = 'date' === $row['type'] ? self::date_timestamp( $row['value'] ) : null;

		if ( null === $timestamp ) {
			return esc_html( $row['value'] );
		}

		return sprintf(
			'<time datetime="%s">%s</time>',
			esc_attr( $row['value'] ),
			esc_html( wp_date( $this->context->date_format(), $timestamp, new DateTimeZone( 'UTC' ) ) )
		);
	}

	/**
	 * The links that are shown: those with an address that WordPress accepts.
	 *
	 * @param EntryData $entry The entry.
	 * @return array[]
	 */
	private function shown_links( EntryData $entry ): array {
		return array_values(
			array_filter(
				$entry->links,
				static fn( $link ) => '' !== esc_url( $link['url'] )
			)
		);
	}

	private function links( EntryData $entry ): string {
		$items  = '';
		$notice = __( '(opens in a new tab)', 'lunar-download' );

		foreach ( $this->shown_links( $entry ) as $link ) {
			$href = esc_url( $link['url'] );

			$attributes = 'class="lunar-dl-button" href="' . $href . '"';

			if ( '' !== $link['rel'] ) {
				$attributes .= ' rel="' . esc_attr( $link['rel'] ) . '"';
			}

			if ( $link['new_tab'] ) {
				$attributes .= ' target="_blank"';
			}

			// The visible text stays short; the full name goes in aria-label, which starts with it.
			if ( '' !== $entry->name ) {
				$name        = $link['label'] . ' ' . $entry->name . ( $link['new_tab'] ? ' ' . $notice : '' );
				$attributes .= ' aria-label="' . esc_attr( $name ) . '"';
			}

			$text = esc_html( $link['label'] );

			if ( $link['new_tab'] ) {
				$text .= '<span class="lunar-dl-visually-hidden"> ' . esc_html( $notice ) . '</span>';
			}

			$items .= '<li><a ' . $attributes . '>' . $text . '</a></li>';
		}

		return '' === $items ? '' : '<ul class="lunar-dl-entry__links">' . $items . '</ul>';
	}

	/**
	 * Hands what the entry shows to the structured data of the page.
	 *
	 * @param EntryData $entry     The entry.
	 * @param bool      $has_image Whether the image is shown.
	 */
	private function collect_schema( EntryData $entry, bool $has_image ): void {
		$image_url = $has_image ? wp_get_attachment_image_url( $entry->image_id, 'full' ) : '';

		$node = ( new SchemaBuilder() )->build(
			array(
				'name'      => $entry->name,
				'image_url' => is_string( $image_url ) ? $image_url : '',
				'rows'      => $entry->rows,
				'links'     => array_map(
					static fn( $link ) => array(
						'label' => $link['label'],
						// A path on this site becomes a full address, so that it does not depend on the page.
						'url'   => 0 === strpos( $link['url'], '/' ) ? home_url( $link['url'] ) : $link['url'],
					),
					$this->shown_links( $entry )
				),
			)
		);

		if ( null !== $node ) {
			$this->context->add_schema( $node );
		}
	}

	/**
	 * Returns a UTC timestamp for a YYYY-MM-DD string, or null when it is not a real date.
	 *
	 * @param string $value Stored date.
	 */
	private static function date_timestamp( string $value ): ?int {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			return null;
		}

		return gmmktime( 0, 0, 0, (int) $parts[2], (int) $parts[3], (int) $parts[1] );
	}

	private static function shared_context(): RenderContext {
		if ( null === self::$shared ) {
			$settings = ( new Settings() )->get();
			$host     = wp_parse_url( home_url(), PHP_URL_HOST );

			self::$shared = new RenderContext(
				$settings['fields'],
				new LinkPolicy( is_string( $host ) ? $host : '', $settings['own_domains'] ),
				__( 'Download', 'lunar-download' ),
				(string) get_option( 'date_format' )
			);
		}

		return self::$shared;
	}
}
