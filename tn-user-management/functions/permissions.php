<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Permission Sets CPT
|--------------------------------------------------------------------------
*/

add_action( 'init', 'tn731_umg_register_permission_set_cpt' );

// Build the permission-set menu catalogue late, after WordPress and plugins have
// registered their admin menus, then apply visibility restrictions after that.
add_action( 'admin_menu', 'tn731_umg_capture_admin_menu_map', 9999 );
add_action( 'admin_menu', 'tn731_umg_apply_admin_visibility', 10000 );

function tn731_umg_register_permission_set_cpt() {

	register_post_type(
		'tn731_permset',
		array(
			'labels' => array(
				'name'          => 'Permission Sets',
				'singular_name' => 'Permission Set',
				'add_new_item'  => 'Add Permission Set',
				'edit_item'     => 'Edit Permission Set',
				'new_item'      => 'New Permission Set',
				'view_item'     => 'View Permission Set',
				'search_items'  => 'Search Permission Sets',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'menu_position'       => 58,
			'menu_icon'           => 'dashicons-lock',
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function tn731_umg_get_native_top_level_menu_slugs() {
	return array(
		'index.php',
		'edit.php',
		'upload.php',
		'edit.php?post_type=page',
		'edit-comments.php',
		'themes.php',
		'plugins.php',
		'users.php',
		'tools.php',
		'options-general.php',
	);
}

function tn731_umg_normalize_admin_slug( $slug ) {

	$slug = trim( (string) $slug );

	if ( '' === $slug ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $slug ) ) {
		$parts = wp_parse_url( $slug );

		if ( empty( $parts['path'] ) ) {
			return $slug;
		}

		$path = $parts['path'];
		$path = preg_replace( '#^.*/wp-admin/#', '', $path );

		$query = '';
		if ( ! empty( $parts['query'] ) ) {
			$query = '?' . $parts['query'];
		}

		return ltrim( $path . $query, '/' );
	}

	return $slug;
}

function tn731_umg_reference_user_can( $cap ) {

	$reference_user_id = function_exists( 'tn731_umg_get_reference_user_id' ) ? tn731_umg_get_reference_user_id() : 0;

	if ( ! $reference_user_id ) {
		return false;
	}

	$cap = (string) $cap;

	if ( '' === $cap ) {
		return false;
	}

	return user_can( $reference_user_id, $cap );
}

function tn731_umg_capture_admin_menu_map() {
	global $tn731_umg_captured_menu_map;

	$tn731_umg_captured_menu_map = tn731_umg_build_menu_map_from_globals();
}

function tn731_umg_get_menu_map() {
	global $tn731_umg_captured_menu_map;

	if ( is_array( $tn731_umg_captured_menu_map ) ) {
		return $tn731_umg_captured_menu_map;
	}

	return tn731_umg_build_menu_map_from_globals();
}

function tn731_umg_build_menu_map_from_globals() {
	global $menu, $submenu;

	$map = array(
		'native_main' => array(),
		'post_types'  => array(),
		'tools'       => array(),
		'settings'    => array(),
		'third_party' => array(),
	);

	$native = tn731_umg_get_native_top_level_menu_slugs();

	if ( ! empty( $menu ) ) {
		foreach ( $menu as $item ) {

			if ( empty( $item[2] ) || false !== strpos( (string) $item[2], 'separator' ) ) {
				continue;
			}

			$slug  = tn731_umg_normalize_admin_slug( $item[2] );
			$label = wp_strip_all_tags( (string) $item[0] );

			if ( '' === $slug || '' === $label ) {
				continue;
			}

			if ( in_array( $slug, $native, true ) ) {
				$map['native_main'][ $slug ] = $label;
			} elseif ( 0 === strpos( $slug, 'edit.php?post_type=' ) ) {
				$map['post_types'][ $slug ] = $label;
			} else {
				$map['third_party'][ $slug ] = $label;
			}
		}
	}

	if ( ! empty( $submenu['tools.php'] ) ) {
		foreach ( $submenu['tools.php'] as $sub_item ) {

			if ( empty( $sub_item[2] ) ) {
				continue;
			}

			$child_slug = tn731_umg_normalize_admin_slug( $sub_item[2] );
			$label      = wp_strip_all_tags( (string) $sub_item[0] );

			if ( 'tools.php' === $child_slug || '' === $child_slug || '' === $label ) {
				continue;
			}

			$key = 'tools.php||' . $child_slug;

			$map['tools'][ $key ] = array(
				'parent' => 'tools.php',
				'slug'   => $child_slug,
				'label'  => $label,
			);
		}
	}

	if ( ! empty( $submenu['options-general.php'] ) ) {
		foreach ( $submenu['options-general.php'] as $sub_item ) {

			if ( empty( $sub_item[2] ) ) {
				continue;
			}

			$child_slug = tn731_umg_normalize_admin_slug( $sub_item[2] );
			$label      = wp_strip_all_tags( (string) $sub_item[0] );

			if ( 'options-general.php' === $child_slug || '' === $child_slug || '' === $label ) {
				continue;
			}

			$key = 'options-general.php||' . $child_slug;

			$map['settings'][ $key ] = array(
				'parent' => 'options-general.php',
				'slug'   => $child_slug,
				'label'  => $label,
			);
		}
	}

	return $map;
}

function tn731_umg_get_all_permission_sets() {

	return get_posts(
		array(
			'post_type'      => 'tn731_permset',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
}

function tn731_umg_get_user_permission_set_ids( $user_id ) {

	$ids = get_user_meta( $user_id, 'tn731_umg_permission_sets', true );

	if ( ! is_array( $ids ) ) {
		$ids = array();
	}

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

function tn731_umg_get_users_for_permission_set( $permission_set_id ) {

	$users = get_users(
		array(
			'role'    => 'user',
			'fields'  => array( 'ID', 'display_name', 'user_email' ),
			'number'  => 999999,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		)
	);

	if ( empty( $users ) ) {
		return array();
	}

	$matched = array();

	foreach ( $users as $user ) {

		if ( get_user_meta( $user->ID, 'tn731_umg_reference_user', true ) === '1' ) {
			continue;
		}

		$current = tn731_umg_get_user_permission_set_ids( $user->ID );

		if ( in_array( (int) $permission_set_id, $current, true ) ) {
			$matched[] = $user;
		}
	}

	return $matched;
}

function tn731_umg_set_permission_set_users( $permission_set_id, $user_ids ) {

	$permission_set_id = (int) $permission_set_id;
	$user_ids          = array_values( array_unique( array_filter( array_map( 'intval', (array) $user_ids ) ) ) );

	$current_users = tn731_umg_get_users_for_permission_set( $permission_set_id );
	$current_ids   = array_map( 'intval', wp_list_pluck( $current_users, 'ID' ) );

	$to_add    = array_values( array_diff( $user_ids, $current_ids ) );
	$to_remove = array_values( array_diff( $current_ids, $user_ids ) );

	foreach ( $to_add as $user_id ) {
		$current = tn731_umg_get_user_permission_set_ids( $user_id );

		if ( ! in_array( $permission_set_id, $current, true ) ) {
			$current[] = $permission_set_id;
		}

		update_user_meta(
			$user_id,
			'tn731_umg_permission_sets',
			array_values( array_unique( array_map( 'intval', $current ) ) )
		);

		clean_user_cache( $user_id );
	}

	foreach ( $to_remove as $user_id ) {
		$current = tn731_umg_get_user_permission_set_ids( $user_id );
		$current = array_values( array_diff( $current, array( $permission_set_id ) ) );

		update_user_meta(
			$user_id,
			'tn731_umg_permission_sets',
			array_values( array_unique( array_map( 'intval', $current ) ) )
		);

		clean_user_cache( $user_id );
	}
}

function tn731_umg_get_user_domain( $email ) {
	$email = strtolower( trim( (string) $email ) );

	if ( strpos( $email, '@' ) === false ) {
		return '';
	}

	$parts = explode( '@', $email );
	return isset( $parts[1] ) ? $parts[1] : '';
}

function tn731_umg_get_permission_set_meta_or_default( $post_id, $meta_key, $default_items ) {

	if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
		return array_values( array_keys( $default_items ) );
	}

	$value = get_post_meta( $post_id, $meta_key, true );

	if ( ! is_array( $value ) ) {
		return array();
	}

	return array_values( array_unique( array_filter( array_map( 'strval', $value ) ) ) );
}

/*
|--------------------------------------------------------------------------
| Runtime Visibility
|--------------------------------------------------------------------------
*/

function tn731_umg_should_apply_permission_sets() {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user = wp_get_current_user();

	if ( empty( $user->ID ) ) {
		return false;
	}

	if ( is_multisite() && is_super_admin( $user->ID ) ) {
		return false;
	}

	if ( in_array( 'administrator', (array) $user->roles, true ) ) {
		return false;
	}

	if ( ! in_array( 'user', (array) $user->roles, true ) ) {
		return false;
	}

	return true;
}

function tn731_umg_get_current_user_visible_items() {

	$user_id = get_current_user_id();
	$set_ids = tn731_umg_get_user_permission_set_ids( $user_id );

	$visible = array(
		'native_main' => array(),
		'post_types'  => array(),
		'tools'       => array(),
		'settings'    => array(),
		'third_party' => array(),
	);

	if ( empty( $set_ids ) ) {
		return $visible;
	}

	foreach ( $set_ids as $set_id ) {
		$visible['native_main'] = array_merge( $visible['native_main'], (array) get_post_meta( $set_id, '_tn731_umg_native_main', true ) );
		$visible['post_types']  = array_merge( $visible['post_types'], (array) get_post_meta( $set_id, '_tn731_umg_post_types', true ) );
		$visible['tools']       = array_merge( $visible['tools'], (array) get_post_meta( $set_id, '_tn731_umg_tools', true ) );
		$visible['settings']    = array_merge( $visible['settings'], (array) get_post_meta( $set_id, '_tn731_umg_settings', true ) );
		$visible['third_party'] = array_merge( $visible['third_party'], (array) get_post_meta( $set_id, '_tn731_umg_third_party', true ) );
	}

	foreach ( $visible as $key => $items ) {
		$visible[ $key ] = array_values( array_unique( array_filter( $items ) ) );
	}

	return $visible;
}

function tn731_umg_apply_admin_visibility() {

	if ( ! tn731_umg_should_apply_permission_sets() ) {
		return;
	}

	$visible = tn731_umg_get_current_user_visible_items();

	$keep_top = array_merge(
		(array) $visible['native_main'],
		(array) $visible['post_types'],
		(array) $visible['third_party']
	);

	if ( ! in_array( 'index.php', $keep_top, true ) ) {
		$keep_top[] = 'index.php';
	}

	global $menu, $submenu;

	if ( ! empty( $menu ) ) {
		foreach ( $menu as $item ) {

			if ( empty( $item[2] ) || false !== strpos( (string) $item[2], 'separator' ) ) {
				continue;
			}

			$slug = tn731_umg_normalize_admin_slug( $item[2] );

			if ( in_array( $slug, array( 'profile.php' ), true ) ) {
				continue;
			}

			if ( in_array( $slug, array( 'tools.php', 'options-general.php' ), true ) ) {
				continue;
			}

			if ( ! in_array( $slug, $keep_top, true ) ) {
				remove_menu_page( $item[2] );
			}
		}
	}

	if ( ! empty( $submenu['tools.php'] ) ) {
		foreach ( $submenu['tools.php'] as $sub_item ) {
			if ( empty( $sub_item[2] ) ) {
				continue;
			}

			$child_slug = tn731_umg_normalize_admin_slug( $sub_item[2] );

			if ( 'tools.php' === $child_slug ) {
				continue;
			}

			$key = 'tools.php||' . $child_slug;

			if ( ! in_array( $key, $visible['tools'], true ) ) {
				remove_submenu_page( 'tools.php', $sub_item[2] );
			}
		}
	}

	if ( ! empty( $submenu['options-general.php'] ) ) {
		foreach ( $submenu['options-general.php'] as $sub_item ) {
			if ( empty( $sub_item[2] ) ) {
				continue;
			}

			$child_slug = tn731_umg_normalize_admin_slug( $sub_item[2] );

			if ( 'options-general.php' === $child_slug ) {
				continue;
			}

			$key = 'options-general.php||' . $child_slug;

			if ( ! in_array( $key, $visible['settings'], true ) ) {
				remove_submenu_page( 'options-general.php', $sub_item[2] );
			}
		}
	}

	if ( empty( $visible['tools'] ) ) {
		remove_menu_page( 'tools.php' );
	}

	if ( empty( $visible['settings'] ) ) {
		remove_menu_page( 'options-general.php' );
	}
}