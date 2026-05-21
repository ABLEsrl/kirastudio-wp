<?php
/**
 * Plugin Name: ablesrl Kira Studio
 * Plugin URI: https://kirastudio.it
 * Description: Integration plugin for Kira Studio.
 * Version: 1.0.0
 * Author: Able
 * Author URI: https://a-ble.com
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Text Domain: kira-studio
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
Copyright (C) 2026 Able Srl

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2,
as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('ABLEKIST_VERSION', '1.0.0');
define('ABLEKIST_FILE', __FILE__);
define('ABLEKIST_PATH', plugin_dir_path(__FILE__));
define('ABLEKIST_URL', plugin_dir_url(__FILE__));

require_once ABLEKIST_PATH . 'traits/Singleton.php';
require_once ABLEKIST_PATH . 'includes/Plugin.php';
require_once ABLEKIST_PATH . 'includes/Assets.php';
require_once ABLEKIST_PATH . 'includes/Settings.php';
require_once ABLEKIST_PATH . 'includes/SessionStore.php';
require_once ABLEKIST_PATH . 'includes/Shortcode.php';
require_once ABLEKIST_PATH . 'includes/CacheCompat.php';
require_once ABLEKIST_PATH . 'includes/TokenUpdater.php';

\KiraStudio\Plugin::instance()->boot();
