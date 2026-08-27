<?php
/**
 * Admin asset loading for TN User Management.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'tn731_umg_enqueue_admin_assets' );

function tn731_umg_enqueue_admin_assets( $hook_suffix ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_permission_screen = $screen && 'tn731_permset' === $screen->post_type;
	$is_user_screen       = in_array( $hook_suffix, array( 'user-new.php', 'user-edit.php', 'profile.php' ), true );

	if ( ! $is_permission_screen && ! $is_user_screen ) {
		return;
	}

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

	if ( $screen && 'tn731_permset_page_tn731-umg-roles' === $screen->id ) {
		wp_localize_script(
			'tn731-umg-admin',
			'TN731UMGCapabilities',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tn731_umg_toggle_user_capability' ),
				'error'   => __( 'The capability could not be updated. Please try again.', 'tn-user-management' ),
			)
		);
	}
}
