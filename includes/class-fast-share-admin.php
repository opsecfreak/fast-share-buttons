<?php
/**
 * Settings page under the Settings menu, built on the Settings API.
 *
 * Single option array: fast_share_settings. Full sanitization.
 *
 * @package FSB_Share
 */

defined( 'ABSPATH' ) || exit;

class FSB_Share_Admin {

	const PAGE_SLUG = 'fast-share-buttons';
	const OPTION_GROUP = 'fast_share_options';

	/**
	 * Wire up hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_options_page(
			__( 'Fast Share Buttons', 'fast-share-buttons' ),
			__( 'Social Share', 'fast-share-buttons' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on our screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'fast-share-admin' );
		wp_enqueue_script( 'fast-share-admin' );
		wp_enqueue_media();
	}

	/**
	 * Register the setting, sections, and fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			FAST_SHARE_OPTION,
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) )
		);

		add_settings_section(
			'fast_share_section_networks',
			__( 'Networks', 'fast-share-buttons' ),
			array( __CLASS__, 'section_networks_desc' ),
			self::PAGE_SLUG
		);
		add_settings_field(
			'fast_share_networks',
			__( 'Share networks', 'fast-share-buttons' ),
			array( __CLASS__, 'field_networks' ),
			self::PAGE_SLUG,
			'fast_share_section_networks'
		);

		add_settings_section(
			'fast_share_section_appearance',
			__( 'Placement and appearance', 'fast-share-buttons' ),
			null,
			self::PAGE_SLUG
		);
		add_settings_field( 'fast_share_placements', __( 'Placements', 'fast-share-buttons' ), array( __CLASS__, 'field_placements' ), self::PAGE_SLUG, 'fast_share_section_appearance' );
		add_settings_field( 'fast_share_shape', __( 'Button shape', 'fast-share-buttons' ), array( __CLASS__, 'field_shape' ), self::PAGE_SLUG, 'fast_share_section_appearance' );
		add_settings_field( 'fast_share_size', __( 'Button size', 'fast-share-buttons' ), array( __CLASS__, 'field_size' ), self::PAGE_SLUG, 'fast_share_section_appearance' );
		add_settings_field( 'fast_share_color', __( 'Colors', 'fast-share-buttons' ), array( __CLASS__, 'field_color' ), self::PAGE_SLUG, 'fast_share_section_appearance' );
		add_settings_field( 'fast_share_labels', __( 'Labels', 'fast-share-buttons' ), array( __CLASS__, 'field_labels' ), self::PAGE_SLUG, 'fast_share_section_appearance' );

		add_settings_section(
			'fast_share_section_display',
			__( 'Display rules', 'fast-share-buttons' ),
			null,
			self::PAGE_SLUG
		);
		add_settings_field( 'fast_share_post_types', __( 'Post types', 'fast-share-buttons' ), array( __CLASS__, 'field_post_types' ), self::PAGE_SLUG, 'fast_share_section_display' );
		add_settings_field( 'fast_share_home', __( 'Homepage and archives', 'fast-share-buttons' ), array( __CLASS__, 'field_home_archives' ), self::PAGE_SLUG, 'fast_share_section_display' );
		add_settings_field( 'fast_share_exclude', __( 'Excluded post IDs', 'fast-share-buttons' ), array( __CLASS__, 'field_exclude' ), self::PAGE_SLUG, 'fast_share_section_display' );

		add_settings_section(
			'fast_share_section_sharing',
			__( 'Sharing and SEO', 'fast-share-buttons' ),
			null,
			self::PAGE_SLUG
		);
		add_settings_field( 'fast_share_utm', __( 'UTM tracking', 'fast-share-buttons' ), array( __CLASS__, 'field_utm' ), self::PAGE_SLUG, 'fast_share_section_sharing' );
		add_settings_field( 'fast_share_og', __( 'Open Graph tags', 'fast-share-buttons' ), array( __CLASS__, 'field_og' ), self::PAGE_SLUG, 'fast_share_section_sharing' );
	}

	/**
	 * Sanitize the full option array.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = fast_share_defaults();
		$input    = is_array( $input ) ? $input : array();
		$out      = $defaults;

		$network_slugs = array_keys( FSB_Share_Networks::all() );

		// Network toggles.
		$networks = array();
		foreach ( $network_slugs as $slug ) {
			$networks[ $slug ] = ! empty( $input['networks'][ $slug ] ) ? 1 : 0;
		}
		$out['networks'] = $networks;

		// Network order: comma string or array, keep known slugs only, append missing.
		$order_raw = isset( $input['network_order'] ) ? $input['network_order'] : array();
		if ( is_string( $order_raw ) ) {
			$order_raw = explode( ',', $order_raw );
		}
		$order = array();
		foreach ( (array) $order_raw as $slug ) {
			$slug = sanitize_key( trim( (string) $slug ) );
			if ( in_array( $slug, $network_slugs, true ) && ! in_array( $slug, $order, true ) ) {
				$order[] = $slug;
			}
		}
		foreach ( $network_slugs as $slug ) {
			if ( ! in_array( $slug, $order, true ) ) {
				$order[] = $slug;
			}
		}
		$out['network_order'] = $order;

		// Placements.
		$allowed_placements = array( 'before', 'after', 'both', 'floating_side', 'floating_bottom' );
		$placements         = array();
		if ( isset( $input['placements'] ) && is_array( $input['placements'] ) ) {
			foreach ( $input['placements'] as $p ) {
				$p = sanitize_key( $p );
				if ( in_array( $p, $allowed_placements, true ) && ! in_array( $p, $placements, true ) ) {
					$placements[] = $p;
				}
			}
		}
		$out['placements'] = $placements;

		// Appearance selects.
		$out['shape'] = in_array( $input['shape'] ?? '', array( 'rounded', 'pill', 'square', 'circle' ), true )
			? $input['shape'] : $defaults['shape'];
		$out['size'] = in_array( $input['size'] ?? '', array( 'small', 'medium', 'large' ), true )
			? $input['size'] : $defaults['size'];
		$out['color_mode'] = in_array( $input['color_mode'] ?? '', array( 'brand', 'mono', 'custom' ), true )
			? $input['color_mode'] : $defaults['color_mode'];
		$custom = isset( $input['custom_color'] ) ? sanitize_hex_color( $input['custom_color'] ) : '';
		$out['custom_color'] = $custom ? $custom : $defaults['custom_color'];
		$out['icon_label'] = ( isset( $input['icon_label'] ) && 'icon_only' === $input['icon_label'] )
			? 'icon_only' : 'icon_label';

		// Display rules.
		$public_types = array_keys( get_post_types( array( 'public' => true ), 'names' ) );
		$post_types   = array();
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $pt ) {
				$pt = sanitize_key( $pt );
				if ( in_array( $pt, $public_types, true ) && ! in_array( $pt, $post_types, true ) ) {
					$post_types[] = $pt;
				}
			}
		}
		$out['post_types']    = $post_types;
		$out['show_home']     = ! empty( $input['show_home'] ) ? 1 : 0;
		$out['show_archives'] = ! empty( $input['show_archives'] ) ? 1 : 0;

		$exclude = isset( $input['exclude_ids'] ) ? (string) $input['exclude_ids'] : '';
		$ids     = array();
		foreach ( explode( ',', $exclude ) as $part ) {
			$part = trim( $part );
			if ( '' !== $part && ctype_digit( $part ) ) {
				$ids[] = (int) $part;
			}
		}
		$out['exclude_ids'] = implode( ', ', array_unique( $ids ) );

		// UTM.
		$out['utm_enable'] = ! empty( $input['utm_enable'] ) ? 1 : 0;
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $key ) {
			$val = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
			// UTM values go into URLs: allow letters, numbers, and - _ . ~ only.
			$val = preg_replace( '/[^A-Za-z0-9\-_.~]/', '', $val );
			$out[ $key ] = substr( $val, 0, 100 );
		}

		// Open Graph.
		$out['og_enable'] = ! empty( $input['og_enable'] ) ? 1 : 0;
		$out['og_image']  = isset( $input['og_image'] ) ? absint( $input['og_image'] ) : 0;

		return $out;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'fast-share-buttons' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fast Share Buttons', 'fast-share-buttons' ); ?></h1>
			<?php
			if ( function_exists( 'mtsuav_tip_box' ) ) {
				mtsuav_tip_box( 'fast-share-buttons', 'Fast Share Buttons' );
			}
			?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<h2><?php esc_html_e( 'Shortcode and template tag', 'fast-share-buttons' ); ?></h2>
			<p>
				<?php esc_html_e( 'Place share buttons anywhere with the shortcode:', 'fast-share-buttons' ); ?>
				<code>[fast_share]</code>
			</p>
			<p>
				<?php esc_html_e( 'Limit networks or override the style, for example:', 'fast-share-buttons' ); ?>
				<code>[fast_share networks="x,facebook,linkedin" shape="pill" size="large"]</code>
			</p>
			<p>
				<?php esc_html_e( 'In a theme template:', 'fast-share-buttons' ); ?>
				<code>&lt;?php fast_share_buttons(); ?&gt;</code>
			</p>
		</div>
		<?php
	}

	/**
	 * Section description for networks.
	 *
	 * @return void
	 */
	public static function section_networks_desc() {
		echo '<p>' . esc_html__( 'Choose which networks appear, and drag to reorder them.', 'fast-share-buttons' ) . '</p>';
	}

	/**
	 * Networks field: toggle checkboxes in a sortable list.
	 *
	 * @return void
	 */
	public static function field_networks() {
		$settings = fast_share_get_settings();
		$all      = FSB_Share_Networks::all();
		$order    = isset( $settings['network_order'] ) && is_array( $settings['network_order'] )
			? $settings['network_order'] : array_keys( $all );
		$flags    = isset( $settings['networks'] ) && is_array( $settings['networks'] ) ? $settings['networks'] : array();
		?>
		<ul id="fast-share-network-list" class="fast-share-sortable">
			<?php foreach ( $order as $slug ) : ?>
				<?php
				if ( ! isset( $all[ $slug ] ) ) {
					continue;
				}
				$checked = ! empty( $flags[ $slug ] );
				?>
				<li class="fast-share-network-item" data-slug="<?php echo esc_attr( $slug ); ?>">
					<span class="dashicons dashicons-menu fast-share-drag" aria-hidden="true"></span>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( FAST_SHARE_OPTION ); ?>[networks][<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( $checked ); ?> />
						<?php echo esc_html( $all[ $slug ]['label'] ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
		<input type="hidden" id="fast-share-network-order" name="<?php echo esc_attr( FAST_SHARE_OPTION ); ?>[network_order]" value="<?php echo esc_attr( implode( ',', $order ) ); ?>" />
		<p class="description"><?php esc_html_e( 'Drag rows to change the button order. Unchecked networks are hidden.', 'fast-share-buttons' ); ?></p>
		<?php
	}

	/**
	 * Placements field.
	 *
	 * @return void
	 */
	public static function field_placements() {
		$settings   = fast_share_get_settings();
		$placements = isset( $settings['placements'] ) && is_array( $settings['placements'] ) ? $settings['placements'] : array();
		$options    = array(
			'before'          => __( 'Before content', 'fast-share-buttons' ),
			'after'           => __( 'After content', 'fast-share-buttons' ),
			'both'            => __( 'Before and after content', 'fast-share-buttons' ),
			'floating_side'   => __( 'Floating side bar (desktop)', 'fast-share-buttons' ),
			'floating_bottom' => __( 'Floating bottom bar (mobile)', 'fast-share-buttons' ),
		);
		foreach ( $options as $value => $label ) {
			printf(
				'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%1$s[placements][]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( FAST_SHARE_OPTION ),
				esc_attr( $value ),
				checked( in_array( $value, $placements, true ), true, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'Leave all unchecked to disable automatic placement and use the shortcode or template tag only.', 'fast-share-buttons' ) . '</p>';
	}

	/**
	 * Shape field.
	 *
	 * @return void
	 */
	public static function field_shape() {
		self::radio_field(
			'shape',
			array(
				'rounded' => __( 'Rounded', 'fast-share-buttons' ),
				'pill'    => __( 'Pill', 'fast-share-buttons' ),
				'square'  => __( 'Square', 'fast-share-buttons' ),
				'circle'  => __( 'Circle (icon only)', 'fast-share-buttons' ),
			)
		);
	}

	/**
	 * Size field.
	 *
	 * @return void
	 */
	public static function field_size() {
		self::radio_field(
			'size',
			array(
				'small'  => __( 'Small', 'fast-share-buttons' ),
				'medium' => __( 'Medium', 'fast-share-buttons' ),
				'large'  => __( 'Large', 'fast-share-buttons' ),
			)
		);
	}

	/**
	 * Color mode field.
	 *
	 * @return void
	 */
	public static function field_color() {
		$settings = fast_share_get_settings();
		self::radio_field(
			'color_mode',
			array(
				'brand'  => __( 'Brand colors', 'fast-share-buttons' ),
				'mono'   => __( 'Monochrome', 'fast-share-buttons' ),
				'custom' => __( 'Custom color', 'fast-share-buttons' ),
			)
		);
		printf(
			'<p><label>%1$s <input type="color" name="%2$s[custom_color]" value="%3$s" /></label></p>',
			esc_html__( 'Custom color:', 'fast-share-buttons' ),
			esc_attr( FAST_SHARE_OPTION ),
			esc_attr( $settings['custom_color'] )
		);
	}

	/**
	 * Labels field.
	 *
	 * @return void
	 */
	public static function field_labels() {
		self::radio_field(
			'icon_label',
			array(
				'icon_label' => __( 'Icon and label', 'fast-share-buttons' ),
				'icon_only'  => __( 'Icon only', 'fast-share-buttons' ),
			)
		);
	}

	/**
	 * Helper: render a radio group for a settings key.
	 *
	 * @param string $key     Settings key.
	 * @param array  $options Value => label pairs.
	 * @return void
	 */
	protected static function radio_field( $key, $options ) {
		$settings = fast_share_get_settings();
		$current  = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		foreach ( $options as $value => $label ) {
			printf(
				'<label style="display:block;margin-bottom:6px;"><input type="radio" name="%1$s[%2$s]" value="%3$s" %4$s /> %5$s</label>',
				esc_attr( FAST_SHARE_OPTION ),
				esc_attr( $key ),
				esc_attr( $value ),
				checked( $value, $current, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Post types field.
	 *
	 * @return void
	 */
	public static function field_post_types() {
		$settings = fast_share_get_settings();
		$selected = isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ? $settings['post_types'] : array();
		$types    = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			printf(
				'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%1$s[post_types][]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( FAST_SHARE_OPTION ),
				esc_attr( $type->name ),
				checked( in_array( $type->name, $selected, true ), true, false ),
				esc_html( $type->labels->singular_name )
			);
		}
	}

	/**
	 * Homepage / archives toggles.
	 *
	 * @return void
	 */
	public static function field_home_archives() {
		$settings = fast_share_get_settings();
		printf(
			'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%1$s[show_home]" value="1" %2$s /> %3$s</label>',
			esc_attr( FAST_SHARE_OPTION ),
			checked( ! empty( $settings['show_home'] ), true, false ),
			esc_html__( 'Show on the blog homepage', 'fast-share-buttons' )
		);
		printf(
			'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%1$s[show_archives]" value="1" %2$s /> %3$s</label>',
			esc_attr( FAST_SHARE_OPTION ),
			checked( ! empty( $settings['show_archives'] ), true, false ),
			esc_html__( 'Show on archive pages', 'fast-share-buttons' )
		);
	}

	/**
	 * Excluded IDs field.
	 *
	 * @return void
	 */
	public static function field_exclude() {
		$settings = fast_share_get_settings();
		printf(
			'<input type="text" class="regular-text" name="%1$s[exclude_ids]" value="%2$s" placeholder="%3$s" />',
			esc_attr( FAST_SHARE_OPTION ),
			esc_attr( $settings['exclude_ids'] ),
			esc_attr__( 'e.g. 12, 34, 56', 'fast-share-buttons' )
		);
		echo '<p class="description">' . esc_html__( 'Comma-separated post or page IDs where buttons never appear.', 'fast-share-buttons' ) . '</p>';
	}

	/**
	 * UTM fields.
	 *
	 * @return void
	 */
	public static function field_utm() {
		$settings = fast_share_get_settings();
		printf(
			'<label style="display:block;margin-bottom:10px;"><input type="checkbox" name="%1$s[utm_enable]" value="1" %2$s /> %3$s</label>',
			esc_attr( FAST_SHARE_OPTION ),
			checked( ! empty( $settings['utm_enable'] ), true, false ),
			esc_html__( 'Append UTM parameters to shared URLs', 'fast-share-buttons' )
		);
		foreach ( array(
			'utm_source'   => __( 'UTM source', 'fast-share-buttons' ),
			'utm_medium'   => __( 'UTM medium', 'fast-share-buttons' ),
			'utm_campaign' => __( 'UTM campaign', 'fast-share-buttons' ),
		) as $key => $label ) {
			printf(
				'<p><label>%1$s<br /><input type="text" class="regular-text" name="%2$s[%3$s]" value="%4$s" /></label></p>',
				esc_html( $label ),
				esc_attr( FAST_SHARE_OPTION ),
				esc_attr( $key ),
				esc_attr( $settings[ $key ] )
			);
		}
	}

	/**
	 * Open Graph fields.
	 *
	 * @return void
	 */
	public static function field_og() {
		$settings = fast_share_get_settings();
		printf(
			'<label style="display:block;margin-bottom:10px;"><input type="checkbox" name="%1$s[og_enable]" value="1" %2$s /> %3$s</label>',
			esc_attr( FAST_SHARE_OPTION ),
			checked( ! empty( $settings['og_enable'] ), true, false ),
			esc_html__( 'Output Open Graph and Twitter Card tags on posts and pages', 'fast-share-buttons' )
		);
		$image_id  = (int) $settings['og_image'];
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<p>
			<label><?php esc_html_e( 'Fallback image (used when a post has no featured image):', 'fast-share-buttons' ); ?></label><br />
			<input type="hidden" id="fast-share-og-image" name="<?php echo esc_attr( FAST_SHARE_OPTION ); ?>[og_image]" value="<?php echo esc_attr( $image_id ); ?>" />
			<img id="fast-share-og-preview" src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:200px;display:<?php echo $image_url ? 'block' : 'none'; ?>;margin:8px 0;" />
			<br />
			<button type="button" class="button" id="fast-share-og-select"><?php esc_html_e( 'Choose image', 'fast-share-buttons' ); ?></button>
			<button type="button" class="button" id="fast-share-og-remove"><?php esc_html_e( 'Remove', 'fast-share-buttons' ); ?></button>
		</p>
		<?php
	}
}
