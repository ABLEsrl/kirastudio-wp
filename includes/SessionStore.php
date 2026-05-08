<?php

declare(strict_types=1);

namespace KiraStudio;

use KiraStudio\Traits\Singleton;

final class SessionStore
{
	use Singleton;

	private const SESSION_KEY  = 'kira_studio_chat';
	private const NONCE_ACTION = 'kira_studio_session';
	private const AJAX_ACTION  = 'kira_studio_save_session';

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
		$this->start();
		add_action('wp_ajax_' . self::AJAX_ACTION,        [$this, 'ajaxSave']);
		add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'ajaxSave']);
	}

	public function start(): void
	{
		if (headers_sent() || session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		session_start();
	}

	public static function get(): array
	{
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

	public static function set(array $data): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		$current = self::get();

		$clean = [
			'conversation_id' => array_key_exists('conversation_id', $data)
				? sanitize_text_field((string) $data['conversation_id'])
				: $current['conversation_id'],
			'chat_open'       => array_key_exists('chat_open', $data)
				? (bool) $data['chat_open']
				: $current['chat_open'],
			'panel_top'       => array_key_exists('panel_top', $data)
				? (int) $data['panel_top']
				: $current['panel_top'],
			'panel_left'      => array_key_exists('panel_left', $data)
				? (int) $data['panel_left']
				: $current['panel_left'],
			'panel_width'     => array_key_exists('panel_width', $data)
				? (int) $data['panel_width']
				: $current['panel_width'],
			'panel_height'    => array_key_exists('panel_height', $data)
				? (int) $data['panel_height']
				: $current['panel_height'],
		];

		$_SESSION[self::SESSION_KEY] = wp_json_encode($clean);
	}

	public function ajaxSave(): void
	{
		if (
			! isset($_POST['nonce']) ||
			! wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['nonce'])),
				self::NONCE_ACTION
			)
		) {
			wp_send_json_error('invalid_nonce', 403);
		}

		$raw     = isset($_POST['data']) ? wp_unslash($_POST['data']) : '{}';
		$payload = json_decode((string) $raw, true);

		if (! is_array($payload)) {
			wp_send_json_error('invalid_data', 400);
		}

		self::set($payload);
		wp_send_json_success();
	}
}
