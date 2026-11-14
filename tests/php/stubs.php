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

		$alt   = $attributes['alt'] ?? 'attachment alt ' . $id;
		$sizes = isset( $attributes['sizes'] ) ? ' sizes="' . esc_attr( $attributes['sizes'] ) . '"' : '';

		return '<img src="https://example.test/uploads/' . $id . '.jpg" class="' . ( $attributes['class'] ?? '' ) . '" alt="' . esc_attr( $alt ) . '" data-size="' . $size . '"' . $sizes . ' loading="lazy" />';
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $flags = 0, $depth = 512 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Stand-in for the WordPress function.
		return json_encode( $data, $flags, $depth );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	// Attachment 404 stands for a missing or non-image attachment.
	function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) {
		return 404 === $id ? false : 'https://example.test/uploads/' . $id . '-' . $size . '.jpg';
	}
}

if ( ! function_exists( 'wp_print_inline_script_tag' ) ) {
	function wp_print_inline_script_tag( $data, $attributes = array() ) {
		echo '<script type="' . esc_attr( $attributes['type'] ?? 'text/javascript' ) . '">' . $data . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Stand-in for the WordPress function.
	}
}

if ( ! function_exists( 'add_action' ) ) {
	$GLOBALS['lunar_download_test_actions'] = array();

	function add_action( $hook, $callback ) {
		$GLOBALS['lunar_download_test_actions'][ $hook ][] = $callback;
	}
}
