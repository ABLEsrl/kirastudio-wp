<?php

namespace KiraStudio;

if ( ! defined( 'ABSPATH' ) ) exit;

use KiraStudio\Traits\Singleton;

final class Assets
{
	use Singleton;

	private const HANDLE = 'kira-studio-app';
	private const ENTRY_KEY = 'src/main.js';
	private array $manifestStyleHandles = [];

	public function register(): void
	{
		add_action('wp_enqueue_scripts', [$this, 'registerFrontendAssets']);
		add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 2);
	}

	public function addModuleType(string $tag, string $handle): string
	{
		if ($handle === self::HANDLE) {
			return str_replace('<script ', '<script type="module" ', $tag);
		}

		return $tag;
	}

	public function registerFrontendAssets(): void
	{
		$manifest     = $this->getManifest();
		$manifestData = $manifest[self::ENTRY_KEY] ?? [];
		$scriptFile   = $manifestData['file'] ?? 'app.js';

		wp_register_script(
			self::HANDLE,
			ABLEKIST_URL . 'assets/dist/' . ltrim($scriptFile, '/'),
			[],
			ABLEKIST_VERSION,
			true
		);

		$this->registerAllChunkStyles($manifest, self::ENTRY_KEY);
	}

	public function enqueueForShortcode(array $props): void
	{
		if (wp_style_is(self::HANDLE, 'registered')) {
			wp_enqueue_style(self::HANDLE);
		}

		foreach ($this->manifestStyleHandles as $styleHandle) {
			if (wp_style_is($styleHandle, 'registered')) {
				wp_enqueue_style($styleHandle);
			}
		}

		wp_enqueue_script(self::HANDLE);
		wp_add_inline_script(
			self::HANDLE,
			'window.kiraStudioConfig = ' . wp_json_encode($props) . ';',
			'before'
		);
	}

	private function registerAllChunkStyles(array $manifest, string $chunkKey, array &$visited = []): void
	{
		if (isset($visited[$chunkKey])) {
			return;
		}

		$visited[$chunkKey] = true;
		$chunk = $manifest[$chunkKey] ?? [];

		foreach (($chunk['css'] ?? []) as $cssPath) {
			$styleHandle = self::HANDLE . '-' . md5($cssPath);

			if (! in_array($styleHandle, $this->manifestStyleHandles, true)) {
				$this->manifestStyleHandles[] = $styleHandle;
				wp_register_style(
					$styleHandle,
					ABLEKIST_URL . 'assets/dist/' . ltrim((string) $cssPath, '/'),
					[],
					ABLEKIST_VERSION
				);
			}
		}

		foreach (($chunk['imports'] ?? []) as $importKey) {
			$this->registerAllChunkStyles($manifest, $importKey, $visited);
		}
	}

	private function getManifest(): array
	{
		$manifestPath = ABLEKIST_PATH . 'assets/dist/.vite/manifest.json';

		if (! file_exists($manifestPath)) {
			return [];
		}

		$decoded = json_decode((string) file_get_contents($manifestPath), true);

		return is_array($decoded) ? $decoded : [];
	}
}
