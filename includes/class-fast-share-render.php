<?php
/**
 * Frontend rendering: buttons, placements, shortcode, template function.
 *
 * @package FSB_Share
 */

defined( 'ABSPATH' ) || exit;

class FSB_Share_Render {

	/**
	 * Wire up hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'render_floating_bars' ), 10 );
		add_shortcode( 'fast_share', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Current post context for sharing (URL, title, description, image).
	 *
	 * @return array
	 */
	public static function share_data() {
		$post_id = get_queried_object_id();
		$url     = home_url( add_query_arg( null, null ) );
		$title   = wp_get_document_title();
		$desc    = get_bloginfo( 'description' );
		$image   = '';

		if ( is_singular() && $post_id ) {
			$url   = get_permalink( $post_id );
			$title = get_the_title( $post_id );
			$desc  = has_excerpt( $post_id )
				? get_the_excerpt( $post_id )
				: wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 30 );
			$thumb = get_the_post_thumbnail_url( $post_id, 'large' );
			if ( $thumb ) {
				$image = $thumb;
			}
		}

		$settings = fast_share_get_settings();
		if ( ! empty( $settings['utm_enable'] ) ) {
			$url = self::add_utm( $url, $settings );
		}

		return array(
			'url'   => $url,
			'title' => html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' ),
			'desc'  => html_entity_decode( wp_strip_all_tags( $desc ), ENT_QUOTES, 'UTF-8' ),
			'image' => $image,
		);
	}

	/**
	 * Append UTM parameters to the shared URL.
	 *
	 * @param string $url      URL to modify.
	 * @param array  $settings Plugin settings.
	 * @return string
	 */
	public static function add_utm( $url, $settings ) {
		$args = array();
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $key ) {
			$val = isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
			if ( '' !== $val ) {
				$args[ $key ] = $val;
			}
		}
		if ( empty( $args ) ) {
			return $url;
		}
		return add_query_arg( $args, $url );
	}

	/**
	 * Whether buttons may auto-display in the current context.
	 *
	 * @param array $settings Plugin settings.
	 * @return bool
	 */
	public static function can_auto_display( $settings ) {
		if ( is_admin() ) {
			return false;
		}
		$excluded = self::excluded_ids( $settings );
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( in_array( $post_id, $excluded, true ) ) {
				return false;
			}
			$post_type = get_post_type( $post_id );
			$allowed   = isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ? $settings['post_types'] : array();
			return in_array( $post_type, $allowed, true );
		}
		if ( is_home() || is_front_page() ) {
			return ! empty( $settings['show_home'] );
		}
		if ( is_archive() ) {
			return ! empty( $settings['show_archives'] );
		}
		return false;
	}

	/**
	 * Parse the exclude-by-ID list.
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	public static function excluded_ids( $settings ) {
		$raw = isset( $settings['exclude_ids'] ) ? (string) $settings['exclude_ids'] : '';
		$ids = array();
		foreach ( explode( ',', $raw ) as $part ) {
			$part = trim( $part );
			if ( '' !== $part && ctype_digit( $part ) ) {
				$ids[] = (int) $part;
			}
		}
		return $ids;
	}

	/**
	 * Render the full buttons markup.
	 *
	 * @param array $args Optional overrides: networks (array), shape, size, color_mode, custom_color, icon_label, layout.
	 * @return string HTML markup (empty string when nothing to show).
	 */
	public static function buttons( $args = array() ) {
		$settings = fast_share_get_settings();

		$networks = isset( $args['networks'] ) && is_array( $args['networks'] ) && ! empty( $args['networks'] )
			? array_values( array_intersect( $args['networks'], array_keys( FSB_Share_Networks::all() ) ) )
			: FSB_Share_Networks::enabled( $settings );

		if ( empty( $networks ) ) {
			return '';
		}

		$shape       = isset( $args['shape'] ) ? $args['shape'] : $settings['shape'];
		$size        = isset( $args['size'] ) ? $args['size'] : $settings['size'];
		$color_mode  = isset( $args['color_mode'] ) ? $args['color_mode'] : $settings['color_mode'];
		$icon_label  = isset( $args['icon_label'] ) ? $args['icon_label'] : $settings['icon_label'];
		$layout      = isset( $args['layout'] ) ? $args['layout'] : 'inline';

		$allowed_shapes = array( 'rounded', 'pill', 'square', 'circle' );
		$allowed_sizes  = array( 'small', 'medium', 'large' );
		$allowed_modes  = array( 'brand', 'mono', 'custom' );
		if ( ! in_array( $shape, $allowed_shapes, true ) ) {
			$shape = 'rounded';
		}
		if ( ! in_array( $size, $allowed_sizes, true ) ) {
			$size = 'medium';
		}
		if ( ! in_array( $color_mode, $allowed_modes, true ) ) {
			$color_mode = 'brand';
		}
		$show_label = ( 'icon_label' === $icon_label );

		$data = self::share_data();
		$all  = FSB_Share_Networks::all();

		$classes = array(
			'fast-share',
			'fast-share--' . $shape,
			'fast-share--' . $size,
			'fast-share--' . $color_mode,
			'fast-share--' . $layout,
			$show_label ? 'fast-share--labeled' : 'fast-share--icons',
		);

		$style_attr = '';
		if ( 'custom' === $color_mode ) {
			$custom = isset( $args['custom_color'] ) ? $args['custom_color'] : $settings['custom_color'];
			if ( preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $custom ) ) {
				$style_attr = ' style="--fast-share-color:' . esc_attr( $custom ) . ';"';
			}
		}

		fast_share_enqueue_frontend();

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" role="group" aria-label="' . esc_attr__( 'Share this page', 'fast-share-buttons' ) . '"' . $style_attr . '>';
		foreach ( $networks as $slug ) {
			$def = $all[ $slug ];
			if ( ! empty( $def['copy'] ) ) {
				$html .= sprintf(
					'<button type="button" class="fast-share-btn fast-share-btn--%1$s" data-fast-copy="%2$s" aria-label="%3$s">%4$s%5$s<span class="fast-share-feedback" aria-live="polite"></span></button>',
					esc_attr( $slug ),
					esc_attr( $data['url'] ),
					esc_attr( $def['label'] ),
					FSB_Share_Networks::icon( $slug ),
					$show_label ? '<span class="fast-share-label">' . esc_html( $def['label'] ) . '</span>' : ''
				);
				continue;
			}
			$href = FSB_Share_Networks::share_url( $slug, $data['url'], $data['title'], $data['desc'], $data['image'] );
			$html .= sprintf(
				'<a class="fast-share-btn fast-share-btn--%1$s" href="%2$s" target="_blank" rel="noopener"%3$s aria-label="%4$s">%5$s%6$s</a>',
				esc_attr( $slug ),
				esc_url( $href ),
				! empty( $def['popup'] ) ? ' data-fast-popup="1"' : '',
				// Translators: %s is the network name, e.g. "Share on Facebook".
				esc_attr( sprintf( __( 'Share on %s', 'fast-share-buttons' ), $def['label'] ) ),
				FSB_Share_Networks::icon( $slug ),
				$show_label ? '<span class="fast-share-label">' . esc_html( $def['label'] ) . '</span>' : ''
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Auto-insert buttons into post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_content( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$settings   = fast_share_get_settings();
		$placements = isset( $settings['placements'] ) && is_array( $settings['placements'] ) ? $settings['placements'] : array();
		if ( empty( $placements ) || ! self::can_auto_display( $settings ) ) {
			return $content;
		}
		$before = in_array( 'before', $placements, true ) || in_array( 'both', $placements, true );
		$after  = in_array( 'after', $placements, true ) || in_array( 'both', $placements, true );
		if ( ! $before && ! $after ) {
			return $content;
		}
		$buttons = self::buttons();
		if ( '' === $buttons ) {
			return $content;
		}
		if ( $before ) {
			$content = $buttons . $content;
		}
		if ( $after ) {
			$content = $content . $buttons;
		}
		return $content;
	}

	/**
	 * Render floating side / bottom bars in the footer.
	 *
	 * @return void
	 */
	public static function render_floating_bars() {
		$settings   = fast_share_get_settings();
		$placements = isset( $settings['placements'] ) && is_array( $settings['placements'] ) ? $settings['placements'] : array();
		if ( empty( $placements ) || ! self::can_auto_display( $settings ) ) {
			return;
		}
		if ( in_array( 'floating_side', $placements, true ) ) {
			$buttons = self::buttons( array( 'layout' => 'floating-side', 'icon_label' => 'icon_only' ) );
			if ( '' !== $buttons ) {
				echo $buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in buttons().
			}
		}
		if ( in_array( 'floating_bottom', $placements, true ) ) {
			$buttons = self::buttons( array( 'layout' => 'floating-bottom', 'icon_label' => 'icon_only' ) );
			if ( '' !== $buttons ) {
				echo $buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in buttons().
			}
		}
	}

	/**
	 * Shortcode [fast_share].
	 *
	 * Attributes: networks (comma list), shape, size, color_mode, custom_color,
	 * icon_label (icon_only|icon_label), layout.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'networks'     => '',
				'shape'        => '',
				'size'         => '',
				'color_mode'   => '',
				'custom_color' => '',
				'icon_label'   => '',
				'layout'       => 'inline',
			),
			$atts,
			'fast_share'
		);

		$args = array();
		if ( '' !== trim( $atts['networks'] ) ) {
			$args['networks'] = array_map( 'trim', explode( ',', strtolower( $atts['networks'] ) ) );
		}
		foreach ( array( 'shape', 'size', 'color_mode', 'custom_color', 'icon_label', 'layout' ) as $key ) {
			if ( '' !== trim( $atts[ $key ] ) ) {
				$args[ $key ] = sanitize_text_field( $atts[ $key ] );
			}
		}
		return self::buttons( $args );
	}
}

/**
 * Template function: echo or return the share buttons.
 *
 * @param array $args  Optional overrides (same keys as FSB_Share_Render::buttons()).
 * @param bool  $echo  Echo the markup (true) or return it (false).
 * @return string|void
 */
function fast_share_buttons( $args = array(), $echo = true ) {
	$html = FSB_Share_Render::buttons( $args );
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in buttons().
		return;
	}
	return $html;
}
