<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Roles Comparison Page
|--------------------------------------------------------------------------
*/

add_action( 'admin_menu', 'tn731_umg_register_roles_submenu', 20 );

function tn731_umg_register_roles_submenu() {

	add_submenu_page(
		'edit.php?post_type=tn731_permset',
		'Roles',
		'Roles',
		'read',
		'tn731-umg-roles',
		'tn731_umg_render_roles_page'
	);
}

function tn731_umg_render_roles_page() {

	$show = isset( $_GET['show'] ) ? sanitize_key( wp_unslash( $_GET['show'] ) ) : 'diff';

	if ( ! in_array( $show, array( 'diff', 'all' ), true ) ) {
		$show = 'diff';
	}

	$admin_role = get_role( 'administrator' );
	$user_role  = get_role( 'user' );
	$sub_role   = get_role( 'subscriber' );

	$admin_caps = $admin_role ? (array) $admin_role->capabilities : array();
	$user_caps  = $user_role  ? (array) $user_role->capabilities  : array();
	$sub_caps   = $sub_role   ? (array) $sub_role->capabilities   : array();

	$all_caps = array_unique(
		array_merge(
			array_keys( $admin_caps ),
			array_keys( $user_caps ),
			array_keys( $sub_caps )
		)
	);

	natcasesort( $all_caps );

	$rows = array();

	foreach ( $all_caps as $cap ) {

		$a = ! empty( $admin_caps[ $cap ] );
		$u = ! empty( $user_caps[ $cap ] );
		$s = ! empty( $sub_caps[ $cap ] );

		$is_diff = ( $a !== $u ) || ( $a !== $s ) || ( $u !== $s );

		if ( 'diff' === $show && ! $is_diff ) {
			continue;
		}

		$rows[] = array(
			'cap'   => $cap,
			'admin' => $a,
			'user'  => $u,
			'sub'   => $s,
			'diff'  => $is_diff,
		);
	}

	$admin_count = count( array_filter( $admin_caps ) );
	$user_count  = count( array_filter( $user_caps ) );
	$sub_count   = count( array_filter( $sub_caps ) );

	$base_url = admin_url( 'edit.php?post_type=tn731_permset&page=tn731-umg-roles' );
	?>

	<div class="wrap">
		<h1>Roles</h1>

		<p class="tn731-umg-button-row">
			<a href="<?php echo esc_url( add_query_arg( 'show', 'diff', $base_url ) ); ?>" class="<?php echo ( 'diff' === $show ) ? 'button button-primary' : 'button'; ?>">Differences</a>
			<a href="<?php echo esc_url( add_query_arg( 'show', 'all', $base_url ) ); ?>" class="<?php echo ( 'all' === $show ) ? 'button button-primary' : 'button'; ?>">All</a>
		</p>

		<p>
			<strong>Administrator:</strong> <?php echo $admin_role ? 'Registered' : 'Missing'; ?>
			&nbsp;|&nbsp;
			<strong>User:</strong> <?php echo $user_role ? 'Registered' : 'Missing'; ?>
			&nbsp;|&nbsp;
			<strong>Subscriber:</strong> <?php echo $sub_role ? 'Registered' : 'Missing'; ?>
		</p>

		<p>
			<strong>Admin caps:</strong> <?php echo intval( $admin_count ); ?>
			&nbsp;|&nbsp;
			<strong>User caps:</strong> <?php echo intval( $user_count ); ?>
			&nbsp;|&nbsp;
			<strong>Subscriber caps:</strong> <?php echo intval( $sub_count ); ?>
		</p>

		<table class="widefat striped tn731-umg-table">
			<thead>
				<tr>
					<th class="tn731-umg-capability-column">Capability</th>
					<th>Administrator</th>
					<th>User</th>
					<th>Subscriber</th>
				</tr>
			</thead>
			<tbody>

			<?php if ( empty( $rows ) ) : ?>
				<tr>
					<td colspan="4">No differences</td>
				</tr>
			<?php else : ?>

				<?php foreach ( $rows as $row ) : ?>
					<tr class="<?php echo $row['diff'] ? 'tn731-umg-diff-row' : ''; ?>">
						<td class="tn731-umg-capability-column"><code><?php echo esc_html( $row['cap'] ); ?></code></td>
						<td><?php echo $row['admin'] ? 'Yes' : ''; ?></td>
						<td><?php echo $row['user'] ? 'Yes' : ''; ?></td>
						<td><?php echo $row['sub'] ? 'Yes' : ''; ?></td>
					</tr>
				<?php endforeach; ?>

			<?php endif; ?>

			</tbody>
		</table>
	</div>

	<?php
}

/*
|--------------------------------------------------------------------------
| Editable Roles
|--------------------------------------------------------------------------
|
| Do not replace WordPress' editable roles list.
| Just make sure our baseline roles are present if they exist.
|
*/

add_filter( 'editable_roles', 'tn731_umg_limit_editable_roles', 999 );

function tn731_umg_limit_editable_roles( $roles ) {

	$allowed = array(
		'administrator',
		'user',
		'subscriber',
	);

	foreach ( $roles as $role_slug => $role_data ) {
		if ( ! in_array( $role_slug, $allowed, true ) ) {
			unset( $roles[ $role_slug ] );
		}
	}

	return $roles;
}
