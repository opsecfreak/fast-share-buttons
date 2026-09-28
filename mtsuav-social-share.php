<?php
/**
 * Plugin Name:       MTSUAV Social Share
 * Plugin URI:        https://github.com/opsecfreak/mtsuav-social-share
 * Description:       Lightweight social share buttons for X, Facebook, LinkedIn, Pinterest, WhatsApp, Telegram, Reddit, Email and Copy Link. Drag-and-drop ordering, multiple placements, UTM builder, and Open Graph tags. No external requests, no tracking.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MTSUAV
 * Author URI:        https://mtsuav.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mtsuav-social-share
 * Update URI:        https://github.com/opsecfreak/mtsuav-social-share
 *
 * @package MTSUAV_Social_Share
 */

defined( 'ABSPATH' ) || exit;

// Updater wiring (shared drop-in, do not modify the file itself).
require_once __DIR__ . '/includes/class-mtsuav-updater.php';
MTSUAV_Updater::register( 'mtsuav-social-share', 'opsecfreak/mtsuav-social-share', '1.0.0', __FILE__ );

// Tip box wiring (shared drop-in, do not modify the file itself).
require_once __DIR__ . '/includes/class-mtsuav-tip-box.php';
mtsuav_tip_box_init();

define( 'MTSUAV_SHARE_VERSION', '1.0.0' );
define( 'MTSUAV_SHARE_OPTION', 'mtsuav_share_settings' );
define( 'MTSUAV_SHARE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MTSUAV_SHARE_URL', plugin_dir_url( __FILE__ ) );

require_once MTSUAV_SHARE_PATH . 'includes/class-mtsuav-share-networks.php';
require_once MTSUAV_SHARE_PATH . 'includes/class-mtsuav-share-render.php';
require_once MTSUAV_SHARE_PATH . 'includes/class-mtsuav-share-admin.php';
require_once MTSUAV_SHARE_PATH . 'includes/class-mtsuav-share-opengraph.php';

/**
 * Default settings for the plugin.
 *
 * @return array
 */
function mtsuav_share_defaults() {
	return array(
		'networks'        => array(
			'x'        => 1,
			'facebook' => 1,
			'linkedin' => 1,
			'pinterest'=> 1,
			'whatsapp' => 1,
			'telegram' => 1,
			'reddit'   => 0,
			'email'    => 1,
			'copy'     => 1,
		),
		'network_order'   => array( 'x', 'facebook', 'linkedin', 'pinterest', 'whatsapp', 'telegram', 'reddit', 'email', 'copy' ),
		'placements'      => array( 'after' ),
		'shape'           => 'rounded',
		'size'            => 'medium',
		'color_mode'      => 'brand',
		'custom_color'    => '#1d4ed8',
		'icon_label'      => 'icon_label',
		'post_types'      => array( 'post' ),
		'show_home'       => 1,
		'show_archives'   => 0,
		'exclude_ids'     => '',
		'utm_enable'      => 0,
		'utm_source'      => 'social-share',
		'utm_medium'      => 'social',
		'utm_campaign'    => '',
		'og_enable'       => 1,
		'og_image'        => 0,
	);
}

/**
 * Get merged settings (stored + defaults).
 *
 * @return array
 */
function mtsuav_share_get_settings() {
	$stored = get_option( MTSUAV_SHARE_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( mtsuav_share_defaults(), $stored );
}

/**
 * Register frontend assets (registered here, enqueued only when needed).
 *
 * @return void
 */
function mtsuav_share_register_assets() {
	wp_register_style(
		'mtsuav-share-frontend',
		MTSUAV_SHARE_URL . 'assets/css/frontend.css',
		array(),
		MTSUAV_SHARE_VERSION
	);
	wp_register_script(
		'mtsuav-share-frontend',
		MTSUAV_SHARE_URL . 'assets/js/frontend.js',
		array(),
		MTSUAV_SHARE_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'mtsuav_share_register_assets' );

/**
 * Register admin assets for the settings screen.
 *
 * @return void
 */
function mtsuav_share_register_admin_assets() {
	wp_register_style(
		'mtsuav-share-admin',
		MTSUAV_SHARE_URL . 'assets/css/admin.css',
		array(),
		MTSUAV_SHARE_VERSION
	);
	wp_register_script(
		'mtsuav-share-admin',
		MTSUAV_SHARE_URL . 'assets/js/admin.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		MTSUAV_SHARE_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'mtsuav_share_register_admin_assets' );

/**
 * Enqueue frontend assets. Safe to call at render time; WordPress prints
 * late-enqueued footer assets correctly.
 *
 * @return void
 */
function mtsuav_share_enqueue_frontend() {
	static $done = false;
	wp_enqueue_style( 'mtsuav-share-frontend' );
	wp_enqueue_script( 'mtsuav-share-frontend' );
	if ( ! $done ) {
		wp_localize_script(
			'mtsuav-share-frontend',
			'mtsuavShare',
			array(
				'copied' => __( 'Copied!', 'mtsuav-social-share' ),
				'copy'   => __( 'Copy link', 'mtsuav-social-share' ),
			)
		);
		$done = true;
	}
}

/**
 * Plugin activation: seed defaults so the plugin works out of the box.
 *
 * @return void
 */
function mtsuav_share_activate() {
	if ( false === get_option( MTSUAV_SHARE_OPTION, false ) ) {
		add_option( MTSUAV_SHARE_OPTION, mtsuav_share_defaults() );
	}
}
register_activation_hook( __FILE__, 'mtsuav_share_activate' );

// Boot the modules.
MTSUAV_Share_Render::init();
MTSUAV_Share_Admin::init();
MTSUAV_Share_OpenGraph::init();
