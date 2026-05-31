<?php

if ( ! defined( 'ABSPATH' ) ) exit;

namespace KiraStudio;

use KiraStudio\Traits\Singleton;

final class Plugin
{
	use Singleton;

	public function boot(): void
	{
		add_action('init', [$this, 'registerModules']);
	}

	public function registerModules(): void
	{
		SessionStore::instance()->register();
		Settings::instance()->register();
		Assets::instance()->register();
		Shortcode::instance()->register();
		TokenUpdater::instance()->register();
	}
}
