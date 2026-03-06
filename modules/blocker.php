<?php
/**
 * Script blocker.
 *
 * @package CCCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get consent from cookie.
 *
 * @return array<string,bool>
 */
function cccp_get_cookie_consent(): array {
	$default = [
		'preferences' => false,
		'functional'  => false,
		'analytics'   => false,
	];

	$raw     = stripslashes( $_COOKIE['cccp_consent'] ?? '' );
	$consent = json_decode( $raw, true );

	if ( ! is_array( $consent ) ) {
		return $default;
	}

	return [
		'preferences' => ! empty( $consent['preferences'] ),
		'functional'  => ! empty( $consent['functional'] ),
		'analytics'   => ! empty( $consent['analytics'] ),
	];
}

/**
 * Whether script queue should be touched.
 */
function cccp_can_block_scripts(): bool {
	if ( is_admin() || wp_doing_cron() ) {
		return false;
	}

	global $pagenow;
	if ( isset( $pagenow ) && in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ], true ) ) {
		return false;
	}

	return true;
}

/**
 * Detect whether script should be blocked.
 *
 * @param string $handle   Script handle.
 * @param string $src      Script source.
 * @param array  $patterns Configured patterns.
 */
function cccp_script_matches_patterns( string $handle, string $src, array $patterns ): bool {
	foreach ( $patterns as $pattern ) {
		$item = strtolower( trim( (string) $pattern ) );
		if ( '' === $item ) {
			continue;
		}

		if ( strtolower( $handle ) === $item ) {
			return true;
		}

		if ( '' !== $src && false !== strpos( strtolower( $src ), $item ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Remove blocked scripts from queue.
 */
function cccp_block_disallowed_scripts(): void {
	if ( ! cccp_can_block_scripts() ) {
		return;
	}

	$settings = cccp_get_settings();
	$consent  = cccp_get_cookie_consent();
	$scripts  = wp_scripts();

	if ( ! $scripts || empty( $scripts->queue ) || ! is_array( $scripts->queue ) ) {
		return;
	}

	$always_allowed = cccp_parse_lines( (string) $settings['always_allowed_scripts'] );

	$groups = [
		'preferences' => cccp_parse_lines( (string) $settings['preferences_scripts'] ),
		'functional'  => cccp_parse_lines( (string) $settings['functional_scripts'] ),
		'analytics'   => cccp_parse_lines( (string) $settings['analytics_scripts'] ),
	];

	foreach ( $scripts->queue as $handle ) {
		$handle_str = (string) $handle;
		$src        = '';
		if ( isset( $scripts->registered[ $handle_str ] ) && is_object( $scripts->registered[ $handle_str ] ) ) {
			$src = (string) $scripts->registered[ $handle_str ]->src;
		}

		if ( cccp_script_matches_patterns( $handle_str, $src, $always_allowed ) ) {
			continue;
		}

		foreach ( $groups as $category => $patterns ) {
			if ( ! empty( $consent[ $category ] ) ) {
				continue;
			}

			if ( cccp_script_matches_patterns( $handle_str, $src, $patterns ) ) {
				wp_dequeue_script( $handle_str );
				wp_deregister_script( $handle_str );
				break;
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'cccp_block_disallowed_scripts', 1 );
