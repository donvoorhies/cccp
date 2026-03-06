<?php
/**
 * Admin settings.
 *
 * @package CCCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add settings menu.
 */
function cccp_admin_menu(): void {
	add_options_page(
		__( 'Clean Cookie Consent', 'cccp' ),
		__( 'Clean Cookie Consent', 'cccp' ),
		'manage_options',
		'cccp',
		'cccp_render_settings_page'
	);
}
add_action( 'admin_menu', 'cccp_admin_menu' );

/**
 * Register admin assets.
 *
 * @param string $hook Hook suffix.
 */
function cccp_admin_assets( string $hook ): void {
	if ( 'settings_page_cccp' !== $hook ) {
		return;
	}

	$style = <<<'CSS'
.cccp-admin-wrap .cccp-card {
  background: #fff;
  border: 1px solid #dcdcde;
  border-radius: 6px;
  margin-top: 16px;
  padding: 20px;
}
.cccp-admin-wrap .cccp-tabs {
  border-bottom: 1px solid #dcdcde;
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  padding-bottom: 8px;
}
.cccp-admin-wrap .cccp-tab {
  background: #f6f7f7;
  border: 1px solid #dcdcde;
  border-radius: 4px;
  color: #1d2327;
  display: inline-block;
  padding: 8px 12px;
  text-decoration: none;
}
.cccp-admin-wrap .cccp-tab.cccp-tab-active {
  background: #fff;
  border-color: #2271b1;
  color: #2271b1;
}
.cccp-admin-wrap .cccp-help {
  color: #50575e;
  margin-top: 6px;
}
.cccp-admin-wrap .cccp-checkbox-row {
  margin-top: 10px;
}
.cccp-admin-wrap textarea {
  min-height: 120px;
}
CSS;

	wp_register_style( 'cccp-admin-inline', false, [], CCCP_VERSION );
	wp_enqueue_style( 'cccp-admin-inline' );
	wp_add_inline_style( 'cccp-admin-inline', $style );
}
add_action( 'admin_enqueue_scripts', 'cccp_admin_assets' );

/**
 * Save settings handler.
 */
function cccp_handle_settings_save(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to update these settings.', 'cccp' ) );
	}

	check_admin_referer( 'cccp_save_settings' );

	$current_tab = isset( $_POST['cccp_current_tab'] ) ? sanitize_key( wp_unslash( (string) $_POST['cccp_current_tab'] ) ) : 'general';
	$allowed     = [ 'general', 'scripts', 'appearance' ];

	if ( ! in_array( $current_tab, $allowed, true ) ) {
		$current_tab = 'general';
	}

	$settings = cccp_get_settings();

	switch ( $current_tab ) {
		case 'general':
			$settings['banner_text'] = isset( $_POST['cccp_banner_text'] )
				? sanitize_textarea_field( wp_unslash( (string) $_POST['cccp_banner_text'] ) )
				: cccp_defaults()['banner_text'];

			$settings['cookie_lifetime_days'] = isset( $_POST['cccp_cookie_lifetime_days'] )
				? absint( wp_unslash( (string) $_POST['cccp_cookie_lifetime_days'] ) )
				: cccp_defaults()['cookie_lifetime_days'];

			if ( $settings['cookie_lifetime_days'] < 1 ) {
				$settings['cookie_lifetime_days'] = 365;
			}

			$settings['enable_settings_button'] = isset( $_POST['cccp_enable_settings_button'] ) && '1' === (string) wp_unslash( $_POST['cccp_enable_settings_button'] );
			$settings['show_clean_badge']       = isset( $_POST['cccp_show_clean_badge'] ) && '1' === (string) wp_unslash( $_POST['cccp_show_clean_badge'] );
			break;

		case 'scripts':
			$settings['analytics_scripts'] = isset( $_POST['cccp_analytics_scripts'] )
				? sanitize_textarea_field( wp_unslash( (string) $_POST['cccp_analytics_scripts'] ) )
				: '';

			$settings['functional_scripts'] = isset( $_POST['cccp_functional_scripts'] )
				? sanitize_textarea_field( wp_unslash( (string) $_POST['cccp_functional_scripts'] ) )
				: '';

			$settings['preferences_scripts'] = isset( $_POST['cccp_preferences_scripts'] )
				? sanitize_textarea_field( wp_unslash( (string) $_POST['cccp_preferences_scripts'] ) )
				: '';

			$settings['always_allowed_scripts'] = isset( $_POST['cccp_always_allowed_scripts'] )
				? sanitize_textarea_field( wp_unslash( (string) $_POST['cccp_always_allowed_scripts'] ) )
				: '';
			break;

		case 'appearance':
			$color = isset( $_POST['cccp_primary_color'] )
				? sanitize_hex_color( wp_unslash( (string) $_POST['cccp_primary_color'] ) )
				: null;

			$settings['primary_color'] = $color ? $color : '#cc0000';

			$settings['accept_button_label'] = isset( $_POST['cccp_accept_button_label'] )
				? sanitize_text_field( wp_unslash( (string) $_POST['cccp_accept_button_label'] ) )
				: cccp_defaults()['accept_button_label'];

			$settings['reject_button_label'] = isset( $_POST['cccp_reject_button_label'] )
				? sanitize_text_field( wp_unslash( (string) $_POST['cccp_reject_button_label'] ) )
				: cccp_defaults()['reject_button_label'];

			$settings['save_button_label'] = isset( $_POST['cccp_save_button_label'] )
				? sanitize_text_field( wp_unslash( (string) $_POST['cccp_save_button_label'] ) )
				: cccp_defaults()['save_button_label'];
			break;
	}

	update_option( CCCP_OPTION_KEY, $settings );

	$redirect = add_query_arg(
		[
			'page'             => 'cccp',
			'cccp_tab'         => $current_tab,
			'cccp_settings_ok' => '1',
		],
		admin_url( 'options-general.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_cccp_save_settings', 'cccp_handle_settings_save' );

/**
 * Render settings page.
 */
function cccp_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings    = cccp_get_settings();
	$current_tab = isset( $_GET['cccp_tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['cccp_tab'] ) ) : 'general';
	$allowed     = [ 'general', 'scripts', 'appearance' ];

	if ( ! in_array( $current_tab, $allowed, true ) ) {
		$current_tab = 'general';
	}
	?>
	<div class="wrap cccp-admin-wrap">
		<h1><?php esc_html_e( 'CCCP — Clean Cookie Consent', 'cccp' ); ?></h1>
		<p><?php esc_html_e( 'Clean Cookie Consent — no tracking, no iron curtain.', 'cccp' ); ?></p>

		<?php if ( isset( $_GET['cccp_settings_ok'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings saved.', 'cccp' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="cccp-card">
			<nav class="cccp-tabs" aria-label="<?php esc_attr_e( 'Settings tabs', 'cccp' ); ?>">
				<?php
				$tabs = [
					'general'    => __( 'General', 'cccp' ),
					'scripts'    => __( 'Scripts', 'cccp' ),
					'appearance' => __( 'Appearance', 'cccp' ),
				];
				foreach ( $tabs as $tab_key => $tab_label ) :
					$url = add_query_arg(
						[
							'page'     => 'cccp',
							'cccp_tab' => $tab_key,
						],
						admin_url( 'options-general.php' )
					);
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="cccp-tab <?php echo $current_tab === $tab_key ? 'cccp-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cccp_save_settings' ); ?>
				<input type="hidden" name="action" value="cccp_save_settings" />
				<input type="hidden" name="cccp_current_tab" value="<?php echo esc_attr( $current_tab ); ?>" />

				<?php if ( 'general' === $current_tab ) : ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="cccp_banner_text"><?php esc_html_e( 'Banner text', 'cccp' ); ?></label></th>
							<td>
								<textarea id="cccp_banner_text" name="cccp_banner_text" class="large-text" rows="5"><?php echo esc_textarea( (string) $settings['banner_text'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_cookie_lifetime_days"><?php esc_html_e( 'Cookie lifetime (days)', 'cccp' ); ?></label></th>
							<td>
								<input id="cccp_cookie_lifetime_days" name="cccp_cookie_lifetime_days" type="number" min="1" value="<?php echo esc_attr( (string) $settings['cookie_lifetime_days'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Footer controls', 'cccp' ); ?></th>
							<td>
								<div class="cccp-checkbox-row">
									<label>
										<input type="checkbox" name="cccp_enable_settings_button" value="1" <?php checked( ! empty( $settings['enable_settings_button'] ) ); ?> />
										<?php esc_html_e( 'Enable "Cookie settings" footer button', 'cccp' ); ?>
									</label>
								</div>
								<div class="cccp-checkbox-row">
									<label>
										<input type="checkbox" name="cccp_show_clean_badge" value="1" <?php checked( ! empty( $settings['show_clean_badge'] ) ); ?> />
										<?php esc_html_e( 'Show "Clean cookies only" badge', 'cccp' ); ?>
									</label>
								</div>
							</td>
						</tr>
					</table>
				<?php elseif ( 'scripts' === $current_tab ) : ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="cccp_analytics_scripts"><?php esc_html_e( 'Analytics scripts', 'cccp' ); ?></label></th>
							<td>
								<textarea id="cccp_analytics_scripts" name="cccp_analytics_scripts" class="large-text" rows="6"><?php echo esc_textarea( (string) $settings['analytics_scripts'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_functional_scripts"><?php esc_html_e( 'Functional scripts', 'cccp' ); ?></label></th>
							<td>
								<textarea id="cccp_functional_scripts" name="cccp_functional_scripts" class="large-text" rows="6"><?php echo esc_textarea( (string) $settings['functional_scripts'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_preferences_scripts"><?php esc_html_e( 'Preferences scripts', 'cccp' ); ?></label></th>
							<td>
								<textarea id="cccp_preferences_scripts" name="cccp_preferences_scripts" class="large-text" rows="6"><?php echo esc_textarea( (string) $settings['preferences_scripts'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_always_allowed_scripts"><?php esc_html_e( 'Always-allowed scripts', 'cccp' ); ?></label></th>
							<td>
								<textarea id="cccp_always_allowed_scripts" name="cccp_always_allowed_scripts" class="large-text" rows="6"><?php echo esc_textarea( (string) $settings['always_allowed_scripts'] ); ?></textarea>
								<p class="description cccp-help"><?php esc_html_e( 'Enter one WordPress script handle or URL fragment per line. Example: google-tag', 'cccp' ); ?></p>
							</td>
						</tr>
					</table>
				<?php else : ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="cccp_primary_color"><?php esc_html_e( 'Primary colour', 'cccp' ); ?></label></th>
							<td>
								<input id="cccp_primary_color" name="cccp_primary_color" type="color" class="cccp-color-field" value="<?php echo esc_attr( (string) $settings['primary_color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_accept_button_label"><?php esc_html_e( 'Accept button label', 'cccp' ); ?></label></th>
							<td>
								<input id="cccp_accept_button_label" name="cccp_accept_button_label" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['accept_button_label'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_reject_button_label"><?php esc_html_e( 'Reject button label', 'cccp' ); ?></label></th>
							<td>
								<input id="cccp_reject_button_label" name="cccp_reject_button_label" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['reject_button_label'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="cccp_save_button_label"><?php esc_html_e( 'Save button label', 'cccp' ); ?></label></th>
							<td>
								<input id="cccp_save_button_label" name="cccp_save_button_label" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['save_button_label'] ); ?>" />
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<?php submit_button( __( 'Save settings', 'cccp' ) ); ?>
			</form>
		</div>
	</div>
	<?php
}
