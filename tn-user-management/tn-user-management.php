<?php
/**
 * Plugin Name: TN User Management
 * Plugin URI: https://github.com/cchatterton/tn-user-management
 * Description: Email-as-username, one-time username migration, role normalisation, permission sets, and multisite user governance.
 * Version: 1.7
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Techn
 * Author URI: https://techn.com.au
 * Network: true
 * Text Domain: tn-user-management
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

define( 'TN731_UMG_PATH', plugin_dir_path( __FILE__ ) );
define( 'TN731_UMG_URL', plugin_dir_url( __FILE__ ) );
define( 'TN731_UMG_VERSION', '1.7' );
define( 'TN731_UMG_SITE_ROLE', 'administrator' );
define( 'TN731_UMG_PLUGIN_FILE', __FILE__ );
define( 'TN731_UMG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| Includes
|--------------------------------------------------------------------------
*/

require_once TN731_UMG_PATH . 'functions/assets.php';
require_once TN731_UMG_PATH . 'functions/usernames.php';
require_once TN731_UMG_PATH . 'functions/multisite.php';
require_once TN731_UMG_PATH . 'functions/permissions.php';
require_once TN731_UMG_PATH . 'functions/permissions-ui.php';
require_once TN731_UMG_PATH . 'functions/roles.php';
require_once TN731_UMG_PATH . 'functions/github-updater.php';

/*
|--------------------------------------------------------------------------
| Activation
|--------------------------------------------------------------------------
*/

register_activation_hook( __FILE__, 'tn731_umg_on_activation' );

function tn731_umg_on_activation( $network_wide ) {

	/*
	|--------------------------------------------------------------------------
	| 1. Ensure baseline roles exist
	|--------------------------------------------------------------------------
	*/
	if ( function_exists( 'tn731_umg_ensure_baseline_roles' ) ) {
		tn731_umg_ensure_baseline_roles( $network_wide );
	}

    if ( function_exists( 'tn731_umg_assign_no_role_users_to_subscriber_all_sites' ) ) {
    	if ( is_multisite() && $network_wide ) {
    		tn731_umg_assign_no_role_users_to_subscriber_all_sites();
    	} else {
    		tn731_umg_assign_no_role_users_to_subscriber_current_site();
    	}
    }

	/*
	|--------------------------------------------------------------------------
	| 2. Ensure User role matches Administrator + reference user exists
	|--------------------------------------------------------------------------
	*/
	if ( function_exists( 'tn731_umg_sync_user_role_from_admin_all_sites' ) && function_exists( 'tn731_umg_sync_user_role_from_admin_current_site' ) ) {
		if ( is_multisite() && $network_wide ) {
			tn731_umg_sync_user_role_from_admin_all_sites();
		} else {
			tn731_umg_sync_user_role_from_admin_current_site();
		}
	}

	/*
	|--------------------------------------------------------------------------
	| 3. Migrate existing usernames to email
	|--------------------------------------------------------------------------
	*/
	if ( function_exists( 'tn731_umg_migrate_existing_usernames' ) ) {
		tn731_umg_migrate_existing_usernames();
		update_site_option( 'tn731_umg_usernames_migrated', current_time( 'mysql' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| 4. Role migration / multisite sync
	|--------------------------------------------------------------------------
	*/
	if ( is_multisite() && $network_wide ) {

		if ( function_exists( 'tn731_umg_add_super_admins_to_all_sites' ) ) {
			tn731_umg_add_super_admins_to_all_sites();
		}

		if ( function_exists( 'tn731_umg_get_site_ids' ) && function_exists( 'tn731_umg_run_site_role_migration' ) ) {
			foreach ( tn731_umg_get_site_ids() as $site_id ) {
				tn731_umg_run_site_role_migration( $site_id );
			}
		}

	} else {

		if ( function_exists( 'tn731_umg_run_single_site_role_migration' ) ) {
			tn731_umg_run_single_site_role_migration();
		}
	}
}

/*
|--------------------------------------------------------------------------
| Plugin Row Actions
|--------------------------------------------------------------------------
*/

add_filter( 'plugin_action_links_' . TN731_UMG_PLUGIN_BASENAME, 'tn731_umg_plugin_action_links' );

function tn731_umg_plugin_action_links( $actions ) {

	$can_manage = is_multisite()
		? current_user_can( 'manage_network_options' )
		: current_user_can( 'manage_options' );

	if ( ! $can_manage ) {
		return $actions;
	}

	$url = wp_nonce_url(
		add_query_arg(
			array(
				'tn731_umg_sync_admin_rights' => '1',
			)
		),
		'tn731_umg_sync_admin_rights'
	);

	$actions['tn731_umg_sync_admin_rights'] = '<a href="' . esc_url( $url ) . '">Sync Admin Rights</a>';

	return $actions;
}

/*
|--------------------------------------------------------------------------
| Manual Sync Handler
|--------------------------------------------------------------------------
*/

add_action( 'admin_init', 'tn731_umg_handle_sync_admin_rights' );

function tn731_umg_handle_sync_admin_rights() {

	$should_sync = isset( $_GET['tn731_umg_sync_admin_rights'] )
		? sanitize_text_field( wp_unslash( $_GET['tn731_umg_sync_admin_rights'] ) )
		: '';

	if ( empty( $should_sync ) ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tn731_umg_sync_admin_rights' ) ) {
		return;
	}

	if ( is_multisite() ) {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
	}

	if ( function_exists( 'tn731_umg_ensure_baseline_roles' ) ) {
		tn731_umg_ensure_baseline_roles( is_multisite() && is_network_admin() );
	}

	if ( function_exists( 'tn731_umg_sync_user_role_from_admin_all_sites' ) && function_exists( 'tn731_umg_sync_user_role_from_admin_current_site' ) ) {
		if ( is_multisite() && is_network_admin() ) {
			tn731_umg_sync_user_role_from_admin_all_sites();
		} else {
			tn731_umg_sync_user_role_from_admin_current_site();
		}
	}

	$redirect = remove_query_arg(
		array(
			'tn731_umg_sync_admin_rights',
			'_wpnonce',
		)
	);

	$redirect = add_query_arg(
		array(
			'tn731_umg_sync_admin_rights_done' => '1',
		),
		$redirect
	);

	wp_safe_redirect( $redirect );
	exit;
}

/*
|--------------------------------------------------------------------------
| Admin Notices
|--------------------------------------------------------------------------
*/

add_action( 'network_admin_notices', 'tn731_umg_admin_notice' );
add_action( 'admin_notices', 'tn731_umg_admin_notice' );

function tn731_umg_admin_notice() {

	if ( is_multisite() ) {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
	}

	$sync_done = isset( $_GET['tn731_umg_sync_admin_rights_done'] )
		? sanitize_text_field( wp_unslash( $_GET['tn731_umg_sync_admin_rights_done'] ) )
		: '';

	if ( ! empty( $sync_done ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>TN User Management</strong>: Baseline roles checked and User role synced from Administrator successfully.</p></div>';
	}

	$result = get_site_option( 'tn731_umg_migration_result' );

	if ( empty( $result ) || ! is_array( $result ) ) {
		return;
	}

	$dismissed = get_site_option( 'tn731_umg_notice_dismissed' );

	if ( $dismissed === $result['ran'] ) {
		return;
	}

	$url = wp_nonce_url(
		add_query_arg( 'tn731_umg_dismiss_notice', '1' ),
		'tn731_umg_dismiss_notice'
	);

	echo '<div class="notice notice-info is-dismissible">';
	echo '<p><strong>TN User Management</strong>: Username migration ran at '
		. esc_html( $result['ran'] )
		. '. Updated: '
		. intval( $result['updated'] )
		. '. Skipped: '
		. intval( $result['skipped'] )
		. '. <a href="' . esc_url( $url ) . '">Dismiss</a></p>';
	echo '</div>';
}

add_action( 'admin_init', 'tn731_umg_handle_notice_dismiss' );

function tn731_umg_handle_notice_dismiss() {

	$dismiss_notice = isset( $_GET['tn731_umg_dismiss_notice'] )
		? sanitize_text_field( wp_unslash( $_GET['tn731_umg_dismiss_notice'] ) )
		: '';

	if ( empty( $dismiss_notice ) ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'tn731_umg_dismiss_notice' ) ) {
		return;
	}

	if ( is_multisite() ) {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
	}

	$result = get_site_option( 'tn731_umg_migration_result' );

	if ( ! empty( $result['ran'] ) ) {
		update_site_option( 'tn731_umg_notice_dismissed', $result['ran'] );
	}

	wp_safe_redirect( remove_query_arg( array( 'tn731_umg_dismiss_notice', '_wpnonce' ) ) );
	exit;
}
