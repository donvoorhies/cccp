=== CCCP — Clean Cookie Consent ===
Contributors: donvoorhies
Tags: cookie consent, GDPR, privacy, cookie banner, script blocker
Requires at least: 5.9
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0+

Clean Cookie Consent — no tracking, no iron curtain.

== Description ==

In a web full of tracking pixels and retargeting scripts, CCCP takes the other side.

Most cookie consent plugins are built by and for marketing teams. They make it easy
to drop surveillance tools onto any site and call it "consent management".
CCCP is different.

There is no Marketing category. That is not an oversight — it is the point.

CCCP is for personal sites, portfolios, and blogs whose owners have made a conscious
decision not to track visitors for advertising. It blocks configured non-essential
scripts until a visitor actively opts in, generates a plain-English Cookie Policy,
and then gets out of the way.

No SaaS. No telemetry. No external API calls. No nonsense.

== Why no Marketing category? ==

Because personal sites do not need ad-tech surveillance to function.
If you need marketing cookies, there are many other plugins for that workflow.
CCCP is unapologetically not one of them.

== Features ==

* Privacy-first model with three categories only: Preferences, Functional, Analytics
* No Marketing category by design
* Blocks configured scripts before output until explicit consent exists
* Page-reload consent flow for reliable server-side script queue decisions
* Settings page with General, Scripts, and Appearance tabs
* Tools → Cookie Policy generator with Create / Update Page workflow
* Optional footer "Cookie settings" button to revisit choices
* Optional "Clean cookies only" footer badge
* Consent stored in a browser cookie only (`cccp_consent`)

== Privacy ==

* Consent state is saved in a first-party cookie in the visitor's browser
* CCCP does not send data to external services
* CCCP does not run telemetry or usage tracking
* Script blocking is configured by handle or URL fragment that you define

== Compliance note ==

CCCP ships with explicit opt-in defaults for non-essential categories and blocks
them until consent, aligning with EU ePrivacy and Danish Datatilsynet expectations
for personal sites.

== Default script matching ==

Analytics:
* google-tag
* gtag
* google-tag-manager

Functional:
* youtube-iframe-api
* youtube.com
* vimeo.com

Preferences:
* (empty)

Always allow:
* jquery
* wp-embed

== Installation ==

1. Upload the `cccp` folder to `/wp-content/plugins/`.
2. Activate **CCCP — Clean Cookie Consent** in Plugins.
3. Open Settings → Clean Cookie Consent and configure the three tabs.
4. Open Tools → Cookie Policy and click **Create / Update Page**.

== Frequently Asked Questions ==

= Why does CCCP reload the page after consent? =

Because script queue decisions are made server-side in PHP on page load. A reload
is the simplest and most reliable way to ensure blocked/allowed scripts align exactly
with your saved choices.

= Can I add a Marketing category? =

No. The absence of Marketing is a deliberate product decision.
If you need marketing cookies, use a plugin designed for that purpose.

= What is blocked by default? =

Everything you place in Preferences, Functional, and Analytics is blocked until
the visitor explicitly opts in. Uncategorized essentials are not blocked.

= Where can visitors change consent later? =

If enabled in settings, visitors can use the floating "Cookie settings" button
in the footer to reopen the banner and change choices.

= Does this plugin call any external service? =

No. Consent is stored in a browser cookie only. No SaaS dependency, no telemetry.

== Changelog ==

= 1.0.0 =
* Initial release.
* Privacy-first cookie banner and script blocker.
* Settings tabs for General, Scripts, and Appearance.
* Cookie Policy generator.
* Footer reopen button and clean-cookie badge.
