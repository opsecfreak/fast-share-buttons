<?php
/**
 * Network definitions, share URL builders, and inline SVG icons.
 *
 * No external requests, no icon fonts, no CDN. Everything inline.
 *
 * @package MTSUAV_Social_Share
 */

defined( 'ABSPATH' ) || exit;

class MTSUAV_Share_Networks {

	/**
	 * All supported networks, keyed by slug.
	 *
	 * Each entry: label, share URL template ({url}, {title}, {desc}, {image}),
	 * brand color, and whether it opens a popup (vs mailto / copy action).
	 *
	 * @return array
	 */
	public static function all() {
		return array(
			'x'         => array(
				'label' => __( 'X', 'mtsuav-social-share' ),
				'url'   => 'https://twitter.com/intent/tweet?url={url}&text={title}',
				'color' => '#000000',
				'popup' => true,
			),
			'facebook'  => array(
				'label' => __( 'Facebook', 'mtsuav-social-share' ),
				'url'   => 'https://www.facebook.com/sharer/sharer.php?u={url}',
				'color' => '#1877f2',
				'popup' => true,
			),
			'linkedin'  => array(
				'label' => __( 'LinkedIn', 'mtsuav-social-share' ),
				'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
				'color' => '#0a66c2',
				'popup' => true,
			),
			'pinterest' => array(
				'label' => __( 'Pinterest', 'mtsuav-social-share' ),
				'url'   => 'https://pinterest.com/pin/create/button/?url={url}&media={image}&description={title}',
				'color' => '#e60023',
				'popup' => true,
			),
			'whatsapp'  => array(
				'label' => __( 'WhatsApp', 'mtsuav-social-share' ),
				'url'   => 'https://wa.me/?text={title_text}',
				'color' => '#25d366',
				'popup' => true,
			),
			'telegram'  => array(
				'label' => __( 'Telegram', 'mtsuav-social-share' ),
				'url'   => 'https://t.me/share/url?url={url}&text={title}',
				'color' => '#229ed9',
				'popup' => true,
			),
			'reddit'    => array(
				'label' => __( 'Reddit', 'mtsuav-social-share' ),
				'url'   => 'https://www.reddit.com/submit?url={url}&title={title}',
				'color' => '#ff4500',
				'popup' => true,
			),
			'email'     => array(
				'label' => __( 'Email', 'mtsuav-social-share' ),
				'url'   => 'mailto:?subject={title}&body={desc_body}',
				'color' => '#6b7280',
				'popup' => false,
			),
			'copy'      => array(
				'label' => __( 'Copy link', 'mtsuav-social-share' ),
				'url'   => '',
				'color' => '#6b7280',
				'popup' => false,
				'copy'  => true,
			),
		);
	}

	/**
	 * Ordered, enabled network slugs from settings.
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	public static function enabled( $settings ) {
		$all     = self::all();
		$flags   = isset( $settings['networks'] ) && is_array( $settings['networks'] ) ? $settings['networks'] : array();
		$order   = isset( $settings['network_order'] ) && is_array( $settings['network_order'] ) ? $settings['network_order'] : array_keys( $all );
		$ordered = array();
		foreach ( $order as $slug ) {
			if ( isset( $all[ $slug ] ) && ! empty( $flags[ $slug ] ) ) {
				$ordered[] = $slug;
			}
		}
		// Any enabled network missing from the order list gets appended.
		foreach ( $all as $slug => $def ) {
			if ( ! in_array( $slug, $ordered, true ) && ! empty( $flags[ $slug ] ) ) {
				$ordered[] = $slug;
			}
		}
		return $ordered;
	}

	/**
	 * Build the share href for a network.
	 *
	 * @param string $slug    Network slug.
	 * @param string $url     URL being shared (UTM already applied).
	 * @param string $title   Page title.
	 * @param string $desc    Page description.
	 * @param string $image   Image URL (for Pinterest).
	 * @return string
	 */
	public static function share_url( $slug, $url, $title, $desc, $image = '' ) {
		$all = self::all();
		if ( ! isset( $all[ $slug ] ) || '' === $all[ $slug ]['url'] ) {
			return '';
		}
		$template = $all[ $slug ]['url'];
		$replacements = array(
			'{url}'        => rawurlencode( $url ),
			'{title}'      => rawurlencode( $title ),
			'{desc}'       => rawurlencode( $desc ),
			'{image}'      => rawurlencode( $image ),
			// WhatsApp wants "Title URL" as one text param.
			'{title_text}' => rawurlencode( trim( $title . ' ' . $url ) ),
			// Email body: description, blank line, URL.
			'{desc_body}'  => rawurlencode( trim( $desc . "\n\n" . $url ) ),
		);
		return strtr( $template, $replacements );
	}

	/**
	 * Inline SVG icon for a network. Stylized glyphs, currentColor fill.
	 *
	 * @param string $slug Network slug.
	 * @return string SVG markup.
	 */
	public static function icon( $slug ) {
		$paths = array(
			'x'         => '<path d="M18.9 1.2h3.7l-8.1 9.2 9.5 12.5h-7.4l-5.8-7.6-6.7 7.6H.5l8.6-9.8L0 1.2h7.6l5.2 6.9 6.1-6.9zm-1.3 19.5h2L6.5 3.2H4.3l13.3 17.5z"/>',
			'facebook'  => '<path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.7c0-.9.3-1.6 1.6-1.6h1.7V4.2c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.4-4 4.1v2.6H7.7V14h2.7v8h3.1z"/>',
			'linkedin'  => '<path d="M6.9 8.6H3.6V21h3.3V8.6zM5.2 3.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM20.4 13.4V21h-3.3v-3.6c0-.9 0-2-1.2-2s-1.4 1-1.4 2V21h-3.3V8.6h3.2v1.7c.4-.8 1.5-1.9 3.2-1.9 2.3 0 2.8 1.8 2.8 4z"/>',
			'pinterest' => '<path d="M12 2a10 10 0 0 0-3.7 19.3c-.1-.8-.2-2 0-2.9l1.2-5s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.6 2.2-.9 3.4-.2 1 .5 1.8 1.5 1.8 1.8 0 3.1-1.9 3.1-4.6 0-2.4-1.7-4.1-4.2-4.1-2.8 0-4.5 2.1-4.5 4.4 0 .9.3 1.8.8 2.3.1.1.1.2.1.3l-.3 1.1c0 .2-.2.2-.4.1-1.2-.6-2-2.4-2-3.9 0-3.2 2.3-6.1 6.7-6.1 3.5 0 6.2 2.5 6.2 5.8 0 3.5-2.2 6.3-5.2 6.3-1 0-2-.5-2.3-1.2l-.6 2.4c-.2.9-.9 2-1.3 2.6A10 10 0 1 0 12 2z"/>',
			'whatsapp'  => '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.6-6.1c-.3-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4 0-.5.1-.7l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.9.9-1.1 2.2-.2 3.9.9 1.9 2.4 3.6 4.3 4.7.6.4 1.9.6 2.6.2.5-.2 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2 0-.1-.2-.2-.4-.3z"/>',
			'telegram'  => '<path d="M21.9 3.3 2.7 10.8c-.8.3-.8 1.4.1 1.6l4.8 1.5 1.9 5.7c.3.8 1.3.9 1.8.2l2.7-3.2 5 3.7c.6.5 1.6.1 1.8-.7l3-14.1c.2-1-.9-1.9-1.9-1.2zM8.5 13.4l9.6-6.6c.2-.2.5.1.3.3l-7.9 7.1-.3 3-1.7-3.8z"/>',
			'reddit'    => '<path d="M22 12.1c0-.7-.6-1.3-1.3-1.3-.3 0-.6.1-.9.3-1.5-1.1-3.6-1.8-5.9-1.9l1-4.7 3.3.8c.1.4.5.7.9.7.6 0 1-.5 1-1s-.5-1-1-1c-.4 0-.7.2-.9.5l-3.7-.9c-.2 0-.4.1-.4.3l-1.1 5.2c-2.4.1-4.5.8-6 1.9-.3-.2-.6-.3-.9-.3-.7 0-1.3.6-1.3 1.3 0 .5.3 1 .8 1.2 0 .2-.1.4-.1.6 0 3.2 4 5.8 9 5.8s9-2.6 9-5.8c0-.2 0-.4-.1-.6.5-.2.8-.7.8-1.2zM8.6 13.1c0-.8.7-1.5 1.5-1.5s1.5.7 1.5 1.5-.7 1.5-1.5 1.5-1.5-.7-1.5-1.5zm6.5 3.9c-.7.7-1.9 1-3.1 1s-2.4-.4-3.1-1c-.1-.1-.1-.3 0-.4.1-.1.3-.1.4 0 .6.6 1.6.9 2.7.9s2.1-.3 2.7-.9c.1-.1.3-.1.4 0 .1.1.1.3 0 .4zm-.4-2.4c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5z"/>',
			'email'     => '<path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7.2L4.2 7h15.6L12 12.2zM5 8.5l5.4 3.9c.3.2.8.2 1.1 0L17 8.5V17H5V8.5z"/>',
			'copy'      => '<path d="M10 14a5 5 0 0 0 7.1.5l2.4-2.4a5 5 0 0 0-7.1-7.1l-1.4 1.4M14 10a5 5 0 0 0-7.1-.5l-2.4 2.4a5 5 0 0 0 7.1 7.1l1.4-1.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="10" cy="14" r="1.6"/><circle cx="14" cy="10" r="1.6"/>',
		);
		$path = isset( $paths[ $slug ] ) ? $paths[ $slug ] : $paths['copy'];
		return '<svg class="mtsuav-share-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}
}
