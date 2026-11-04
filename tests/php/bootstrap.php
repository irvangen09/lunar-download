<?php

$lunar_download_root = dirname( __DIR__, 2 );

require_once $lunar_download_root . '/vendor/autoload.php';
require_once $lunar_download_root . '/tests/php/stubs.php';
require_once $lunar_download_root . '/includes/Core/Autoloader.php';

( new \LunarDownload\Core\Autoloader( 'LunarDownload\\', $lunar_download_root . '/includes/' ) )->register();
