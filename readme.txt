=== ablesrl Kira Studio ===
Contributors: ablesrl
Tags: ai, chatbot, assistant, chat, support
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed the Kira Studio AI assistant on any page or post via a simple shortcode.

== Description ==

Kira Studio connects your WordPress site to the Kira Studio AI platform, providing an intelligent chat assistant for your visitors.

= Features =

* Floating chat widget — appears as a draggable, resizable panel
* Shortcode integration — place `[kira_studio]` on any page or post
* Session persistence — conversation state is preserved across page loads
* File & image attachments — visitors can share files inside the chat
* Audio recording — voice message support built in
* Multilingual — bundled translations for de, en, es, fr, it, pt
* Developer-friendly — clean PHP namespacing, no jQuery dependency

= Requirements =

A Kira Studio account and API token are required. Configure them under **Settings → Kira Studio** after activating the plugin.

== Installation ==

1. Upload the `kira-studio` folder to the `/wp-content/plugins/` directory, or install via **Plugins → Add New**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Kira Studio** and enter your API token and WebSocket endpoint.
4. Add the shortcode `[kira_studio]` to any page or post where you want the widget to appear.

== Frequently Asked Questions ==

= Do I need a Kira Studio account? =

Yes. The plugin communicates with the Kira Studio backend. Sign up at https://kirastudio.it.

= Can I place multiple widgets on the same page? =

Each shortcode instance is independent. Only one widget is recommended per page for the best user experience.

= Which browsers are supported? =

All modern browsers (Chrome, Firefox, Safari, Edge). Internet Explorer is not supported.

== Screenshots ==

1. The floating chat widget open on the front end.
2. The Kira Studio settings page in the WordPress admin.

== Changelog ==

= 1.0.0 =
* Initial release.

== Source Code ==

This plugin is built with Vue 3 (Vite + Vuetify). The full human-readable source — including the Vue application and all build tooling — is publicly available at:

https://github.com/ablesrl/kirastudio-wp

The `vue-app/` directory in that repository contains the complete, uncompiled Vue 3 source. To rebuild the assets yourself:

1. `cd vue-app`
2. `npm install`
3. `npm run build:wp` — outputs to `assets/dist/`

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
