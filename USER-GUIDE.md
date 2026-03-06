# CCCP — Clean Cookie Consent
## User Guide & Admin Manual

Clean Cookie Consent — no tracking, no iron curtain.

This guide explains how to install, configure, test, and troubleshoot CCCP on a WordPress site.

---

## 1) What CCCP does (and does not do)

CCCP is an opinionated cookie consent plugin for privacy-focused personal sites.

### Included cookie categories
- **Preferences** (optional, blocked by default)
- **Functional** (optional, blocked by default)
- **Analytics** (optional, blocked by default)

### Deliberately not included
- **Marketing** category

If your site needs marketing/advertising cookies, CCCP is intentionally not designed for that workflow.

---

## 2) Installation

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Upload `cccp-1.0.0.zip`.
3. Activate **CCCP — Clean Cookie Consent**.
4. Go to **Settings → Clean Cookie Consent**.

---

## 3) Quick start (recommended)

1. Open **Settings → Clean Cookie Consent**.
2. In **General**:
   - Keep default banner text or customize it.
   - Keep cookie lifetime at `365` days unless you need a different period.
   - Leave **Cookie settings** button enabled.
   - Leave **Clean cookies only** badge enabled (optional, but recommended).
3. In **Scripts**:
   - Keep defaults first, then adapt after testing.
4. In **Appearance**:
   - Keep default primary color (`#cc0000`) or set your own.
5. Save each tab.
6. Open **Tools → Cookie Policy** and click **Create / Update Page**.

---

## 4) Settings reference

## General tab

### Banner text
Shown in the consent banner.

### Cookie lifetime (days)
How long the `cccp_consent` cookie is valid.

### Enable “Cookie settings” footer button
Shows a floating button after consent exists so visitors can reopen the banner.

### Show “Clean cookies only” badge
Shows a small footer badge with a GitHub link.

## Scripts tab

Enter **one value per line**:
- a WordPress script **handle**, or
- a script URL **fragment**

### Analytics scripts (default)
- `google-tag`
- `gtag`
- `google-tag-manager`

### Functional scripts (default)
- `youtube-iframe-api`
- `youtube.com`
- `vimeo.com`

### Preferences scripts (default)
- empty

### Always-allowed scripts (default)
- `jquery`
- `wp-embed`

## Appearance tab

### Primary colour
Used for the **Accept All** button and active toggle state.

### Button labels
- Accept All
- Reject All
- Save preferences

---

## 5) How consent flow works

1. On each front-end request, PHP reads `cccp_consent` cookie.
2. If missing or invalid, consent defaults to all `false`.
3. Scripts in blocked categories are dequeued before output.
4. Banner appears if no valid consent exists.
5. Visitor chooses options.
6. JavaScript writes the cookie and reloads once.
7. Next load applies the saved choices server-side.
8. Banner stays hidden; footer **Cookie settings** button can reopen it.

---

## 6) Testing checklist

## A) First-visit behavior
1. Open an incognito/private window.
2. Visit your site.
3. Confirm banner appears.
4. Confirm all three toggles are initially off.

## B) Cookie write behavior
1. Click **Accept All**.
2. Page reloads once.
3. In browser DevTools → Application/Storage → Cookies, confirm `cccp_consent` exists.
4. Value should contain JSON with booleans for preferences/functional/analytics.

## C) Script blocking behavior
1. DevTools → Network.
2. With no consent (or all false), verify configured scripts do not load.
3. Enable category consent and reload.
4. Verify matching scripts now load.

## D) Reopen and change consent
1. Click **Cookie settings** floating button.
2. Change toggles.
3. Click **Save preferences**.
4. Confirm one reload and updated cookie values.

## E) Reset test state
In browser console:

```js
document.cookie = "cccp_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
location.reload();
```

Banner should appear again.

---

## 7) Cookie Policy generator

Location: **Tools → Cookie Policy**

### What it does
- Builds a policy text from your current CCCP settings.
- Includes privacy-first opening statement.
- Includes category sections based on configured lists.
- Mentions Google Analytics/GTM and YouTube/Vimeo when relevant.
- Includes withdrawal instructions, cookie lifetime, and last-updated date.

### Create / Update behavior
- Creates a page titled **Cookie Policy** if none exists.
- Updates the tracked policy page if it already exists.
- Publishes the page.

You can manually edit the page afterward. It is only overwritten when **Create / Update Page** is clicked again.

---

## 8) Common troubleshooting

## Banner not visible
- Confirm HTML exists: `document.getElementById('cccp-banner')`
- Confirm style exists: `document.getElementById('cccp-frontend-style')`
- Check class on banner:
  - `cccp-visible` means show
  - `cccp-dismissed` means hidden due to existing valid consent
- If hidden and you want first-visit test, delete `cccp_consent` cookie and reload.

## Banner hidden but footer button visible
This means consent already exists. Click **Cookie settings** to reopen.

## Scripts still loading when they should be blocked
- Verify script identifier is correct (exact handle or unique URL fragment).
- Ensure identifier is in the correct category list.
- Hard refresh and retest.
- Test in private window to avoid stale cache/session state.

## Script not loading even after consent
- Confirm it is not accidentally listed under another blocked category.
- Confirm it is not deregistered by another plugin/theme.

## Theme compatibility concerns
CCCP is designed to work on standard front-end renders and avoids admin/login/cron contexts.

---

## 9) Operational recommendations

- Keep script lists minimal and explicit.
- Prefer known script handles over broad URL fragments.
- Re-test consent flow after theme/plugin updates.
- Regenerate Cookie Policy page after major settings changes.

---

## 10) Privacy posture summary (for site owners)

- No marketing category
- No implied consent
- No external SaaS dependency
- No plugin telemetry
- Consent stored in browser cookie only

CCCP is for site owners who intentionally choose less tracking and clearer privacy boundaries.
