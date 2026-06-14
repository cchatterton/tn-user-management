<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Email as Username
|--------------------------------------------------------------------------
*/

add_action( 'admin_init', 'tn731_umg_force_email_username', 1 );
add_filter( 'wpmu_validate_user_signup', 'tn731_umg_validate_email_as_username', 20 );

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
		update_site_option(
			'tn731_umg_migration_result',
			array(
				'ran'     => current_time( 'mysql' ),
				'updated' => 0,
				'skipped' => 0,
			)
		);
		return;
	}

	foreach ( $users as $user ) {
		$user_id = (int) $user->ID;
		$email   = strtolower( trim( (string) $user->user_email ) );
		$login   = strtolower( trim( (string) $user->user_login ) );

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
			$updated++;
		} else {
			$skipped++;
		}
	}

	wp_cache_flush();

	update_site_option(
		'tn731_umg_migration_result',
		array(
			'ran'     => current_time( 'mysql' ),
			'updated' => $updated,
			'skipped' => $skipped,
		)
	);
}
