# Cookie Consent by Web Pixel Studio
![Plugin for](https://img.shields.io/badge/WordPress-21759B?style=flat&logo=wordpress&logoColor=white)
![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/cookie-consent-webpixelstudio?style=flat)
![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/cookie-consent-webpixelstudio?style=flat)
![WordPress Plugin Active Installs](https://img.shields.io/wordpress/plugin/i/cookie-consent-webpixelstudio?style=flat)
[![Facebook](https://img.shields.io/badge/Facebook-0866FF?style=flat&logo=facebook&logoColor=white)](https://www.facebook.com/strankanakluc)
[![Instagram](https://img.shields.io/badge/Instagram-E4405F?style=flat&logo=instagram&logoColor=white)](https://www.instagram.com/tvorbawebov/)

**Contributors:** webpixelstudio  
**Tags:** cookie consent, GDPR, cookie banner, ePrivacy, consent mode  
**Requires at least:** 5.9  
**Tested up to:** 6.9  
**Stable tag:** 1.0.0  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

## Description

Cookie Consent by Web Pixel Studio gives you a modern, customizable, GDPR-ready cookie banner for WordPress without unnecessary complexity.

It combines a polished frontend experience with practical compliance tools: Google Consent Mode v2 and v3 support, script blocking, cookie declarations, consent logging, multilingual presets, and an admin panel built for real websites.

If you want a consent plugin that looks professional, gives visitors clear choices, and keeps you in control of cookies and third-party scripts, this plugin is built for that.

## Why This Plugin

- Modern cookie banner with box, bar, and cloud layouts
- Detailed preferences modal with category-level consent
- Google Consent Mode v2 and v3 integration ready for GA4 and Google Ads
- Script blocking before consent with simple rules or regex
- Built-in consent log with exportable records
- Floating consent icon so visitors can reopen settings anytime
- Multilingual presets with editable frontend texts
- Full visual control over colors, buttons, fonts, layout, and icon style

## Built For

- business websites that need a polished cookie banner
- agencies managing multiple WordPress sites
- site owners who want clear cookie declarations
- projects using Google Tag Manager, GA4, or Google Ads
- anyone who wants self-hosted cookie control instead of a SaaS popup

### Features

- Google Consent Mode v2 and v3 support
- Cookie banner in box, bar, or cloud layout
- Preferences modal with cookie categories and toggles
- Script blocking based on URL match or regex rules
- Cookie declarations with name, domain, expiration, and description
- Consent logging with unique consent ID and CSV export
- Floating consent icon to reopen settings anytime
- Import and export of plugin settings in JSON
- Built-in language presets and editable frontend texts
- Custom colors, buttons, font, banner position, and icon style

## What You Can Configure

- banner texts and button labels
- colors for banner, modal, buttons, toggles, and accents
- font family and border radius
- banner position and layout style
- floating icon type and position
- cookie categories and declared cookies
- script blocking rules by category
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

## Installation

1. Upload the plugin files to the /wp-content/plugins/cookie-consent-webpixelstudio directory, or install the plugin through the WordPress plugins screen.
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

### 1.0.0

- Initial release
- Added cookie banner, preferences modal, and consent logging
- Added Google Consent Mode v2 and v3 integration
- Added script blocking and cookie declaration management
- Added import/export, floating icon, and translation presets

## Upgrade Notice

### 1.0.0

Initial release. Configure Consent Mode, translations, cookies, and blocking rules after activation.

## License

This plugin is licensed under the GPL-2.0-or-later license. For more information, see https://www.gnu.org/licenses/gpl-2.0.html.