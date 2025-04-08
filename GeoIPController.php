<?php
/**
 * GeoIP Targeting Controller I co-wrote for a corporate website.
 *
 * @package CLIENT\Core\Controller
 */

namespace CLIENT\Core\Controller;

use CLIENT\Core\Controller\Controller;

/**
 * Controller responsible for Geotargeting functionality.
 */
class GeoIPController extends Controller {

	/**
	 * Map of Country Code to Language.
	 *
	 * TODO: Make this map editable.
	 *
	 * @var array
	 */
	const GEO_COUNTRY_ARRAY = [
		'AU' => 'au',
	];

	/**
	 * Paths to exclude from the GEOIP redirect.
	 *
	 * @var array
	 */
	const PATHS_TO_EXCLUDE_START_WITH = [
		'/wp-',
	];

	/**
	 * The name of the cookie to store the users locale preference.
	 *
	 * @var string
	 */
	const GEO_LOCALE_COOKIE_NAME = 'wpe-locale';

	/**
	 * The name of the cookie to store the users geolocated ISO country code.
	 *
	 * @var string
	 */
	const GEO_COUNTRY_CODE_COOKIE_NAME = 'wpe-geo';

	/**
	 * Boot the controller.
	 *
	 * @return void
	 */
	public function set_up() {
		add_action( 'send_headers', [ $this, 'send_vary_headers' ] );
		add_action( 'init', [ $this, 'detect_geolocation' ] );
	}

	/**
	 * Send the WP Engine Vary Header for GeoIP.
	 *
	 * @return void
	 */
	public function send_vary_headers() {
		header( 'Vary: X-WPENGINE-SEGMENT' );
	}

	/**
	 * Detect the users geolocation and redirect appropriately.
	 *
	 * @return void
	 */
	public function detect_geolocation() { // phpcs:ignore
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		// Bail early on certain paths.
		foreach ( self::PATHS_TO_EXCLUDE_START_WITH as $path ) {
			if ( str_starts_with( $_SERVER['REQUEST_URI'], $path ) ) { // phpcs:ignore
				return;
			}
		}

		/**
		 * If this is running within a WPE environment we want to utilise their geo targeting system. If not we will fall back
		 * to the legacy GeoIp2 system so it will still function properly. The end result should be identical as WPE uses MaxMind
		 * data behind the scenes.
		 */
		$iso_code = getenv( 'HTTP_GEOIP_COUNTRY_CODE' );

		if ( ! $iso_code ) {
			if ( class_exists( '\\WPEngine\\GeoIp' ) ) {
				// Use the WPEngine GeoIp class if available.
				$geoip    = \WPEngine\GeoIp::instance();
				$iso_code = $geoip->country();
				if ( ! $iso_code ) {
					// Do nothing.
					return;
				}
			}
		}

		$country_code = in_array( $iso_code, array_keys( self::GEO_COUNTRY_ARRAY ), true ) ? self::GEO_COUNTRY_ARRAY[ $iso_code ] : 'global';

		// Get the current WPML Language.
		$wpml_current_language = apply_filters( 'wpml_current_language', null );

		$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore

		/**
		 * If we're accessing the index page only, redirect to the country code which also includes /global/' for
		 * countries that don't exist in the array.
		 */
		if ( '/' === $uri ) {
			wp_safe_redirect( "/$country_code/" . '?redirected=' . $wpml_current_language );
			exit;
		}

		$contains_valid_language = false;

		// These are valid URL's.
		if (
			str_starts_with( $uri, '/global/' ) ||
			str_starts_with( $uri, '/?p=' )
		) {
			return true;
		}

		foreach ( self::GEO_COUNTRY_ARRAY as $code ) {
			if ( str_starts_with( $uri, "/$code" ) ) {
				$contains_valid_language = true;
				break;
			}
		}

		if ( $contains_valid_language ) {
			return;
		}

		/**
		 * If the URL doesn't contain a valid language code, redirect to the country code.
		 */
		if ( ! $contains_valid_language && 'global' !== $country_code && str_contains( $uri, '/global/' ) ) { // phpcs:ignore
			// We may need to do something here in the future.
		} else {
			wp_safe_redirect( "/$country_code$uri" . '?redirected=' . $wpml_current_language );
			exit;
		}
	}

	/**
	 * Set the users cookie.
	 *
	 * @param string $name  The cookie name.
	 * @param string $value The cookie value.
	 * @param int    $time  Time in seconds, 86400 = 1 day.
	 */
	public function set_cookie( $name, $value, $time = 86400 ) {
		$domain = '.' . $_SERVER['HTTP_HOST']; // phpcs:ignore

		if ( ! is_user_logged_in() ) {
			$current_cookie_params = session_get_cookie_params(); // phpcs:ignore

			if ( $current_cookie_params ) {
				$max_life_time = $current_cookie_params['lifetime'];
				$path          = $current_cookie_params['path'];
				$secure        = $current_cookie_params['secure'];
				$httponly      = $current_cookie_params['httponly'];
			} else {
				$max_life_time = 0;
				$path          = '/';
				$secure        = false;
				$httponly      = false;
			}

			$cookie_params = [
				'lifetime' => $max_life_time,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => $secure,
				'httponly' => $httponly,
			];
			session_set_cookie_params( $cookie_params ); // phpcs:ignore
			session_start(); // phpcs:ignore
		}

		// Set the cookie.
		setcookie( $name, $value, time() + $time, '/', $domain ); // phpcs:ignore
	}
}
