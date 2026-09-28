<?php
/**
 * Uninstall cleanup for MTSUAV Social Share.
 *
 * Deletes the settings option and every user's tip-box dismissal flag.
 *
 * @package MTSUAV_Social_Share
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'mtsuav_share_settings' );

// Per-user tip-box dismissal meta (set by the shared tip-box drop-in).
delete_metadata( 'user', 0, 'mtsuav_tip_dismissed_mtsuav-social-share', '', true );
