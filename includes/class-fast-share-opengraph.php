<?php
/**
 * Open Graph and Twitter Card tags on singular views.
 *
 * @package FSB_Share
 */

defined( 'ABSPATH' ) || exit;

class FSB_Share_OpenGraph {

	/**
	 * Wire up hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output_tags' ), 5 );
	}

	/**
	 * Print og:/twitter: meta tags.
	 *
	 * @return void
	 */
	public static function output_tags() {
		$settings = fast_share_get_settings();
		if ( empty( $settings['og_enable'] ) ) {
			return;
		}
		if ( ! is_singular() ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$data  = FSB_Share_Render::share_data();
		$image = $data['image'];
		if ( '' === $image && ! empty( $settings['og_image'] ) ) {
			$fallback = wp_get_attachment_image_url( (int) $settings['og_image'], 'large' );
			if ( $fallback ) {
				$image = $fallback;
			}
		}

		$tags = array(
			'og:title'       => $data['title'],
			'og:description' => $data['desc'],
			'og:url'         => $data['url'],
			'og:type'        => 'article',
			'twitter:card'   => 'summary_large_image',
			'twitter:title'  => $data['title'],
			'twitter:description' => $data['desc'],
		);
		if ( '' !== $image ) {
			$tags['og:image']       = $image;
			$tags['twitter:image']  = $image;
		}

		foreach ( $tags as $property => $content ) {
			if ( '' === trim( (string) $content ) ) {
				continue;
			}
			printf(
				'<meta property="%s" content="%s" />' . "\n",
				esc_attr( $property ),
				esc_attr( $content )
			);
		}
	}
}
