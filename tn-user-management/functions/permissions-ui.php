<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
|--------------------------------------------------------------------------
| Hooks
|--------------------------------------------------------------------------
*/

add_action( 'add_meta_boxes', 'tn731_umg_add_permission_set_metaboxes' );
add_action( 'save_post_tn731_permset', 'tn731_umg_save_permission_set_meta' );

add_action( 'show_user_profile', 'tn731_umg_show_user_permission_sets_field' );
add_action( 'edit_user_profile', 'tn731_umg_show_user_permission_sets_field' );
add_action( 'personal_options_update', 'tn731_umg_save_user_permission_sets_field' );
add_action( 'edit_user_profile_update', 'tn731_umg_save_user_permission_sets_field' );

/*
|--------------------------------------------------------------------------
| Meta Boxes
|--------------------------------------------------------------------------
*/

function tn731_umg_add_permission_set_metaboxes() {

	add_meta_box(
		'tn731_umg_permset_visibility',
		'Visible Admin Areas',
		'tn731_umg_render_permission_set_visibility_metabox',
		'tn731_permset',
		'normal',
		'high'
	);

	add_meta_box(
		'tn731_umg_permset_users',
		'Assigned Users',
		'tn731_umg_render_permission_set_users_metabox',
		'tn731_permset',
		'side',
		'default'
	);
}

function tn731_umg_render_checkbox_group( $args ) {

	$group_id   = isset( $args['group_id'] ) ? $args['group_id'] : '';
	$title      = isset( $args['title'] ) ? $args['title'] : '';
	$field_name = isset( $args['field_name'] ) ? $args['field_name'] : '';
	$items      = isset( $args['items'] ) ? (array) $args['items'] : array();
	$selected   = isset( $args['selected'] ) ? (array) $args['selected'] : array();
	$formatter  = isset( $args['formatter'] ) && is_callable( $args['formatter'] ) ? $args['formatter'] : null;

	?>
	<div class="tn731-umg-group">
		<div class="tn731-umg-group-header">
			<h4><?php echo esc_html( $title ); ?></h4>
			<div class="tn731-umg-group-actions">
				<button type="button" class="button-link tn731-umg-select-all" data-target="<?php echo esc_attr( $group_id ); ?>">Select all</button>
				<span> / </span>
				<button type="button" class="button-link tn731-umg-deselect-all" data-target="<?php echo esc_attr( $group_id ); ?>">Deselect all</button>
			</div>
		</div>

		<div class="tn731-umg-checklist" id="<?php echo esc_attr( $group_id ); ?>">
			<?php foreach ( $items as $key => $item ) : ?>
				<?php
				$value = is_string( $key ) ? $key : $item;

				if ( $formatter ) {
					$label = call_user_func( $formatter, $value, $item );
				} else {
					$label = is_array( $item ) && isset( $item['label'] ) ? $item['label'] : $item;
				}
				?>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( (string) $value, array_map( 'strval', $selected ), true ) ); ?>>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

function tn731_umg_render_permission_set_visibility_metabox( $post ) {

	wp_nonce_field( 'tn731_umg_save_permission_set', 'tn731_umg_permission_set_nonce' );

	$map = tn731_umg_get_menu_map();

	$selected_native_main = tn731_umg_get_permission_set_meta_or_default(
		$post->ID,
		'_tn731_umg_native_main',
		$map['native_main']
	);

	$selected_post_types = tn731_umg_get_permission_set_meta_or_default(
		$post->ID,
		'_tn731_umg_post_types',
		$map['post_types']
	);

	$selected_tools = tn731_umg_get_permission_set_meta_or_default(
		$post->ID,
		'_tn731_umg_tools',
		$map['tools']
	);

	$selected_settings = tn731_umg_get_permission_set_meta_or_default(
		$post->ID,
		'_tn731_umg_settings',
		$map['settings']
	);

	$selected_third_party = tn731_umg_get_permission_set_meta_or_default(
		$post->ID,
		'_tn731_umg_third_party',
		$map['third_party']
	);

	tn731_umg_render_checkbox_group( array(
		'group_id'   => 'tn731-umg-native-main',
		'title'      => 'Main Menu – Native',
		'field_name' => 'tn731_umg_native_main',
		'items'      => $map['native_main'],
		'selected'   => $selected_native_main,
		'formatter'  => function( $value, $item ) {
			return $item . ' [' . $value . ']';
		},
	) );

	tn731_umg_render_checkbox_group( array(
		'group_id'   => 'tn731-umg-post-types',
		'title'      => 'Main Menu – Post Types',
		'field_name' => 'tn731_umg_post_types',
		'items'      => $map['post_types'],
		'selected'   => $selected_post_types,
		'formatter'  => function( $value, $item ) {
			return $item . ' [' . $value . ']';
		},
	) );

	tn731_umg_render_checkbox_group( array(
		'group_id'   => 'tn731-umg-tools',
		'title'      => 'Tools',
		'field_name' => 'tn731_umg_tools',
		'items'      => $map['tools'],
		'selected'   => $selected_tools,
		'formatter'  => function( $value, $item ) {
			return $item['label'] . ' [' . $item['slug'] . ']';
		},
	) );

	tn731_umg_render_checkbox_group( array(
		'group_id'   => 'tn731-umg-settings',
		'title'      => 'Settings',
		'field_name' => 'tn731_umg_settings',
		'items'      => $map['settings'],
		'selected'   => $selected_settings,
		'formatter'  => function( $value, $item ) {
			return $item['label'] . ' [' . $item['slug'] . ']';
		},
	) );

	tn731_umg_render_checkbox_group( array(
		'group_id'   => 'tn731-umg-third-party',
		'title'      => 'Third-Party Menus',
		'field_name' => 'tn731_umg_third_party',
		'items'      => $map['third_party'],
		'selected'   => $selected_third_party,
		'formatter'  => function( $value, $item ) {
			return $item . ' [' . $value . ']';
		},
	) );
}

function tn731_umg_render_permission_set_users_metabox( $post ) {

	$all_users = get_users(
		array(
			'role'    => 'user',
			'fields'  => array( 'ID', 'display_name', 'user_email' ),
			'number'  => 999999,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		)
	);

	if ( empty( $all_users ) ) {
		echo '<p>No users with role User found.</p>';
		return;
	}

	$current_users = tn731_umg_get_users_for_permission_set( $post->ID );
	$current_ids   = array_map( 'intval', wp_list_pluck( $current_users, 'ID' ) );

	$grouped = array();

	foreach ( $all_users as $user ) {
		$email  = strtolower( trim( (string) $user->user_email ) );
		$domain = tn731_umg_get_user_domain( $email );

		if ( empty( $domain ) ) {
			$domain = 'other';
		}

		if ( ! isset( $grouped[ $domain ] ) ) {
			$grouped[ $domain ] = array();
		}

		$grouped[ $domain ][] = $user;
	}

	ksort( $grouped, SORT_NATURAL | SORT_FLAG_CASE );

	echo '<div class="tn731-umg-actions">';
	echo '<button type="button" class="button-link tn731-umg-select-all" data-target="tn731-umg-assigned-users">Select all</button>';
	echo ' / ';
	echo '<button type="button" class="button-link tn731-umg-deselect-all" data-target="tn731-umg-assigned-users">Deselect all</button>';
	echo '</div>';

	echo '<div id="tn731-umg-assigned-users" class="tn731-umg-user-list">';

	foreach ( $grouped as $domain => $domain_users ) {

		usort( $domain_users, function( $a, $b ) {
			$a_email = strtolower( trim( (string) $a->user_email ) );
			$b_email = strtolower( trim( (string) $b->user_email ) );
			return strnatcasecmp( $a_email, $b_email );
		} );

		echo '<div class="tn731-umg-user-group">';
		echo '<div class="tn731-umg-user-group-title">' . esc_html( $domain ) . '</div>';

		foreach ( $domain_users as $user ) {
			$label = strtolower( trim( (string) $user->user_email ) );
			?>
			<label class="tn731-umg-user-item">
				<input type="checkbox" name="tn731_umg_users[]" value="<?php echo esc_attr( $user->ID ); ?>" <?php checked( in_array( (int) $user->ID, $current_ids, true ) ); ?>>
				<span class="tn731-umg-user-label" title="<?php echo esc_attr( $label ); ?>">
					<?php echo esc_html( $label ); ?>
				</span>
			</label>
			<?php
		}

		echo '</div>';
	}

	echo '</div>';
}

/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

function tn731_umg_save_permission_set_meta( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( empty( $_POST['tn731_umg_permission_set_nonce'] ) || ! wp_verify_nonce( $_POST['tn731_umg_permission_set_nonce'], 'tn731_umg_save_permission_set' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$native_main = isset( $_POST['tn731_umg_native_main'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tn731_umg_native_main'] ) ) : array();
	$post_types  = isset( $_POST['tn731_umg_post_types'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tn731_umg_post_types'] ) ) : array();
	$tools       = isset( $_POST['tn731_umg_tools'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tn731_umg_tools'] ) ) : array();
	$settings    = isset( $_POST['tn731_umg_settings'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tn731_umg_settings'] ) ) : array();
	$third_party = isset( $_POST['tn731_umg_third_party'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tn731_umg_third_party'] ) ) : array();
	$users       = isset( $_POST['tn731_umg_users'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['tn731_umg_users'] ) ) : array();

	update_post_meta( $post_id, '_tn731_umg_native_main', array_values( array_unique( $native_main ) ) );
	update_post_meta( $post_id, '_tn731_umg_post_types', array_values( array_unique( $post_types ) ) );
	update_post_meta( $post_id, '_tn731_umg_tools', array_values( array_unique( $tools ) ) );
	update_post_meta( $post_id, '_tn731_umg_settings', array_values( array_unique( $settings ) ) );
	update_post_meta( $post_id, '_tn731_umg_third_party', array_values( array_unique( $third_party ) ) );

	tn731_umg_set_permission_set_users( $post_id, $users );
}

function tn731_umg_show_user_permission_sets_field( $user ) {

	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	$roles = (array) $user->roles;

	if ( ! in_array( 'user', $roles, true ) ) {
		return;
	}

	$sets     = tn731_umg_get_all_permission_sets();
	$selected = tn731_umg_get_user_permission_set_ids( $user->ID );

	wp_nonce_field( 'tn731_umg_save_user_permission_sets', 'tn731_umg_user_permission_sets_nonce' );
	?>
	<h2>Permission Sets</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label>Assigned Permission Sets</label></th>
			<td>
				<?php if ( empty( $sets ) ) : ?>
					<p>No permission sets found.</p>
				<?php else : ?>
					<p class="tn731-umg-actions">
						<button type="button" class="button-link tn731-umg-select-all" data-target="tn731-umg-user-profile-sets">Select all</button>
						<span> / </span>
						<button type="button" class="button-link tn731-umg-deselect-all" data-target="tn731-umg-user-profile-sets">Deselect all</button>
					</p>
					<div id="tn731-umg-user-profile-sets">
						<?php foreach ( $sets as $set ) : ?>
							<label class="tn731-umg-profile-set">
								<input type="checkbox" name="tn731_umg_permission_sets[]" value="<?php echo esc_attr( $set->ID ); ?>" <?php checked( in_array( $set->ID, $selected, true ) ); ?>>
								<?php echo esc_html( $set->post_title ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}

function tn731_umg_save_user_permission_sets_field( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if ( empty( $_POST['tn731_umg_user_permission_sets_nonce'] ) || ! wp_verify_nonce( $_POST['tn731_umg_user_permission_sets_nonce'], 'tn731_umg_save_user_permission_sets' ) ) {
		return;
	}

	$sets = isset( $_POST['tn731_umg_permission_sets'] )
		? array_values( array_unique( array_map( 'intval', (array) wp_unslash( $_POST['tn731_umg_permission_sets'] ) ) ) )
		: array();

	update_user_meta( $user_id, 'tn731_umg_permission_sets', $sets );
	clean_user_cache( $user_id );
}
