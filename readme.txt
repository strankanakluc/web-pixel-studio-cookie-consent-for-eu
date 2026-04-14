=== Cookie Consent by Web Pixel Studio ===
Contributors: duddi, webpixelstudio
Tags: cookie consent, GDPR, cookie banner, ePrivacy, consent mode
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR & ePrivacy compliant cookie consent manager with Google Consent Mode v2/v3, full customization, consent logging and script blocking.

== Description ==

**Cookie Consent by Web Pixel Studio** is a comprehensive, fully customisable cookie consent solution for WordPress. Built on the [orest bida cookieconsent library](https://cookieconsent.orestbida.com/), it extends it with a full WordPress admin panel, database consent logging and advanced script blocking.

= What this plugin offers =

**Cookie Banner & Preferences Modal**
A polished, accessible cookie banner (box, bar or cloud layout) with a full-screen preferences modal. Users can accept all, reject all or manage individual cookie categories: Necessary, Analytics, Targeting and Preferences. The banner supports 7 positions on screen and is fully animated.

**Google Consent Mode v2 & v3**
Automatically outputs the correct default denied state in `<head>` before any scripts run. After the user makes a choice, signals are updated via `gtag('consent', 'update', ...)`. Supports both Consent Mode v2 (required for Google Ads and GA4 in the EU since March 2024) and Consent Mode v3 with enhanced developer ID signals. Optional automatic Google Tag Manager loading.

**Consent Logging**
Every consent is recorded in the database with a unique ID, IP address, URL, user agent and timestamp. The log is viewable in the admin panel and exportable as CSV. Records serve as proof of consent for GDPR audits.

**Script Blocking**
Define URL patterns (plain text or regex) that match script `src` attributes. Matching scripts are blocked (type changed to `text/plain`) until the user consents to the corresponding category.

**Cookie Declarations**
Declare individual cookies per category with name, domain, expiration, path and description. Descriptions are shown to visitors in the preferences modal so they understand what each cookie does.

**Floating Consent Icon**
After consent is given, a floating icon lets users re-open their preferences at any time. Clicking the icon first shows a small popup with the consent ID, date/time of consent, and two buttons: Close and Manage Consent.

**Shortcodes**
- `[ccwps_consent_id]` – shows the visitor's current consent ID. Ideal for cookie policy pages.
- `[ccwps_cookie_list]` – renders an automatic table of declared cookies grouped by category.

**9 Language Presets**
Built-in translations for: 🇸🇰 Slovak, 🇬🇧 English, 🇨🇿 Czech, 🇩🇪 German, 🇫🇷 French, 🇪🇸 Spanish, 🇵🇱 Polish, 🇭🇺 Hungarian, 🇮🇹 Italian. All frontend text is fully editable from the admin panel. The admin panel language and frontend translations can be switched with a single click.

**Full Visual Customisation**
Primary colour, background, text colour, button text colour, button border radius, font family, banner layout (box/bar/cloud), banner position (7 options), floating icon type (cookie/shield/settings/lock) and icon position (4 options).

**Export / Import Settings**
Export all plugin settings to a JSON file for backup or migration between sites. Import restores all settings in one click.

**Bot Detection**
Automatically hides the banner from search engine crawlers and bots to prevent incorrect indexing.

**Re-consent**
Automatically asks users to re-consent when the cookie list changes, keeping you compliant with GDPR requirements for updated consent.

**Admin Panel**
- Left sidebar navigation with all sections
- Preview buttons (banner and preferences modal) always visible in the sidebar
- Tips and explanations for every setting (expandable)
- Consent log with pagination and CSV export
- About section with full documentation, step-by-step setup guide and GTM integration instructions

= GDPR & ePrivacy Compliance =

This plugin is designed to help you comply with GDPR, ePrivacy Directive and similar legislation. However, compliance ultimately depends on your specific implementation and jurisdiction. We recommend consulting a legal professional.

== Installation ==

1. Upload the `cookie-consent-webpixelstudio` folder to `/wp-content/plugins/`, or install through the WordPress Plugins screen.
2. Activate the plugin.
3. Go to **Cookie Consent** in your WordPress admin menu.
4. In **Nastavenia** (Settings): enable consent logging, bot detection and re-consent. Select your Consent Mode version. Enter your GTM Container ID if applicable.
5. In **Preklady** (Translations): select your language preset or edit texts manually.
6. In **Vzhľad** (Appearance): customise colours, layout and icon.
7. In **Cookies**: declare all cookies your site uses.
8. In **Blokovanie skriptov** (Script Blocking): add blocking rules for third-party scripts.
9. Add `[ccwps_cookie_list]` and `[ccwps_consent_id]` to your Cookie Policy page.
10. Test in an incognito window.

== Frequently Asked Questions ==

= Does this support Google Consent Mode v2? =

Yes. The plugin outputs the correct default denied state and updates all Google signals after user interaction. Both v2 and v3 are supported.

= Can I block third-party scripts? =

Yes. The Script Blocking tab lets you define URL patterns. Matching scripts have their `type` changed to `text/plain` until the user consents.

= Where is consent data stored? =

In your WordPress database in a dedicated table (`wp_ccwps_consent_log`). You can view, export and clear this data from the Consent Log tab.

= Can I translate the banner? =

Yes. All frontend strings are editable in the Translations tab. 9 language presets are included. The plugin also supports `.po`/`.mo` translation files.

= Is the consent cookie httpOnly? =

The consent cookie is set client-side (not httpOnly) so the frontend can read it to restore the user's choice without a server round-trip. This is standard practice for consent management.

== Screenshots ==

1. Cookie consent banner (box layout)
2. Preferences modal with category toggles and cookie table
3. Floating icon consent info popup
4. Admin – Settings tab with Consent Mode radio buttons
5. Admin – Appearance tab
6. Admin – Translations tab with language presets
7. Admin – Cookie declarations
8. Admin – Script blocking rules
9. Admin – Consent log
10. Admin – About tab

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
