<?php

namespace KiraStudio;

if ( ! defined( 'ABSPATH' ) ) exit;

use KiraStudio\Traits\Singleton;

final class TokenUpdater
{
	use Singleton;

	const ROUTE_NAMESPACE = 'kira-studio/v1';
	const ROUTE_PATH      = '/update-token';
	const ALLOWED_HOST    = '';

	// RS256 public key from kirastudio.it.
	const PUBLIC_KEY =
		"-----BEGIN PUBLIC KEY-----\n" .
		"MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAvDxwa2QXh8RfOYLOdmmx\n" .
		"oAW4nzVIYMw7NSeorGjJiPPccYu1L1bm9uu8azTCzhw8BC9xAuCi6Y+E15sFEqMM\n" .
		"/iGkOdQE7pc3rK9GM/Wa0YaN144NUSr73+NrO9LK4qvUq2MhPHYOceSlZsQoSFQK\n" .
		"TCjG0eN8N5rd0O3NU5+mv2eQYryG324n0HvfeFiNw5Z21GbRO2fPRdtVkny9uxaG\n" .
		"E978DaD7jqYXif63WtjOALbJ3ejj3pYW4/lbmwmJj4aZ9GJxp7jlnAxy0V8nWTu1\n" .
		"dLpljD7n2IiT+lMwOF5n6xTmmmBYgXrngunB3zInQ/yGk1ekODre8VDpYavUpqZx\n" .
		"QjAfmMncVkiP53niZ2YaW50MbN9vrTeW7Mjhs22AP+PItNGNc90/D6RwRhZgr4pX\n" .
		"Mo3RZ7OyZe5sBc/Gc2HWLAGXub8ose4lTazuoMNa57etMKklXuW/VDPP3SiNDFUs\n" .
		"OYIC/lh04A1qmHpJCI27WFhl6+6bKwYF8+yW4+pwhvsHAgMBAAE=\n" .
		"-----END PUBLIC KEY-----";

	public function register(): void
	{
		add_action('rest_api_init', [$this, 'registerRoute']);
	}

	public function registerRoute(): void
	{
		register_rest_route(self::ROUTE_NAMESPACE, self::ROUTE_PATH, [
			'methods'             => 'POST',
			'callback'            => [$this, 'handleRequest'],
			'permission_callback' => [$this, 'checkPermission'],
		]);
	}

	public function checkPermission(\WP_REST_Request $request): bool|\WP_Error
	{
		if (! $this->originAllowed($request)) {
			return new \WP_Error(
				'forbidden_origin',
				'Forbidden',
				['status' => 403]
			);
		}

		$auth = $request->get_header('Authorization');

		if (! $auth || ! str_starts_with($auth, 'Bearer ')) {
			return new \WP_Error(
				'missing_token',
				'Authorization header missing or malformed',
				['status' => 401]
			);
		}

		$jwt = substr($auth, 7);

		if (! $this->verifyJwt($jwt)) {
			return new \WP_Error(
				'invalid_token',
				'JWT signature invalid',
				['status' => 401]
			);
		}

		return true;
	}

	public function handleRequest(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$body  = $request->get_json_params();
		$token = $body['token'] ?? null;

		if (! $token || ! is_string($token) || trim($token) === '') {
			return new \WP_Error(
				'invalid_body',
				'Missing or empty token field',
				['status' => 400]
			);
		}

		update_option(Settings::TOKEN_KEY, sanitize_text_field($token));

		CacheCompat::purgeAll();

		return rest_ensure_response(['success' => true]);
	}

	private function originAllowed(\WP_REST_Request $request): bool
	{
		if (! self::ALLOWED_HOST) {
			return true;
		}

		foreach (['Origin', 'Referer'] as $header) {
			$value = $request->get_header($header);
			if ($value && $this->hostIsAllowed($value)) {
				return true;
			}
		}

		return false;
	}

	private function hostIsAllowed(string $url): bool
	{
		$host = wp_parse_url($url, PHP_URL_HOST);

		if (! $host) {
			return false;
		}

		return $host === self::ALLOWED_HOST
			|| str_ends_with($host, '.' . self::ALLOWED_HOST);
	}

	private function verifyJwt(string $jwt): bool
	{
		$parts = explode('.', $jwt);

		if (count($parts) !== 3) {
			return false;
		}

		[$headerB64, $payloadB64, $signatureB64] = $parts;

		$header = json_decode($this->base64urlDecode($headerB64), true);

		if (! is_array($header) || ($header['alg'] ?? '') !== 'RS256') {
			return false;
		}

		$signature = $this->base64urlDecode($signatureB64);

		if ($signature === '' || $signature === false) {
			return false;
		}

		$pubKey = openssl_pkey_get_public(self::PUBLIC_KEY);

		if (! $pubKey) {
			return false;
		}

		return openssl_verify("$headerB64.$payloadB64", $signature, $pubKey, OPENSSL_ALGO_SHA256) === 1;
	}

	private function base64urlDecode(string $input): string
	{
		$padded = str_pad(
			strtr($input, '-_', '+/'),
			strlen($input) + (4 - strlen($input) % 4) % 4,
			'='
		);

		return (string) base64_decode($padded, true);
	}
}
