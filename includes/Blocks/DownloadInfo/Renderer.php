<?php

namespace LunarDownload\Blocks\DownloadInfo;

use DateTimeZone;
use LunarDownload\Services\LinkPolicy;
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

		$image = wp_get_attachment_image( $entry->image_id, 'large', false, $attributes );

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

	private function links( EntryData $entry ): string {
		$items  = '';
		$notice = __( '(opens in a new tab)', 'lunar-download' );

		foreach ( $entry->links as $link ) {
			$href = esc_url( $link['url'] );

			if ( '' === $href ) {
				continue;
			}

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
