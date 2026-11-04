<?php
/**
 * Server-side render of the Download Info block.
 */

use LunarDownload\Blocks\DownloadInfo\Renderer;

defined( 'ABSPATH' ) || exit;

// Every value is escaped inside Renderer.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo ( new Renderer() )->render();
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
