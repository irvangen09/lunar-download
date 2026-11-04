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
