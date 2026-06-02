<?php

namespace KiraStudio;

if ( ! defined( 'ABSPATH' ) ) exit;

use KiraStudio\Traits\Singleton;

final class Settings
{
	use Singleton;

	const TOKEN_KEY              = 'ablekist_token';
	const TITLE_KEY              = 'ablekist_title';
	const SHOW_LOGGED_IN_ONLY_KEY = 'ablekist_show_logged_in_only';

	public function register(): void
	{
		add_action('admin_menu', [$this, 'addMenuPage']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
		add_action('admin_init', [$this, 'registerSettings']);
		add_filter(
			'plugin_action_links_' . plugin_basename(ABLEKIST_FILE),
			[$this, 'addSettingsLink']
		);
	}

	public function addMenuPage(): void
	{
		add_menu_page(
			__('Kira Studio', 'kira-studio'),
			__('Kira Studio', 'kira-studio'),
			'manage_options',
			'kira-studio',
			[$this, 'renderPage'],
			ABLEKIST_URL . 'logo.svg',
			30
		);
	}

	public function enqueueAdminStyles(): void
	{
		wp_enqueue_style(
			'kira-studio-admin',
			ABLEKIST_URL . 'assets/admin.css',
			[],
			ABLEKIST_VERSION
		);

		$copied = esc_js(__('Copied!', 'kira-studio'));
		wp_register_script('kira-studio-admin', false, [], ABLEKIST_VERSION, true);
		wp_enqueue_script('kira-studio-admin');
		wp_add_inline_script('kira-studio-admin', $this->buildCopyScript($copied));
	}

	private function buildCopyScript(string $copiedLabel): string
	{
		$s  = "const ksCopyText = (text) => {\n";
		$s .= "\tif (navigator.clipboard && navigator.clipboard.writeText) {\n";
		$s .= "\t\treturn navigator.clipboard.writeText(text);\n";
		$s .= "\t}\n";
		$s .= "\tconst ta = document.createElement('textarea');\n";
		$s .= "\tta.value = text;\n";
		$s .= "\tta.style.cssText = 'position:fixed;opacity:0;pointer-events:none;';\n";
		$s .= "\tdocument.body.appendChild(ta);\n";
		$s .= "\tta.focus();\n";
		$s .= "\tta.select();\n";
		$s .= "\tdocument.execCommand('copy');\n";
		$s .= "\tdocument.body.removeChild(ta);\n";
		$s .= "\treturn Promise.resolve();\n";
		$s .= "};\n\n";
		$s .= "document.querySelectorAll('.ks-copy-btn').forEach((btn) => {\n";
		$s .= "\tbtn.addEventListener('click', () => {\n";
		$s .= "\t\tconst target = document.getElementById(btn.dataset.target);\n";
		$s .= "\t\tif (! target) return;\n";
		$s .= "\t\tksCopyText(target.textContent.trim()).then(() => {\n";
		$s .= "\t\t\tconst label = btn.querySelector(':not(.dashicons)') ?? btn;\n";
		$s .= "\t\t\tconst icon  = btn.querySelector('.dashicons');\n";
		$s .= "\t\t\tconst orig  = label.textContent;\n";
		$s .= "\t\t\tbtn.classList.add('copied');\n";
		$s .= "\t\t\tif (icon) icon.className = 'dashicons dashicons-yes';\n";
		$s .= "\t\t\tlabel.textContent = '" . $copiedLabel . "';\n";
		$s .= "\t\t\tsetTimeout(() => {\n";
		$s .= "\t\t\t\tbtn.classList.remove('copied');\n";
		$s .= "\t\t\t\tif (icon) icon.className = 'dashicons dashicons-clipboard';\n";
		$s .= "\t\t\t\tlabel.textContent = orig;\n";
		$s .= "\t\t\t}, 2000);\n";
		$s .= "\t\t});\n";
		$s .= "\t});\n";
		$s .= "});";
		return $s;
	}

	public function registerSettings(): void
	{
		register_setting('ablekist_settings', self::TOKEN_KEY, [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		]);
		register_setting('ablekist_settings', self::TITLE_KEY, [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Kira Studio',
		]);
		register_setting('ablekist_settings', self::SHOW_LOGGED_IN_ONLY_KEY, [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		]);
	}

	public function renderPage(): void
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		$token     = self::getToken();
		$hasToken  = $token !== '';
		$shortcode = '[ablekist]';
		$shortcodeWithClass = '[ablekist class="my-chat"]';
		?>
		<div class="wrap ks-page">

			<!-- Header -->
			<div class="ks-header">
				<img
					src="<?php echo esc_url(ABLEKIST_URL . 'logo.svg'); ?>"
					alt="Kira Studio"
				/>
				<div class="ks-header-text">
					<h1><?php esc_html_e('Kira Studio', 'kira-studio'); ?></h1>
					<p><?php esc_html_e('AI chat integration for WordPress', 'kira-studio'); ?></p>
				</div>
			</div>

			<div class="ks-grid">

				<!-- Shortcode card -->
				<div class="ks-card">
					<h2 class="ks-card-title">
						<span class="dashicons dashicons-shortcode"></span>
						<?php esc_html_e('Shortcode', 'kira-studio'); ?>
					</h2>

					<p style="font-size:13px;color:#3c434a;margin:0 0 14px;">
						<?php esc_html_e('Paste this shortcode into any page, post, or widget to embed the Kira Studio chat.', 'kira-studio'); ?>
					</p>

					<div class="ks-shortcode-box">
						<code id="ks-shortcode-basic"><?php echo esc_html($shortcode); ?></code>
						<button
							type="button"
							class="ks-copy-btn"
							data-target="ks-shortcode-basic"
						>
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e('Copy', 'kira-studio'); ?>
						</button>
					</div>

					<div class="ks-shortcode-box">
						<code id="ks-shortcode-class"><?php echo esc_html($shortcodeWithClass); ?></code>
						<button
							type="button"
							class="ks-copy-btn"
							data-target="ks-shortcode-class"
						>
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e('Copy', 'kira-studio'); ?>
						</button>
					</div>

					<table class="ks-usage-table" style="margin-top:16px;">
						<thead>
							<tr>
								<th><?php esc_html_e('Attribute', 'kira-studio'); ?></th>
								<th><?php esc_html_e('Default', 'kira-studio'); ?></th>
								<th><?php esc_html_e('Description', 'kira-studio'); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>class</code></td>
								<td><?php esc_html_e('none', 'kira-studio'); ?></td>
								<td><?php esc_html_e('Extra CSS class(es) added to the chat container.', 'kira-studio'); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Status card -->
				<div class="ks-card">
					<h2 class="ks-card-title">
						<span class="dashicons dashicons-info-outline"></span>
						<?php esc_html_e('Status', 'kira-studio'); ?>
					</h2>

					<table class="ks-form-table" style="font-size:13px;">
						<tr>
							<th><?php esc_html_e('API Key', 'kira-studio'); ?></th>
							<td>
								<?php if ($hasToken) : ?>
									<span class="ks-status ok">
										<span class="dashicons dashicons-yes-alt"></span>
										<?php esc_html_e('Configured', 'kira-studio'); ?>
									</span>
								<?php else : ?>
									<span class="ks-status missing">
										<span class="dashicons dashicons-warning"></span>
										<?php esc_html_e('Missing', 'kira-studio'); ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e('Version', 'kira-studio'); ?></th>
							<td><?php echo esc_html(ABLEKIST_VERSION); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e('Documentation', 'kira-studio'); ?></th>
							<td>
								<a
									href="https://kirastudio.it"
									target="_blank"
									rel="noopener"
								>kirastudio.it &nearr;</a>
							</td>
						</tr>
					</table>
				</div>

			</div>

			<!-- How to use card -->
			<div class="ks-card">
				<h2 class="ks-card-title">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e('How to use', 'kira-studio'); ?>
				</h2>
				<ol class="ks-steps">
					<li><?php esc_html_e('Enter your API Key in the Settings section below and save.', 'kira-studio'); ?></li>
					<li><?php esc_html_e('Open any page or post in the WordPress editor.', 'kira-studio'); ?></li>
					<li>
						<?php
						printf(
							/* translators: %s: shortcode */
							esc_html__('Add a Shortcode block and paste %s inside it.', 'kira-studio'),
							'<code>[ablekist]</code>'
						);
						?>
					</li>
					<li><?php esc_html_e('Publish or update the page — the chat widget will appear on the front end.', 'kira-studio'); ?></li>
					<li><?php esc_html_e('Optionally use the class attribute to target the container with custom CSS.', 'kira-studio'); ?></li>
				</ol>
			</div>

			<!-- Settings card -->
			<div class="ks-card">
				<h2 class="ks-card-title">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e('Settings', 'kira-studio'); ?>
				</h2>

				<form method="post" action="options.php">
					<?php settings_fields('ablekist_settings'); ?>
					<table class="ks-form-table">
						<tr>
							<th>
								<label for="ablekist_token">
									<?php esc_html_e('API Key', 'kira-studio'); ?>
								</label>
							</th>
							<td>
								<input
									type="text"
									id="ablekist_token"
									name="<?php echo esc_attr(self::TOKEN_KEY); ?>"
									value="<?php echo esc_attr(get_option(self::TOKEN_KEY, '')); ?>"
									class="regular-text"
								/>
								<p class="description">
									<?php esc_html_e('API token provided by Kira Studio. Required for the chat to connect.', 'kira-studio'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="ablekist_title">
									<?php esc_html_e('Chat Title', 'kira-studio'); ?>
								</label>
							</th>
							<td>
								<input
									type="text"
									id="ablekist_title"
									name="<?php echo esc_attr(self::TITLE_KEY); ?>"
									value="<?php echo esc_attr(get_option(self::TITLE_KEY, 'Kira Studio')); ?>"
									class="regular-text"
								/>
								<p class="description">
									<?php esc_html_e('Title shown in the chat header. Defaults to "Kira Studio".', 'kira-studio'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="<?php echo esc_attr(self::SHOW_LOGGED_IN_ONLY_KEY); ?>">
									<?php esc_html_e('Show only to logged-in users', 'kira-studio'); ?>
								</label>
							</th>
							<td>
								<input
									type="hidden"
									name="<?php echo esc_attr(self::SHOW_LOGGED_IN_ONLY_KEY); ?>"
									value="0"
								/>
								<input
									type="checkbox"
									id="<?php echo esc_attr(self::SHOW_LOGGED_IN_ONLY_KEY); ?>"
									name="<?php echo esc_attr(self::SHOW_LOGGED_IN_ONLY_KEY); ?>"
									value="1"
									<?php checked(get_option(self::SHOW_LOGGED_IN_ONLY_KEY, 0), 1); ?>
								/>
								<p class="description">
									<?php esc_html_e('When enabled, the chat widget is hidden for visitors who are not logged in.', 'kira-studio'); ?>
								</p>
							</td>
						</tr>
					</table>
					<?php submit_button(__('Save Settings', 'kira-studio')); ?>
				</form>
			</div>

		</div>

		<?php
	}

	public function addSettingsLink(array $links): array
	{
		$url = admin_url('admin.php?page=kira-studio');
		array_unshift($links, sprintf(
			'<a href="%s">%s</a>',
			esc_url($url),
			esc_html__('Settings', 'kira-studio')
		));

		return $links;
	}

	public static function getToken(): string
	{
		return (string) get_option(self::TOKEN_KEY, '');
	}

	public static function getTitle(): string
	{
		$title = (string) get_option(self::TITLE_KEY, '');

		return $title !== '' ? $title : 'Kira Studio';
	}

	public static function getShowLoggedInOnly(): bool
	{
		return (bool) get_option(self::SHOW_LOGGED_IN_ONLY_KEY, 0);
	}
}
