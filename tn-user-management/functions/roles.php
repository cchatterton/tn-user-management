<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Capabilities Page
|--------------------------------------------------------------------------
*/

add_action( 'admin_menu', 'tn731_umg_register_capabilities_submenu', 20 );
add_action( 'admin_init', 'tn731_umg_handle_capability_action' );
add_action( 'wp_ajax_tn731_umg_toggle_user_capability', 'tn731_umg_ajax_toggle_user_capability' );
add_action( 'wp_ajax_tn731_umg_delete_capability', 'tn731_umg_ajax_delete_capability' );

function tn731_umg_register_capabilities_submenu() {

	add_submenu_page(
		'edit.php?post_type=tn731_permset',
		'Capabilities',
		'Capabilities',
		'manage_options',
		'tn731-umg-roles',
		'tn731_umg_render_capabilities_page'
	);
}

function tn731_umg_get_manual_capabilities() {

	$capabilities = get_option( 'tn731_umg_manual_capabilities', array() );
	$capabilities = is_array( $capabilities ) ? array_map( 'sanitize_key', $capabilities ) : array();
	$capabilities = array_values( array_unique( array_filter( $capabilities ) ) );
	natcasesort( $capabilities );

	return array_values( $capabilities );
}

function tn731_umg_get_user_excluded_capabilities() {

	$capabilities = get_option( 'tn731_umg_user_excluded_capabilities', array() );
	$capabilities = is_array( $capabilities ) ? array_map( 'sanitize_key', $capabilities ) : array();
	$capabilities = array_values( array_unique( array_filter( $capabilities ) ) );
	natcasesort( $capabilities );

	return array_values( $capabilities );
}

function tn731_umg_set_user_capability_excluded( $capability, $excluded ) {

	$capability   = sanitize_key( $capability );
	$capabilities = tn731_umg_get_user_excluded_capabilities();

	if ( $excluded ) {
		$capabilities[] = $capability;
	} else {
		$capabilities = array_diff( $capabilities, array( $capability ) );
	}

	$capabilities = array_values( array_unique( array_filter( $capabilities ) ) );
	update_option( 'tn731_umg_user_excluded_capabilities', $capabilities, false );
}

function tn731_umg_get_capabilities_page_url( $args = array() ) {
	return add_query_arg(
		$args,
		admin_url( 'edit.php?post_type=tn731_permset&page=tn731-umg-roles' )
	);
}

function tn731_umg_capability_exists( $capability ) {

	foreach ( (array) wp_roles()->roles as $role_data ) {
		if ( isset( $role_data['capabilities'][ $capability ] ) ) {
			return true;
		}
	}

	return false;
}

function tn731_umg_set_user_role_capability( $capability, $enabled ) {

	$user_role = get_role( 'user' );

	if ( ! $user_role ) {
		return false;
	}

	if ( $enabled ) {
		$user_role->add_cap( $capability, true );
		tn731_umg_set_user_capability_excluded( $capability, false );
	} else {
		$user_role->remove_cap( $capability );
		tn731_umg_set_user_capability_excluded( $capability, true );
	}

	return true;
}

function tn731_umg_remove_capability_from_all_roles( $capability ) {

	if ( ! tn731_umg_capability_exists( $capability ) ) {
		return false;
	}

	foreach ( array_keys( (array) wp_roles()->roles ) as $role_slug ) {
		$role = get_role( $role_slug );

		if ( $role ) {
			$role->remove_cap( $capability );
		}
	}

	$manual_capabilities   = array_values( array_diff( tn731_umg_get_manual_capabilities(), array( $capability ) ) );
	$excluded_capabilities = array_values( array_diff( tn731_umg_get_user_excluded_capabilities(), array( $capability ) ) );
	update_option( 'tn731_umg_manual_capabilities', $manual_capabilities, false );
	update_option( 'tn731_umg_user_excluded_capabilities', $excluded_capabilities, false );

	return true;
}

function tn731_umg_get_managed_role_capability_counts() {

	$counts = array();

	foreach ( array( 'administrator', 'user', 'subscriber' ) as $role_slug ) {
		$role = get_role( $role_slug );
		$counts[ $role_slug ] = $role ? count( array_filter( (array) $role->capabilities ) ) : 0;
	}

	return $counts;
}

function tn731_umg_ajax_toggle_user_capability() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'You do not have permission to manage capabilities.', 'tn-user-management' ) ),
			403
		);
	}

	check_ajax_referer( 'tn731_umg_manage_capability_ajax', 'nonce' );

	$submitted  = isset( $_POST['capability'] ) && is_scalar( $_POST['capability'] )
		? trim( wp_unslash( $_POST['capability'] ) )
		: '';
	$capability = sanitize_key( $submitted );
	$enabled    = isset( $_POST['enabled'] ) && is_scalar( $_POST['enabled'] )
		? sanitize_key( wp_unslash( $_POST['enabled'] ) )
		: '';

	if ( ! $capability || $capability !== $submitted || ! tn731_umg_capability_exists( $capability ) || ! in_array( $enabled, array( 'yes', 'no' ), true ) ) {
		wp_send_json_error(
			array( 'message' => __( 'The capability request was invalid.', 'tn-user-management' ) ),
			400
		);
	}

	$is_enabled = 'yes' === $enabled;

	if ( ! tn731_umg_set_user_role_capability( $capability, $is_enabled ) ) {
		wp_send_json_error(
			array( 'message' => __( 'The User role is not available.', 'tn-user-management' ) ),
			409
		);
	}

	$user_role = get_role( 'user' );

	wp_send_json_success(
		array(
			'enabled'    => $is_enabled,
			'label'      => $is_enabled ? __( 'Yes', 'tn-user-management' ) : __( 'No', 'tn-user-management' ),
			'ariaLabel'  => sprintf(
				$is_enabled ? __( 'Remove %s from User', 'tn-user-management' ) : __( 'Add %s to User', 'tn-user-management' ),
				$capability
			),
			'userCount'  => $user_role ? count( array_filter( (array) $user_role->capabilities ) ) : 0,
		)
	);
}

function tn731_umg_ajax_delete_capability() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'You do not have permission to manage capabilities.', 'tn-user-management' ) ),
			403
		);
	}

	check_ajax_referer( 'tn731_umg_manage_capability_ajax', 'nonce' );

	$submitted  = isset( $_POST['capability'] ) && is_scalar( $_POST['capability'] )
		? trim( wp_unslash( $_POST['capability'] ) )
		: '';
	$capability = sanitize_key( $submitted );

	if ( ! $capability || $capability !== $submitted || ! tn731_umg_remove_capability_from_all_roles( $capability ) ) {
		wp_send_json_error(
			array( 'message' => __( 'The capability could not be removed.', 'tn-user-management' ) ),
			400
		);
	}

	wp_send_json_success(
		array(
			'counts' => tn731_umg_get_managed_role_capability_counts(),
		)
	);
}

function tn731_umg_handle_capability_action() {

	if ( empty( $_POST['tn731_umg_capability_action'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage capabilities.', 'tn-user-management' ) );
	}

	check_admin_referer( 'tn731_umg_manage_capability' );

	$action_value = is_scalar( $_POST['tn731_umg_capability_action'] ) ? $_POST['tn731_umg_capability_action'] : '';
	$submitted    = isset( $_POST['capability'] ) && is_scalar( $_POST['capability'] )
		? trim( wp_unslash( $_POST['capability'] ) )
		: '';
	$action       = sanitize_key( wp_unslash( $action_value ) );
	$capability   = sanitize_key( $submitted );
	$notice       = 'invalid';

	if ( 'add' === $action ) {
		$is_valid = $capability
			&& $capability === $submitted
			&& preg_match( '/^[a-z][a-z0-9_]*$/', $capability )
			&& ! tn731_umg_capability_exists( $capability );

		if ( $is_valid ) {
			$admin_role = get_role( 'administrator' );
			$user_role  = get_role( 'user' );

			if ( $admin_role && $user_role ) {
				$admin_role->add_cap( $capability, true );
				$user_role->add_cap( $capability, true );

				$manual_capabilities   = tn731_umg_get_manual_capabilities();
				$manual_capabilities[] = $capability;
				$manual_capabilities   = array_values( array_unique( $manual_capabilities ) );
				update_option( 'tn731_umg_manual_capabilities', $manual_capabilities, false );
				$notice = 'added';
			}
		}
	} elseif ( 'remove' === $action || 'remove_any' === $action ) {
		$manual_capabilities = tn731_umg_get_manual_capabilities();

		if ( ( 'remove_any' === $action || in_array( $capability, $manual_capabilities, true ) ) && tn731_umg_remove_capability_from_all_roles( $capability ) ) {
			$notice = 'removed';
		}
	} elseif ( 'toggle_user' === $action && tn731_umg_capability_exists( $capability ) ) {
		$user_role = get_role( 'user' );

		if ( $user_role ) {
			$has_capability = ! empty( $user_role->capabilities[ $capability ] );
			tn731_umg_set_user_role_capability( $capability, ! $has_capability );
			$notice = $has_capability ? 'user_removed' : 'user_added';
		}
	} elseif ( 'remove_group' === $action ) {
		$group_value = isset( $_POST['capability_group'] ) && is_scalar( $_POST['capability_group'] )
			? trim( wp_unslash( $_POST['capability_group'] ) )
			: '';
		$group       = sanitize_key( $group_value );

		if ( $group && $group === $group_value && preg_match( '/^[a-z][a-z0-9-]*$/', $group ) ) {
			$removed_capabilities = array();

			foreach ( (array) wp_roles()->roles as $role_data ) {
				foreach ( array_keys( (array) $role_data['capabilities'] ) as $role_capability ) {
					if ( $group === $role_capability || 0 === strpos( $role_capability, $group . '_' ) ) {
						$removed_capabilities[] = $role_capability;
					}
				}
			}

			$removed_capabilities = array_values( array_unique( $removed_capabilities ) );

			foreach ( array_keys( (array) wp_roles()->roles ) as $role_slug ) {
				$role = get_role( $role_slug );

				if ( ! $role ) {
					continue;
				}

				foreach ( $removed_capabilities as $role_capability ) {
					$role->remove_cap( $role_capability );
				}
			}

			$manual_capabilities = array_values( array_diff( tn731_umg_get_manual_capabilities(), $removed_capabilities ) );
			$excluded_capabilities = array_values( array_diff( tn731_umg_get_user_excluded_capabilities(), $removed_capabilities ) );
			update_option( 'tn731_umg_manual_capabilities', $manual_capabilities, false );
			update_option( 'tn731_umg_user_excluded_capabilities', $excluded_capabilities, false );
			$notice = empty( $removed_capabilities ) ? 'group_empty' : 'group_removed';
		}
	}

	$show_value = isset( $_POST['show'] ) && is_scalar( $_POST['show'] ) ? $_POST['show'] : '';
	$show       = 'all' === sanitize_key( wp_unslash( $show_value ) ) ? 'all' : 'diff';

	wp_safe_redirect(
		tn731_umg_get_capabilities_page_url(
			array(
				'show'             => $show,
				'capability_notice' => $notice,
			)
		)
	);
	exit;
}

function tn731_umg_get_capability_groups( $rows, $manual_capabilities = array() ) {

	$prefix_groups = array();
	$single_rows   = array();
	$groups        = array(
		'Singles' => array(),
		'Custom'  => array(),
	);

	foreach ( $rows as $row ) {
		if ( in_array( $row['cap'], $manual_capabilities, true ) ) {
			$groups['Custom'][] = $row;
			continue;
		}

		if ( false === strpos( $row['cap'], '_' ) ) {
			$single_rows[ $row['cap'] ] = $row;
			continue;
		}

		$parts  = explode( '_', $row['cap'], 2 );
		$prefix = $parts[0];
		$prefix_groups[ $prefix ][] = $row;
	}

	foreach ( $single_rows as $capability => $row ) {
		if ( isset( $prefix_groups[ $capability ] ) ) {
			array_unshift( $prefix_groups[ $capability ], $row );
		} else {
			$groups['Singles'][] = $row;
		}
	}

	uksort( $prefix_groups, 'strnatcasecmp' );

	return array_merge( $groups, $prefix_groups );
}

function tn731_umg_get_capability_group_details( $group ) {
	if ( 'Singles' === $group ) {
		$title = 'Single capabilities';
	} elseif ( 'Custom' === $group ) {
		$title = 'Custom capabilities';
	} else {
		$title = $group;
	}

	$id    = 'tn731-umg-capability-group-' . sanitize_html_class( strtolower( $group ) );

	return array(
		'id'    => $id,
		'title' => $title,
	);
}

function tn731_umg_render_capability_table( $group, $title, $section_id, $rows, $show ) {
	?>
	<div class="tn731-umg-capability-heading-row">
		<h2 id="<?php echo esc_attr( $section_id ); ?>" class="tn731-umg-capability-heading"><?php echo esc_html( $title ); ?></h2>
		<?php if ( ! in_array( $group, array( 'Singles', 'Custom' ), true ) ) : ?>
			<form method="post" class="tn731-umg-inline-form">
				<?php wp_nonce_field( 'tn731_umg_manage_capability' ); ?>
				<input type="hidden" name="tn731_umg_capability_action" value="remove_group">
				<input type="hidden" name="capability_group" value="<?php echo esc_attr( $group ); ?>">
				<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
				<button type="submit" class="button-link-delete tn731-umg-remove-capability tn731-umg-confirm-remove-capability-group" data-capability-group="<?php echo esc_attr( $title ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove all %s capabilities from every role', 'tn-user-management' ), $title ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Remove all %s capabilities', 'tn-user-management' ), $title ) ); ?>">
					<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Remove capability group', 'tn-user-management' ); ?></span>
				</button>
			</form>
		<?php endif; ?>
	</div>
	<table class="widefat striped tn731-umg-table">
		<colgroup>
			<col class="tn731-umg-capability-col">
			<col class="tn731-umg-role-col">
			<col class="tn731-umg-role-col">
			<col class="tn731-umg-role-col">
			<col class="tn731-umg-action-col">
		</colgroup>
		<thead>
			<tr>
				<th class="tn731-umg-capability-column"><?php esc_html_e( 'Capability', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'Administrator', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'User', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'Subscriber', 'tn-user-management' ); ?></th>
				<th class="tn731-umg-action-column"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'tn-user-management' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $rows ) ) : ?>
			<tr><td colspan="5"><?php esc_html_e( 'No capabilities in this group.', 'tn-user-management' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr class="<?php echo $row['diff'] ? 'tn731-umg-diff-row' : ''; ?>">
					<td class="tn731-umg-capability-column"><code><?php echo esc_html( $row['cap'] ); ?></code></td>
					<td><?php echo $row['admin'] ? esc_html__( 'Yes', 'tn-user-management' ) : '&mdash;'; ?></td>
					<td>
						<?php if ( $row['admin'] || $row['user'] ) : ?>
							<form method="post" class="tn731-umg-inline-form">
								<?php wp_nonce_field( 'tn731_umg_manage_capability' ); ?>
								<input type="hidden" name="tn731_umg_capability_action" value="toggle_user">
								<input type="hidden" name="capability" value="<?php echo esc_attr( $row['cap'] ); ?>">
								<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
								<button type="submit" class="tn731-umg-capability-toggle" role="switch" aria-checked="<?php echo $row['user'] ? 'true' : 'false'; ?>" data-capability="<?php echo esc_attr( $row['cap'] ); ?>" data-enabled="<?php echo $row['user'] ? 'yes' : 'no'; ?>" aria-label="<?php echo esc_attr( sprintf( $row['user'] ? __( 'Remove %s from User', 'tn-user-management' ) : __( 'Add %s to User', 'tn-user-management' ), $row['cap'] ) ); ?>">
									<span class="tn731-umg-capability-toggle-track" aria-hidden="true"></span>
									<span class="tn731-umg-capability-toggle-label" aria-hidden="true"><?php echo $row['user'] ? esc_html__( 'Yes', 'tn-user-management' ) : esc_html__( 'No', 'tn-user-management' ); ?></span>
								</button>
							</form>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td><?php echo $row['sub'] ? esc_html__( 'Yes', 'tn-user-management' ) : '&mdash;'; ?></td>
					<td class="tn731-umg-action-column">
						<form method="post" class="tn731-umg-inline-form">
							<?php wp_nonce_field( 'tn731_umg_manage_capability' ); ?>
							<input type="hidden" name="tn731_umg_capability_action" value="remove_any">
							<input type="hidden" name="capability" value="<?php echo esc_attr( $row['cap'] ); ?>">
							<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
							<button type="submit" class="button-link-delete tn731-umg-remove-capability tn731-umg-delete-capability" data-capability="<?php echo esc_attr( $row['cap'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove capability %s from every role', 'tn-user-management' ), $row['cap'] ) ); ?>" title="<?php esc_attr_e( 'Remove capability from every role', 'tn-user-management' ); ?>">
								<span class="dashicons dashicons-trash" aria-hidden="true"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Remove', 'tn-user-management' ); ?></span>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<?php
}

function tn731_umg_render_capabilities_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view capabilities.', 'tn-user-management' ) );
	}

	$show = 'all';

	$roles = array(
		'admin' => get_role( 'administrator' ),
		'user'  => get_role( 'user' ),
		'sub'   => get_role( 'subscriber' ),
	);

	$capabilities = array();
	foreach ( $roles as $key => $role ) {
		$capabilities[ $key ] = $role ? (array) $role->capabilities : array();
	}

	$all_capabilities = array_unique(
		array_merge(
			array_keys( $capabilities['admin'] ),
			array_keys( $capabilities['user'] ),
			array_keys( $capabilities['sub'] )
		)
	);
	natcasesort( $all_capabilities );

	$rows = array();
	foreach ( $all_capabilities as $capability ) {
		$row = array(
			'cap'   => $capability,
			'admin' => ! empty( $capabilities['admin'][ $capability ] ),
			'user'  => ! empty( $capabilities['user'][ $capability ] ),
			'sub'   => ! empty( $capabilities['sub'][ $capability ] ),
		);
		$row['diff'] = $row['admin'] !== $row['user'];

		$rows[] = $row;
	}

	$manual_capabilities = tn731_umg_get_manual_capabilities();
	$groups              = tn731_umg_get_capability_groups( $rows, $manual_capabilities );
	$notice_value        = isset( $_GET['capability_notice'] ) && is_scalar( $_GET['capability_notice'] ) ? $_GET['capability_notice'] : '';
	$notice              = sanitize_key( wp_unslash( $notice_value ) );
	?>
	<div class="wrap tn731-umg-capabilities-wrap">
		<h1><?php esc_html_e( 'Capabilities', 'tn-user-management' ); ?></h1>
		<div id="tn731-umg-capability-status" class="tn731-umg-capability-status" role="status" aria-live="polite" hidden></div>

		<?php if ( 'invalid' === $notice ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Capability could not be changed. Use a unique lowercase name containing only letters, numbers, and underscores.', 'tn-user-management' ); ?></p></div>
		<?php endif; ?>

		<p>
			<strong><?php esc_html_e( 'Administrator:', 'tn-user-management' ); ?></strong> <?php if ( $roles['admin'] ) : ?><span id="tn731-umg-administrator-capability-count"><?php echo esc_html( sprintf( 'Registered (%d)', count( array_filter( $capabilities['admin'] ) ) ) ); ?></span><?php else : ?><?php esc_html_e( 'Missing', 'tn-user-management' ); ?><?php endif; ?>
			&nbsp;|&nbsp; <strong><?php esc_html_e( 'User:', 'tn-user-management' ); ?></strong> <?php if ( $roles['user'] ) : ?><span id="tn731-umg-user-capability-count"><?php echo esc_html( sprintf( 'Registered (%d)', count( array_filter( $capabilities['user'] ) ) ) ); ?></span><?php else : ?><?php esc_html_e( 'Missing', 'tn-user-management' ); ?><?php endif; ?>
			&nbsp;|&nbsp; <strong><?php esc_html_e( 'Subscriber:', 'tn-user-management' ); ?></strong> <?php if ( $roles['sub'] ) : ?><span id="tn731-umg-subscriber-capability-count"><?php echo esc_html( sprintf( 'Registered (%d)', count( array_filter( $capabilities['sub'] ) ) ) ); ?></span><?php else : ?><?php esc_html_e( 'Missing', 'tn-user-management' ); ?><?php endif; ?>
		</p>
		<nav class="tn731-umg-section-links" aria-label="<?php esc_attr_e( 'Capability sections', 'tn-user-management' ); ?>">
			<strong><?php esc_html_e( 'Sections:', 'tn-user-management' ); ?></strong>
			<?php $section_number = 0; ?>
			<?php foreach ( $groups as $group => $group_rows ) : ?>
				<?php $group_details = tn731_umg_get_capability_group_details( $group ); ?>
				<?php if ( $section_number > 0 ) : ?><span aria-hidden="true">|</span><?php endif; ?>
				<a href="#<?php echo esc_attr( $group_details['id'] ); ?>"><?php echo esc_html( $group_details['title'] ); ?></a>
				<?php $section_number++; ?>
			<?php endforeach; ?>
		</nav>

		<h2><?php esc_html_e( 'Add capability', 'tn-user-management' ); ?></h2>
		<form method="post" class="tn731-umg-add-capability-form">
			<?php wp_nonce_field( 'tn731_umg_manage_capability' ); ?>
			<input type="hidden" name="tn731_umg_capability_action" value="add">
			<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
			<label class="screen-reader-text" for="tn731-umg-capability"><?php esc_html_e( 'Capability name', 'tn-user-management' ); ?></label>
			<input type="text" id="tn731-umg-capability" name="capability" class="regular-text" pattern="[a-z][a-z0-9_]*" placeholder="example_capability" required>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add to Administrator and User', 'tn-user-management' ); ?></button>
		</form>

		<?php foreach ( $groups as $group => $group_rows ) : ?>
			<?php $group_details = tn731_umg_get_capability_group_details( $group ); ?>
			<?php tn731_umg_render_capability_table( $group, $group_details['title'], $group_details['id'], $group_rows, $show ); ?>
		<?php endforeach; ?>
	</div>
	<?php
}

/*
|--------------------------------------------------------------------------
| Editable Roles
|--------------------------------------------------------------------------
*/

add_filter( 'editable_roles', 'tn731_umg_limit_editable_roles', 999 );

function tn731_umg_limit_editable_roles( $roles ) {

	$allowed = array( 'administrator', 'user', 'subscriber', 'integration' );

	foreach ( $roles as $role_slug => $role_data ) {
		if ( ! in_array( $role_slug, $allowed, true ) ) {
			unset( $roles[ $role_slug ] );
		}
	}

	return $roles;
}
