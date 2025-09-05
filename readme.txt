=== Sticky Contact Lite ===
Contributors: hrezaei
Tags: contact, whatsapp, call, floating, sticky
Requires at least: 5.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ultra-lightweight floating Call & WhatsApp buttons for WordPress. Minimal, mobile-friendly, and easy to use.

== Description ==
Sticky Contact Lite adds minimal floating Call and WhatsApp buttons to your WordPress site.
- Lightweight (no DB tables, no external deps)
- Auto-injection site-wide or via `[sticky_contact]` shortcode
- Mobile-only toggle
- RTL-friendly
- Position: left or right
- i18n-ready (Text Domain: sticky-contact-lite)

== Installation ==
1. Upload the plugin files to `/wp-content/plugins/sticky-contact-lite/`, or install the ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to **Settings → Sticky Contact** and enter your phone/WhatsApp.
4. (Optional) Use shortcode `[sticky_contact]` where needed.

== Frequently Asked Questions ==
= Buttons don’t show up =
- Ensure you filled phone or WhatsApp in settings and clicked **Save**.
- If “Mobile only” is enabled, test on mobile or disable it.
- Your theme must call `wp_footer()`. The plugin also hooks `wp_body_open` as fallback.

= How to set WhatsApp link? =
- Enter only the digits (e.g., `98912xxxxxxx`) or a full `https://wa.me/98912xxxxxxx`.

== Screenshots ==
1. Settings page
2. Buttons on front-end (right position)
3. Buttons on front-end (left position)

== Changelog ==
= 1.0.1 =
* Align ownership metadata and headers/readme.
* Remove discouraged load_plugin_textdomain() call; rely on WP core auto-loading (since 4.6).
* Minor sanitization of inputs.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.0.1 =
Metadata/i18n alignment and minor fixes. Please update.
