<?php
/**
 * Uninstall cleanup for Fast Share Buttons.
 *
 * Deletes the settings option and every user's tip-box dismissal flag.
 *
 * @package FSB_Share
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'fast_share_settings' );

// Per-user tip-box dismissal meta (set by the shared tip-box drop-in).
delete_metadata( 'user', 0, 'mtsuav_tip_dismissed_fast-share-buttons', '', true );
