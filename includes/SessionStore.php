<?php

declare(strict_types=1);

namespace KiraStudio;

use KiraStudio\Traits\Singleton;

final class SessionStore
{
	use Singleton;

	private const SESSION_KEY = 'ablekist_chat';
	private const AJAX_ACTION = 'ablekist_save_session';

	private const DEFAULTS = [
		'conversation_id' => '',
		'chat_open'       => false,
		'panel_top'       => 0,
		'panel_left'      => 0,
		'panel_width'     => 0,
		'panel_height'    => 0,
	];

	public function register(): void
	{
		add_action('wp_ajax_' . self::AJAX_ACTION,        [$this, 'ajaxSave']);
		add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'ajaxSave']);
	}

	private static function resumeSession(): void
	{
		if (headers_sent() || session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		session_start();
	}

	public static function get(): array
	{
		// Only resume an existing session — never create one just for a read.
		// Anonymous visitors with no session cookie remain fully cacheable.
		if (session_status() !== PHP_SESSION_ACTIVE) {
			if (empty($_COOKIE[session_name()])) {
				return self::DEFAULTS;
			}

			self::resumeSession();
		}

		if (session_status() !== PHP_SESSION_ACTIVE) {
			return self::DEFAULTS;
		}

		$raw = $_SESSION[self::SESSION_KEY] ?? null;

		if (! is_string($raw)) {
			return self::DEFAULTS;
		}

		$decoded = json_decode($raw, true);

		if (! is_array($decoded)) {
			return self::DEFAULTS;
		}

		return [
			'conversation_id' => isset($decoded['conversation_id'])
				? sanitize_text_field((string) $decoded['conversation_id'])
				: '',
			'chat_open'       => ! empty($decoded['chat_open']),
			'panel_top'       => isset($decoded['panel_top'])   ? (int) $decoded['panel_top']   : 0,
			'panel_left'      => isset($decoded['panel_left'])  ? (int) $decoded['panel_left']  : 0,
			'panel_width'     => isset($decoded['panel_width']) ? (int) $decoded['panel_width'] : 0,
			'panel_height'    => isset($decoded['panel_height'])? (int) $decoded['panel_height']: 0,
		];
	}

	private static function clampInt($value, int $min, int $max): int
	{
		return max($min, min($max, (int) $value));
	}

	public static function set(array $data): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		$current = self::get();

		$clean = [
			'conversation_id' => array_key_exists('conversation_id', $data)
				? substr(sanitize_text_field((string) $data['conversation_id']), 0, 200)
				: $current['conversation_id'],
			'chat_open'       => array_key_exists('chat_open', $data)
				? (bool) $data['chat_open']
				: $current['chat_open'],
			'panel_top'       => array_key_exists('panel_top', $data)
				? self::clampInt($data['panel_top'], 0, 16000)
				: $current['panel_top'],
			'panel_left'      => array_key_exists('panel_left', $data)
				? self::clampInt($data['panel_left'], 0, 16000)
				: $current['panel_left'],
			'panel_width'     => array_key_exists('panel_width', $data)
				? self::clampInt($data['panel_width'], 0, 8000)
				: $current['panel_width'],
			'panel_height'    => array_key_exists('panel_height', $data)
				? self::clampInt($data['panel_height'], 0, 8000)
				: $current['panel_height'],
		];

		$_SESSION[self::SESSION_KEY] = wp_json_encode($clean);
	}

	public function ajaxSave(): void
	{
		self::resumeSession();

		$raw     = isset($_POST['data']) ? wp_unslash($_POST['data']) : '{}';
		$payload = json_decode((string) $raw, true);

		if (! is_array($payload)) {
			wp_send_json_error('invalid_data', 400);
		}

		self::set($payload);
		wp_send_json_success();
	}
}
