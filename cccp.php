<?php
/**
 * Plugin Name: CCCP — Clean Cookie Consent
 * Plugin URI: https://github.com/donvoorhies/cccp
 * Description: Clean Cookie Consent — no tracking, no iron curtain.
 * Version: 1.0.0
 * Author: donvoorhies
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cccp
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CCCP_VERSION', '1.0.0' );
define( 'CCCP_PLUGIN_FILE', __FILE__ );
define( 'CCCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCCP_OPTION_KEY', 'cccp_settings' );

/**
 * Get plugin defaults.
 *
 * @return array<string,mixed>
 */
function cccp_defaults(): array {
	return [
		'banner_text'             => __( 'This site uses cookies to remember your preferences and understand how it\'s used. No advertising or tracking cookies are ever set here. Choose what you\'re comfortable with below.', 'cccp' ),
		'cookie_lifetime_days'    => 365,
		'enable_settings_button'  => true,
		'show_clean_badge'        => true,
		'analytics_scripts'       => "google-tag\ngtag\ngoogle-tag-manager",
		'functional_scripts'      => "youtube-iframe-api\nyoutube.com\nvimeo.com",
		'preferences_scripts'     => '',
		'always_allowed_scripts'  => "jquery\nwp-embed",
		'primary_color'           => '#cc0000',
		'accept_button_label'     => __( 'Accept All', 'cccp' ),
		'reject_button_label'     => __( 'Reject All', 'cccp' ),
		'save_button_label'       => __( 'Save preferences', 'cccp' ),
	];
}

/**
 * Get merged settings.
 *
 * @return array<string,mixed>
 */
function cccp_get_settings(): array {
	$settings = get_option( CCCP_OPTION_KEY, [] );

	if ( ! is_array( $settings ) ) {
		$settings = [];
	}

	return array_merge( cccp_defaults(), $settings );
}

/**
 * Parse textarea into script/fragment list.
 *
 * @param string $raw Raw lines.
 *
 * @return array<int,string>
 */
function cccp_parse_lines( string $raw ): array {
	$lines  = preg_split( '/\r\n|\r|\n/', $raw ) ?: [];
	$parsed = [];

	foreach ( $lines as $line ) {
		$item = trim( (string) $line );
		if ( '' !== $item ) {
			$parsed[] = $item;
		}
	}

	return array_values( array_unique( $parsed ) );
}

register_activation_hook( __FILE__, 'cccp_activate' );

/**
 * Activation hook.
 */
function cccp_activate(): void {
	$existing = get_option( CCCP_OPTION_KEY, [] );
	if ( ! is_array( $existing ) ) {
		$existing = [];
	}
	update_option( CCCP_OPTION_KEY, array_merge( cccp_defaults(), $existing ) );
}

/**
 * Load i18n strings.
 */
function cccp_load_textdomain(): void {
	load_plugin_textdomain( 'cccp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'cccp_load_textdomain' );

require_once CCCP_PLUGIN_DIR . 'modules/admin.php';
require_once CCCP_PLUGIN_DIR . 'modules/blocker.php';
require_once CCCP_PLUGIN_DIR . 'modules/frontend.php';
require_once CCCP_PLUGIN_DIR . 'modules/policy.php';
