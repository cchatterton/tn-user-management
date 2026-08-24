<?php
/**
 * GitHub release updater for TN User Management.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TN731_UMG_GitHub_Updater {
	private const OWNER = 'cchatterton';
	private const REPO = 'tn-user-management';
	private const SLUG = 'tn-user-management';
	private const ASSET_NAME = 'tn-user-management.zip';
	private const RELEASE_TRANSIENT = 'tn731_umg_github_latest_release';
	private const ERROR_TRANSIENT = 'tn731_umg_github_latest_release_error';
	private const DETAILS_TRANSIENT = 'tn731_umg_github_plugin_details';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'add_update_data' ) );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'add_update_data' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'handle_manual_update_check' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache_after_update' ), 10, 2 );
	}

	public static function add_update_data( $transient ) {

		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = self::get_latest_release();

		if ( empty( $release ) ) {
			return $transient;
		}

		$version      = self::release_version( $release );
		$download_url = self::release_asset_url( $release );
		$plugin_file  = plugin_basename( TN731_UMG_PLUGIN_FILE );

		$transient->response = isset( $transient->response ) && is_array( $transient->response )
			? $transient->response
			: array();
		$transient->no_update = isset( $transient->no_update ) && is_array( $transient->no_update )
			? $transient->no_update
			: array();

		if ( empty( $version ) || empty( $download_url ) || ! version_compare( $version, TN731_UMG_VERSION, '>' ) ) {
			unset( $transient->response[ $plugin_file ] );
			unset( $transient->no_update[ $plugin_file ] );
			return $transient;
		}

		unset( $transient->no_update[ $plugin_file ] );
		$transient->response[ $plugin_file ] = (object) array(
			'id'           => self::repository_url(),
			'slug'         => self::SLUG,
			'plugin'       => $plugin_file,
			'new_version'  => $version,
			'url'          => self::release_page_url( $release ),
			'package'      => $download_url,
			'requires'     => '6.0',
			'requires_php' => '8.1',
		);

		return $transient;
	}

	public static function plugin_information( $result, $action, $args ) {

		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}

		$release = self::get_latest_release();

		if ( empty( $release ) ) {
			return $result;
		}

		$version      = self::release_version( $release );
		$download_url = self::release_asset_url( $release );

		if ( empty( $version ) || empty( $download_url ) ) {
			return $result;
		}

		$sections = self::get_plugin_information_sections();

		return (object) array(
			'name'           => 'TN User Management',
			'slug'           => self::SLUG,
			'version'        => $version,
			'author'         => 'Techn',
			'author_profile' => 'https://techn.com.au',
			'homepage'       => self::repository_url(),
			'download_link'  => $download_url,
			'requires'       => '6.0',
			'requires_php'   => '8.1',
			'sections'       => $sections,
		);
	}

	private static function get_plugin_information_sections() {

		$sections = get_site_transient( self::DETAILS_TRANSIENT );

		if ( is_array( $sections ) && ! empty( $sections['description'] ) && ! empty( $sections['changelog'] ) ) {
			return $sections;
		}

		$description = self::get_repository_document_html( 'README.md' );
		$changelog   = self::get_repository_document_html( 'CHANGELOG.md' );

		$sections = array(
			'description' => $description ?: '<p>' . esc_html__( 'TN User Management provides a binary access model, permission sets, capability management, email-as-username handling, and multisite user governance for WordPress.', 'tn-user-management' ) . '</p>',
			'changelog'   => $changelog ?: sprintf(
				'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_url( self::repository_url() . '/blob/main/CHANGELOG.md' ),
				esc_html__( 'View the complete changelog on GitHub.', 'tn-user-management' )
			),
		);

		if ( ! empty( $description ) && ! empty( $changelog ) ) {
			set_site_transient( self::DETAILS_TRANSIENT, $sections, 6 * HOUR_IN_SECONDS );
		}

		return $sections;
	}

	private static function get_repository_document_html( $path ) {

		if ( ! in_array( $path, array( 'README.md', 'CHANGELOG.md' ), true ) ) {
			return '';
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/contents/' . rawurlencode( $path ) . '?ref=main',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'               => 'application/vnd.github.html+json',
					'X-GitHub-Api-Version' => '2022-11-28',
					'User-Agent'           => 'TN-User-Management/' . TN731_UMG_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$html = trim( wp_remote_retrieve_body( $response ) );

		if ( '' === $html ) {
			return '';
		}

		$html = preg_replace( '#^<div\s+id="file"[^>]*><article[^>]*>#i', '', $html );
		$html = preg_replace( '#</article></div>$#i', '', $html );
		$html = preg_replace( '#<a\b[^>]*class="anchor"[^>]*>.*?</a>#is', '', $html );
		$html = preg_replace( '#<div\s+class="markdown-heading"[^>]*>\s*<h1\b[^>]*>.*?</h1>\s*</div>#is', '', $html, 1 );

		return wp_kses_post( trim( $html ) );
	}

	public static function plugin_row_meta( $links, $file ) {

		if ( plugin_basename( TN731_UMG_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		if ( ! self::has_plugin_details_link( $links ) ) {
			$details_url = add_query_arg(
				array(
					'tab'       => 'plugin-information',
					'plugin'    => self::SLUG,
					'TB_iframe' => 'true',
					'width'     => '600',
					'height'    => '550',
				),
				self_admin_url( 'plugin-install.php' )
			);

			$links[] = sprintf(
				'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
				esc_url( $details_url ),
				esc_attr__( 'View TN User Management details', 'tn-user-management' ),
				esc_html__( 'View details', 'tn-user-management' )
			);
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( self::repository_url() ),
			esc_html__( 'GitHub', 'tn-user-management' )
		);

		if ( current_user_can( 'update_plugins' ) ) {
			$plugins_url = is_multisite() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
			$check_url   = wp_nonce_url(
				add_query_arg( 'tn731_umg_check_updates', '1', $plugins_url ),
				'tn731_umg_check_updates'
			);

			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $check_url ),
				esc_html__( 'Check for updates', 'tn-user-management' )
			);
		}

		return $links;
	}

	private static function has_plugin_details_link( $links ) {

		foreach ( (array) $links as $link ) {
			if ( false !== strpos( (string) $link, 'open-plugin-details-modal' ) || false !== strpos( (string) $link, 'tab=plugin-information' ) ) {
				return true;
			}
		}

		return false;
	}

	public static function handle_manual_update_check() {

		$should_check = isset( $_GET['tn731_umg_check_updates'] )
			? sanitize_text_field( wp_unslash( $_GET['tn731_umg_check_updates'] ) )
			: '';

		if ( '1' !== $should_check ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tn731_umg_check_updates' ) ) {
			wp_die( esc_html__( 'The update check request could not be verified.', 'tn-user-management' ) );
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to check for plugin updates.', 'tn-user-management' ) );
		}

		self::clear_release_cache();
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();

		$plugins_url = is_multisite() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
		wp_safe_redirect( $plugins_url );
		exit;
	}

	public static function clear_cache_after_update( $upgrader, $options ) {

		if ( empty( $options['action'] ) || 'update' !== $options['action'] || empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
			return;
		}

		$plugins = isset( $options['plugins'] ) ? (array) $options['plugins'] : array();
		if ( ! empty( $options['plugin'] ) ) {
			$plugins[] = $options['plugin'];
		}

		if ( in_array( plugin_basename( TN731_UMG_PLUGIN_FILE ), $plugins, true ) ) {
			self::clear_release_cache();
		}
	}

	private static function get_latest_release() {

		$forced_check = self::is_forced_update_check();

		if ( $forced_check ) {
			self::clear_release_cache();
		}

		$release = get_site_transient( self::RELEASE_TRANSIENT );

		if ( is_array( $release ) ) {
			return $release;
		}

		if ( ! $forced_check && get_site_transient( self::ERROR_TRANSIENT ) ) {
			return array();
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'TN-User-Management/' . TN731_UMG_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::record_error(
				array(
					'type'    => 'wp_error',
					'message' => $response->get_error_message(),
				)
			);
			return array();
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			self::record_error(
				array(
					'type'    => 'http_error',
					'code'    => $response_code,
					'message' => wp_remote_retrieve_response_message( $response ),
					'body'    => substr( wp_strip_all_tags( wp_remote_retrieve_body( $response ) ), 0, 500 ),
				)
			);
			return array();
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $release ) || empty( self::release_version( $release ) ) ) {
			self::record_error(
				array(
					'type'    => 'json_error',
					'message' => __( 'GitHub returned release data without a valid version.', 'tn-user-management' ),
				)
			);
			return array();
		}

		$has_newer_package = version_compare( self::release_version( $release ), TN731_UMG_VERSION, '>' )
			&& ! empty( self::release_asset_url( $release ) );
		$cache_duration    = $has_newer_package ? 6 * HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS;

		set_site_transient( self::RELEASE_TRANSIENT, $release, $cache_duration );
		delete_site_transient( self::ERROR_TRANSIENT );

		return $release;
	}

	private static function is_forced_update_check() {

		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		$force_check = self::request_value( 'force-check' );
		$action      = self::request_value( 'action' );
		$action_two  = self::request_value( 'action2' );
		$actions     = array( 'update-selected', 'upgrade-plugin', 'do-plugin-upgrade' );

		return ( '' !== $force_check && '0' !== $force_check )
			|| in_array( $action, $actions, true )
			|| in_array( $action_two, $actions, true );
	}

	private static function request_value( $key ) {

		if ( isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		}

		if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) {
			return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
		}

		return '';
	}

	private static function record_error( $details ) {
		$details['checked_at'] = time();
		delete_site_transient( self::RELEASE_TRANSIENT );
		set_site_transient( self::ERROR_TRANSIENT, $details, 10 * MINUTE_IN_SECONDS );
	}

	private static function clear_release_cache() {
		delete_site_transient( self::RELEASE_TRANSIENT );
		delete_site_transient( self::ERROR_TRANSIENT );
		delete_site_transient( self::DETAILS_TRANSIENT );
	}

	private static function release_version( $release ) {
		$version = ltrim( (string) ( $release['tag_name'] ?? '' ), 'vV' );

		if ( ! preg_match( '/^\d+(?:\.\d+)+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
			return '';
		}

		return $version;
	}

	private static function release_asset_url( $release ) {

		if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
			return '';
		}

		foreach ( $release['assets'] as $asset ) {
			if ( self::ASSET_NAME === ( $asset['name'] ?? '' ) && ! empty( $asset['browser_download_url'] ) ) {
				return esc_url_raw( (string) $asset['browser_download_url'] );
			}
		}

		return '';
	}

	private static function repository_url() {
		return 'https://github.com/' . self::OWNER . '/' . self::REPO;
	}

	private static function release_page_url( $release ) {
		return ! empty( $release['html_url'] )
			? esc_url_raw( (string) $release['html_url'] )
			: self::repository_url() . '/releases/latest';
	}
}

TN731_UMG_GitHub_Updater::init();
