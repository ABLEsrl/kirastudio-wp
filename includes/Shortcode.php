<?php

if ( ! defined( 'ABSPATH' ) ) exit;

declare(strict_types=1);

namespace KiraStudio;

use KiraStudio\Traits\Singleton;

final class Shortcode
{
	use Singleton;

	private const SHORTCODE = 'ablekist';

	public function register(): void
	{
		add_shortcode(self::SHORTCODE, [$this, 'render']);
	}

	public function render(array $atts = []): string
	{
		if (Settings::getShowLoggedInOnly() && ! is_user_logged_in()) {
			return '';
		}

		// Nonce is user/time-specific — tell page caches not to store this page.
		if (! defined('DONOTCACHEPAGE')) {
			define('DONOTCACHEPAGE', true); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- standard WP caching constant expected by caching plugins
		}

		$attributes = shortcode_atts([
			'class' => '',
		], $atts, self::SHORTCODE);

		$token = Settings::getToken();
		$appId = 'kira-studio-app-' . wp_unique_id();
		$extraClasses = array_filter(array_map('sanitize_html_class', explode(' ', (string) $attributes['class'])));
		$cssClass = trim('kira-studio-app-root ' . implode(' ', $extraClasses));

		$session   = SessionStore::get();
		$sessionId = session_id() ?: '';
		$userId    = get_current_user_id();

		$config = [
			'token'          => $token,
			'title'          => Settings::getTitle(),
			'appId'          => $appId,
			'conversationId' => $session['conversation_id'],
			'chatOpen'       => $session['chat_open'],
			'ajaxUrl'        => admin_url('admin-ajax.php'),
			'sessionId'      => $sessionId,
			'panelTop'       => $session['panel_top'],
			'panelLeft'      => $session['panel_left'],
			'panelWidth'     => $session['panel_width'],
			'panelHeight'    => $session['panel_height'],
		];

		if ($userId > 0) {
			$config['loggedUserId'] = $userId;
		}

		Assets::instance()->enqueueForShortcode($config);

		return sprintf('<div id="%s" class="%s"></div>', esc_attr($appId), esc_attr($cssClass));
	}
}
