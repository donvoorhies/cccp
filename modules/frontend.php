<?php
/**
 * Frontend rendering.
 *
 * @package CCCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if frontend components should run.
 */
function cccp_should_render_frontend(): bool {
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
 * Check whether consent cookie is valid and complete.
 */
function cccp_has_valid_consent_cookie(): bool {
	if ( ! isset( $_COOKIE['cccp_consent'] ) ) {
		return false;
	}

	$raw     = stripslashes( (string) $_COOKIE['cccp_consent'] );
	$consent = json_decode( $raw, true );

	if ( ! is_array( $consent ) ) {
		return false;
	}

	// Require all categories so first-load and legacy cookie states are handled consistently.
	return array_key_exists( 'preferences', $consent ) && array_key_exists( 'functional', $consent ) && array_key_exists( 'analytics', $consent );
}

/**
 * Output frontend CSS and JS.
 */
function cccp_enqueue_frontend_assets(): void {
	if ( ! cccp_should_render_frontend() ) {
		return;
	}

	$settings      = cccp_get_settings();
	$primary_color = sanitize_hex_color( (string) $settings['primary_color'] );
	if ( ! $primary_color ) {
		$primary_color = '#cc0000';
	}

	$lifetime_days = max( 1, absint( (string) $settings['cookie_lifetime_days'] ) );
	$has_cookie    = cccp_has_valid_consent_cookie();
	$consent       = cccp_get_cookie_consent();

	$style = '
.cccp-banner {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 99999;
  background: #ffffff;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
  border-top: 1px solid #e6e8eb;
  transform: translateY(0);
  opacity: 1;
  transition: transform 0.28s ease, opacity 0.28s ease;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.cccp-banner.cccp-visible {
  transform: translateY(0);
  opacity: 1;
}
.cccp-banner.cccp-dismissed {
  transform: translateY(100%);
  opacity: 0;
  pointer-events: none;
}
.cccp-inner {
  margin: 0 auto;
  max-width: 1240px;
  padding: 14px 18px;
  display: grid;
  grid-template-columns: minmax(280px, 1.4fr) minmax(240px, 1fr) auto;
  align-items: center;
  gap: 16px;
}
.cccp-text {
  margin: 0;
  color: #111827;
  font-size: 14px;
  line-height: 1.5;
}
.cccp-toggles {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}
.cccp-toggle-row {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #1f2937;
  font-size: 13px;
}
.cccp-toggle-label {
  white-space: nowrap;
}
.cccp-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}
.cccp-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.cccp-slider {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background-color: #d1d5db;
  transition: all 0.2s ease;
}
.cccp-slider::before {
  content: "";
  position: absolute;
  height: 18px;
  width: 18px;
  left: 3px;
  top: 3px;
  border-radius: 50%;
  background-color: #ffffff;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s ease;
}
.cccp-switch input:checked + .cccp-slider {
  background-color: ' . esc_attr( $primary_color ) . ';
}
.cccp-switch input:checked + .cccp-slider::before {
  transform: translateX(20px);
}
.cccp-buttons {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  flex-wrap: wrap;
}
.cccp-btn {
  cursor: pointer;
  border-radius: 6px;
  font-size: 13px;
  line-height: 1;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  background: transparent;
  color: #1f2937;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
}
.cccp-btn:hover,
.cccp-btn:focus-visible {
  border-color: #94a3b8;
  outline: none;
}
.cccp-btn-accept {
  background: ' . esc_attr( $primary_color ) . ';
  border-color: ' . esc_attr( $primary_color ) . ';
  color: #ffffff;
}
.cccp-btn-accept:hover,
.cccp-btn-accept:focus-visible {
  filter: brightness(0.95);
}
.cccp-settings-button {
  position: fixed;
  left: 12px;
  bottom: 12px;
  z-index: 99998;
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #111827;
  border-radius: 999px;
  font-size: 12px;
  line-height: 1;
  padding: 8px 12px;
  text-decoration: none;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
}
.cccp-settings-button:hover,
.cccp-settings-button:focus-visible {
  text-decoration: none;
  border-color: #9ca3af;
  outline: none;
}
.cccp-badge {
  margin: 16px 0 0;
  font-size: 12px;
  color: #6b7280;
  text-align: center;
}
.cccp-badge a {
  color: #6b7280;
  text-decoration: none;
}
.cccp-badge a:hover,
.cccp-badge a:focus-visible {
  text-decoration: underline;
}
@media (max-width: 860px) {
  .cccp-inner {
    grid-template-columns: 1fr;
    align-items: flex-start;
  }
  .cccp-toggles {
    justify-content: flex-start;
  }
  .cccp-buttons {
    justify-content: flex-start;
  }
}
';

	$settings_button_label = __( 'Cookie settings', 'cccp' );

	$script = '(() => {
  const cccpStyleText = ' . wp_json_encode( $style ) . ';
  if (!document.getElementById("cccp-frontend-style")) {
    const styleEl = document.createElement("style");
    styleEl.id = "cccp-frontend-style";
    styleEl.textContent = cccpStyleText;
    (document.head || document.body || document.documentElement).appendChild(styleEl);
  }

  const banner = document.getElementById("cccp-banner");
  if (!banner) return;

  // Fallback: if navigation was blocked last time (e.g. Brave Android),
  // keep banner hidden via localStorage so it does not reappear.
  try {
    if (localStorage.getItem("cccp_dismissed") === "1") {
      banner.classList.add("cccp-dismissed");
      banner.classList.remove("cccp-visible");
    }
  } catch(e) {}

  const hasValidConsentCookie = ' . ( $has_cookie ? 'true' : 'false' ) . ';
  const consentFromServer = ' . wp_json_encode( $consent ) . ';
  const cookieDays = ' . (int) $lifetime_days . ';

  const preferencesInput = banner.querySelector("input[name=\"cccp-preferences\"]");
  const functionalInput = banner.querySelector("input[name=\"cccp-functional\"]");
  const analyticsInput = banner.querySelector("input[name=\"cccp-analytics\"]");

  const rejectButton = banner.querySelector(".cccp-btn-reject");
  const saveButton = banner.querySelector(".cccp-btn-save");
  const acceptButton = banner.querySelector(".cccp-btn-accept");
  const reopenButton = document.getElementById("cccp-open-settings");

  function safeCookieRead() {
    const match = document.cookie.match(/(?:^|; )cccp_consent=([^;]*)/);
    if (!match) return null;
    try {
      return JSON.parse(decodeURIComponent(match[1]));
    } catch (e) {
      return null;
    }
  }

  function currentState() {
    return {
      preferences: !!preferencesInput.checked,
      functional: !!functionalInput.checked,
      analytics: !!analyticsInput.checked,
    };
  }

  function applyState(state) {
    preferencesInput.checked = !!state.preferences;
    functionalInput.checked = !!state.functional;
    analyticsInput.checked = !!state.analytics;
  }

  function writeConsent(consent) {
    const expires = new Date(Date.now() + cookieDays * 86400000).toUTCString();
    const secure = location.protocol === "https:" ? "; Secure" : "";
    document.cookie = "cccp_consent=" + encodeURIComponent(JSON.stringify(consent)) + "; expires=" + expires + "; path=/; SameSite=Lax" + secure;
  }

  function closeBanner() {
    banner.classList.remove("cccp-visible");
    banner.classList.add("cccp-dismissed");
  }

  function openBannerFromCookie() {
    const cookieConsent = safeCookieRead() || consentFromServer;
    applyState(cookieConsent);
    banner.classList.remove("cccp-dismissed");
    banner.classList.add("cccp-visible");
  }

  function saveAndReload(consent) {
    writeConsent(consent);
    // localStorage fallback for browsers that block programmatic navigation.
    try { localStorage.setItem("cccp_dismissed", "1"); } catch(e) {}
    closeBanner();
    window.location.href = window.location.href;
  }

  let handling = false;
  function addHandler(el, fn) {
    el.addEventListener("touchend", (e) => {
      if (handling) return;
      handling = true;
      e.preventDefault();
      fn(e);
      setTimeout(() => { handling = false; }, 500);
    });
    el.addEventListener("click", (e) => {
      if (handling) return;
      handling = true;
      e.preventDefault();
      fn(e);
      setTimeout(() => { handling = false; }, 500);
    });
  }

  addHandler(acceptButton, () => {
    saveAndReload({ preferences: true, functional: true, analytics: true });
  });

  addHandler(rejectButton, () => {
    saveAndReload({ preferences: false, functional: false, analytics: false });
  });

  addHandler(saveButton, () => {
    saveAndReload(currentState());
  });

  if (reopenButton) {
    reopenButton.addEventListener("click", (event) => {
      event.preventDefault();
      try { localStorage.removeItem("cccp_dismissed"); } catch(e) {}
      openBannerFromCookie();
    });
  }

  if (!hasValidConsentCookie) {
    applyState({ preferences: false, functional: false, analytics: false });
    banner.classList.add("cccp-visible");
  } else {
    applyState(consentFromServer);
    banner.classList.remove("cccp-visible");
  }
})();';

	add_action(
		'wp_footer',
		static function () use ( $style ): void {
			echo '<style id="cccp-frontend-style">' . $style . '</style>';
		},
		18
	);

	add_action(
		'wp_footer',
		static function () use ( $script ): void {
			echo '<script id="cccp-frontend-script">' . $script . '</script>';
		},
		25
	);

	if ( ! empty( $settings['enable_settings_button'] ) && $has_cookie ) {
		add_action(
			'wp_footer',
			static function () use ( $settings_button_label ): void {
				echo '<a href="#" id="cccp-open-settings" class="cccp-settings-button">' . esc_html( $settings_button_label ) . '</a>';
			},
			21
		);
	}
}
add_action( 'wp_enqueue_scripts', 'cccp_enqueue_frontend_assets', 20 );

/**
 * Render banner HTML.
 */
function cccp_render_banner(): void {
	if ( ! cccp_should_render_frontend() ) {
		return;
	}

	$settings      = cccp_get_settings();
	$banner_class  = 'cccp-banner';
	$has_consent   = cccp_has_valid_consent_cookie();
	$banner_class .= $has_consent ? ' cccp-dismissed' : ' cccp-visible';
	?>
	<div id="cccp-banner" class="<?php echo esc_attr( $banner_class ); ?>" role="dialog" aria-label="<?php esc_attr_e( 'Cookie consent', 'cccp' ); ?>" aria-live="polite">
		<div class="cccp-inner">
			<p class="cccp-text"><?php echo esc_html( (string) $settings['banner_text'] ); ?></p>
			<div class="cccp-toggles">
				<label class="cccp-toggle-row">
					<span class="cccp-toggle-label"><?php esc_html_e( 'Preferences', 'cccp' ); ?></span>
					<span class="cccp-switch">
						<input type="checkbox" name="cccp-preferences" />
						<span class="cccp-slider"></span>
					</span>
				</label>
				<label class="cccp-toggle-row">
					<span class="cccp-toggle-label"><?php esc_html_e( 'Functional', 'cccp' ); ?></span>
					<span class="cccp-switch">
						<input type="checkbox" name="cccp-functional" />
						<span class="cccp-slider"></span>
					</span>
				</label>
				<label class="cccp-toggle-row">
					<span class="cccp-toggle-label"><?php esc_html_e( 'Analytics', 'cccp' ); ?></span>
					<span class="cccp-switch">
						<input type="checkbox" name="cccp-analytics" />
						<span class="cccp-slider"></span>
					</span>
				</label>
			</div>
			<div class="cccp-buttons">
				<button type="button" class="cccp-btn cccp-btn-reject"><?php echo esc_html( (string) $settings['reject_button_label'] ); ?></button>
				<button type="button" class="cccp-btn cccp-btn-save"><?php echo esc_html( (string) $settings['save_button_label'] ); ?></button>
				<button type="button" class="cccp-btn cccp-btn-accept"><?php echo esc_html( (string) $settings['accept_button_label'] ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'cccp_render_banner', 20 );

/**
 * Output clean-cookie badge.
 */
function cccp_render_clean_badge(): void {
	if ( ! cccp_should_render_frontend() ) {
		return;
	}

	$settings = cccp_get_settings();
	if ( empty( $settings['show_clean_badge'] ) ) {
		return;
	}

	echo '<p class="cccp-badge"><a href="https://github.com/donvoorhies/cccp" target="_blank" rel="noopener noreferrer">' . esc_html__( '🔒 Clean cookies only — no surveillance capitalism on this site', 'cccp' ) . '</a></p>';
}
add_action( 'wp_footer', 'cccp_render_clean_badge', 30 );
