<?php
/**
 * Plugin deactivator.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
