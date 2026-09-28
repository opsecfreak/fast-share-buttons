=== Fast Share Buttons ===
Contributors: mobiletechspecialists
Tags: social share, share buttons, social media, open graph, privacy
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight social share buttons for X, Facebook, LinkedIn, Pinterest, WhatsApp, Telegram, Reddit, Email and Copy Link. No external requests, no tracking.

== Description ==

Fast Share Buttons adds fast, privacy-friendly share buttons to your WordPress site. Everything is rendered locally: inline SVG icons, no icon fonts, no CDN scripts, no tracking pixels.

Features:

* 9 share networks: X, Facebook, LinkedIn, Pinterest, WhatsApp, Telegram, Reddit, Email, and Copy Link
* Toggle each network on or off and reorder them with drag and drop
* Placements: before content, after content, both, floating side bar (desktop), floating bottom bar (mobile), or shortcode/manual only
* Button styles: rounded, pill, square, or circle; small, medium, or large; brand colors, monochrome, or a custom color; icon only or icon with label
* Display rules: choose post types, show on the blog homepage and archives, exclude specific post IDs
* UTM builder: automatically append utm_source, utm_medium, and utm_campaign to shared URLs
* Open Graph and Twitter Card tags on posts and pages, with a fallback image picker
* Copy Link button with clipboard support and "Copied!" feedback
* Shortcode `[fast_share]` with style overrides, plus the `fast_share_buttons()` template tag
* Accessible: aria-labels on every button, keyboard-focus styles, reduced-motion support
* Automatic updates from the public GitHub repository

== Installation ==

1. Upload the `fast-share-buttons` folder to `/wp-content/plugins/` or install the zip through Plugins > Add New > Upload Plugin.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Settings > Social Share to choose networks, placement, and appearance.

To place buttons manually, add the shortcode `[fast_share]` to any post or page, or call `<?php fast_share_buttons(); ?>` in your theme.

== Frequently Asked Questions ==

= Does this plugin load anything from external servers? =
No. Icons are inline SVG, styles and scripts are served from your own site. The only outbound request the plugin ever makes is the update check against the public GitHub releases API (see Privacy below).

= Does it track my visitors? =
No. There are no analytics, pixels, or tracking scripts of any kind.

= How do automatic updates work? =
The plugin polls the public GitHub releases page for this plugin (api.github.com) at most every 12 hours. When a new release exists, WordPress offers it as a normal plugin update. No license key or account is needed.

= Can I show buttons only on certain posts? =
Yes. Use the post type checkboxes, the blog homepage and archive toggles, the excluded post IDs field, or place buttons manually with the shortcode or template tag.

= Which networks are supported? =
X, Facebook, LinkedIn, Pinterest, WhatsApp, Telegram, Reddit, Email, and Copy Link. Each can be toggled and reordered.

== Screenshots ==

1. Settings page: networks with drag-and-drop ordering.
2. Placement and appearance options.
3. Share buttons on a post (pill style, brand colors).
4. Floating side bar on desktop.

== Changelog ==

= 1.0.0 =
* Initial release. Nine share networks, drag-and-drop ordering, five placement options, button style controls, display rules, UTM builder, Open Graph tags, copy-link button, shortcode and template tag, automatic updates from GitHub.

== Privacy ==

This plugin does not collect, store, or transmit any visitor data. It sets no cookies and loads no third-party resources on your site.

The automatic updater makes one outbound request from your server (never from visitor browsers) to `https://api.github.com/repos/opsecfreak/fast-share-buttons/releases/latest`, cached for 12 hours (1 hour after a failure). The request sends only a generic updater user-agent (MTSUAV-Updater plus your WordPress version); no personal data, license keys, or site identifiers are transmitted. If the request fails, the plugin simply offers no update.
