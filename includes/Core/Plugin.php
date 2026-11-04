<?php

namespace LunarDownload\Core;

class Plugin {

	public function run(): void {
		( new BlockRegistry() )->register();
	}
}
