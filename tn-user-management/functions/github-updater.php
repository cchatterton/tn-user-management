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
	private const FAILED_TRANSIENT = 'tn731_umg_github_latest_release_failed';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
	}

	public static function inject_update( $transient ) {

		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = self::get_latest_release();

		if ( empty( $release ) ) {
			return $transient;
		}

		$version      = self::release_version( $release );
		$download_url = self::release_asset_url( $release );

		if ( empty( $version ) || empty( $download_url ) || ! version_compare( $version, TN731_UMG_VERSION, '>' ) ) {
			return $transient;
		}

		$plugin_file = plugin_basename( TN731_UMG_PLUGIN_FILE );

		$transient->response[ $plugin_file ] = (object) array(
			'id'           => self::repository_url(),
			'slug'         => self::SLUG,
			'plugin'       => $plugin_file,
			'new_version'  => $version,
			'url'          => self::repository_url(),
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
			'sections'       => array(
				'description' => 'Email-as-username, role normalisation, permission sets, and multisite user governance.',
				'changelog'   => wp_kses_post( (string) ( $release['body'] ?? '' ) ),
			),
		);
	}

	public static function plugin_row_meta( $links, $file ) {

		if ( plugin_basename( TN731_UMG_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( self::repository_url() ),
			esc_html__( 'GitHub', 'tn-user-management' )
		);

		return $links;
	}

	private static function get_latest_release() {

		$release = get_site_transient( self::RELEASE_TRANSIENT );

		if ( is_array( $release ) ) {
			return $release;
		}

		if ( get_site_transient( self::FAILED_TRANSIENT ) ) {
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

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::FAILED_TRANSIENT, 1, 30 * MINUTE_IN_SECONDS );
			return array();
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $release ) ) {
			set_site_transient( self::FAILED_TRANSIENT, 1, 30 * MINUTE_IN_SECONDS );
			return array();
		}

		set_site_transient( self::RELEASE_TRANSIENT, $release, 6 * HOUR_IN_SECONDS );
		delete_site_transient( self::FAILED_TRANSIENT );

		return $release;
	}

	private static function release_version( $release ) {
		return ltrim( (string) ( $release['tag_name'] ?? '' ), 'vV' );
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
}

TN731_UMG_GitHub_Updater::init();
