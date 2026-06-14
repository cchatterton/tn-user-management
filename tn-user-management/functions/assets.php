<?php
/**
 * Admin asset loading for TN User Management.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'tn731_umg_enqueue_admin_assets' );

function tn731_umg_enqueue_admin_assets( $hook_suffix ) {

	wp_enqueue_style(
		'tn731-umg-admin',
		TN731_UMG_URL . 'styles/tn-user-management.css',
		array(),
		TN731_UMG_VERSION
	);

	wp_enqueue_script(
		'tn731-umg-admin',
		TN731_UMG_URL . 'scripts/tn-user-management.js',
		array(),
		TN731_UMG_VERSION,
		true
	);
}
