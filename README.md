# Web Pixel Studio Cookie Consent for EU
![GDPR Ready](https://img.shields.io/badge/GDPR-ready-success)
![Plugin for](https://img.shields.io/badge/WordPress-21759B?style=flat&logo=wordpress&logoColor=white)
![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/web-pixel-studio-cookie-consent-eu?style=flat)
[![WordPress Plugin Tested WP Version](https://img.shields.io/wordpress/plugin/tested/web-pixel-studio-cookie-consent-eu)](https://wordpress.org/plugins/web-pixel-studio-cookie-consent-eu/)
![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/web-pixel-studio-cookie-consent-eu?style=flat)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/web-pixel-studio-cookie-consent-eu)](https://wordpress.org/plugins/web-pixel-studio-cookie-consent-eu/)
[![WordPress Plugin Active Installs](https://img.shields.io/wordpress/plugin/installs/web-pixel-studio-cookie-consent-eu)](https://wordpress.org/plugins/web-pixel-studio-cookie-consent-eu?style=flat)

[![Facebook](https://img.shields.io/badge/Facebook-0866FF?style=flat&logo=facebook&logoColor=white)](https://www.facebook.com/wps.sk)
[![Instagram](https://img.shields.io/badge/Instagram-E4405F?style=flat&logo=instagram&logoColor=white)](https://www.instagram.com/tvorbawebov/)

**Contributors:** duddi, wpssk, webpixelstudio  
**Tags:** cookie consent, GDPR, cookie banner, ePrivacy, google consent mode  
**Requires at least:** 5.9  
**Tested up to:** 6.9  
**Stable tag:** 1.0.9  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

## Description

Web Pixel Studio Cookie Consent for EU gives you a modern, customizable cookie banner for WordPress designed primarily for GDPR and EU cookie/ePrivacy requirements.

It combines a polished frontend experience with practical compliance tools: Google Consent Mode v2 and v3 support, script blocking, cookie declarations, consent logging, multilingual presets, and an admin panel built for real websites.

Built-in translations are available in Slovak, English, Czech, German, French, Spanish, Polish, Hungarian, and Italian.

If you want a consent plugin that looks professional, gives visitors clear choices, and keeps you in control of cookies and third-party scripts, this plugin is built for that.

## Why This Plugin

- Modern cookie banner with box, bar, and cloud layouts
- Detailed preferences modal with category-level consent
- Google Consent Mode v2 and v3 integration ready for GA4 and Google Ads
- Script blocking before consent with simple rules or regex
- Prebuilt cookie and script presets for Google Analytics, Google Ads, Facebook Pixel, Matomo (Analytics + Tag Manager), Hotjar, and Sourcebuster (sbjs)
- **Matomo Analytics support** — connect your self-hosted Matomo instance directly in plugin settings; choose between strict no-tracking after reject or optional anonymous cookieless measurement, with automatic switch to full tracking on later consent
- **No dependency on Google** — works equally well with Matomo as your only analytics platform, keeping all visitor data on your own infrastructure
- Built-in consent log with exportable records
- Floating consent icon so visitors can reopen settings anytime
- Multilingual presets with editable frontend texts
- Full visual control over colors, buttons, fonts, layout, and icon style

## Built For

- business websites that need a polished cookie banner
- agencies managing multiple WordPress sites
- site owners who want clear cookie declarations
- projects using Google Tag Manager, GA4, or Google Ads
- **public sector and government sites** that need to keep visitor data away from third-party and Google infrastructure — Matomo can be fully self-hosted on your own server, so no data ever leaves your environment
- anyone who wants self-hosted cookie control instead of a SaaS popup

### Features

- Google Consent Mode v2 and v3 support
- Cookie banner in box, bar, or cloud layout
- Preferences modal with cookie categories and toggles
- Script blocking based on URL match or regex rules
- Cookie declarations with name, domain, expiration, and description
- Prebuilt presets that add common cookie declarations together with matching blocking rules
- **Matomo Analytics integration** — configure Matomo URL and Site ID, choose strict or anonymous mode, automatic consent switching
- **Self-hosted analytics option** — full Matomo support means no Google dependency; ideal for GDPR-sensitive projects and public sector sites
- Consent logging with unique consent ID and CSV export
- Floating consent icon to reopen settings anytime
- Import and export of plugin settings in JSON
- Built-in language presets and editable frontend texts (Slovak, English, Czech, German, French, Spanish, Polish, Hungarian, Italian)
- Custom colors, buttons, font, banner position, and icon style

## What You Can Configure

- banner texts and button labels
- colors for banner, modal, buttons, toggles, and accents
- font family and border radius
- banner position and layout style
- floating icon type and position
- cookie categories and declared cookies
- script blocking rules by category
- prebuilt Google, Meta, Matomo, Hotjar, and Sourcebuster (sbjs) presets for cookies and script blocking
- visitor language detection for frontend texts
- consent logging, re-consent behavior, and bot hiding

## Compliance-Friendly Workflow

The plugin helps you build a cleaner compliance workflow directly inside WordPress:

- show a clear banner before non-essential consent is granted
- let visitors accept all, reject all, or manage preferences
- block selected third-party scripts until consent exists
- keep a record of consent actions with ID and timestamp
- show declared cookies inside the preferences modal or on a policy page

This plugin is built to support GDPR and ePrivacy requirements, but legal compliance always depends on your actual implementation and jurisdiction.

## External Services

### Google Tag Manager (optional)

The plugin loads Google Tag Manager only if you explicitly set a GTM Container ID in plugin settings. If the GTM field is empty, no GTM requests are made by the plugin.

When enabled, the browser requests resources from Google domains (for example `www.googletagmanager.com`) and sends standard technical request data required by HTTP, typically including IP address, user agent, referrer, and request metadata.

- Service provider: Google Ireland Limited / Google LLC
- Terms of Service: https://policies.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

### Matomo Tracking Script (optional)

The plugin loads Matomo only if you configure Matomo URL and Site ID in plugin settings. The script source is the Matomo endpoint you provide.

When enabled, the browser requests Matomo script resources and sends tracking requests (for example page views) to the configured Matomo server according to your Matomo setup and current consent state.

- Service provider: your configured Matomo host (self-hosted or Matomo Cloud)
- Terms: depends on your Matomo provider
- Privacy: depends on your Matomo provider
- Matomo legal docs (Matomo Cloud reference): https://matomo.org/terms/ and https://matomo.org/privacy-policy/

## Installation

1. Upload the plugin files to the /wp-content/plugins/web-pixel-studio-cookie-consent-eu directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Open Cookie Consent in the WordPress admin menu.
4. Configure Consent Mode, banner texts, cookie categories, script blocking, and appearance settings.
5. Add the cookie list shortcode to your cookie policy page if needed.
6. Test the banner in an incognito window before going live.

## Quick Start

1. Select your preferred language preset.
2. Review and edit the banner texts.
3. Add your site cookies to the declaration table.
4. Add blocking rules for analytics, marketing, or preference scripts.
5. Enable the correct Consent Mode version for your setup.
6. Preview the banner and modal directly from the admin sidebar.

## Frequently Asked Questions

### Does the plugin support Google Consent Mode v2?

Yes. The plugin outputs the default denied consent state before marketing scripts load and updates consent signals after the visitor makes a choice.

### Can I block third-party scripts before consent?

Yes. You can define blocking rules for script URLs and assign them to categories such as Analytics, Targeting, or Preferences.

### Does the plugin support Matomo?

Yes. You can configure Matomo URL and Site ID, choose strict mode (no tracking after reject), or optionally allow anonymous cookieless measurement after reject and switch to full tracking after analytics consent is granted.

### Can visitors change their choice later?

Yes. After consent is given, the floating icon lets visitors reopen the consent panel and update their preferences.

### Is there a way to show declared cookies on a policy page?

Yes. The plugin includes shortcodes for the cookie list and for displaying the current visitor consent ID.

### Can I translate the banner and modal texts?

Yes. The plugin includes built-in language presets and all frontend texts can also be edited manually in the admin.

### Can I export settings to another site?

Yes. The plugin can export and import settings in JSON format, which is useful for backups, staging, or multisite workflows.

### Can I show the current consent ID on a page?

Yes. The plugin includes shortcodes for the cookie list and for displaying the current visitor consent ID.

## Screenshots

### 1. Cookie consent banner on the frontend

![Cookie consent banner on the frontend](admin/images/screenshot-1.webp)

### 2. Preferences modal with category management

![Preferences modal with category management](admin/images/screenshot-2.webp)

### 3. Floating consent icon with consent details popup

![Floating consent icon with consent details popup](admin/images/screenshot-3.webp)

### 4. Admin settings screen

![Admin settings screen](admin/images/screenshot-4.webp)

### 5. Appearance customization screen

![Appearance customization screen](admin/images/screenshot-5.webp)

### 6. Cookie declarations screen

![Cookie declarations screen](admin/images/screenshot-6.webp)

### 7. Script blocking rules screen

![Script blocking rules screen](admin/images/screenshot-7.webp)

### 8. Consent log screen

![Consent log screen](admin/images/screenshot-8.webp)

## Shortcodes

- `[ccwps_cookie_list]` shows the declared cookies grouped by category
- `[ccwps_consent_id]` shows the current visitor consent ID
- `[ccwps_manage_consent]` opens the consent management interface from your content

## Changelog

### 1.0.9

- Added translations for all 34 admin UI messages (Settings saved, errors, confirmations, button labels, etc.) across all 8 supported languages
- Improved localization of the `[ccwps_cookie_list]` shortcode to use detected visitor language when "Language by visitor" is enabled
- Added data attributes to shortcode output to support dynamic language switching in JavaScript
- Enhanced frontend i18n initialization to detect and apply visitor language early when detection is active

### 1.0.8

- Added new cookie presets for Hotjar and Sourcebuster (sbjs), including default domain placeholders, categories, and expiration values
- Added new script-blocking presets for Hotjar and Sourcebuster with precise regex-based source matching
- Added preset description and expiration translations for all 9 supported languages
- Improved frontend localization for cookie preset texts: preset descriptions and expiration values are now localized in the consent modal and in the `[ccwps_cookie_list]` output
- Updated language behavior: when visitor language detection is enabled, frontend preset texts follow visitor language; when disabled, they follow configured frontend/admin translation settings

### 1.0.7

- Switched to local Poppins font (Regular, Medium, SemiBold) and removed all Google Fonts dependencies; no external font requests are made by the plugin
- Added upgrade migration: existing installations with empty or inherited font setting are automatically updated to the Poppins stack
- Changed font detection in the Appearance tab to manual-only: fonts are detected only when the admin clicks "Detect used fonts", preventing automatic remote requests on every page load
- Added "Detect used fonts" button with nonce protection and per-click result cache
- Added translations for new font-detection UI across all 9 supported admin languages
- Updated text domain slug to match the WordPress.org plugin directory slug (web-pixel-studio-cookie-consent-eu)
- Added "External services" section to readme disclosing optional GTM and Matomo remote resource loading, as required by WordPress.org guidelines
- Renamed GTM template files and POT file to match the corrected plugin slug

### 1.0.6

- Updated plugin display name for consistency across the admin panel and WordPress plugin repository
- Removed "Powered by" link from banner output to keep the frontend completely unbranded
- Revised import and export settings flow: improved field handling to prevent data loss on partial imports and avoid overwriting fields not included in the exported file
- Removed REST API endpoint registration that was previously used for internal consent handling

### 1.0.5

- Fixed fatal activation error caused by a missing closing brace in the admin class
- Replaced inline style output in the cookie list shortcode with styles loaded from the enqueued frontend stylesheet
- Completed rename consistency for new plugin naming (main plugin file, GTM template files, and POT filename)
- Updated sidebar author link to `https://webpixelstudio.org`

### 1.04

- Added plugin action link for direct access to Cookie Consent settings from the Plugins list
- Removed Powered by text from banner layouts; branding remains available in the floating consent tip popup
- Removed editable Powered by field from Translations settings to keep frontend branding consistent
- Improved frontend i18n fallback handling for consent labels and cookie table headers across all supported languages
- Added missing language preset keys for consent ID label and cookie table headers in all language presets
- Added new cookie preset groups: Google Targeting and Google Preferences
- Added automatic script-blocking presets for the new Google Targeting and Google Preferences preset groups
- Improved Google Targeting blocking rules to use more specific ad/targeting endpoints instead of broad google.com matching
- Added multilingual preset descriptions for the new Google cookies and localized preset durations in admin preset workflow
- Added frontend runtime localization for preset cookie descriptions based on the active banner language

### 1.0.3

- Added Matomo integration settings (Matomo URL + Site ID) in the Settings tab
- Added new consent behavior toggle for Matomo: strict no-tracking on reject (default) or optional anonymous cookieless measurement on reject
- Added automatic Matomo consent-mode switching: full tracking starts immediately after later analytics consent
- Fixed duplicate Matomo pageview call on initial page load when existing analytics consent was already stored
- Fixed strict Matomo mode: script is now lazy-loaded only after analytics consent is granted, preventing early script execution
- Added new cookie presets: Matomo Analytics and Matomo Tag Manager
- Added new script blocking presets: Matomo Analytics and Matomo Tag Manager with dynamic host-scoped regex (www + non-www)
- Added Matomo guidance section in the About tab, including recommended legal-safe deployment mode
- Added installation screenshots with lightbox preview for Matomo Tag setup directly in the Settings tab
- Added bulk delete functionality with checkboxes and select-all in the Cookies and Blocking admin lists
- Added admin translations for all new texts (Matomo settings, bulk actions, screenshot labels) across all 8 supported languages

### 1.0.2

- Fixed banner visibility regression where the banner could lose fixed positioning and appear only after scrolling
- Added Branding options for custom banner logo: upload, preview, width control, and optional clickable logo URL
- Improved banner layouts with refined Box/Bar/Cloud rendering, spacing, and alignment behavior
- Enhanced Cloud visual style with a cleaner frosted-glass look and improved readability
- Added Bar-specific behavior for banner position: only "Top center" and "Bottom center" are available, with automatic default to "Bottom center"
- Improved admin usability with an additional sidebar "Save settings" action button
- Expanded admin translations and wording coverage across supported languages for newly added options
- Improved export/import reliability so full configuration is preserved, including custom texts, colors, logos/icons, cookies, and block rules

### 1.0.1

- Added grouped cookie presets and script-blocking presets directly in popup forms for faster setup
- Added automatic pairing of blocking rules when applying cookie presets
- Added automatic insertion of required plugin cookies (`ccwps_consent`, `ccwps_version`) if missing
- Improved preset domain normalization to use shared subdomain format (for example `.example.com` without forced `www`)
- Improved frontend translation consistency for the Necessary label "Always active" across language presets and browser language detection
- Updated preset UI so the "Apply preset" action uses the primary action button style
- Expanded and synced preset/admin translations across supported languages

### 1.0.0

- Initial release
- Added cookie banner, preferences modal, and consent logging
- Added Google Consent Mode v2 and v3 integration
- Added script blocking and cookie declaration management
- Added import/export, floating icon, and translation presets
- Added predefined cookie and blocking presets for Google Analytics, Google Ads, and Facebook Pixel

## Upgrade Notice

### 1.0.9

Adds complete translations for all admin UI messages (Settings saved, errors, confirmations, etc.) in all languages, and localizes the [ccwps_cookie_list] shortcode output to use detected visitor language when language detection is enabled.

### 1.0.8

Adds Hotjar and Sourcebuster cookie/blocking presets, expands localized preset descriptions and expirations across all supported languages, and improves frontend language behavior for preset texts in both consent modal and cookie list output.

### 1.0.7

Compliance and font update: removes Google Fonts dependency (uses local Poppins instead), switches font detection to manual-only to prevent automatic remote requests, corrects text domain slug for WordPress.org, and adds required external services disclosure.

### 1.0.6

Updates plugin name, removes Powered by link from the banner, improves import/export reliability, and removes the REST API endpoint.

### 1.0.5

Maintenance and compliance release: fixes a fatal activation issue, moves shortcode inline CSS to the enqueued stylesheet for WordPress.org compliance, finalizes renamed file consistency, and updates the sidebar author URL.

### 1.04

Version 1.04 adds new Google Targeting and Google Preferences presets with auto-block rules, improves localization coverage and runtime translation behavior for cookie descriptions, removes Powered by from banner layouts (kept in floating tip), and adds a direct plugin settings action link in the Plugins list.

### 1.0.3

Version 1.0.3 adds full Matomo Analytics integration with strict no-tracking and optional anonymous cookieless modes, fixes duplicate pageview and lazy-loading issues, adds host-scoped Matomo blocking presets, introduces bulk delete with checkboxes in the Cookies and Blocking lists, adds Matomo installation screenshots with lightbox in the Settings tab, and expands translation coverage across all 8 supported languages.

### 1.0.2

Includes major 1.0.2 improvements: banner visibility fix, new Branding options (custom logo + link), refined layouts, stronger translation coverage, and more reliable full export/import roundtrip.

### 1.0.1

Improves presets workflow, localization consistency, and admin UI polish.

### 1.0.0

Initial release. Configure Consent Mode, translations, cookies, and blocking rules after activation.

## License

This plugin is licensed under the GPL-2.0-or-later license. For more information, see https://www.gnu.org/licenses/gpl-2.0.html.
