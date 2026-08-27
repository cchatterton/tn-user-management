<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Capabilities Page
|--------------------------------------------------------------------------
*/

add_action( 'admin_menu', 'tn731_umg_register_capabilities_submenu', 20 );
add_action( 'admin_init', 'tn731_umg_handle_capability_action' );

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
	} elseif ( 'remove' === $action ) {
		$manual_capabilities = tn731_umg_get_manual_capabilities();

		if ( in_array( $capability, $manual_capabilities, true ) ) {
			$admin_role = get_role( 'administrator' );
			$user_role  = get_role( 'user' );

			if ( $admin_role ) {
				$admin_role->remove_cap( $capability );
			}

			if ( $user_role ) {
				$user_role->remove_cap( $capability );
			}

			$manual_capabilities = array_values( array_diff( $manual_capabilities, array( $capability ) ) );
			update_option( 'tn731_umg_manual_capabilities', $manual_capabilities, false );
			tn731_umg_set_user_capability_excluded( $capability, false );
			$notice = 'removed';
		}
	} elseif ( 'toggle_user' === $action && tn731_umg_capability_exists( $capability ) ) {
		$user_role = get_role( 'user' );

		if ( $user_role ) {
			$has_capability = ! empty( $user_role->capabilities[ $capability ] );

			if ( $has_capability ) {
				$user_role->remove_cap( $capability );
				tn731_umg_set_user_capability_excluded( $capability, true );
				$notice = 'user_removed';
			} else {
				$user_role->add_cap( $capability, true );
				tn731_umg_set_user_capability_excluded( $capability, false );
				$notice = 'user_added';
			}
		}
	} elseif ( 'remove_group' === $action ) {
		$group_value = isset( $_POST['capability_group'] ) && is_scalar( $_POST['capability_group'] )
			? trim( wp_unslash( $_POST['capability_group'] ) )
			: '';
		$group       = sanitize_key( $group_value );

		if ( $group && $group === $group_value && preg_match( '/^[a-z][a-z0-9]*$/', $group ) ) {
			$removed_capabilities = array();

			foreach ( (array) wp_roles()->roles as $role_data ) {
				foreach ( array_keys( (array) $role_data['capabilities'] ) as $role_capability ) {
					if ( 0 === strpos( $role_capability, $group . '_' ) ) {
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

	$rows_by_prefix = array();
	$groups         = array(
		'Singles' => array(),
		'Custom'  => array(),
	);

	foreach ( $rows as $row ) {
		if ( in_array( $row['cap'], $manual_capabilities, true ) ) {
			$groups['Custom'][] = $row;
			continue;
		}

		$parts  = explode( '_', $row['cap'], 2 );
		$prefix = $parts[0];
		$rows_by_prefix[ $prefix ][] = $row;
	}

	$prefix_groups = array();

	foreach ( $rows_by_prefix as $prefix => $prefix_rows ) {
		if ( count( $prefix_rows ) >= 2 ) {
			$prefix_groups[ $prefix ] = $prefix_rows;
		} else {
			$groups['Singles'][] = $prefix_rows[0];
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
		$title = $group . '_*';
	}

	$id    = 'tn731-umg-capability-group-' . sanitize_html_class( strtolower( $group ) );

	return array(
		'id'    => $id,
		'title' => $title,
	);
}

function tn731_umg_render_capability_table( $group, $title, $section_id, $rows, $manual_capabilities, $show ) {
	?>
	<div class="tn731-umg-capability-heading-row">
		<h2 id="<?php echo esc_attr( $section_id ); ?>" class="tn731-umg-capability-heading"><?php echo esc_html( $title ); ?></h2>
		<?php if ( ! in_array( $group, array( 'Singles', 'Custom' ), true ) ) : ?>
			<form method="post" class="tn731-umg-inline-form">
				<?php wp_nonce_field( 'tn731_umg_manage_capability' ); ?>
				<input type="hidden" name="tn731_umg_capability_action" value="remove_group">
				<input type="hidden" name="capability_group" value="<?php echo esc_attr( $group ); ?>">
				<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
				<button type="submit" class="button button-secondary tn731-umg-confirm-remove-capability-group" data-capability-group="<?php echo esc_attr( $title ); ?>">
					<?php echo esc_html( sprintf( __( 'Remove %s from all roles', 'tn-user-management' ), $title ) ); ?>
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
			<col class="tn731-umg-role-col">
			<col class="tn731-umg-action-col">
		</colgroup>
		<thead>
			<tr>
				<th class="tn731-umg-capability-column"><?php esc_html_e( 'Capability', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'Administrator', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'User', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'Subscriber', 'tn-user-management' ); ?></th>
				<th><?php esc_html_e( 'Integration', 'tn-user-management' ); ?></th>
				<th class="tn731-umg-action-column"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'tn-user-management' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $rows ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No capabilities in this group.', 'tn-user-management' ); ?></td></tr>
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
								<button type="submit" class="button-link tn731-umg-capability-toggle" aria-label="<?php echo esc_attr( sprintf( $row['user'] ? __( 'Remove %s from User', 'tn-user-management' ) : __( 'Add %s to User', 'tn-user-management' ), $row['cap'] ) ); ?>">
									<?php echo $row['user'] ? esc_html__( 'Yes', 'tn-user-management' ) : esc_html__( 'No', 'tn-user-management' ); ?>
								</button>
							</form>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td><?php echo $row['sub'] ? esc_html__( 'Yes', 'tn-user-management' ) : '&mdash;'; ?></td>
					<td>&mdash;</td>
					<td class="tn731-umg-action-column">
					<?php if ( in_array( $row['cap'], $manual_capabilities, true ) ) : ?>
						<form method="post" class="tn731-umg-inline-form">
							<?php wp_nonce_field( 'tn731_umg_manage_capability' ); ?>
							<input type="hidden" name="tn731_umg_capability_action" value="remove">
							<input type="hidden" name="capability" value="<?php echo esc_attr( $row['cap'] ); ?>">
							<input type="hidden" name="show" value="<?php echo esc_attr( $show ); ?>">
							<button type="submit" class="button-link-delete tn731-umg-remove-capability tn731-umg-confirm-remove-capability" aria-label="<?php echo esc_attr( sprintf( __( 'Remove capability %s', 'tn-user-management' ), $row['cap'] ) ); ?>" title="<?php esc_attr_e( 'Remove capability', 'tn-user-management' ); ?>">
								<span class="dashicons dashicons-trash" aria-hidden="true"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Remove', 'tn-user-management' ); ?></span>
							</button>
						</form>
					<?php endif; ?>
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

	$show_value = isset( $_GET['show'] ) && is_scalar( $_GET['show'] ) ? $_GET['show'] : 'diff';
	$show       = sanitize_key( wp_unslash( $show_value ) );
	$show       = in_array( $show, array( 'diff', 'all' ), true ) ? $show : 'diff';

	$roles = array(
		'admin'       => get_role( 'administrator' ),
		'user'        => get_role( 'user' ),
		'sub'         => get_role( 'subscriber' ),
		'integration' => get_role( 'integration' ),
	);

	$capabilities = array();
	foreach ( $roles as $key => $role ) {
		$capabilities[ $key ] = $role ? (array) $role->capabilities : array();
	}

	$all_capabilities = array_unique(
		array_merge(
			array_keys( $capabilities['admin'] ),
			array_keys( $capabilities['user'] ),
			array_keys( $capabilities['sub'] ),
			array_keys( $capabilities['integration'] )
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
		$row['diff'] = ( $row['admin'] !== $row['user'] )
			|| ( $row['admin'] !== $row['sub'] )
			|| $row['admin']
			|| $row['user']
			|| $row['sub'];

		if ( 'all' === $show || $row['diff'] ) {
			$rows[] = $row;
		}
	}

	$manual_capabilities = tn731_umg_get_manual_capabilities();
	$groups              = tn731_umg_get_capability_groups( $rows, $manual_capabilities );
	$base_url            = tn731_umg_get_capabilities_page_url();
	$notice_value        = isset( $_GET['capability_notice'] ) && is_scalar( $_GET['capability_notice'] ) ? $_GET['capability_notice'] : '';
	$notice              = sanitize_key( wp_unslash( $notice_value ) );
	?>
	<div class="wrap tn731-umg-capabilities-wrap">
		<h1><?php esc_html_e( 'Capabilities', 'tn-user-management' ); ?></h1>

		<?php if ( 'added' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Capability added to Administrator and User.', 'tn-user-management' ); ?></p></div>
		<?php elseif ( 'removed' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Manually added capability removed.', 'tn-user-management' ); ?></p></div>
		<?php elseif ( 'user_removed' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Capability removed from User.', 'tn-user-management' ); ?></p></div>
		<?php elseif ( 'user_added' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Capability added to User.', 'tn-user-management' ); ?></p></div>
		<?php elseif ( 'group_removed' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Capability group removed from all roles on this site.', 'tn-user-management' ); ?></p></div>
		<?php elseif ( 'group_empty' === $notice ) : ?>
			<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'No stored capabilities were found in that group.', 'tn-user-management' ); ?></p></div>
		<?php elseif ( 'invalid' === $notice ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Capability could not be changed. Use a unique lowercase name containing only letters, numbers, and underscores.', 'tn-user-management' ); ?></p></div>
		<?php endif; ?>

		<p class="tn731-umg-button-row">
			<a href="<?php echo esc_url( add_query_arg( 'show', 'diff', $base_url ) ); ?>" class="<?php echo 'diff' === $show ? 'button button-primary' : 'button'; ?>"><?php esc_html_e( 'Differences', 'tn-user-management' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'show', 'all', $base_url ) ); ?>" class="<?php echo 'all' === $show ? 'button button-primary' : 'button'; ?>"><?php esc_html_e( 'All', 'tn-user-management' ); ?></a>
		</p>

		<p>
			<strong><?php esc_html_e( 'Administrator:', 'tn-user-management' ); ?></strong> <?php echo $roles['admin'] ? esc_html( sprintf( 'Registered (%d)', count( array_filter( $capabilities['admin'] ) ) ) ) : esc_html__( 'Missing', 'tn-user-management' ); ?>
			&nbsp;|&nbsp; <strong><?php esc_html_e( 'User:', 'tn-user-management' ); ?></strong> <?php echo $roles['user'] ? esc_html( sprintf( 'Registered (%d)', count( array_filter( $capabilities['user'] ) ) ) ) : esc_html__( 'Missing', 'tn-user-management' ); ?>
			&nbsp;|&nbsp; <strong><?php esc_html_e( 'Subscriber:', 'tn-user-management' ); ?></strong> <?php echo $roles['sub'] ? esc_html( sprintf( 'Registered (%d)', count( array_filter( $capabilities['sub'] ) ) ) ) : esc_html__( 'Missing', 'tn-user-management' ); ?>
			&nbsp;|&nbsp; <strong><?php esc_html_e( 'Integration:', 'tn-user-management' ); ?></strong> <?php echo $roles['integration'] ? esc_html__( 'Registered (0)', 'tn-user-management' ) : esc_html__( 'Missing', 'tn-user-management' ); ?>
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

		<p><em><?php esc_html_e( 'Integration is a database-only role. It has no capabilities and accounts assigned to it cannot log in.', 'tn-user-management' ); ?></em></p>
		<p><em><?php esc_html_e( 'WordPress does not identify which capabilities still belong to active code. Remove a prefix group only after confirming that its plugin or theme is no longer used.', 'tn-user-management' ); ?></em></p>

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
			<?php tn731_umg_render_capability_table( $group, $group_details['title'], $group_details['id'], $group_rows, $manual_capabilities, $show ); ?>
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
