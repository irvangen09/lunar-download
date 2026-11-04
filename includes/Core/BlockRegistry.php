<?php

namespace LunarDownload\Core;

use DirectoryIterator;

class BlockRegistry {

	private const NAMESPACE_PREFIX = 'lunar-download/';

	private const CATEGORY = 'lunar-download';

	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ) );
		add_filter( 'block_type_metadata', array( $this, 'add_version' ) );
	}

	/**
	 * Gives block stylesheets the plugin version. Without it WordPress uses its
	 * own version, so a cached stylesheet outlives a plugin update.
	 *
	 * @param array $metadata Block metadata.
	 */
	public function add_version( array $metadata ): array {
		$name = $metadata['name'] ?? '';

		if ( is_string( $name ) && str_starts_with( $name, self::NAMESPACE_PREFIX ) ) {
			$metadata['version'] = LUNAR_DOWNLOAD_VERSION;
		}

		return $metadata;
	}

	/**
	 * Registers every build/{folder} that holds a block.json. Folders without
	 * one, such as the admin bundle, are skipped.
	 */
	public function register_blocks(): void {
		$build_dir = LUNAR_DOWNLOAD_DIR . 'build';

		if ( ! is_dir( $build_dir ) ) {
			return;
		}

		foreach ( new DirectoryIterator( $build_dir ) as $entry ) {
			if ( $entry->isDot() || ! $entry->isDir() ) {
				continue;
			}

			if ( is_file( $entry->getPathname() . '/block.json' ) ) {
				register_block_type( $entry->getPathname() );
			}
		}
	}

	public function add_category( array $categories ): array {
		$categories[] = array(
			'slug'  => self::CATEGORY,
			'title' => __( 'Lunar Download', 'lunar-download' ),
		);

		return $categories;
	}
}
