<?php
// Minimal stand-ins for the WordPress functions the tested classes call.

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Stand-in for a WordPress core function.

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	// Mirrors core, including that "$" lets a trailing newline through.
	function sanitize_hex_color( $color ) {
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}

		return null;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Keeps the core signature.
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		return gmdate( $format, $timestamp );
	}
}

if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
	function get_block_wrapper_attributes( $extra = array() ) {
		return 'class="wp-block-lunar-download-download-info ' . ( $extra['class'] ?? '' ) . '"';
	}
}

if ( ! function_exists( 'wp_get_attachment_image' ) ) {
	// Attachment 404 stands for a missing or non-image attachment.
	function wp_get_attachment_image( $id, $size = 'thumbnail', $icon = false, $attributes = array() ) {
		if ( 404 === $id ) {
			return '';
		}

		$alt = $attributes['alt'] ?? 'attachment alt ' . $id;

		return '<img src="https://example.test/uploads/' . $id . '.jpg" class="' . ( $attributes['class'] ?? '' ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" />';
	}
}
