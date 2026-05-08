<?php

declare(strict_types=1);

namespace KiraStudio;

use KiraStudio\Traits\Singleton;

final class Plugin
{
	use Singleton;

	public function boot(): void
	{
		add_action('plugins_loaded', [$this, 'loadTextdomain']);
		add_action('init', [$this, 'registerModules']);
	}

	public function loadTextdomain(): void
	{
		load_plugin_textdomain('kira-studio', false, dirname(plugin_basename(KIRA_STUDIO_FILE)) . '/languages');
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
