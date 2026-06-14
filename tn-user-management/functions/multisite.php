<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| User Role Sync + Hidden Reference User
|--------------------------------------------------------------------------
|
| - administrator and subscriber are repaired if missing
| - user role must match administrator
| - permission set menu model is based on a hidden reference user
| - reference user is ensured on activation and Sync Admin Rights
| - roles are no longer deleted
|
*/

/*
|--------------------------------------------------------------------------
| Runtime capability inheritance
|--------------------------------------------------------------------------
*/

add_filter( 'user_has_cap', 'tn731_umg_grant_admin_caps_to_user_role', 20, 4 );

function tn731_umg_grant_admin_caps_to_user_role( $allcaps, $caps, $args, $user ) {

	if ( empty( $user ) || empty( $user->ID ) ) {
		return $allcaps;
	}

	$roles = isset( $user->roles ) ? (array) $user->roles : array();

	if ( in_array( 'administrator', $roles, true ) ) {
		return $allcaps;
	}

	if ( ! in_array( 'user', $roles, true ) ) {
		return $allcaps;
	}

	$admin_role = get_role( 'administrator' );

	if ( ! $admin_role ) {
		return $allcaps;
	}

	foreach ( (array) $admin_role->capabilities as $cap => $grant ) {
		if ( $grant ) {
			$allcaps[ $cap ] = true;
		}
	}

	return $allcaps;
}

/*
|--------------------------------------------------------------------------
| Hide reference user from normal Users UI
|--------------------------------------------------------------------------
*/

add_action( 'pre_get_users', 'tn731_umg_hide_reference_user_from_users_screen' );

function tn731_umg_hide_reference_user_from_users_screen( $query ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'users' !== $screen->id ) {
		return;
	}

	$meta_query   = (array) $query->get( 'meta_query' );
	$meta_query[] = array(
		'relation' => 'OR',
		array(
			'key'     => 'tn731_umg_reference_user',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'tn731_umg_reference_user',
			'value'   => '1',
			'compare' => '!=',
		),
	);

	$query->set( 'meta_query', $meta_query );
}

/*
|--------------------------------------------------------------------------
| Reference user helpers
|--------------------------------------------------------------------------
*/

function tn731_umg_get_reference_user_login() {
	return 'tn731_umg_reference_user';
}

function tn731_umg_get_reference_user_email() {

	$blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 1;

	return 'tn731-umg-reference-user+' . $blog_id . '@local.invalid';
}

function tn731_umg_get_reference_user_id() {

	$users = get_users(
		array(
			'meta_key'    => 'tn731_umg_reference_user',
			'meta_value'  => '1',
			'number'      => 1,
			'count_total' => false,
			'fields'      => array( 'ID' ),
		)
	);

	if ( empty( $users ) ) {
		return 0;
	}

	return (int) $users[0]->ID;
}

function tn731_umg_ensure_reference_user_exists_current_site() {

	$existing_id = tn731_umg_get_reference_user_id();

	if ( $existing_id ) {
		$user = new WP_User( $existing_id );
		$user->set_role( 'user' );
		update_user_meta( $existing_id, 'tn731_umg_reference_user', '1' );
		update_user_meta( $existing_id, 'tn731_umg_hidden_user', '1' );
		clean_user_cache( $existing_id );
		return $existing_id;
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => tn731_umg_get_reference_user_login(),
			'user_pass'    => wp_generate_password( 32, true, true ),
			'user_email'   => tn731_umg_get_reference_user_email(),
			'display_name' => 'TN UMG Reference User',
			'role'         => 'user',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		return 0;
	}

	update_user_meta( $user_id, 'tn731_umg_reference_user', '1' );
	update_user_meta( $user_id, 'tn731_umg_hidden_user', '1' );

	clean_user_cache( $user_id );

	return (int) $user_id;
}

function tn731_umg_ensure_reference_user_exists_all_sites() {

	if ( ! is_multisite() ) {
		tn731_umg_ensure_reference_user_exists_current_site();
		return;
	}

	$site_ids = tn731_umg_get_site_ids();

	if ( empty( $site_ids ) ) {
		return;
	}

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		tn731_umg_ensure_reference_user_exists_current_site();
		restore_current_blog();
	}
}

/*
|--------------------------------------------------------------------------
| Baseline Roles
|--------------------------------------------------------------------------
*/

function tn731_umg_ensure_baseline_roles( $network_wide = false ) {

	if ( is_multisite() && $network_wide ) {
		foreach ( tn731_umg_get_site_ids() as $site_id ) {
			switch_to_blog( $site_id );
			tn731_umg_ensure_baseline_roles_current_site();
			restore_current_blog();
		}
		return;
	}

	tn731_umg_ensure_baseline_roles_current_site();
}

function tn731_umg_ensure_baseline_roles_current_site() {

	if ( ! function_exists( 'populate_roles' ) ) {
		require_once ABSPATH . 'wp-admin/includes/schema.php';
	}

	if ( ! get_role( 'administrator' ) || ! get_role( 'subscriber' ) ) {
		populate_roles();
	}

	tn731_umg_sync_user_role_from_admin();
}

/*
|--------------------------------------------------------------------------
| User Role Sync
|--------------------------------------------------------------------------
*/

function tn731_umg_sync_user_role_from_admin_current_site() {
	tn731_umg_ensure_baseline_roles_current_site();
	tn731_umg_ensure_reference_user_exists_current_site();
}

function tn731_umg_sync_user_role_from_admin_all_sites() {

	if ( ! is_multisite() ) {
		tn731_umg_sync_user_role_from_admin_current_site();
		return;
	}

	$site_ids = tn731_umg_get_site_ids();

	if ( empty( $site_ids ) ) {
		return;
	}

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		tn731_umg_ensure_baseline_roles_current_site();
		tn731_umg_ensure_reference_user_exists_current_site();
		restore_current_blog();
	}
}

function tn731_umg_sync_user_role_from_admin() {

	$admin_role = get_role( 'administrator' );

	if ( ! $admin_role ) {
		return;
	}

	$admin_caps = (array) $admin_role->capabilities;

	remove_role( 'user' );
	add_role( 'user', 'User', $admin_caps );

	global $wp_roles;

	if ( isset( $wp_roles ) && is_object( $wp_roles ) ) {
		$wp_roles->for_site();
	}
}

/**
 * Backward-friendly wrapper.
 */
function tn731_umg_create_user_role() {
	tn731_umg_ensure_baseline_roles_current_site();
}

/*
|--------------------------------------------------------------------------
| Site Helpers
|--------------------------------------------------------------------------
*/

function tn731_umg_get_site_ids() {

	if ( ! is_multisite() ) {
		return array();
	}

	return get_sites(
		array(
			'number' => 0,
			'fields' => 'ids',
		)
	);
}

/*
|--------------------------------------------------------------------------
| New Site Setup
|--------------------------------------------------------------------------
*/

add_action( 'wp_initialize_site', 'tn731_umg_on_initialize_site', 20, 1 );

function tn731_umg_on_initialize_site( $new_site ) {

	if ( empty( $new_site ) || empty( $new_site->blog_id ) ) {
		return;
	}

	switch_to_blog( (int) $new_site->blog_id );

	tn731_umg_ensure_baseline_roles_current_site();
	tn731_umg_ensure_reference_user_exists_current_site();

	restore_current_blog();
}

/*
|--------------------------------------------------------------------------
| Super Admin Sync
|--------------------------------------------------------------------------
*/

function tn731_umg_add_super_admins_to_all_sites() {

	if ( ! is_multisite() ) {
		return;
	}

	$super_admins = get_super_admins();
	$site_ids     = tn731_umg_get_site_ids();

	if ( empty( $super_admins ) || empty( $site_ids ) ) {
		return;
	}

	foreach ( $super_admins as $login ) {
		$user = get_user_by( 'login', $login );

		if ( ! $user ) {
			continue;
		}

		foreach ( $site_ids as $site_id ) {

			if ( ! is_user_member_of_blog( $user->ID, $site_id ) ) {
				add_user_to_blog( $site_id, $user->ID, 'administrator' );
			} else {
				switch_to_blog( $site_id );
				$wp_user = new WP_User( $user->ID );
				$wp_user->set_role( 'administrator' );
				clean_user_cache( $user->ID );
				restore_current_blog();
			}
		}
	}
}

/*
|--------------------------------------------------------------------------
| Role Migration
|--------------------------------------------------------------------------
*/

function tn731_umg_run_site_role_migration( $site_id ) {

	if ( ! is_multisite() ) {
		return;
	}

	switch_to_blog( $site_id );

	tn731_umg_ensure_baseline_roles_current_site();
	tn731_umg_ensure_reference_user_exists_current_site();

	$users = get_users(
		array(
			'blog_id' => $site_id,
			'fields'  => array( 'ID', 'roles' ),
			'number'  => 999999,
		)
	);

	if ( ! empty( $users ) ) {
		foreach ( $users as $user ) {
			tn731_umg_normalise_user_role( $user->ID );
		}
	}

	restore_current_blog();
}

function tn731_umg_run_single_site_role_migration() {

	tn731_umg_ensure_baseline_roles_current_site();
	tn731_umg_ensure_reference_user_exists_current_site();

	$users = get_users(
		array(
			'fields' => array( 'ID', 'roles' ),
			'number' => 999999,
		)
	);

	if ( empty( $users ) ) {
		return;
	}

	foreach ( $users as $user ) {
		tn731_umg_normalise_user_role( $user->ID );
	}
}

function tn731_umg_normalise_user_role( $user_id ) {

	$user = new WP_User( $user_id );

	if ( ! $user || empty( $user->ID ) ) {
		return;
	}

	if ( get_user_meta( $user_id, 'tn731_umg_reference_user', true ) === '1' ) {
		$user->set_role( 'user' );
		clean_user_cache( $user_id );
		return;
	}

	if ( is_multisite() && is_super_admin( $user_id ) ) {
		$user->set_role( 'administrator' );
		clean_user_cache( $user_id );
		return;
	}

	$roles = (array) $user->roles;

	if ( in_array( 'administrator', $roles, true ) ) {
		$user->set_role( 'administrator' );
		clean_user_cache( $user_id );
		return;
	}

	if ( in_array( 'subscriber', $roles, true ) ) {
		$user->set_role( 'subscriber' );
		clean_user_cache( $user_id );
		return;
	}

	$user->set_role( 'user' );
	clean_user_cache( $user_id );
}

/*
|--------------------------------------------------------------------------
| Role Cleanup
|--------------------------------------------------------------------------
|
| Intentionally disabled.
| We no longer delete existing roles.
|
*/

function tn731_umg_remove_non_baseline_roles() {
	return;
}

function tn731_umg_assign_no_role_users_to_subscriber_current_site() {

	$users = get_users(
		array(
			'fields' => array( 'ID', 'roles' ),
			'number' => 999999,
		)
	);

	if ( empty( $users ) ) {
		return;
	}

	foreach ( $users as $user ) {

		if ( get_user_meta( $user->ID, 'tn731_umg_reference_user', true ) === '1' ) {
			continue;
		}

		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			continue;
		}

		$roles = (array) $user->roles;

		if ( empty( $roles ) ) {
			$wp_user = new WP_User( $user->ID );
			$wp_user->set_role( 'subscriber' );
			clean_user_cache( $user->ID );
		}
	}
}

function tn731_umg_assign_no_role_users_to_subscriber_all_sites() {

	if ( ! is_multisite() ) {
		tn731_umg_assign_no_role_users_to_subscriber_current_site();
		return;
	}

	foreach ( tn731_umg_get_site_ids() as $site_id ) {
		switch_to_blog( $site_id );
		tn731_umg_assign_no_role_users_to_subscriber_current_site();
		restore_current_blog();
	}
}