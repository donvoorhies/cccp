<?php
/**
 * Cookie policy generator.
 *
 * @package CCCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add tools submenu.
 */
function cccp_policy_admin_menu(): void {
	add_management_page(
		__( 'Cookie Policy', 'cccp' ),
		__( 'Cookie Policy', 'cccp' ),
		'manage_options',
		'cccp-cookie-policy',
		'cccp_render_policy_page'
	);
}
add_action( 'admin_menu', 'cccp_policy_admin_menu' );

/**
 * Build generated policy HTML.
 */
function cccp_generate_policy_html(): string {
	$settings      = cccp_get_settings();
	$lifetime_days = max( 1, absint( (string) $settings['cookie_lifetime_days'] ) );

	$analytics   = cccp_parse_lines( (string) $settings['analytics_scripts'] );
	$functional  = cccp_parse_lines( (string) $settings['functional_scripts'] );
	$preferences = cccp_parse_lines( (string) $settings['preferences_scripts'] );

	$parts   = [];
	$parts[] = '<p>' . esc_html__( 'Unlike much of the web, this site does not track you for advertising purposes. There are no marketing cookies here — by choice. We use only the minimum cookie categories needed for a respectful personal website, and every non-essential category stays off until you opt in.', 'cccp' ) . '</p>';

	if ( ! empty( $preferences ) ) {
		$parts[] = '<h2>' . esc_html__( 'Preferences Cookies', 'cccp' ) . '</h2>';
		$parts[] = '<p>' . esc_html__( 'These cookies remember choices you make on the site, such as language or interface preferences. They help the site feel consistent without profiling you.', 'cccp' ) . '</p>';
		$parts[] = '<p><strong>' . esc_html__( 'Configured handles/fragments:', 'cccp' ) . '</strong> ' . esc_html( implode( ', ', $preferences ) ) . '</p>';
	}

	if ( ! empty( $functional ) ) {
		$parts[] = '<h2>' . esc_html__( 'Functional Cookies', 'cccp' ) . '</h2>';
		$parts[] = '<p>' . esc_html__( 'These cookies support optional features such as embedded media players or non-essential interface components. They are blocked unless you explicitly allow them.', 'cccp' ) . '</p>';

		$mentions = [];
		if ( in_array( 'youtube-iframe-api', $functional, true ) || in_array( 'youtube.com', $functional, true ) ) {
			$mentions[] = __( 'YouTube embeds', 'cccp' );
		}
		if ( in_array( 'vimeo.com', $functional, true ) ) {
			$mentions[] = __( 'Vimeo embeds', 'cccp' );
		}

		if ( ! empty( $mentions ) ) {
			$parts[] = '<p>' . sprintf(
				/* translators: %s is a comma-separated list of named integrations. */
				esc_html__( 'This category includes: %s.', 'cccp' ),
				esc_html( implode( ', ', $mentions ) )
			) . '</p>';
		}

		$parts[] = '<p><strong>' . esc_html__( 'Configured handles/fragments:', 'cccp' ) . '</strong> ' . esc_html( implode( ', ', $functional ) ) . '</p>';
	}

	if ( ! empty( $analytics ) ) {
		$parts[] = '<h2>' . esc_html__( 'Analytics Cookies', 'cccp' ) . '</h2>';
		$parts[] = '<p>' . esc_html__( 'These cookies collect anonymous usage statistics so the site owner can understand what content is useful. They are optional and disabled by default until you consent.', 'cccp' ) . '</p>';

		$mentions = [];
		if ( in_array( 'google-tag', $analytics, true ) || in_array( 'gtag', $analytics, true ) ) {
			$mentions[] = __( 'Google Analytics (gtag)', 'cccp' );
		}
		if ( in_array( 'google-tag-manager', $analytics, true ) ) {
			$mentions[] = __( 'Google Tag Manager', 'cccp' );
		}

		if ( ! empty( $mentions ) ) {
			$parts[] = '<p>' . sprintf(
				/* translators: %s is a comma-separated list of named integrations. */
				esc_html__( 'This category includes: %s.', 'cccp' ),
				esc_html( implode( ', ', $mentions ) )
			) . '</p>';
		}

		$parts[] = '<p><strong>' . esc_html__( 'Configured handles/fragments:', 'cccp' ) . '</strong> ' . esc_html( implode( ', ', $analytics ) ) . '</p>';
	}

	$parts[] = '<h2>' . esc_html__( 'Consent and Withdrawal', 'cccp' ) . '</h2>';
	$parts[] = '<p>' . esc_html__( 'You can change or withdraw your consent at any time using the "Cookie settings" button in the footer. Updating your choices applies on page reload so script blocking remains reliable and predictable.', 'cccp' ) . '</p>';

	$parts[] = '<h2>' . esc_html__( 'Cookie Lifetime', 'cccp' ) . '</h2>';
	$parts[] = '<p>' . sprintf(
		/* translators: %d is number of days. */
		esc_html__( 'Your consent is stored in a browser cookie for %d days.', 'cccp' ),
		(int) $lifetime_days
	) . '</p>';

	$parts[] = '<p><em>' . sprintf(
		/* translators: %s is a formatted date. */
		esc_html__( 'Last updated: %s', 'cccp' ),
		esc_html( wp_date( get_option( 'date_format' ) ) )
	) . '</em></p>';

	return implode( "\n", $parts );
}

/**
 * Upsert policy page.
 */
function cccp_upsert_policy_page(): int {
	$content = cccp_generate_policy_html();

	$existing = get_posts(
		[
			'post_type'      => 'page',
			'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
			'posts_per_page' => 1,
			'meta_key'       => '_cccp_policy_page',
			'meta_value'     => '1',
			'fields'         => 'ids',
		]
	);

	$page_id = ! empty( $existing ) ? (int) $existing[0] : 0;

	$post_data = [
		'post_title'   => __( 'Cookie Policy', 'cccp' ),
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	];

	if ( $page_id > 0 ) {
		$post_data['ID'] = $page_id;
		$updated         = wp_update_post( wp_slash( $post_data ), true );
		if ( is_wp_error( $updated ) ) {
			return 0;
		}
		$page_id = (int) $updated;
	} else {
		$inserted = wp_insert_post( wp_slash( $post_data ), true );
		if ( is_wp_error( $inserted ) ) {
			return 0;
		}
		$page_id = (int) $inserted;
	}

	if ( $page_id > 0 ) {
		update_post_meta( $page_id, '_cccp_policy_page', '1' );
	}

	return $page_id;
}

/**
 * Handle policy page update action.
 */
function cccp_handle_policy_update(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'cccp' ) );
	}

	check_admin_referer( 'cccp_policy_generate' );

	$page_id  = cccp_upsert_policy_page();
	$redirect = add_query_arg(
		[
			'page'               => 'cccp-cookie-policy',
			'cccp_policy_result' => $page_id > 0 ? 'ok' : 'error',
		],
		admin_url( 'tools.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_cccp_generate_policy', 'cccp_handle_policy_update' );

/**
 * Render policy tool page.
 */
function cccp_render_policy_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$preview = cccp_generate_policy_html();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Cookie Policy Generator', 'cccp' ); ?></h1>
		<p><?php esc_html_e( 'Generate a privacy policy page that reflects your current CCCP settings and script categories.', 'cccp' ); ?></p>

		<?php $policy_result = isset( $_GET['cccp_policy_result'] ) ? sanitize_key( wp_unslash( (string) $_GET['cccp_policy_result'] ) ) : ''; ?>
		<?php if ( 'ok' === $policy_result ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Cookie Policy page created or updated successfully.', 'cccp' ); ?></p>
			</div>
		<?php elseif ( 'error' === $policy_result ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Could not create or update the Cookie Policy page.', 'cccp' ); ?></p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Preview', 'cccp' ); ?></h2>
		<div style="background:#fff;border:1px solid #dcdcde;padding:20px;max-width:860px;">
			<?php echo wp_kses_post( $preview ); ?>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
			<?php wp_nonce_field( 'cccp_policy_generate' ); ?>
			<input type="hidden" name="action" value="cccp_generate_policy" />
			<?php submit_button( __( 'Create / Update Page', 'cccp' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
