<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Email as Username
|--------------------------------------------------------------------------
*/

add_action( 'admin_init', 'tn731_umg_force_email_username', 1 );
add_action( 'admin_init', 'tn731_umg_force_skip_confirmation_email', 1 );
add_action( 'plugins_loaded', 'tn731_umg_disable_legacy_new_user_notifications' );
add_filter( 'wpmu_validate_user_signup', 'tn731_umg_validate_email_as_username', 20 );
add_filter( 'wp_send_new_user_notification_to_user', 'tn731_umg_disable_new_user_notification', 10, 2 );
add_filter( 'gettext', 'tn731_umg_filter_new_user_notification_text', 10, 3 );

/**
 * Prevent WordPress from emailing account details to newly created users.
 */
function tn731_umg_disable_new_user_notification( $send, $user ) {
	return false;
}

/**
 * Use admin-only notification callbacks on WordPress 6.0, before the
 * wp_send_new_user_notification_to_user filter was introduced.
 */
function tn731_umg_disable_legacy_new_user_notifications() {
	global $wp_version;

	if ( version_compare( $wp_version, '6.1', '>=' ) ) {
		return;
	}

	$notification_hooks = array(
		'register_new_user',
		'edit_user_created_user',
		'network_site_new_created_user',
		'network_site_users_created_user',
		'network_user_new_created_user',
	);

	foreach ( $notification_hooks as $hook ) {
		remove_action( $hook, 'wp_send_new_user_notifications', 10 );
		add_action( $hook, 'tn731_umg_send_admin_new_user_notification', 10 );
	}
}

/**
 * Send only the administrator copy of a new-user notification.
 */
function tn731_umg_send_admin_new_user_notification( $user_id ) {
	wp_new_user_notification( $user_id, null, 'admin' );
}

/**
 * Keep the Add User screens consistent with the disabled notification.
 */
function tn731_umg_filter_new_user_notification_text( $translation, $text, $domain ) {
	global $pagenow;

	if ( ! is_admin() || 'user-new.php' !== $pagenow || 'default' !== $domain ) {
		return $translation;
	}

	switch ( $text ) {
		case 'A password reset link will be sent to the user via email.':
			return __( 'No account email will be sent automatically.', 'tn-user-management' );
		case 'Send the new user an email about their account.':
			return __( 'New-user account emails are disabled by TN User Management.', 'tn-user-management' );
		case 'Add User will set up a new user account on the network and send that person an email with username and password.':
			return __( 'Add User will set up a new user account on the network. No account email will be sent automatically.', 'tn-user-management' );
		case 'New users will receive an email letting them know they&#8217;ve been added as a user for your site. This email will also contain their password. Check the box if you do not want the user to receive a welcome email.':
			return __( 'TN User Management adds users to this site without sending a confirmation or welcome email.', 'tn-user-management' );
	}

	return $translation;
}

/**
 * Add multisite users directly to a subsite without confirmation email.
 */
function tn731_umg_force_skip_confirmation_email() {
	global $pagenow;

	if ( ! is_multisite() || is_network_admin() || 'user-new.php' !== $pagenow ) {
		return;
	}

	if ( ! current_user_can( 'manage_network_users' ) || empty( $_POST ) ) {
		return;
	}

	$_POST['noconfirmation'] = '1';
}

/**
 * Force username = email on submit.
 */
function tn731_umg_force_email_username() {
	global $pagenow;

	if ( 'user-new.php' !== $pagenow ) {
		return;
	}

	if ( ! current_user_can( 'create_users' ) ) {
		return;
	}

	if ( empty( $_POST ) ) {
		return;
	}

	if ( isset( $_POST['email'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['email'] ) );

		if ( ! empty( $email ) ) {
			$_POST['user_login'] = strtolower( $email );
		}
	}

	if ( isset( $_POST['user_email'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['user_email'] ) );

		if ( ! empty( $email ) ) {
			$_POST['user_login'] = strtolower( $email );
		}
	}

	if ( isset( $_POST['user'] ) && is_array( $_POST['user'] ) && isset( $_POST['user']['email'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['user']['email'] ) );

		if ( ! empty( $email ) ) {
			$_POST['user']['username'] = strtolower( $email );
		}
	}
}

/**
 * Multisite signup validation fallback.
 */
function tn731_umg_validate_email_as_username( $result ) {

	if ( empty( $result['user_email'] ) ) {
		return $result;
	}

	$email = sanitize_email( $result['user_email'] );

	if ( empty( $email ) ) {
		return $result;
	}

	$result['user_name'] = strtolower( $email );

	if ( ! empty( $result['errors'] ) && ! empty( $result['errors']->errors['user_name'] ) ) {
		unset( $result['errors']->errors['user_name'] );
	}

	return $result;
}

/*
|--------------------------------------------------------------------------
| One-Time Username Migration
|--------------------------------------------------------------------------
*/

/**
 * On activation, loop all existing users and set user_login = user_email where safe.
 */
function tn731_umg_migrate_existing_usernames() {
	global $wpdb;
	static $activation_result = null;

	if ( null !== $activation_result ) {
		return $activation_result;
	}

	$updated = 0;
	$skipped = 0;

	$users = $wpdb->get_results(
		"
		SELECT ID, user_login, user_email
		FROM {$wpdb->users}
		WHERE user_email <> ''
		"
	);

	if ( empty( $users ) ) {
		$activation_result = array(
			'ran'     => current_time( 'mysql' ),
			'updated' => 0,
			'skipped' => 0,
		);

		update_site_option( 'tn731_umg_migration_result', $activation_result );
		return $activation_result;
	}

	foreach ( $users as $user ) {
		$user_id         = (int) $user->ID;
		$previous_login  = (string) $user->user_login;
		$email           = strtolower( trim( (string) $user->user_email ) );
		$login           = strtolower( trim( $previous_login ) );
		$was_super_admin = is_multisite() && is_super_admin( $user_id );

		if ( empty( $email ) ) {
			$skipped++;
			continue;
		}

		if ( $email === $login ) {
			$skipped++;
			continue;
		}

		$conflict = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->users}
				WHERE user_login = %s
				AND ID != %d
				LIMIT 1
				",
				$email,
				$user_id
			)
		);

		if ( $conflict ) {
			$skipped++;
			continue;
		}

		$result = $wpdb->update(
			$wpdb->users,
			array(
				'user_login' => $email,
			),
			array(
				'ID' => $user_id,
			),
			array( '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			clean_user_cache( $user_id );

			if ( $was_super_admin ) {
				tn731_umg_update_super_admin_login( $previous_login, $email );
			}

			$updated++;
		} else {
			$skipped++;
		}
	}

	wp_cache_flush();

	$activation_result = array(
		'ran'     => current_time( 'mysql' ),
		'updated' => $updated,
		'skipped' => $skipped,
	);

	update_site_option( 'tn731_umg_migration_result', $activation_result );
	return $activation_result;
}

/**
 * Keep multisite's login-based Super Admin list aligned with a migrated login.
 *
 * @param string $previous_login Login before migration.
 * @param string $new_login      Login after migration.
 * @return bool Whether the network option was updated.
 */
function tn731_umg_update_super_admin_login( $previous_login, $new_login ) {

	if ( ! is_multisite() || empty( $previous_login ) || empty( $new_login ) ) {
		return false;
	}

	$super_admins = get_site_option( 'site_admins', array() );

	if ( ! is_array( $super_admins ) ) {
		return false;
	}

	$updated = false;

	foreach ( $super_admins as $index => $login ) {
		if ( (string) $login !== (string) $previous_login ) {
			continue;
		}

		$super_admins[ $index ] = $new_login;
		$updated                = true;
	}

	if ( ! $updated ) {
		return false;
	}

	$super_admins = array_values( array_unique( $super_admins ) );

	return update_site_option( 'site_admins', $super_admins );
}

/**
 * Reissue the activating user's cookies if username migration changed the
 * login embedded in their existing authentication cookies.
 *
 * @param int         $user_id        Activating user ID.
 * @param string      $previous_login Login captured before migration.
 * @param string      $session_token  Existing session token.
 * @param array|false $cookie         Parsed logged-in cookie data.
 * @return bool Whether the session needed to be refreshed.
 */
function tn731_umg_refresh_activating_user_session( $user_id, $previous_login, $session_token, $cookie ) {

	$user_id = absint( $user_id );

	if ( ! $user_id || empty( $session_token ) ) {
		return false;
	}

	clean_user_cache( $user_id );
	$user = get_userdata( $user_id );

	if ( ! $user || (string) $user->user_login === (string) $previous_login ) {
		return false;
	}

	$remember = is_array( $cookie )
		&& ! empty( $cookie['expiration'] )
		&& (int) $cookie['expiration'] > time() + ( 2 * DAY_IN_SECONDS );

	wp_set_current_user( 0 );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, $remember, is_ssl(), $session_token );

	return true;
}
