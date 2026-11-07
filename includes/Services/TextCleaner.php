<?php

namespace LunarDownload\Services;

/**
 * Cleans text taken from block attributes. It makes no WordPress calls, so it
 * can be tested directly.
 */
class TextCleaner {

	/**
	 * Returns single-line text cut to $max characters, or '' when the value is
	 * not a string or is not valid UTF-8.
	 *
	 * @param mixed $text Raw value.
	 * @param int   $max  Maximum number of characters.
	 */
	public static function single_line( $text, int $max ): string {
		if ( ! is_string( $text ) ) {
			return '';
		}

		// Control characters are ASCII bytes, so this is safe on UTF-8 text.
		$text = trim( (string) preg_replace( '/[\x00-\x1f\x7f]+/', ' ', $text ) );

		// Counts characters without needing mbstring; fails on invalid UTF-8.
		return 1 === preg_match( '/^.{0,' . $max . '}/us', $text, $match ) ? rtrim( $match[0] ) : '';
	}
}
