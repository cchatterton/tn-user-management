<?php
/**
 * Focused test for synchronising a newly granted Super Admin to all sites.
 */

define( 'ABSPATH', __DIR__ . '/' );

$tn731_test_actions      = array();
$tn731_test_memberships  = array( 1 => true, 2 => false, 3 => true );
$tn731_test_added        = array();
$tn731_test_roles        = array();
$tn731_test_current_site = 0;

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['tn731_test_actions'][ $hook ] = array( $callback, $priority, $accepted_args );
}

function add_filter() {}
function absint( $value ) { return abs( (int) $value ); }
function is_multisite() { return true; }
function get_userdata( $user_id ) { return 77 === $user_id ? (object) array( 'ID' => 77 ) : false; }
function is_super_admin( $user_id ) { return 77 === $user_id; }
function get_sites() { return array( 1, 2, 3 ); }
function is_user_member_of_blog( $user_id, $site_id ) { return ! empty( $GLOBALS['tn731_test_memberships'][ $site_id ] ); }
function is_wp_error() { return false; }
function clean_user_cache() {}

function add_user_to_blog( $site_id, $user_id, $role ) {
	$GLOBALS['tn731_test_added'][] = array( $site_id, $user_id, $role );
	return true;
}

function switch_to_blog( $site_id ) {
	$GLOBALS['tn731_test_current_site'] = $site_id;
}

function restore_current_blog() {
	$GLOBALS['tn731_test_current_site'] = 0;
}

class WP_User {
	public $ID;
	public $roles = array();

	public function __construct( $user_id ) {
		$this->ID = $user_id;
	}

	public function set_role( $role ) {
		$GLOBALS['tn731_test_roles'][] = array( $GLOBALS['tn731_test_current_site'], $this->ID, $role );
	}
}

require dirname( __DIR__ ) . '/tn-user-management/functions/multisite.php';

if ( empty( $tn731_test_actions['granted_super_admin'] ) ) {
	throw new RuntimeException( 'The granted_super_admin handler was not registered.' );
}

$registered_callback = $tn731_test_actions['granted_super_admin'][0];

if ( 'tn731_umg_sync_granted_super_admin_to_all_sites' !== $registered_callback ) {
	throw new RuntimeException( 'The wrong granted_super_admin callback was registered.' );
}

if ( ! tn731_umg_sync_granted_super_admin_to_all_sites( 77 ) ) {
	throw new RuntimeException( 'The Super Admin synchronisation reported failure.' );
}

$expected_added = array(
	array( 2, 77, 'administrator' ),
);

$expected_roles = array(
	array( 1, 77, 'administrator' ),
	array( 3, 77, 'administrator' ),
);

if ( $expected_added !== $tn731_test_added ) {
	throw new RuntimeException( 'A missing site membership was not added correctly.' );
}

if ( $expected_roles !== $tn731_test_roles ) {
	throw new RuntimeException( 'Existing site memberships were not promoted correctly.' );
}

echo "PASS: granted Super Admin synchronised to every existing site\n";
