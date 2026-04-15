<?php
/**
 * Plugin Name:       Cookie Consent by Web Pixel Studio
 * Plugin URI:        https://wps.sk
 * Description:       GDPR & ePrivacy compliant cookie consent manager with Consent Mode v2 support, full customization, consent logging, and cookie blocking.
 * Version:           1.0.1
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Web Pixel Studio
 * Author URI:        https://wps.sk
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cookie-consent-webpixelstudio
 * Domain Path:       /languages
 * Tested up to:      6.9
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CCWPS_VERSION', '1.0.1' );
define( 'CCWPS_PLUGIN_FILE', __FILE__ );
define( 'CCWPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCWPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCWPS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoload classes.
spl_autoload_register( function ( $class ) {
	$prefix = 'CookieConsentWPS\\';
	$base_dir = CCWPS_PLUGIN_DIR . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . 'class-' . strtolower( str_replace( [ '\\', '_' ], [ '/', '-' ], $relative_class ) ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Main plugin class.
 */
final class Cookie_Consent_WPS {

	private static ?Cookie_Consent_WPS $instance = null;

	public static function instance(): Cookie_Consent_WPS {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies(): void {
		require_once CCWPS_PLUGIN_DIR . 'includes/class-activator.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-deactivator.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-settings.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-language-presets.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-consent-log.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-cookie-manager.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-block-manager.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-admin.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-ajax.php';
		require_once CCWPS_PLUGIN_DIR . 'includes/class-rest-api.php';
	}

	private function init_hooks(): void {
		register_activation_hook( CCWPS_PLUGIN_FILE, [ 'CCWPS_Activator', 'activate' ] );
		register_deactivation_hook( CCWPS_PLUGIN_FILE, [ 'CCWPS_Deactivator', 'deactivate' ] );

		add_action( 'init', [ $this, 'init' ] );
	}

	public function init(): void {
		CCWPS_Activator::maybe_upgrade();

		$settings      = new CCWPS_Settings();
		$consent_log   = new CCWPS_Consent_Log();
		$cookie_manager = new CCWPS_Cookie_Manager();
		$block_manager = new CCWPS_Block_Manager();
		$frontend      = new CCWPS_Frontend( $settings );
		$admin         = new CCWPS_Admin( $settings, $consent_log, $cookie_manager, $block_manager );
		$ajax          = new CCWPS_Ajax( $consent_log, $settings );
		$rest_api      = new CCWPS_Rest_API( $consent_log, $settings );
	}
}

Cookie_Consent_WPS::instance();
