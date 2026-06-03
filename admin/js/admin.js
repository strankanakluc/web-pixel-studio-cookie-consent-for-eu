/* Cookie Consent WPS – Admin JS */
(function ($) {
	'use strict';

	const { ajaxUrl, nonce, i18n, langPresets, cookies, settings, siteUrl, siteHost, appearanceDefaults } = window.ccwpsAdmin || {};

	const normalizedHost = String(siteHost || '').replace(/^\.+/, '') || 'localhost';
	const dottedHost = '.' + normalizedHost.replace(/^www\./i, '');
	const baseHost = normalizedHost.replace(/^www\./i, '');

	function escapeRegex(value) {
		return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	const matomoHostPattern = '(?:www\\.)?' + escapeRegex(baseHost);

	const blockRulesData = Array.isArray(window.ccwpsAdmin?.blockRules) ? window.ccwpsAdmin.blockRules.slice() : [];
	const spacingSettingKeys = [
		'banner_text_padding_t', 'banner_text_padding_r', 'banner_text_padding_b', 'banner_text_padding_l',
		'banner_text_margin_t', 'banner_text_margin_r', 'banner_text_margin_b', 'banner_text_margin_l',
		'banner_links_padding_t', 'banner_links_padding_r', 'banner_links_padding_b', 'banner_links_padding_l',
		'banner_links_margin_t', 'banner_links_margin_r', 'banner_links_margin_b', 'banner_links_margin_l',
		'banner_accept_btn_padding_t', 'banner_accept_btn_padding_r', 'banner_accept_btn_padding_b', 'banner_accept_btn_padding_l',
		'banner_accept_btn_margin_t', 'banner_accept_btn_margin_r', 'banner_accept_btn_margin_b', 'banner_accept_btn_margin_l',
		'banner_reject_btn_padding_t', 'banner_reject_btn_padding_r', 'banner_reject_btn_padding_b', 'banner_reject_btn_padding_l',
		'banner_reject_btn_margin_t', 'banner_reject_btn_margin_r', 'banner_reject_btn_margin_b', 'banner_reject_btn_margin_l',
		'banner_manage_btn_padding_t', 'banner_manage_btn_padding_r', 'banner_manage_btn_padding_b', 'banner_manage_btn_padding_l',
		'banner_manage_btn_margin_t', 'banner_manage_btn_margin_r', 'banner_manage_btn_margin_b', 'banner_manage_btn_margin_l',
		'banner_btn_gap',
		'modal_body_padding_t', 'modal_body_padding_r', 'modal_body_padding_b', 'modal_body_padding_l',
		'modal_body_margin_t', 'modal_body_margin_r', 'modal_body_margin_b', 'modal_body_margin_l',
		'modal_save_btn_padding_t', 'modal_save_btn_padding_r', 'modal_save_btn_padding_b', 'modal_save_btn_padding_l',
		'modal_save_btn_margin_t', 'modal_save_btn_margin_r', 'modal_save_btn_margin_b', 'modal_save_btn_margin_l',
		'modal_reject_btn_padding_t', 'modal_reject_btn_padding_r', 'modal_reject_btn_padding_b', 'modal_reject_btn_padding_l',
		'modal_reject_btn_margin_t', 'modal_reject_btn_margin_r', 'modal_reject_btn_margin_b', 'modal_reject_btn_margin_l',
		'modal_accept_btn_padding_t', 'modal_accept_btn_padding_r', 'modal_accept_btn_padding_b', 'modal_accept_btn_padding_l',
		'modal_accept_btn_margin_t', 'modal_accept_btn_margin_r', 'modal_accept_btn_margin_b', 'modal_accept_btn_margin_l',
		'modal_btn_gap',
		'modal_desc_margin_t', 'modal_desc_margin_r', 'modal_desc_margin_b', 'modal_desc_margin_l',
	];

	function hasValue(value) {
		return !(value === undefined || value === null || String(value).trim() === '');
	}

	function getSettingValue(key) {
		if (!settings || typeof settings !== 'object') return '';
		return Object.prototype.hasOwnProperty.call(settings, key) ? settings[key] : '';
	}

	function getFirstSettingValue(keys) {
		for (var i = 0; i < keys.length; i += 1) {
			var value = getSettingValue(keys[i]);
			if (hasValue(value)) return value;
		}
		return '';
	}

	function getLegacySpacingValue(key) {
		var map = {
			banner_text_margin_b: ['banner_desc_margin_bottom'],
			banner_links_margin_t: ['banner_links_margin_top'],
			banner_links_margin_b: ['banner_links_margin_bottom'],

			banner_accept_btn_padding_t: ['banner_accept_btn_padding_y', 'banner_btn_padding_y'],
			banner_accept_btn_padding_b: ['banner_accept_btn_padding_y', 'banner_btn_padding_y'],
			banner_accept_btn_padding_r: ['banner_accept_btn_padding_x', 'banner_btn_padding_x'],
			banner_accept_btn_padding_l: ['banner_accept_btn_padding_x', 'banner_btn_padding_x'],
			banner_accept_btn_margin_t: ['banner_accept_btn_margin_y'],
			banner_accept_btn_margin_b: ['banner_accept_btn_margin_y'],
			banner_accept_btn_margin_r: ['banner_accept_btn_margin_x'],
			banner_accept_btn_margin_l: ['banner_accept_btn_margin_x'],

			banner_reject_btn_padding_t: ['banner_reject_btn_padding_y', 'banner_btn_padding_y'],
			banner_reject_btn_padding_b: ['banner_reject_btn_padding_y', 'banner_btn_padding_y'],
			banner_reject_btn_padding_r: ['banner_reject_btn_padding_x', 'banner_btn_padding_x'],
			banner_reject_btn_padding_l: ['banner_reject_btn_padding_x', 'banner_btn_padding_x'],
			banner_reject_btn_margin_t: ['banner_reject_btn_margin_y'],
			banner_reject_btn_margin_b: ['banner_reject_btn_margin_y'],
			banner_reject_btn_margin_r: ['banner_reject_btn_margin_x'],
			banner_reject_btn_margin_l: ['banner_reject_btn_margin_x'],

			banner_manage_btn_padding_t: ['banner_manage_btn_padding_y', 'banner_btn_padding_y'],
			banner_manage_btn_padding_b: ['banner_manage_btn_padding_y', 'banner_btn_padding_y'],
			banner_manage_btn_padding_r: ['banner_manage_btn_padding_x', 'banner_btn_padding_x'],
			banner_manage_btn_padding_l: ['banner_manage_btn_padding_x', 'banner_btn_padding_x'],
			banner_manage_btn_margin_t: ['banner_manage_btn_margin_y'],
			banner_manage_btn_margin_b: ['banner_manage_btn_margin_y'],
			banner_manage_btn_margin_r: ['banner_manage_btn_margin_x'],
			banner_manage_btn_margin_l: ['banner_manage_btn_margin_x'],

			modal_body_padding_t: ['modal_body_padding_y'],
			modal_body_padding_b: ['modal_body_padding_y'],
			modal_body_padding_r: ['modal_body_padding_x'],
			modal_body_padding_l: ['modal_body_padding_x'],

			modal_save_btn_padding_t: ['modal_save_btn_padding_y', 'modal_btn_padding_y'],
			modal_save_btn_padding_b: ['modal_save_btn_padding_y', 'modal_btn_padding_y'],
			modal_save_btn_padding_r: ['modal_save_btn_padding_x', 'modal_btn_padding_x'],
			modal_save_btn_padding_l: ['modal_save_btn_padding_x', 'modal_btn_padding_x'],
			modal_save_btn_margin_t: ['modal_save_btn_margin_y'],
			modal_save_btn_margin_b: ['modal_save_btn_margin_y'],
			modal_save_btn_margin_r: ['modal_save_btn_margin_x'],
			modal_save_btn_margin_l: ['modal_save_btn_margin_x'],

			modal_reject_btn_padding_t: ['modal_reject_btn_padding_y', 'modal_btn_padding_y'],
			modal_reject_btn_padding_b: ['modal_reject_btn_padding_y', 'modal_btn_padding_y'],
			modal_reject_btn_padding_r: ['modal_reject_btn_padding_x', 'modal_btn_padding_x'],
			modal_reject_btn_padding_l: ['modal_reject_btn_padding_x', 'modal_btn_padding_x'],
			modal_reject_btn_margin_t: ['modal_reject_btn_margin_y'],
			modal_reject_btn_margin_b: ['modal_reject_btn_margin_y'],
			modal_reject_btn_margin_r: ['modal_reject_btn_margin_x'],
			modal_reject_btn_margin_l: ['modal_reject_btn_margin_x'],

			modal_accept_btn_padding_t: ['modal_accept_btn_padding_y', 'modal_btn_padding_y'],
			modal_accept_btn_padding_b: ['modal_accept_btn_padding_y', 'modal_btn_padding_y'],
			modal_accept_btn_padding_r: ['modal_accept_btn_padding_x', 'modal_btn_padding_x'],
			modal_accept_btn_padding_l: ['modal_accept_btn_padding_x', 'modal_btn_padding_x'],
			modal_accept_btn_margin_t: ['modal_accept_btn_margin_y'],
			modal_accept_btn_margin_b: ['modal_accept_btn_margin_y'],
			modal_accept_btn_margin_r: ['modal_accept_btn_margin_x'],
			modal_accept_btn_margin_l: ['modal_accept_btn_margin_x'],

			modal_desc_margin_b: ['modal_desc_margin_bottom'],
		};

		return map[key] ? getFirstSettingValue(map[key]) : '';
	}

	function hydrateSpacingModalValues() {
		var $modal = $('#ccwps-spacing-modal');
		if (!$modal.length) return;

		$modal.find('input[type="number"][name]').each(function () {
			var $field = $(this);
			var key = String($field.attr('name') || '');
			if (!key || hasValue($field.val())) return;

			var current = getSettingValue(key);
			if (hasValue(current)) {
				$field.val(String(current));
				return;
			}

			var legacy = getLegacySpacingValue(key);
			if (hasValue(legacy)) {
				$field.val(String(legacy));
			}
		});
	}

	const blockPresets = {
		ga: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:www\\.)?google-analytics\\.com',
				category: 'analytics',
				is_regex: true,
			},
		],
		gtm: [
			{
				script_source: 'www.googletagmanager.com/gtag/js',
				category: 'analytics',
				is_regex: false,
			},
		],
		gads: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:www\\.)?(pagead2\\.googlesyndication\\.com|www\\.googleadservices\\.com|tpc\\.googlesyndication\\.com)',
				category: 'targeting',
				is_regex: true,
			},
			{
				script_source: '*.doubleclick.net',
				category: 'targeting',
				is_regex: false,
			},
		],
		fb: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:(?:www\\.)?facebook\\.com|connect\\.facebook\\.net)',
				category: 'targeting',
				is_regex: true,
			},
		],
		pys: [
			{
				script_source: 'wp-content/plugins/pixelyoursite',
				category: 'targeting',
				is_regex: false,
			},
			{
				script_source: '^(?:https?:)?\\/\\/(?:cdn\\.)?pixelyoursite\\.com(?:\\/|$)',
				category: 'targeting',
				is_regex: true,
			},
		],
		mtm_analytics: [
			{
				script_source: '^(?:https?:)?\\/\\/' + matomoHostPattern + '\\/(?:matomo|piwik)\\.(?:js|php)(?:\\?.*)?$',
				category: 'analytics',
				is_regex: true,
			},
		],
		mtm_tag_manager: [
			{
				script_source: '^(?:https?:)?\\/\\/' + matomoHostPattern + '\\/js\\/container_[A-Za-z0-9]{6,}\\.js(?:\\?.*)?$',
				category: 'analytics',
				is_regex: true,
			},
		],
		hotjar: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:script|static)\\.hotjar\\.com(?:\\/|$)',
				category: 'analytics',
				is_regex: true,
			},
			{
				script_source: '^(?:https?:)?\\/\\/(?:events|surveys|content|vars)\\.hotjar\\.io(?:\\/|$)',
				category: 'analytics',
				is_regex: true,
			},
		],
		sourcebuster: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:cdn\\.jsdelivr\\.net|unpkg\\.com|cdnjs\\.cloudflare\\.com)(?:\\/|$).*(?:sourcebuster|sbjs)(?:\\.min)?\\.js(?:\\?.*)?$',
				category: 'analytics',
				is_regex: true,
			},
			{
				script_source: '(?:^|\\/)(?:sourcebuster|sbjs)(?:\\.min)?\\.js(?:\\?.*)?$',
				category: 'analytics',
				is_regex: true,
			},
		],
		g_targeting: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:www\\.)?(?:googleadservices\\.com|pagead2\\.googlesyndication\\.com|tpc\\.googlesyndication\\.com|adservice\\.google\\.com)(?:\\/|$)',
				category: 'targeting',
				is_regex: true,
			},
			{
				script_source: '^(?:https?:)?\\/\\/(?:[a-z0-9-]+\\.)?doubleclick\\.net(?:\\/|$)',
				category: 'targeting',
				is_regex: true,
			},
			{
				script_source: '^(?:https?:)?\\/\\/(?:www\\.)?google\\.com\\/(?:ads?|adservice|pagead)(?:[\\/?#]|$)',
				category: 'targeting',
				is_regex: true,
			},
		],
		g_preferences: [
			{
				script_source: '^(?:https?:)?\\/\\/(?:www\\.)?google\\.com\\/(?:search|setprefs|preferences|complete\\/search)(?:[\\/?#]|$)',
				category: 'preferences',
				is_regex: true,
			},
		],
	};

	const cookiePresetGroups = {
		google_necessary: {
			cookies: [
				{
					name: 'AEC',
					domain: '.google.com',
					expiration: '1 year',
					path: '/',
					description: 'Used to ensure that requests within a session are made by the user and not by other pages.',
					category: 'necessary',
					is_regex: '',
				},
				{
					name: 'SOCS',
					domain: '.google.com',
					expiration: '1 year',
					path: '/',
					description: 'To store the user\'s state regarding their cookie choices.',
					category: 'necessary',
					is_regex: '',
				},
			],
			blockPresets: [],
		},
		google_analytics: {
			cookies: [
				{
					name: '^_ga_',
					domain: '.yourdomain.com',
					expiration: '1 year',
					path: '/',
					description: 'It is used by Google Analytics to collect data on how many times a user has visited a website, as well as the dates of the first and last visit.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '_ga',
					domain: '.yourdomain.com',
					expiration: '2 years',
					path: '/',
					description: 'It registers a unique ID that is used to generate statistical data about how the visitor uses the website.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '_gat_gtag_',
					domain: '.yourdomain.com',
					expiration: '1 year',
					path: '/',
					description: 'It is used by Google Analytics to limit requests to its service.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '_gid',
					domain: '.yourdomain.com',
					expiration: '1 year',
					path: '/',
					description: 'Is used to track the behavior of website visitors and identify them during different sessions, which allows Google Analytics to analyze traffic and interactions with websites.',
					category: 'analytics',
					is_regex: '1',
				},
			],
			blockPresets: [ 'ga', 'gtm' ],
		},
		google_targeting: {
			cookies: [
				{
					name: '__Secure-1PAPISID / 3PAPISID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Used for ad personalization and interaction measurement. Linked to your Google account.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: '__Secure-1PSID / 3PSID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Contains encrypted details about your Google account identity and recent sign-in time.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: '__Secure-1PSIDCC / 3PSIDCC',
					domain: '.google.com',
					expiration: '1 year',
					path: '/',
					description: 'Used for security and storing user ad-related preferences in Google services.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: '__Secure-1PSIDTS / 3PSIDTS',
					domain: '.google.com',
					expiration: '1 year',
					path: '/',
					description: 'Timestamp and security cookie helping detect suspicious activity and reduce fraud.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'ADS_VISITOR_ID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Visitor identifier used to track interactions with ads.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'APISID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Used to identify users when embedded Google services (like Maps or YouTube) are loaded on other sites.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'SAPISID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Secure API identity cookie used for background Google service requests and fraud prevention.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'SID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Signed and encrypted Google account identifier used to maintain logged-in sessions.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'SSID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Secure-only companion cookie used for identity verification over HTTPS.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'HSID',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Used to verify user identity and help prevent fraudulent use of login credentials.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'NID',
					domain: '.google.com',
					expiration: '6 months',
					path: '/',
					description: 'Stores unique identifier, search preferences, and ad personalization information.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'OTZ',
					domain: 'www.google.com',
					expiration: '1 month',
					path: '/',
					description: 'Aggregated usage statistics and search engine version indicators.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'S',
					domain: '.google.com',
					expiration: '2 years',
					path: '/',
					description: 'Session identifier used by Google billing interfaces to keep purchase or billing state.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'SIDCC',
					domain: '.google.com',
					expiration: '1 year',
					path: '/',
					description: 'Security cookie tied to Google services and fraud protection.',
					category: 'targeting',
					is_regex: '',
				},
			],
			blockPresets: [ 'g_targeting' ],
		},
		google_preferences: {
			cookies: [
				{
					name: '__Secure-BUCKET',
					domain: '.google.com',
					expiration: '6 months',
					path: '/',
					description: 'Internal identifier used to assign users to Google experimental features (A/B tests).',
					category: 'preferences',
					is_regex: '',
				},
				{
					name: 'SEARCH_SAMESITE',
					domain: '.google.com',
					expiration: '6 months',
					path: '/',
					description: 'Helps protect against CSRF attacks by ensuring cookies are sent only in safe contexts.',
					category: 'preferences',
					is_regex: '',
				},
			],
			blockPresets: [ 'g_preferences' ],
		},
		google_ads: {
			cookies: [
				{
					name: 'IDE',
					domain: '.doubleclick.net',
					expiration: '1 year',
					path: '/',
					description: 'Used by Google DoubleClick to track user behavior, allowing for ad personalization and targeting based on previous interactions with ads and websites, storing a unique identifier for each user and used to measure the effectiveness of ads.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: '_gcl_au',
					domain: '.yourdomain.com',
					expiration: '3 months',
					path: '/',
					description: 'It is used by Google AdSense to track users\' interactions with ads on the website and to optimize and personalize advertising content based on their behavior and preferences.',
					category: 'targeting',
					is_regex: '1',
				},
			],
			blockPresets: [ 'gads' ],
		},
		facebook_pixel: {
			cookies: [
				{
					name: '_fbp',
					domain: '.yourdomain.com',
					expiration: '3 months',
					path: '/',
					description: 'It is a marketing cookie used by Facebook Pixel to identify website visitors and track them across websites that use Facebook ads.',
					category: 'targeting',
					is_regex: '1',
				},
			],
			blockPresets: [ 'fb' ],
		},
		pixel_your_site: {
			cookies: [
				{
					name: 'last_pys_landing_page',
					domain: '.yourdomain.com',
					expiration: '6 months',
					path: '/',
					description: 'Pixel Your Site (PYS). Stores the last landing page to support conversion attribution for ads.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'last_pysTrafficSource',
					domain: '.yourdomain.com',
					expiration: '6 months',
					path: '/',
					description: 'Pixel Your Site. Tracks the latest traffic source (for example where the visit came from before purchase).',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'pys_first_visit',
					domain: '.yourdomain.com',
					expiration: '13 months',
					path: '/',
					description: 'Pixel Your Site. Identifies whether this is the first visit, which is important for customer journey tracking.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'pys_landing_page',
					domain: '.yourdomain.com',
					expiration: '6 months',
					path: '/',
					description: 'Pixel Your Site. Stores the very first page URL through which the visitor entered the website.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'pys_session_limit',
					domain: '.yourdomain.com',
					expiration: '30 minutes',
					path: '/',
					description: 'Pixel Your Site. Technical session-duration tracking for behavior analysis used by pixels.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'pys_start_session',
					domain: '.yourdomain.com',
					expiration: '30 minutes',
					path: '/',
					description: 'Pixel Your Site. Identifies session start to synchronize data with Facebook and Google pixels.',
					category: 'targeting',
					is_regex: '',
				},
				{
					name: 'pysTrafficSource',
					domain: '.yourdomain.com',
					expiration: '6 months',
					path: '/',
					description: 'Pixel Your Site. Stores the original traffic source (for example "facebook_ad") for remarketing attribution.',
					category: 'targeting',
					is_regex: '',
				},
			],
			blockPresets: [ 'pys' ],
		},
		matomo: {
			cookies: [
				{
					name: '^_pk_id',
					domain: '.yourdomain.com',
					expiration: '13 months',
					path: '/',
					description: 'Stores a unique visitor ID used by Matomo Analytics to recognize returning visitors.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '^_pk_ses',
					domain: '.yourdomain.com',
					expiration: '30 minutes',
					path: '/',
					description: 'Session cookie used by Matomo Analytics to temporarily store page view data for the current visit.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '^_pk_ref',
					domain: '.yourdomain.com',
					expiration: '6 months',
					path: '/',
					description: 'Stores attribution details (referrer/campaign) for Matomo Analytics reporting.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: 'mtm_consent',
					domain: '.yourdomain.com',
					expiration: '13 months',
					path: '/',
					description: 'Stores the Matomo consent status for this visitor.',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'mtm_cookie_consent',
					domain: '.yourdomain.com',
					expiration: '13 months',
					path: '/',
					description: 'Stores Matomo cookie consent status when cookie-consent mode is used.',
					category: 'analytics',
					is_regex: '',
				},
			],
			blockPresets: [ 'mtm_analytics', 'mtm_tag_manager' ],
		},
		hotjar: {
			cookies: [
				{
					name: '^_hjSessionUser_',
					domain: '.yourdomain.com',
					expiration: '1 year',
					path: '/',
					description: 'Hotjar cookie that recognizes the user on repeat visits for analytics and user-experience improvement.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '^_hjSession_',
					domain: '.yourdomain.com',
					expiration: '1 hour',
					path: '/',
					description: 'Hotjar cookie used to identify the current user session and analyze website usage.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '^_hjIncludedInSessionSample_?$',
					domain: '.yourdomain.com',
					expiration: '1 hour',
					path: '/',
					description: 'Hotjar cookie that determines whether the user visit is included in the analytics measurement sample.',
					category: 'analytics',
					is_regex: '1',
				},
				{
					name: '_hjOptOut',
					domain: '.yourdomain.com',
					expiration: '1 year',
					path: '/',
					description: 'Hotjar cookie storing the information that the user does not want to be tracked by Hotjar analytics.',
					category: 'preferences',
					is_regex: '',
				},
			],
			blockPresets: [ 'hotjar' ],
		},
		sourcebuster: {
			cookies: [
				{
					name: 'sbjs_first',
					domain: '.yourdomain.com',
					expiration: 'Session',
					path: '/',
					description: 'Data about the very first traffic source (where the visitor originally came from).',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'sbjs_current',
					domain: '.yourdomain.com',
					expiration: 'Session',
					path: '/',
					description: 'Stores information about the current website traffic source (for example search, ad, or direct visit).',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'sbjs_first_add',
					domain: '.yourdomain.com',
					expiration: 'Session',
					path: '/',
					description: 'Additional metadata about the first visit (date, page count, entry URL).',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'sbjs_current_add',
					domain: '.yourdomain.com',
					expiration: 'Session',
					path: '/',
					description: 'Additional metadata about the current visit (same as first_add, but for the active session).',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'sbjs_udata',
					domain: '.yourdomain.com',
					expiration: 'Session',
					path: '/',
					description: 'Stores anonymized technical and navigation data for traffic analytics.',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'sbjs_session',
					domain: '.yourdomain.com',
					expiration: '30 minutes',
					path: '/',
					description: 'Determines whether one visit is still active or a new session has started.',
					category: 'analytics',
					is_regex: '',
				},
				{
					name: 'sbjs_migrations',
					domain: '.yourdomain.com',
					expiration: 'Session',
					path: '/',
					description: 'Helper cookie used to process and migrate Sourcebuster tracking data between versions.',
					category: 'analytics',
					is_regex: '',
				},
			],
			blockPresets: [ 'sourcebuster' ],
		},
	};

	const alwaysPluginCookies = [
		{
			name: 'ccwps_consent',
			domain: dottedHost,
			expiration: '1 year',
			path: '/',
			description: 'Stores the visitor consent choices in this plugin so selected categories remain respected across page loads.',
			category: 'necessary',
			is_regex: '',
		},
		{
			name: 'ccwps_version',
			domain: normalizedHost,
			expiration: '6 months',
			path: '/',
			description: 'Stores the plugin consent configuration version to detect changes and trigger re-consent when needed.',
			category: 'necessary',
			is_regex: '',
		},
	];

	const adminLang = String(settings?.admin_lang || 'sk').toLowerCase().split(/[-_]/)[0] || 'sk';

	const presetDurationTranslations = {
		'1 year': { sk: '1 rok', en: '1 year', cs: '1 rok', de: '1 Jahr', fr: '1 an', es: '1 año', pl: '1 rok', hu: '1 év', it: '1 anno' },
		'1 hour': { sk: '1 hodina', en: '1 hour', cs: '1 hodina', de: '1 Stunde', fr: '1 heure', es: '1 hora', pl: '1 godzina', hu: '1 ora', it: '1 ora' },
		'1 month': { sk: '1 mesiac', en: '1 month', cs: '1 měsíc', de: '1 Monat', fr: '1 mois', es: '1 mes', pl: '1 miesiąc', hu: '1 hónap', it: '1 mese' },
		'2 years': { sk: '2 roky', en: '2 years', cs: '2 roky', de: '2 Jahre', fr: '2 ans', es: '2 años', pl: '2 lata', hu: '2 év', it: '2 anni' },
		'3 months': { sk: '3 mesiace', en: '3 months', cs: '3 měsíce', de: '3 Monate', fr: '3 mois', es: '3 meses', pl: '3 miesiące', hu: '3 hónap', it: '3 mesi' },
		'6 months': { sk: '6 mesiacov', en: '6 months', cs: '6 měsíců', de: '6 Monate', fr: '6 mois', es: '6 meses', pl: '6 miesięcy', hu: '6 hónap', it: '6 mesi' },
		'13 months': { sk: '13 mesiacov', en: '13 months', cs: '13 měsíců', de: '13 Monate', fr: '13 mois', es: '13 meses', pl: '13 miesięcy', hu: '13 hónap', it: '13 mesi' },
		'Session': { sk: 'Relácia', en: 'Session', cs: 'Relace', de: 'Sitzung', fr: 'Session', es: 'Sesión', pl: 'Sesja', hu: 'Munkamenet', it: 'Sessione' },
		'30 minutes': { sk: '30 minút', en: '30 minutes', cs: '30 minut', de: '30 Minuten', fr: '30 minutes', es: '30 minutos', pl: '30 minut', hu: '30 perc', it: '30 minuti' },
	};

	const addonPresetCookieDescriptions = {
		sk: {
			'^_hjSessionUser_': 'Cookie služby Hotjar, ktorá rozpoznáva používateľa pri opakovaných návštevách na účely analytiky a zlepšovania používateľského zážitku.',
			'^_hjSession_': 'Cookie služby Hotjar používaná na identifikáciu aktuálnej relácie používateľa a analýzu používania webu.',
			'^_hjIncludedInSessionSample_?$': 'Cookie služby Hotjar určujúca, či je návšteva používateľa zahrnutá do vzorky analytického merania a analýzy používania webu.',
			_hjOptOut: 'Cookie služby Hotjar ukladajúca informáciu, že používateľ si neželá byť sledovaný analytickými nástrojmi Hotjar.',
			sbjs_first: 'Údaje o úplne prvom zdroji návštevy (odkiaľ prišiel používateľ prvýkrát).',
			sbjs_current: 'Ukladá informácie o aktuálnom zdroji návštevy webu (napr. vyhľadávač, reklama alebo priama návšteva) pre analytiku a meranie kampaní.',
			sbjs_first_add: 'Doplnkové dáta o prvej návšteve (dátum, počet stránok, vstupná URL).',
			sbjs_current_add: 'Doplnkové dáta o aktuálnej relácii (rovnaké ako pri first_add, ale pre aktuálnu návštevu).',
			sbjs_udata: 'Ukladá anonymizované technické a navigačné údaje o používateľovi pre analytiku návštevnosti.',
			sbjs_session: 'Určuje, či stále trvá jedna návšteva alebo začala nová (relácia).',
			sbjs_migrations: 'Pomocná cookie používaná na správne spracovanie a migráciu analytických údajov medzi verziami sledovania.',
		},
		cs: {
			'^_hjSessionUser_': 'Cookie služby Hotjar, která rozpozná uživatele při opakovaných návštěvách pro analytiku a zlepšování uživatelského zážitku.',
			'^_hjSession_': 'Cookie služby Hotjar používaná k identifikaci aktuální relace uživatele a analýze používání webu.',
			'^_hjIncludedInSessionSample_?$': 'Cookie služby Hotjar určující, zda je návštěva uživatele zahrnuta do vzorku analytického měření.',
			_hjOptOut: 'Cookie služby Hotjar ukládající informaci, že uživatel nechce být sledován analytickými nástroji Hotjar.',
			sbjs_first: 'Údaje o úplně prvním zdroji návštěvy (odkud uživatel přišel poprvé).',
			sbjs_current: 'Ukládá informace o aktuálním zdroji návštěvy webu (např. vyhledávač, reklama nebo přímá návštěva) pro analytiku kampaní.',
			sbjs_first_add: 'Doplňková data o první návštěvě (datum, počet stránek, vstupní URL).',
			sbjs_current_add: 'Doplňková data o aktuální relaci (stejná jako first_add, ale pro aktuální návštěvu).',
			sbjs_udata: 'Ukládá anonymizované technické a navigační údaje o uživateli pro analytiku návštěvnosti.',
			sbjs_session: 'Určuje, zda stále trvá jedna návštěva, nebo začala nová relace.',
			sbjs_migrations: 'Pomocná cookie pro správné zpracování a migraci analytických dat mezi verzemi sledování.',
		},
		de: {
			'^_hjSessionUser_': 'Hotjar-Cookie zur Wiedererkennung von Nutzern bei erneuten Besuchen für Analytik und UX-Verbesserung.',
			'^_hjSession_': 'Hotjar-Cookie zur Identifizierung der aktuellen Nutzersitzung und Analyse der Website-Nutzung.',
			'^_hjIncludedInSessionSample_?$': 'Hotjar-Cookie, das bestimmt, ob der Besuch in die Analyse-Stichprobe aufgenommen wird.',
			_hjOptOut: 'Hotjar-Cookie, das speichert, dass der Nutzer nicht von Hotjar-Analysewerkzeugen verfolgt werden möchte.',
			sbjs_first: 'Daten zur allerersten Besuchsquelle (woher der Nutzer ursprünglich kam).',
			sbjs_current: 'Speichert Informationen zur aktuellen Besuchsquelle (z. B. Suche, Anzeige oder Direktzugriff) für Kampagnenanalyse.',
			sbjs_first_add: 'Zusatzdaten zum ersten Besuch (Datum, Seitenanzahl, Einstiegs-URL).',
			sbjs_current_add: 'Zusatzdaten zur aktuellen Sitzung (wie first_add, aber für den aktuellen Besuch).',
			sbjs_udata: 'Speichert anonymisierte technische und Navigationsdaten für Traffic-Analysen.',
			sbjs_session: 'Bestimmt, ob eine Sitzung noch aktiv ist oder eine neue Sitzung begonnen hat.',
			sbjs_migrations: 'Hilfs-Cookie zur korrekten Verarbeitung und Migration von Sourcebuster-Trackingdaten zwischen Versionen.',
		},
		fr: {
			'^_hjSessionUser_': 'Cookie Hotjar qui reconnaît l’utilisateur lors de visites répétées à des fins d’analyse et d’amélioration UX.',
			'^_hjSession_': 'Cookie Hotjar utilisé pour identifier la session utilisateur en cours et analyser l’utilisation du site.',
			'^_hjIncludedInSessionSample_?$': 'Cookie Hotjar qui détermine si la visite est incluse dans l’échantillon de mesure analytique.',
			_hjOptOut: 'Cookie Hotjar enregistrant que l’utilisateur ne souhaite pas être suivi par les outils analytiques Hotjar.',
			sbjs_first: 'Données sur la toute première source de visite (provenance initiale de l’utilisateur).',
			sbjs_current: 'Stocke des informations sur la source de visite actuelle (moteur, publicité ou accès direct) pour l’analyse.',
			sbjs_first_add: 'Données complémentaires sur la première visite (date, nombre de pages, URL d’entrée).',
			sbjs_current_add: 'Données complémentaires sur la session actuelle (identiques à first_add mais pour la visite en cours).',
			sbjs_udata: 'Stocke des données techniques et de navigation anonymisées pour l’analyse du trafic.',
			sbjs_session: 'Indique si la même visite est toujours active ou si une nouvelle session a commencé.',
			sbjs_migrations: 'Cookie d’assistance utilisé pour traiter et migrer correctement les données de suivi entre versions.',
		},
		es: {
			'^_hjSessionUser_': 'Cookie de Hotjar que reconoce al usuario en visitas repetidas para analítica y mejora de experiencia.',
			'^_hjSession_': 'Cookie de Hotjar usada para identificar la sesión actual del usuario y analizar el uso del sitio.',
			'^_hjIncludedInSessionSample_?$': 'Cookie de Hotjar que determina si la visita se incluye en la muestra de medición analítica.',
			_hjOptOut: 'Cookie de Hotjar que guarda que el usuario no desea ser rastreado por las herramientas analíticas de Hotjar.',
			sbjs_first: 'Datos sobre la primera fuente de visita (de dónde llegó el usuario por primera vez).',
			sbjs_current: 'Guarda información sobre la fuente actual de visita (buscador, anuncio o visita directa) para analítica.',
			sbjs_first_add: 'Datos adicionales de la primera visita (fecha, número de páginas, URL de entrada).',
			sbjs_current_add: 'Datos adicionales de la sesión actual (igual que first_add, pero para la visita actual).',
			sbjs_udata: 'Guarda datos técnicos y de navegación anonimizados para analítica de tráfico.',
			sbjs_session: 'Determina si la visita actual sigue activa o si comenzó una nueva sesión.',
			sbjs_migrations: 'Cookie auxiliar para el procesamiento y migración correctos de datos de seguimiento entre versiones.',
		},
		pl: {
			'^_hjSessionUser_': 'Cookie Hotjar rozpoznające użytkownika podczas ponownych wizyt do celów analityki i poprawy UX.',
			'^_hjSession_': 'Cookie Hotjar używane do identyfikacji bieżącej sesji użytkownika i analizy korzystania z witryny.',
			'^_hjIncludedInSessionSample_?$': 'Cookie Hotjar określające, czy wizyta użytkownika jest włączona do próbki analitycznej.',
			_hjOptOut: 'Cookie Hotjar zapisujące informację, że użytkownik nie chce być śledzony przez narzędzia analityczne Hotjar.',
			sbjs_first: 'Dane o pierwszym źródle wizyty (skąd użytkownik przyszedł po raz pierwszy).',
			sbjs_current: 'Przechowuje informacje o aktualnym źródle wizyty (wyszukiwarka, reklama lub wejście bezpośrednie) do analityki.',
			sbjs_first_add: 'Dodatkowe dane o pierwszej wizycie (data, liczba stron, URL wejścia).',
			sbjs_current_add: 'Dodatkowe dane o bieżącej sesji (jak first_add, ale dla aktualnej wizyty).',
			sbjs_udata: 'Przechowuje zanonimizowane dane techniczne i nawigacyjne użytkownika do analityki ruchu.',
			sbjs_session: 'Określa, czy trwa ta sama wizyta, czy rozpoczęła się nowa sesja.',
			sbjs_migrations: 'Pomocnicze cookie do prawidłowego przetwarzania i migracji danych śledzenia między wersjami.',
		},
		hu: {
			'^_hjSessionUser_': 'Hotjar cookie, amely visszatero latogatasokkor felismeri a felhasznalot analitikahoz es UX javitashoz.',
			'^_hjSession_': 'Hotjar cookie az aktualis felhasznaloi munkamenet azonositasahoz es a webhelyhasznalat elemzesehez.',
			'^_hjIncludedInSessionSample_?$': 'Hotjar cookie, amely meghatarozza, hogy a latogatas bekerul-e az analitikai mintaba.',
			_hjOptOut: 'Hotjar cookie, amely tarolja, hogy a felhasznalo nem szeretne Hotjar analitikai kovetest.',
			sbjs_first: 'Adatok a legelso forgalmi forrasrol (honnan erkezett a felhasznalo eloszor).',
			sbjs_current: 'Az aktualis latogatasi forras adatait tarolja (kereso, hirdetes vagy kozvetlen forgalom) analitikahoz.',
			sbjs_first_add: 'Kiegeszito adatok az elso latogatasrol (datum, oldalszam, belepo URL).',
			sbjs_current_add: 'Kiegeszito adatok az aktualis munkamenetrol (mint a first_add, de az aktualis latogatashoz).',
			sbjs_udata: 'Anonimizalt technikai es navigacios adatokat tarol forgalmi analitikahoz.',
			sbjs_session: 'Meghatarozza, hogy ugyanaz a latogatas tart-e, vagy uj munkamenet indult.',
			sbjs_migrations: 'Segedcookie a kovetesi adatok verzioik kozotti helyes feldolgozasahoz es migraciojahoz.',
		},
		it: {
			'^_hjSessionUser_': 'Cookie Hotjar che riconosce l’utente nelle visite ripetute per analisi e miglioramento UX.',
			'^_hjSession_': 'Cookie Hotjar usato per identificare la sessione utente corrente e analizzare l’uso del sito.',
			'^_hjIncludedInSessionSample_?$': 'Cookie Hotjar che determina se la visita è inclusa nel campione di misurazione analitica.',
			_hjOptOut: 'Cookie Hotjar che memorizza che l’utente non desidera essere tracciato dagli strumenti analitici Hotjar.',
			sbjs_first: 'Dati sulla primissima fonte di visita (da dove l’utente è arrivato la prima volta).',
			sbjs_current: 'Memorizza informazioni sulla fonte di visita corrente (motore, annuncio o accesso diretto) per analisi.',
			sbjs_first_add: 'Dati aggiuntivi sulla prima visita (data, numero pagine, URL di ingresso).',
			sbjs_current_add: 'Dati aggiuntivi sulla sessione corrente (come first_add, ma per la visita attuale).',
			sbjs_udata: 'Memorizza dati tecnici e di navigazione anonimizzati per analisi del traffico.',
			sbjs_session: 'Determina se la visita corrente è ancora attiva o se è iniziata una nuova sessione.',
			sbjs_migrations: 'Cookie di supporto usato per la corretta elaborazione e migrazione dei dati di tracciamento tra versioni.',
		},
		en: {
			'^_hjSessionUser_': 'Hotjar cookie that recognizes the user on repeat visits for analytics and user-experience improvement.',
			'^_hjSession_': 'Hotjar cookie used to identify the current user session and analyze website usage.',
			'^_hjIncludedInSessionSample_?$': 'Hotjar cookie that determines whether the user visit is included in the analytics measurement sample.',
			_hjOptOut: 'Hotjar cookie storing the information that the user does not want to be tracked by Hotjar analytics.',
			sbjs_first: 'Data about the very first traffic source (where the visitor originally came from).',
			sbjs_current: 'Stores information about the current website traffic source (for example search, ad, or direct visit).',
			sbjs_first_add: 'Additional metadata about the first visit (date, page count, entry URL).',
			sbjs_current_add: 'Additional metadata about the current visit (same as first_add, but for the active session).',
			sbjs_udata: 'Stores anonymized technical and navigation data for traffic analytics.',
			sbjs_session: 'Determines whether one visit is still active or a new session has started.',
			sbjs_migrations: 'Helper cookie used to process and migrate Sourcebuster tracking data between versions.',
		},
	};

	const presetCookieDescriptions = {
		sk: {
			AEC: 'Používa sa na zabezpečenie, aby požiadavky v rámci relácie vykonával používateľ a nie iné stránky.',
			SOCS: 'Ukladá stav používateľa vzhľadom na jeho voľby cookies.',
			'^_ga_': 'Používa sa službou Google Analytics na zhromažďovanie údajov o tom, koľkokrát používateľ navštívil web, vrátane dátumov prvej a poslednej návštevy.',
			_ga: 'Registruje jedinečné ID, ktoré sa používa na generovanie štatistických údajov o tom, ako návštevník používa web.',
			'_gat_gtag_': 'Používa sa službou Google Analytics na obmedzenie počtu požiadaviek na jej službu.',
			_gid: 'Používa sa na sledovanie správania návštevníkov webu a ich rozpoznanie počas rôznych relácií, aby Google Analytics mohlo analyzovať návštevnosť a interakcie.',
			IDE: 'Používa sa službou Google DoubleClick na sledovanie správania používateľa a personalizáciu reklám podľa predchádzajúcich interakcií.',
			_gcl_au: 'Používa sa službou Google AdSense na sledovanie interakcií používateľov s reklamami a optimalizáciu reklamného obsahu.',
			_fbp: 'Marketingová cookie služby Facebook Pixel na identifikáciu návštevníkov a sledovanie naprieč webmi používajúcimi Facebook reklamy.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Ukladá poslednú pristávaciu stránku pre potreby priradenia konverzie k reklame.',
			last_pysTrafficSource: 'Pixel Your Site. Sleduje posledný zdroj návštevnosti (napr. odkiaľ ste prišli pred nákupom).',
			pys_first_visit: 'Pixel Your Site. Identifikuje, či ide o vašu prvú návštevu, čo je kľúčové pre sledovanie cesty zákazníka.',
			pys_landing_page: 'Pixel Your Site. Ukladá adresu úplne prvej stránky, cez ktorú ste na web vstúpili.',
			pys_session_limit: 'Pixel Your Site. Technické sledovanie dĺžky relácie na účely analýzy správania pre pixely.',
			pys_start_session: 'Pixel Your Site. Identifikuje začiatok relácie na synchronizáciu dát s Facebook a Google pixelom.',
			pysTrafficSource: 'Pixel Your Site. Ukladá pôvodný zdroj návštevnosti (napr. "facebook_ad") pre potreby remarketingového priradenia.',
			'^_pk_id': 'Ukladá jedinečné ID návštevníka používané službou Matomo Analytics na rozpoznanie vracajúcich sa návštevníkov.',
			'^_pk_ses': 'Relačná cookie služby Matomo Analytics, ktorá dočasne ukladá údaje o zobrazeniach stránok počas aktuálnej návštevy.',
			'^_pk_ref': 'Ukladá atribučné údaje (referrer/kampaň) pre reporty Matomo Analytics.',
			mtm_consent: 'Ukladá stav súhlasu Matomo pre tohto návštevníka.',
			mtm_cookie_consent: 'Ukladá stav súhlasu s cookies pre Matomo pri použití režimu cookie-consent.',
			ccwps_consent: 'Ukladá voľby súhlasu návštevníka v tomto plugine, aby zostali vybrané kategórie rešpektované pri ďalších načítaniach stránky.',
			ccwps_version: 'Ukladá verziu konfigurácie súhlasu v plugine, aby sa po zmene nastavení mohol vyžiadať nový súhlas.',
		},
		cs: {
			AEC: 'Používá se k zajištění, aby požadavky v rámci relace prováděl uživatel a ne jiné stránky.',
			SOCS: 'Ukládá stav uživatele vzhledem k jeho volbám cookies.',
			'^_ga_': 'Používá se službou Google Analytics ke sběru údajů o tom, kolikrát uživatel navštívil web, včetně data první a poslední návštěvy.',
			_ga: 'Registruje jedinečné ID používané ke generování statistických údajů o tom, jak návštěvník web používá.',
			'_gat_gtag_': 'Používá se službou Google Analytics k omezení počtu požadavků na službu.',
			_gid: 'Používá se ke sledování chování návštěvníků webu a jejich rozpoznání během různých relací, aby Google Analytics mohlo analyzovat návštěvnost a interakce.',
			IDE: 'Používá Google DoubleClick ke sledování chování uživatele a personalizaci reklam podle předchozích interakcí.',
			_gcl_au: 'Používá Google AdSense ke sledování interakcí uživatelů s reklamami a optimalizaci reklamního obsahu.',
			_fbp: 'Marketingová cookie služby Facebook Pixel pro identifikaci návštěvníků a sledování napříč weby používajícími reklamy Facebooku.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Ukládá poslední vstupní stránku pro přiřazení konverze k reklamě.',
			last_pysTrafficSource: 'Pixel Your Site. Sleduje poslední zdroj návštěvnosti (např. odkud jste přišli před nákupem).',
			pys_first_visit: 'Pixel Your Site. Identifikuje, zda jde o vaši první návštěvu, což je důležité pro sledování cesty zákazníka.',
			pys_landing_page: 'Pixel Your Site. Ukládá adresu úplně první stránky, přes kterou jste na web vstoupili.',
			pys_session_limit: 'Pixel Your Site. Technické sledování délky relace pro účely analýzy chování pixelů.',
			pys_start_session: 'Pixel Your Site. Identifikuje začátek relace pro synchronizaci dat s Facebook a Google pixelem.',
			pysTrafficSource: 'Pixel Your Site. Ukládá původní zdroj návštěvnosti (např. "facebook_ad") pro účely remarketingového přiřazení.',
			'^_pk_id': 'Ukládá jedinečné ID návštěvníka používané službou Matomo Analytics k rozpoznání vracejících se návštěvníků.',
			'^_pk_ses': 'Relační cookie služby Matomo Analytics, která dočasně ukládá data o zobrazeních stránek během aktuální návštěvy.',
			'^_pk_ref': 'Ukládá atribuční údaje (odkaz/kampaň) pro reporty Matomo Analytics.',
			mtm_consent: 'Ukládá stav souhlasu Matomo pro tohoto návštěvníka.',
			mtm_cookie_consent: 'Ukládá stav souhlasu s cookies pro Matomo při použití režimu cookie-consent.',
			ccwps_consent: 'Ukládá volby souhlasu návštěvníka v tomto pluginu, aby vybrané kategorie zůstaly respektované při dalších načteních stránky.',
			ccwps_version: 'Ukládá verzi konfigurace souhlasu v pluginu, aby bylo možné po změně nastavení vyžádat nový souhlas.',
		},
		de: {
			AEC: 'Wird verwendet, um sicherzustellen, dass Anfragen innerhalb einer Sitzung vom Nutzer und nicht von anderen Seiten gestellt werden.',
			SOCS: 'Speichert den Status des Nutzers in Bezug auf seine Cookie-Auswahl.',
			'^_ga_': 'Wird von Google Analytics verwendet, um Daten darüber zu sammeln, wie oft ein Nutzer eine Website besucht hat, einschließlich des Datums des ersten und letzten Besuchs.',
			_ga: 'Registriert eine eindeutige ID, die zur Erstellung statistischer Daten über die Nutzung der Website verwendet wird.',
			'_gat_gtag_': 'Wird von Google Analytics verwendet, um die Anzahl der Anfragen an den Dienst zu begrenzen.',
			_gid: 'Wird verwendet, um das Verhalten von Website-Besuchern zu verfolgen und sie über verschiedene Sitzungen hinweg zu erkennen, damit Google Analytics Traffic und Interaktionen analysieren kann.',
			IDE: 'Wird von Google DoubleClick verwendet, um das Nutzerverhalten zu verfolgen und Werbung anhand früherer Interaktionen zu personalisieren.',
			_gcl_au: 'Wird von Google AdSense verwendet, um Interaktionen mit Anzeigen zu verfolgen und Werbeinhalte zu optimieren.',
			_fbp: 'Marketing-Cookie von Facebook Pixel zur Identifizierung von Besuchern und zum Tracking über Websites hinweg, die Facebook-Werbung nutzen.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Speichert die letzte Landingpage zur Zuordnung von Conversions zu Werbeanzeigen.',
			last_pysTrafficSource: 'Pixel Your Site. Erfasst die letzte Traffic-Quelle (z. B. woher der Besuch vor dem Kauf kam).',
			pys_first_visit: 'Pixel Your Site. Erkennt, ob es sich um den ersten Besuch handelt, was für die Customer-Journey-Analyse wichtig ist.',
			pys_landing_page: 'Pixel Your Site. Speichert die URL der allerersten Seite, über die der Besucher eingestiegen ist.',
			pys_session_limit: 'Pixel Your Site. Technisches Tracking der Sitzungsdauer zur Verhaltensanalyse für Pixel.',
			pys_start_session: 'Pixel Your Site. Kennzeichnet den Sitzungsstart zur Datensynchronisierung mit Facebook- und Google-Pixel.',
			pysTrafficSource: 'Pixel Your Site. Speichert die ursprüngliche Traffic-Quelle (z. B. "facebook_ad") für Remarketing-Zuordnung.',
			'^_pk_id': 'Speichert eine eindeutige Besucher-ID, die von Matomo Analytics verwendet wird, um wiederkehrende Besucher zu erkennen.',
			'^_pk_ses': 'Sitzungs-Cookie von Matomo Analytics, das Seitenaufrufdaten für den aktuellen Besuch vorübergehend speichert.',
			'^_pk_ref': 'Speichert Zuordnungsdetails (Referrer/Kampagne) für Matomo-Analytics-Berichte.',
			mtm_consent: 'Speichert den Matomo-Einwilligungsstatus für diesen Besucher.',
			mtm_cookie_consent: 'Speichert den Cookie-Einwilligungsstatus für Matomo, wenn der Cookie-Consent-Modus verwendet wird.',
			ccwps_consent: 'Speichert die Einwilligungsentscheidungen des Besuchers in diesem Plugin, damit die gewählten Kategorien bei weiteren Seitenaufrufen berücksichtigt bleiben.',
			ccwps_version: 'Speichert die Version der Einwilligungskonfiguration im Plugin, um Änderungen zu erkennen und gegebenenfalls eine neue Einwilligung anzufordern.',
		},
		fr: {
			AEC: 'Utilisé pour garantir que les requêtes effectuées pendant une session proviennent bien de l’utilisateur et non d’autres pages.',
			SOCS: 'Stocke l’état de l’utilisateur concernant ses choix de cookies.',
			'^_ga_': 'Utilisé par Google Analytics pour collecter des données sur le nombre de visites d’un utilisateur, ainsi que les dates de première et de dernière visite.',
			_ga: 'Enregistre un identifiant unique utilisé pour générer des données statistiques sur l’utilisation du site par le visiteur.',
			'_gat_gtag_': 'Utilisé par Google Analytics pour limiter le nombre de requêtes adressées au service.',
			_gid: 'Utilisé pour suivre le comportement des visiteurs du site et les identifier au fil des sessions afin que Google Analytics puisse analyser le trafic et les interactions.',
			IDE: 'Utilisé par Google DoubleClick pour suivre le comportement de l’utilisateur et personnaliser les publicités selon ses interactions précédentes.',
			_gcl_au: 'Utilisé par Google AdSense pour suivre les interactions des utilisateurs avec les annonces et optimiser le contenu publicitaire.',
			_fbp: 'Cookie marketing de Facebook Pixel utilisé pour identifier les visiteurs et les suivre sur les sites utilisant des publicités Facebook.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Stocke la dernière page d\'atterrissage pour attribuer les conversions aux publicités.',
			last_pysTrafficSource: 'Pixel Your Site. Suit la dernière source de trafic (par ex. d\'où vous venez avant un achat).',
			pys_first_visit: 'Pixel Your Site. Identifie s\'il s\'agit de votre première visite, essentiel pour suivre le parcours client.',
			pys_landing_page: 'Pixel Your Site. Stocke l\'URL de la toute première page par laquelle vous êtes entré sur le site.',
			pys_session_limit: 'Pixel Your Site. Suivi technique de la durée de session pour l\'analyse du comportement via les pixels.',
			pys_start_session: 'Pixel Your Site. Identifie le début de session pour synchroniser les données avec les pixels Facebook et Google.',
			pysTrafficSource: 'Pixel Your Site. Stocke la source de trafic d\'origine (par ex. "facebook_ad") pour l\'attribution remarketing.',
			'^_pk_id': 'Stocke un identifiant visiteur unique utilisé par Matomo Analytics pour reconnaître les visiteurs récurrents.',
			'^_pk_ses': 'Cookie de session Matomo Analytics utilisé pour stocker temporairement les données de pages vues pendant la visite en cours.',
			'^_pk_ref': 'Stocke les détails d’attribution (référent/campagne) pour les rapports Matomo Analytics.',
			mtm_consent: 'Stocke l’état du consentement Matomo pour ce visiteur.',
			mtm_cookie_consent: 'Stocke l’état du consentement aux cookies pour Matomo lorsque le mode cookie-consent est utilisé.',
			ccwps_consent: 'Stocke les choix de consentement du visiteur dans ce plugin afin que les catégories sélectionnées restent respectées lors des chargements suivants.',
			ccwps_version: 'Stocke la version de la configuration du consentement dans le plugin afin de détecter les changements et demander un nouveau consentement si nécessaire.',
		},
		es: {
			AEC: 'Se utiliza para garantizar que las solicitudes dentro de una sesión las realice el usuario y no otras páginas.',
			SOCS: 'Almacena el estado del usuario respecto a sus elecciones de cookies.',
			'^_ga_': 'Google Analytics la utiliza para recopilar datos sobre cuántas veces un usuario ha visitado un sitio web, incluidas las fechas de la primera y la última visita.',
			_ga: 'Registra un identificador único que se utiliza para generar datos estadísticos sobre cómo el visitante usa el sitio web.',
			'_gat_gtag_': 'Google Analytics la utiliza para limitar el número de solicitudes al servicio.',
			_gid: 'Se utiliza para rastrear el comportamiento de los visitantes y reconocerlos durante distintas sesiones, lo que permite a Google Analytics analizar el tráfico y las interacciones.',
			IDE: 'Google DoubleClick la utiliza para rastrear el comportamiento del usuario y personalizar anuncios según interacciones anteriores.',
			_gcl_au: 'Google AdSense la utiliza para rastrear interacciones con anuncios y optimizar el contenido publicitario.',
			_fbp: 'Cookie de marketing de Facebook Pixel utilizada para identificar visitantes y rastrearlos entre sitios que usan anuncios de Facebook.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Guarda la última página de aterrizaje para atribuir conversiones a anuncios.',
			last_pysTrafficSource: 'Pixel Your Site. Rastrea la última fuente de tráfico (por ejemplo, de dónde llegaste antes de comprar).',
			pys_first_visit: 'Pixel Your Site. Identifica si es tu primera visita, clave para el seguimiento del recorrido del cliente.',
			pys_landing_page: 'Pixel Your Site. Guarda la URL de la primera página por la que entraste en el sitio.',
			pys_session_limit: 'Pixel Your Site. Seguimiento técnico de la duración de la sesión para análisis de comportamiento de píxeles.',
			pys_start_session: 'Pixel Your Site. Identifica el inicio de sesión para sincronizar datos con los píxeles de Facebook y Google.',
			pysTrafficSource: 'Pixel Your Site. Guarda la fuente de tráfico original (por ejemplo "facebook_ad") para atribución de remarketing.',
			'^_pk_id': 'Almacena un identificador único del visitante utilizado por Matomo Analytics para reconocer a los visitantes recurrentes.',
			'^_pk_ses': 'Cookie de sesión de Matomo Analytics utilizada para almacenar temporalmente datos de páginas vistas durante la visita actual.',
			'^_pk_ref': 'Almacena detalles de atribución (referente/campaña) para los informes de Matomo Analytics.',
			mtm_consent: 'Almacena el estado de consentimiento de Matomo para este visitante.',
			mtm_cookie_consent: 'Almacena el estado de consentimiento de cookies para Matomo cuando se utiliza el modo cookie-consent.',
			ccwps_consent: 'Almacena las elecciones de consentimiento del visitante en este plugin para que las categorías seleccionadas sigan respetándose en las siguientes cargas de página.',
			ccwps_version: 'Almacena la versión de la configuración de consentimiento en el plugin para detectar cambios y solicitar un nuevo consentimiento cuando sea necesario.',
		},
		pl: {
			AEC: 'Służy do zapewnienia, że żądania w ramach sesji są wykonywane przez użytkownika, a nie przez inne strony.',
			SOCS: 'Przechowuje stan użytkownika dotyczący jego wyborów cookies.',
			'^_ga_': 'Używana przez Google Analytics do zbierania danych o tym, ile razy użytkownik odwiedził witrynę, w tym dat pierwszej i ostatniej wizyty.',
			_ga: 'Rejestruje unikalny identyfikator używany do generowania danych statystycznych o sposobie korzystania z witryny przez odwiedzającego.',
			'_gat_gtag_': 'Używana przez Google Analytics do ograniczania liczby żądań do usługi.',
			_gid: 'Służy do śledzenia zachowania odwiedzających i rozpoznawania ich podczas różnych sesji, dzięki czemu Google Analytics może analizować ruch i interakcje.',
			IDE: 'Używana przez Google DoubleClick do śledzenia zachowania użytkownika i personalizacji reklam na podstawie wcześniejszych interakcji.',
			_gcl_au: 'Używana przez Google AdSense do śledzenia interakcji użytkowników z reklamami oraz optymalizacji treści reklamowych.',
			_fbp: 'Marketingowe cookie Facebook Pixel służące do identyfikacji odwiedzających i śledzenia ich między witrynami korzystającymi z reklam Facebooka.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Przechowuje ostatnią stronę lądowania do przypisania konwersji do reklamy.',
			last_pysTrafficSource: 'Pixel Your Site. Śledzi ostatnie źródło ruchu (np. skąd przyszedłeś przed zakupem).',
			pys_first_visit: 'Pixel Your Site. Identyfikuje, czy to Twoja pierwsza wizyta, co jest kluczowe dla śledzenia ścieżki klienta.',
			pys_landing_page: 'Pixel Your Site. Przechowuje adres pierwszej strony, przez którą wszedłeś na witrynę.',
			pys_session_limit: 'Pixel Your Site. Techniczne śledzenie długości sesji do analizy zachowania dla pikseli.',
			pys_start_session: 'Pixel Your Site. Identyfikuje początek sesji do synchronizacji danych z pikselem Facebook i Google.',
			pysTrafficSource: 'Pixel Your Site. Przechowuje pierwotne źródło ruchu (np. "facebook_ad") dla atrybucji remarketingowej.',
			'^_pk_id': 'Przechowuje unikalny identyfikator odwiedzającego używany przez Matomo Analytics do rozpoznawania powracających użytkowników.',
			'^_pk_ses': 'Sesyjne cookie Matomo Analytics używane do tymczasowego przechowywania danych o odsłonach podczas bieżącej wizyty.',
			'^_pk_ref': 'Przechowuje dane atrybucji (źródło/kampania) do raportów Matomo Analytics.',
			mtm_consent: 'Przechowuje stan zgody Matomo dla tego odwiedzającego.',
			mtm_cookie_consent: 'Przechowuje stan zgody na cookies dla Matomo, gdy używany jest tryb cookie-consent.',
			ccwps_consent: 'Przechowuje wybory zgody użytkownika w tej wtyczce, aby wybrane kategorie były respektowane przy kolejnych odsłonach.',
			ccwps_version: 'Przechowuje wersję konfiguracji zgody we wtyczce, aby wykrywać zmiany i w razie potrzeby poprosić o nową zgodę.',
		},
		hu: {
			AEC: 'Arra szolgál, hogy biztosítsa: a munkameneten belüli kéréseket a felhasználó, ne más oldalak indítsák.',
			SOCS: 'Tárolja a felhasználó állapotát a cookie-választásai tekintetében.',
			'^_ga_': 'A Google Analytics használja annak gyűjtésére, hogy egy felhasználó hányszor látogatta meg a webhelyet, beleértve az első és utolsó látogatás dátumát is.',
			_ga: 'Egyedi azonosítót rögzít, amelyet statisztikai adatok készítésére használnak arról, hogyan használja a látogató a webhelyet.',
			'_gat_gtag_': 'A Google Analytics használja a szolgáltatás felé irányuló kérések számának korlátozására.',
			_gid: 'A webhelylátogatók viselkedésének nyomon követésére és azonosítására szolgál különböző munkamenetek során, hogy a Google Analytics elemezhesse a forgalmat és az interakciókat.',
			IDE: 'A Google DoubleClick használja a felhasználói viselkedés követésére és a hirdetések személyre szabására a korábbi interakciók alapján.',
			_gcl_au: 'A Google AdSense használja a hirdetésekkel kapcsolatos interakciók követésére és a hirdetési tartalom optimalizálására.',
			_fbp: 'A Facebook Pixel marketing cookie-ja, amely a látogatók azonosítására és a Facebook-hirdetéseket használó webhelyek közötti követésre szolgál.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Tarolja az utolso nyito oldalt, hogy a konverzio hozzarendelheto legyen a hirdeteshez.',
			last_pysTrafficSource: 'Pixel Your Site. Koveti az utolso forgalmi forrast (pl. honnan erkeztel vasarlas elott).',
			pys_first_visit: 'Pixel Your Site. Azonositja, hogy ez az elso latogatas-e, ami fontos az ugyfelut kovetesehez.',
			pys_landing_page: 'Pixel Your Site. Tarolja az elso oldal URL-jet, amelyen keresztul a latogato belepett a webhelyre.',
			pys_session_limit: 'Pixel Your Site. Technikai munkamenet-hossz kovetes pixelalapu viselkedeselemzeshez.',
			pys_start_session: 'Pixel Your Site. Azonositja a munkamenet kezdetet az adatok Facebook es Google pixellel valo szinkronizalasahoz.',
			pysTrafficSource: 'Pixel Your Site. Tarolja az eredeti forgalmi forrast (pl. "facebook_ad") remarketing attribuciohoz.',
			'^_pk_id': 'A Matomo Analytics által használt egyedi látogatóazonosítót tárolja a visszatérő látogatók felismeréséhez.',
			'^_pk_ses': 'A Matomo Analytics munkamenet-cookie-ja, amely ideiglenesen tárolja az aktuális látogatás oldalmegtekintési adatait.',
			'^_pk_ref': 'Attribúciós adatokat (hivatkozó/kampány) tárol a Matomo Analytics riportjaihoz.',
			mtm_consent: 'Tárolja a Matomo hozzájárulási állapotát ennél a látogatónál.',
			mtm_cookie_consent: 'Tárolja a Matomo cookie-hozzájárulási állapotát, amikor a cookie-consent mód van használatban.',
			ccwps_consent: 'Tárolja a látogató hozzájárulási választásait ebben a bővítményben, hogy a kiválasztott kategóriák a következő oldalbetöltéseknél is érvényesek maradjanak.',
			ccwps_version: 'Tárolja a hozzájárulási konfiguráció verzióját a bővítményben, hogy a módosításokat észlelje és szükség esetén új hozzájárulást kérjen.',
		},
		it: {
			AEC: 'Viene utilizzato per garantire che le richieste all\'interno di una sessione siano effettuate dall\'utente e non da altre pagine.',
			SOCS: 'Memorizza lo stato dell\'utente riguardo alle sue scelte sui cookie.',
			'^_ga_': 'Viene utilizzato da Google Analytics per raccogliere dati su quante volte un utente ha visitato un sito web, comprese le date della prima e dell\'ultima visita.',
			_ga: 'Registra un ID univoco utilizzato per generare dati statistici su come il visitatore utilizza il sito web.',
			'_gat_gtag_': 'Viene utilizzato da Google Analytics per limitare il numero di richieste al servizio.',
			_gid: 'Viene utilizzato per tracciare il comportamento dei visitatori del sito e riconoscerli durante diverse sessioni, consentendo a Google Analytics di analizzare traffico e interazioni.',
			IDE: 'Viene utilizzato da Google DoubleClick per tracciare il comportamento dell\'utente e personalizzare gli annunci in base alle interazioni precedenti.',
			_gcl_au: 'Viene utilizzato da Google AdSense per tracciare le interazioni con gli annunci e ottimizzare i contenuti pubblicitari.',
			_fbp: 'Cookie di marketing di Facebook Pixel usato per identificare i visitatori e tracciarli tra siti che utilizzano annunci Facebook.',
			last_pys_landing_page: 'Pixel Your Site (PYS). Memorizza l\'ultima landing page per attribuire le conversioni alle campagne pubblicitarie.',
			last_pysTrafficSource: 'Pixel Your Site. Traccia l\'ultima sorgente di traffico (ad esempio da dove arrivi prima dell\'acquisto).',
			pys_first_visit: 'Pixel Your Site. Identifica se si tratta della prima visita, elemento chiave per tracciare il percorso cliente.',
			pys_landing_page: 'Pixel Your Site. Memorizza l\'URL della prima pagina attraverso cui sei entrato nel sito.',
			pys_session_limit: 'Pixel Your Site. Tracciamento tecnico della durata sessione per analisi comportamentale dei pixel.',
			pys_start_session: 'Pixel Your Site. Identifica l\'inizio sessione per sincronizzare i dati con i pixel Facebook e Google.',
			pysTrafficSource: 'Pixel Your Site. Memorizza la sorgente di traffico originale (es. "facebook_ad") per attribuzione remarketing.',
			'^_pk_id': 'Memorizza un ID visitatore univoco utilizzato da Matomo Analytics per riconoscere i visitatori di ritorno.',
			'^_pk_ses': 'Cookie di sessione di Matomo Analytics usato per memorizzare temporaneamente i dati delle visualizzazioni di pagina durante la visita corrente.',
			'^_pk_ref': 'Memorizza i dettagli di attribuzione (referrer/campagna) per i report di Matomo Analytics.',
			mtm_consent: 'Memorizza lo stato di consenso Matomo per questo visitatore.',
			mtm_cookie_consent: 'Memorizza lo stato del consenso ai cookie per Matomo quando viene utilizzata la modalità cookie-consent.',
			ccwps_consent: 'Memorizza le scelte di consenso del visitatore in questo plugin, così le categorie selezionate restano rispettate nei caricamenti successivi della pagina.',
			ccwps_version: 'Memorizza la versione della configurazione del consenso nel plugin per rilevare modifiche e richiedere un nuovo consenso quando necessario.',
		},
	};

	const googleMarketingPresetDescriptions = {
		sk: {
			'__Secure-1PAPISID / 3PAPISID': 'Používa sa na personalizáciu reklám a meranie interakcií. Súvisí s vaším Google účtom.',
			'__Secure-1PSID / 3PSID': 'Obsahujú šifrované informácie o vašom Google ID a poslednom čase prihlásenia. Kľúčové pre vašu identitu.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Slúžia na zabezpečenie a ukladanie preferencií používateľa pri zobrazovaní reklám Google.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Časová pečiatka a zabezpečenie; pomáhajú detegovať podozrivú aktivitu a predchádzať podvodom.',
			'__Secure-BUCKET': 'Interný identifikátor na priraďovanie používateľa k experimentálnym funkciám (Google A/B testy).',
			'ADS_VISITOR_ID': 'Identifikátor návštevníka na sledovanie interakcie s reklamami.',
			'APISID': 'Používa sa na identifikáciu používateľa pri načítaní vložených Google služieb (napr. Maps alebo YouTube) na iných weboch.',
			'SAPISID': 'Bezpečná API verzia identity cookie pre požiadavky na Google služby a prevenciu podvodov.',
			'SID': 'Podpísaný a šifrovaný identifikátor Google účtu používaný na udržiavanie prihlásenej relácie.',
			'SSID': 'Príbuzná cookie k SID nastavená iba pre HTTPS na ochranu relácie v zabezpečenom kontexte.',
			'HSID': 'Používa sa na overenie identity používateľa a prevenciu podvodného použitia prihlasovacích údajov.',
			'NID': 'Ukladá jedinečné ID, preferencie vyhľadávania a informácie o personalizácii reklám.',
			'OTZ': 'Súhrnné štatistiky o návštevnosti a informácie o verzii vyhľadávacieho nástroja.',
			'S': 'Identifikátor relácie pre platobné služby Google (billing UI), ktorý drží stav nákupu alebo fakturácie.',
			'SEARCH_SAMESITE': 'Pomáha chrániť proti CSRF útokom tým, že cookies sú odosielané iba v bezpečnom kontexte.',
			'SIDCC': 'Bezpečnostná cookie pre doručovanie služieb Google a ochranu pred podvodmi.',
		},
		cs: {
			'__Secure-1PAPISID / 3PAPISID': 'Používá se pro personalizaci reklam a měření interakcí. Souvisí s vaším Google účtem.',
			'__Secure-1PSID / 3PSID': 'Obsahují šifrované informace o vašem Google ID a posledním čase přihlášení. Klíčové pro vaši identitu.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Slouží k zabezpečení a ukládání uživatelských preferencí při zobrazování reklam Google.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Časové razítko a zabezpečení; pomáhají odhalovat podezřelou aktivitu a předcházet podvodům.',
			'__Secure-BUCKET': 'Interní identifikátor pro zařazení uživatele do experimentálních funkcí Google (A/B testy).',
			'ADS_VISITOR_ID': 'Identifikátor návštěvníka pro sledování interakce s reklamami.',
			'APISID': 'Používá se k identifikaci uživatele při načtení vložených služeb Google (např. Maps nebo YouTube) na jiných webech.',
			'SAPISID': 'Bezpečná API verze identity cookie pro požadavky na služby Google a prevenci podvodů.',
			'SID': 'Podepsaný a šifrovaný identifikátor Google účtu používaný k udržení přihlášené relace.',
			'SSID': 'Související cookie k SID nastavená pouze přes HTTPS pro ochranu relace v bezpečném kontextu.',
			'HSID': 'Používá se k ověření identity uživatele a prevenci podvodného použití přihlašovacích údajů.',
			'NID': 'Ukládá jedinečné ID, preference vyhledávání a informace o personalizaci reklam.',
			'OTZ': 'Souhrnné statistiky návštěvnosti a informace o verzi vyhledávače.',
			'S': 'Identifikátor relace pro platební služby Google (billing UI), který drží stav nákupu nebo fakturace.',
			'SEARCH_SAMESITE': 'Pomáhá chránit proti CSRF útokům tím, že cookies jsou odesílány pouze v bezpečném kontextu.',
			'SIDCC': 'Bezpečnostní cookie pro doručování služeb Google a ochranu proti podvodům.',
		},
		de: {
			'__Secure-1PAPISID / 3PAPISID': 'Wird zur Personalisierung von Anzeigen und zur Messung von Interaktionen verwendet. Sie ist mit Ihrem Google-Konto verknüpft.',
			'__Secure-1PSID / 3PSID': 'Enthält verschlüsselte Informationen über Ihre Google-ID und den letzten Anmeldezeitpunkt. Wichtig für Ihre Identität.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Dient der Sicherheit und Speicherung von Nutzerpräferenzen bei der Anzeige von Google-Werbung.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Zeitstempel und Sicherheitsinformationen; helfen, verdächtige Aktivitäten zu erkennen und Betrug zu verhindern.',
			'__Secure-BUCKET': 'Interner Identifikator, um Nutzer experimentellen Google-Funktionen (A/B-Tests) zuzuordnen.',
			'ADS_VISITOR_ID': 'Besucher-ID zur Nachverfolgung von Interaktionen mit Anzeigen.',
			'APISID': 'Wird zur Identifizierung genutzt, wenn eingebettete Google-Dienste (z. B. Maps oder YouTube) auf anderen Websites geladen werden.',
			'SAPISID': 'Sichere API-Identitäts-Cookie für Google-Dienstanfragen und Betrugsprävention.',
			'SID': 'Signierte und verschlüsselte Google-Konto-ID zur Aufrechterhaltung angemeldeter Sitzungen.',
			'SSID': 'Verwandte Cookie zu SID, nur über HTTPS gesetzt, um Sitzungen in sicheren Kontexten zu schützen.',
			'HSID': 'Dient zur Überprüfung der Nutzeridentität und zur Verhinderung missbräuchlicher Nutzung von Anmeldedaten.',
			'NID': 'Speichert eindeutige ID, Suchpräferenzen und Informationen zur Anzeigenpersonalisierung.',
			'OTZ': 'Zusammengefasste Nutzungsstatistiken und Informationen zur Version der Suchmaschine.',
			'S': 'Sitzungskennzeichen für Google-Abrechnungsdienste (Billing UI), das Kauf- oder Rechnungsstatus hält.',
			'SEARCH_SAMESITE': 'Hilft beim Schutz vor CSRF-Angriffen, indem Cookies nur in sicheren Kontexten gesendet werden.',
			'SIDCC': 'Sicherheits-Cookie für Google-Dienste und Betrugsschutz.',
		},
		fr: {
			'__Secure-1PAPISID / 3PAPISID': 'Utilisee pour personnaliser les annonces et mesurer les interactions. Liee a votre compte Google.',
			'__Secure-1PSID / 3PSID': 'Contient des informations chiffrees sur votre identifiant Google et l\'heure de votre derniere connexion.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Sert a la securite et au stockage des preferences utilisateur pour l\'affichage des annonces Google.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Horodatage et securite ; aide a detecter les activites suspectes et a prevenir la fraude.',
			'__Secure-BUCKET': 'Identifiant interne pour affecter les utilisateurs a des fonctionnalites experimentales Google (tests A/B).',
			'ADS_VISITOR_ID': 'Identifiant visiteur pour suivre les interactions avec les annonces.',
			'APISID': 'Utilisee pour identifier l\'utilisateur lorsque des services Google integres (Maps, YouTube) sont charges sur d\'autres sites.',
			'SAPISID': 'Version securisee de cookie d\'identite API pour les requetes Google et la prevention de la fraude.',
			'SID': 'Identifiant de compte Google signe et chiffre pour maintenir la session connectee.',
			'SSID': 'Cookie associe a SID, transmis uniquement via HTTPS pour proteger la session.',
			'HSID': 'Utilisee pour verifier l\'identite utilisateur et prevenir l\'utilisation frauduleuse des identifiants.',
			'NID': 'Stocke un identifiant unique, les preferences de recherche et des informations de personnalisation publicitaire.',
			'OTZ': 'Statistiques d\'usage agregees et informations sur la version du moteur de recherche.',
			'S': 'Identifiant de session pour les services de facturation Google (billing UI), conservant l\'etat achat/facturation.',
			'SEARCH_SAMESITE': 'Aide a proteger contre les attaques CSRF en n\'autorisant l\'envoi des cookies que dans un contexte securise.',
			'SIDCC': 'Cookie de securite pour les services Google et la protection contre la fraude.',
		},
		es: {
			'__Secure-1PAPISID / 3PAPISID': 'Se utiliza para personalizar anuncios y medir interacciones. Esta vinculada a tu cuenta de Google.',
			'__Secure-1PSID / 3PSID': 'Contiene informacion cifrada sobre tu ID de Google y el ultimo inicio de sesion.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Se usa para seguridad y para guardar preferencias del usuario en la publicidad de Google.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Marca temporal y seguridad; ayuda a detectar actividad sospechosa y prevenir fraude.',
			'__Secure-BUCKET': 'Identificador interno para asignar usuarios a funciones experimentales de Google (A/B tests).',
			'ADS_VISITOR_ID': 'Identificador de visitante para rastrear interacciones con anuncios.',
			'APISID': 'Se usa para identificar al usuario cuando se cargan servicios Google incrustados (Maps o YouTube) en otros sitios.',
			'SAPISID': 'Version segura de cookie de identidad API para solicitudes de Google y prevencion de fraude.',
			'SID': 'Identificador de cuenta Google firmado y cifrado para mantener sesiones iniciadas.',
			'SSID': 'Cookie relacionada con SID, enviada solo por HTTPS para proteger la sesion.',
			'HSID': 'Se utiliza para verificar la identidad del usuario y prevenir el uso fraudulento de credenciales.',
			'NID': 'Guarda ID unica, preferencias de busqueda e informacion de personalizacion publicitaria.',
			'OTZ': 'Estadisticas agregadas de uso e informacion de version del motor de busqueda.',
			'S': 'Identificador de sesion para servicios de facturacion de Google (billing UI), mantiene estado de compra/facturacion.',
			'SEARCH_SAMESITE': 'Ayuda a proteger contra ataques CSRF asegurando que las cookies se envien solo en contextos seguros.',
			'SIDCC': 'Cookie de seguridad para servicios de Google y proteccion antifraude.',
		},
		pl: {
			'__Secure-1PAPISID / 3PAPISID': 'Uzywana do personalizacji reklam i pomiaru interakcji. Powiazana z Twoim kontem Google.',
			'__Secure-1PSID / 3PSID': 'Zawiera zaszyfrowane informacje o Twoim identyfikatorze Google i czasie ostatniego logowania.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Sluzy do zabezpieczen i zapisywania preferencji uzytkownika przy wyswietlaniu reklam Google.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Znacznik czasu i zabezpieczenia; pomagaja wykrywac podejrzana aktywnosc i zapobiegac oszustwom.',
			'__Secure-BUCKET': 'Wewnetrzny identyfikator przypisujacy uzytkownika do eksperymentalnych funkcji Google (testy A/B).',
			'ADS_VISITOR_ID': 'Identyfikator odwiedzajacego do sledzenia interakcji z reklamami.',
			'APISID': 'Sluzy do identyfikacji uzytkownika, gdy osadzone uslugi Google (np. Maps lub YouTube) laduja sie na innych stronach.',
			'SAPISID': 'Bezpieczna wersja ciasteczka tozsamosci API dla zapytan Google i ochrony przed naduzyciami.',
			'SID': 'Podpisany i szyfrowany identyfikator konta Google utrzymujacy zalogowana sesje.',
			'SSID': 'Powiazane z SID ciasteczko wysylane tylko przez HTTPS dla ochrony sesji.',
			'HSID': 'Uzywane do weryfikacji tozsamosci uzytkownika i zapobiegania oszustwom z danymi logowania.',
			'NID': 'Przechowuje unikalny ID, preferencje wyszukiwania i informacje o personalizacji reklam.',
			'OTZ': 'Zbiorcze statystyki ruchu i informacje o wersji wyszukiwarki.',
			'S': 'Identyfikator sesji dla uslug rozliczeniowych Google (billing UI), przechowuje stan zakupu/rozliczenia.',
			'SEARCH_SAMESITE': 'Pomaga chronic przed atakami CSRF, zapewniajac wysylanie cookies tylko w bezpiecznym kontekscie.',
			'SIDCC': 'Ciasteczko bezpieczenstwa dla uslug Google i ochrony przed naduzyciami.',
		},
		hu: {
			'__Secure-1PAPISID / 3PAPISID': 'Hirdetesek szemelyre szabasa es interakciok merese celjabol hasznaljak. Osszefugg a Google-fiokkal.',
			'__Secure-1PSID / 3PSID': 'Titkositott informaciokat tartalmaz a Google-azonositorol es az utolso bejelentkezes idejerol.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Biztonsagi es felhasznaloi preferencia tarolasi celokat szolgal a Google-hirdeteseknel.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Idobelyeg es biztonsagi funkcio; segit a gyanus tevekenyseg felismereseben es a csalások megelozeseben.',
			'__Secure-BUCKET': 'Belso azonosito, amely a felhasznalot Google kiserleti funkciokhoz (A/B tesztekhez) rendeli.',
			'ADS_VISITOR_ID': 'Latogatoi azonosito a hirdetes-interakciok kovetesehez.',
			'APISID': 'A felhasznalo azonositasa celjabol hasznaljak, amikor beagyazott Google-szolgaltatasok (pl. Maps vagy YouTube) toltodnek be mas oldalakon.',
			'SAPISID': 'Biztonsagos API-identitas cookie Google-keresekhez es csalásmegelozeshez.',
			'SID': 'Alairt es titkositott Google-fiokazonosito a bejelentkezett munkamenet fenntartasahoz.',
			'SSID': 'SID-hez kapcsolodo cookie, amelyet csak HTTPS-en kuldenek a munkamenet vedelme erdekeben.',
			'HSID': 'A felhasznalo azonossaganak ellenorzesere es a bejelentkezesi adatokkal valo visszaeles megelozesere szolgal.',
			'NID': 'Egyedi azonosítot, keresesi preferenciakat es hirdetes-szemelyreszabasi informaciokat tarol.',
			'OTZ': 'Osszesitett hasznalati statisztikak es a keresomotor verzioinformacioi.',
			'S': 'Munkamenet-azonosito a Google szamlazasi feluleteihez (billing UI), a vasarlasi/szamlazasi allapot tarolasahoz.',
			'SEARCH_SAMESITE': 'Segit vedeni a CSRF tamadasok ellen azzal, hogy a cookie-k csak biztonsagos kontextusban kuldhetok.',
			'SIDCC': 'Biztonsagi cookie a Google szolgaltatasokhoz es a csalás elleni vedelemhez.',
		},
		it: {
			'__Secure-1PAPISID / 3PAPISID': 'Usato per la personalizzazione degli annunci e la misurazione delle interazioni. Collegato al tuo account Google.',
			'__Secure-1PSID / 3PSID': 'Contiene informazioni cifrate sul tuo ID Google e sull\'ora dell\'ultimo accesso.',
			'__Secure-1PSIDCC / 3PSIDCC': 'Serve per sicurezza e salvataggio delle preferenze utente nella pubblicita Google.',
			'__Secure-1PSIDTS / 3PSIDTS': 'Timestamp e sicurezza; aiuta a rilevare attivita sospette e prevenire frodi.',
			'__Secure-BUCKET': 'Identificatore interno per assegnare gli utenti a funzionalita sperimentali Google (test A/B).',
			'ADS_VISITOR_ID': 'Identificatore visitatore per tracciare le interazioni con gli annunci.',
			'APISID': 'Usato per identificare l\'utente quando servizi Google incorporati (come Maps o YouTube) vengono caricati su altri siti.',
			'SAPISID': 'Versione sicura del cookie di identita API per richieste ai servizi Google e prevenzione frodi.',
			'SID': 'Identificatore account Google firmato e cifrato usato per mantenere la sessione autenticata.',
			'SSID': 'Cookie correlato a SID inviato solo via HTTPS per proteggere la sessione.',
			'HSID': 'Usato per verificare l\'identita utente e prevenire l\'uso fraudolento delle credenziali di accesso.',
			'NID': 'Memorizza ID univoco, preferenze di ricerca e informazioni di personalizzazione pubblicitaria.',
			'OTZ': 'Statistiche aggregate di utilizzo e informazioni sulla versione del motore di ricerca.',
			'S': 'Identificatore di sessione per i servizi di fatturazione Google (billing UI), mantiene lo stato di acquisto/fatturazione.',
			'SEARCH_SAMESITE': 'Aiuta a proteggere dagli attacchi CSRF garantendo che i cookie siano inviati solo in contesti sicuri.',
			'SIDCC': 'Cookie di sicurezza per i servizi Google e la protezione antifrode.',
		},
	};

	function translatePresetDuration(value) {
		const variants = presetDurationTranslations[String(value || '')] || {};
		return variants[adminLang] || variants.en || value;
	}

	function localizePresetCookie(cookie) {
		const localizedDescriptions = {
			...(presetCookieDescriptions[adminLang] || {}),
			...(addonPresetCookieDescriptions[adminLang] || {}),
			...(googleMarketingPresetDescriptions[adminLang] || {}),
		};
		const cookieName = String(cookie?.name || '');

		return {
			...cookie,
			expiration: translatePresetDuration(cookie.expiration),
			description: localizedDescriptions[cookieName] || cookie.description,
		};
	}

	Object.keys(cookiePresetGroups).forEach((groupKey) => {
		cookiePresetGroups[groupKey] = {
			...cookiePresetGroups[groupKey],
			cookies: (cookiePresetGroups[groupKey].cookies || []).map(localizePresetCookie),
		};
	});

	const localizedAlwaysPluginCookies = alwaysPluginCookies.map(localizePresetCookie);

	function localizeAdminCookieListDescriptions() {
		$('.ccwps-cookies-row').each(function () {
			const $row = $(this);
			const cookieName = String($row.data('cookie-name') || '');
			if (!cookieName) return;

			const localizedDescriptions = {
				...(presetCookieDescriptions[adminLang] || {}),
				...(addonPresetCookieDescriptions[adminLang] || {}),
				...(googleMarketingPresetDescriptions[adminLang] || {}),
			};

			const translatedDescription = localizedDescriptions[cookieName];
			if (!translatedDescription) return;

			const preview = translatedDescription.length > 70
				? translatedDescription.slice(0, 70) + '…'
				: translatedDescription;

			const $descCell = $row.find('.ccwps-desc-preview');
			$descCell.text(preview);
			$descCell.closest('td').attr('title', translatedDescription);
		});
	}

	localizeAdminCookieListDescriptions();

	/* ---- Notice ---- */
	function showNotice(msg, type = 'success') {
		const $n = $('#ccwps-notice');
		$n.text(msg).attr('class', 'ccwps-notice ' + type).show();
		setTimeout(() => $n.fadeOut(), 4000);
	}

	function ajaxPost(action, data, cb) {
		$.post(ajaxUrl, { action, nonce, ...data }, cb).fail(() => showNotice(i18n.error, 'error'));
	}

	function replaceDomainPlaceholder(domainValue) {
		if (domainValue === '.yourdomain.com') return dottedHost;
		if (domainValue === 'yourdomain.com') return normalizedHost;
		return domainValue;
	}

	function getExistingCookieNames() {
		const existingNames = new Set();
		Object.values(cookies || {}).forEach((list) => {
			(list || []).forEach((item) => {
				if (item && item.name) {
					existingNames.add(String(item.name).toLowerCase());
				}
			});
		});
		return existingNames;
	}

	function getBlockSignature(rule) {
		return [
			String(rule.script_source || '').trim().toLowerCase(),
			String(rule.category || '').trim().toLowerCase(),
			rule.is_regex ? '1' : '0',
		].join('|');
	}

	function getExistingBlockSignatures() {
		const existing = new Set();
		(blockRulesData || []).forEach((rule) => {
			existing.add(getBlockSignature(rule));
		});
		return existing;
	}

	function runSequentialSaves(items, saveAction, toPayload, done) {
		const saveNext = (index) => {
			if (index >= items.length) {
				done();
				return;
			}

			ajaxPost(saveAction, toPayload(items[index]), function (res) {
				if (!res.success) {
					showNotice(res.data || i18n.error, 'error');
					return;
				}
				saveNext(index + 1);
			});
		};

		saveNext(0);
	}

	/* ---- Color pickers ---- */
	$('.ccwps-color-picker').wpColorPicker({
		change: function(event, ui) {
			var key = $(this).attr('name');
			var chk = $('[data-target="' + key + '"]');
			if (chk.length) chk.prop('checked', false);
		}
	});

	function setColorPickerValue($picker, value) {
		if (!$picker.length) return;
		$picker.val(value);
		if (typeof $picker.wpColorPicker === 'function') {
			$picker.wpColorPicker('color', value);
		}
		$picker.trigger('change');
	}

	/* ---- Transparent checkbox ---- */
	$(document).on('change', '.ccwps-transparent-check', function () {
		var key = $(this).data('target');
		var $picker = $('#' + key);
		if ($(this).is(':checked')) {
			setColorPickerValue($picker, 'transparent');
		}
	});

	$(document).on('click', '.ccwps-color-reset', function () {
		var key = $(this).data('target');
		var def = $(this).data('default');
		var $picker = $('#' + key);
		if (!$picker.length) return;

		setColorPickerValue($picker, String(def || ''));

		var $transparent = $('.ccwps-transparent-check[data-target="' + key + '"]');
		if ($transparent.length) {
			$transparent.prop('checked', String(def) === 'transparent');
		}
	});

	/* ---- Radio highlight ---- */
	$(document).on('change', '.ccwps-radio-option input[type="radio"]', function () {
		$(this).closest('.ccwps-radio-group').find('.ccwps-radio-option').removeClass('selected');
		$(this).closest('.ccwps-radio-option').addClass('selected');
	});

	/* ---- Layout picker ---- */
	$(document).on('change', '.ccwps-layout-opt input[type="radio"]', function () {
		$('.ccwps-layout-opt').removeClass('active');
		$(this).closest('.ccwps-layout-opt').addClass('active');
	});

	function syncBarPositionOptions(forceBarDefault = false) {
		const $position = $('#banner_position');
		if (!$position.length) return;

		const layout = $('input[name="banner_layout"]:checked').val();
		const allowedBarPositions = ['top-center', 'bottom-center'];

		$position.find('option').each(function () {
			const $option = $(this);
			const value = String($option.val() || '');

			if (layout === 'bar') {
				const isAllowed = allowedBarPositions.includes(value);
				$option.prop('hidden', !isAllowed);
				$option.prop('disabled', !isAllowed);
			} else {
				$option.prop('hidden', false);
				$option.prop('disabled', false);
			}
		});

		if (layout === 'bar') {
			const current = String($position.val() || '');
			if (forceBarDefault || !allowedBarPositions.includes(current)) {
				$position.val('bottom-center');
			}
		} else if (!$position.find('option[value="' + $position.val() + '"]').length) {
			$position.val('bottom-center');
		}
	}

	$(document).on('change', 'input[name="banner_layout"]', function () {
		syncBarPositionOptions($(this).val() === 'bar');
	});

	syncBarPositionOptions(false);

	$(document).on('click', '.ccwps-reset-appearance', function () {
		if (!appearanceDefaults || typeof appearanceDefaults !== 'object') return;
		if (!confirm(i18n.confirmReset || 'Resetovať všetky nastavenia na predvolené hodnoty?')) return;

		Object.entries(appearanceDefaults).forEach(function ([key, value]) {
			var strVal = String(value ?? '');
			var $fields = $('#ccwps-settings-form').find('[name="' + key + '"]');
			if (!$fields.length) return;

			var $first = $fields.first();
			if ($first.hasClass('ccwps-color-picker')) {
				setColorPickerValue($first, strVal);
				var $transparent = $('.ccwps-transparent-check[data-target="' + key + '"]');
				if ($transparent.length) {
					$transparent.prop('checked', strVal === 'transparent');
				}
				return;
			}

			if ($first.is(':checkbox')) {
				$first.prop('checked', strVal === '1').trigger('change');
				return;
			}

			if ($first.is(':radio')) {
				$fields.filter('[value="' + strVal + '"]').prop('checked', true).trigger('change');
				return;
			}

			$first.val(strVal).trigger('change');
		});

		$('.ccwps-radio-option input[type="radio"]:checked').trigger('change');
		$('.ccwps-layout-opt input[type="radio"]:checked').trigger('change');
		syncBarPositionOptions(false);
		showNotice(i18n.resetDone || 'Nastavenia boli resetované.');
	});

	/* =====================
	   SAVE SETTINGS
	   ===================== */
	$(document).on('click', '.ccwps-save-settings', function () {
		const $btn = $(this).prop('disabled', true).text(i18n.saving || 'Saving...');
		const data = {};

		$('#ccwps-settings-form').find('[name]').each(function () {
			const el  = $(this);
			const key = el.attr('name');
			if (!key) return;
			if (el.is(':checkbox')) {
				data[key] = el.is(':checked') ? '1' : '0';
			} else if (el.is(':radio')) {
				if (el.is(':checked')) data[key] = el.val();
			} else {
				data[key] = el.val();
			}
		});

		// WP color picker stores value separately
		$('.ccwps-color-picker').each(function () {
			const key = $(this).attr('name');
			if (key) data[key] = $(this).val();
		});

		ajaxPost('ccwps_save_settings', { settings: data }, function (res) {
			$btn.prop('disabled', false).text(i18n.saveSettings || 'Save settings');
			res.success ? showNotice(i18n.saved) : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   ADMIN FLAG PICKER
	   ===================== */
	// Toggle dropdown — position fixed to escape sidebar overflow
	$(document).on('click', '.ccwps-flag-picker-current', function (e) {
		e.stopPropagation();
		const $picker   = $(this).closest('.ccwps-flag-picker');
		const $dropdown = $picker.find('.ccwps-flag-picker-dropdown');
		const isOpen    = $picker.hasClass('open');

		// Close any other open pickers
		$('.ccwps-flag-picker').removeClass('open');

		if (!isOpen) {
			// Position the dropdown using viewport coordinates
			const rect = this.getBoundingClientRect();
			$dropdown.css({
				top:   rect.bottom + 4,
				left:  rect.left,
				width: rect.width
			});
			$picker.addClass('open');
		}
	});

	// Close on outside click
	$(document).on('click', function (e) {
		if (!$(e.target).closest('.ccwps-flag-picker').length) {
			$('.ccwps-flag-picker').removeClass('open');
		}
	});

	// Select language
	$(document).on('click', '.ccwps-fp-option', function () {
		const lang    = $(this).data('lang');
		const $picker = $(this).closest('.ccwps-flag-picker');
		const current = $picker.data('current');
		if (lang === current) { $picker.removeClass('open'); return; }

		if (!confirm(i18n.confirmLangChange || 'Change admin language and apply frontend translations?')) {
			$picker.removeClass('open');
			return;
		}
		$picker.removeClass('open');

		ajaxPost('ccwps_save_admin_lang', { lang }, function (res) {
			if (res.success) {
				window.location.reload();
			} else {
				showNotice(i18n.error, 'error');
			}
		});
	});

	/* =====================
	   TRANSLATION LANG PRESETS
	   ===================== */
	$(document).on('click', '.ccwps-lang-btn', function () {
		const lang = $(this).data('lang');
		const preset = langPresets && langPresets[lang];
		if (!preset || !preset.strings) return;

		Object.entries(preset.strings).forEach(([key, value]) => {
			const $el = $('[data-lang-key="' + key + '"]');
			if ($el.length) $el.val(value);
		});

		$('.ccwps-lang-btn').removeClass('active');
		$(this).addClass('active');
		showNotice(i18n.langApplied);
	});

	/* =====================
	   CLEAR LOG
	   ===================== */
	$(document).on('click', '#ccwps-clear-log', function () {
		if (!confirm(i18n.confirmClear)) return;
		ajaxPost('ccwps_clear_log', {}, function (res) {
			if (res.success) { showNotice(i18n.logCleared); setTimeout(() => location.reload(), 1200); }
		});
	});

	/* =====================
	   RESET SETTINGS
	   ===================== */
	$(document).on('click', '#ccwps-reset-defaults', function () {
		if (!confirm(i18n.confirmReset)) return;
		ajaxPost('ccwps_reset_settings', {}, function (res) {
			if (res.success) { showNotice(i18n.resetDone); setTimeout(() => location.reload(), 1400); }
		});
	});

	/* =====================
	   COOKIE MANAGEMENT
	   ===================== */
	function isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
	}

	function openRequestPresetModal() {
		$('#ccwps-request-email').val('');
		$('#ccwps-request-subject').val('');
		$('#ccwps-request-message').val('');
		$('#ccwps-request-company').val('');
		$('#ccwps-request-preset-modal').show();
	}

	$(document).on('click', '#ccwps-request-preset', function () {
		openRequestPresetModal();
	});

	$(document).on('click', '#ccwps-send-preset-request', function () {
		const $btn = $(this);
		const email = String($('#ccwps-request-email').val() || '').trim();
		const subject = String($('#ccwps-request-subject').val() || '').trim();
		const message = String($('#ccwps-request-message').val() || '').trim();
		const honeypot = String($('#ccwps-request-company').val() || '').trim();

		if (!isValidEmail(email) || email.length > 190) {
			showNotice(i18n.requestPresetEmailInvalid || 'Zadajte platný e-mail.', 'error');
			return;
		}

		if (subject.length < 3 || subject.length > 150) {
			showNotice(i18n.requestPresetSubjectInvalid || 'Predmet musí mať 3 až 150 znakov.', 'error');
			return;
		}

		if (message.length < 20 || message.length > 4000) {
			showNotice(i18n.requestPresetMessageInvalid || 'Text správy musí mať 20 až 4000 znakov.', 'error');
			return;
		}

		$btn.prop('disabled', true).text(i18n.sendingRequest || 'Odosielam žiadosť…');

		ajaxPost('ccwps_request_cookie_preset', {
			email,
			subject,
			message,
			company: honeypot,
		}, function (res) {
			$btn.prop('disabled', false).text(i18n.requestPresetSend || 'Odoslať žiadosť');

			if (res.success) {
				$('#ccwps-request-preset-modal').hide();
				showNotice(res.data || i18n.requestPresetSent || 'Žiadosť bola úspešne odoslaná.');
				return;
			}

			showNotice(res.data || i18n.error, 'error');
		});
	});

	function openCookieModal(data = {}) {
		const isEdit = !!data.id;
		$('#ccwps-cookie-modal-title').text(isEdit ? (i18n.editCookie || 'Edit cookie') : (i18n.addCookie || 'Add cookie'));
		$('#c-preset').val('');
		$('#ccwps-cookie-id').val(data.id || '');
		$('#c-name').val(data.name || '');
		$('#c-domain').val(data.domain || '');
		$('#c-expiration').val(data.expiration || '');
		$('#c-path').val(data.path || '/');
		$('#c-description').val(data.description || '');
		$('#c-category').val(data.category || 'necessary');
		$('#c-is-regex').prop('checked', !!+data.is_regex);
		$('#ccwps-cookie-modal').show();
	}

	$(document).on('click', '#ccwps-add-cookie', () => openCookieModal());
	$(document).on('click', '.ccwps-edit-cookie', function () { openCookieModal($(this).data('row')); });

	$(document).on('click', '#ccwps-apply-cookie-preset', function () {
		const groupKey = $('#c-preset').val();
		const preset = groupKey ? cookiePresetGroups[groupKey] : null;
		if (!preset) {
			alert(i18n.selectPreset || 'Vyberte predvoľbu.');
			return;
		}

		const existingNames = getExistingCookieNames();
		const existingBlockSignatures = getExistingBlockSignatures();

		const requested = [
			...localizedAlwaysPluginCookies,
			...preset.cookies,
		].map((item) => ({
			...item,
			domain: replaceDomainPlaceholder(item.domain),
		})).filter((item) => !existingNames.has(String(item.name).toLowerCase()));

		const requestedBlockRules = (preset.blockPresets || [])
			.flatMap((key) => blockPresets[key] || [])
			.filter((rule) => !existingBlockSignatures.has(getBlockSignature(rule)));

		if (!requested.length && !requestedBlockRules.length) {
			showNotice(i18n.noNewPresets || 'Všetky vybrané predvoľby už existujú.', 'success');
			return;
		}

		runSequentialSaves(requested, 'ccwps_save_cookie', (item) => ({
			id: '',
			name: item.name,
			domain: item.domain,
			expiration: item.expiration,
			path: item.path,
			description: item.description,
			category: item.category,
			is_regex: item.is_regex,
		}), function () {
			runSequentialSaves(requestedBlockRules, 'ccwps_save_block', (rule) => ({
				id: '',
				script_source: rule.script_source,
				category: rule.category,
				is_regex: rule.is_regex ? '1' : '',
			}), function () {
				showNotice(i18n.presetsAdded || 'Predvoľby boli pridané.');
				setTimeout(() => location.reload(), 600);
			});
		});
	});

	$(document).on('click', '#ccwps-save-cookie', function () {
		const name = $('#c-name').val().trim();
		if (!name) { alert(i18n.enterCookieName || 'Enter cookie name.'); return; }
		const d = {
			id: $('#ccwps-cookie-id').val(),
			name, domain: $('#c-domain').val(), expiration: $('#c-expiration').val(),
			path: $('#c-path').val(), description: $('#c-description').val(),
			category: $('#c-category').val(), is_regex: $('#c-is-regex').is(':checked') ? '1' : '',
		};
		ajaxPost('ccwps_save_cookie', d, function (res) {
			if (res.success) { $('#ccwps-cookie-modal').hide(); location.reload(); }
			else showNotice(res.data || i18n.error, 'error');
		});
	});

	$(document).on('click', '.ccwps-delete-cookie', function () {
		if (!confirm(i18n.confirmDelete)) return;
		ajaxPost('ccwps_delete_cookie', { id: $(this).data('id') }, function (res) {
			res.success ? location.reload() : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   BLOCK MANAGEMENT
	   ===================== */
	function openBlockModal(data = {}) {
		const isEdit = !!data.id;
		$('#ccwps-block-modal-title').text(isEdit ? (i18n.editRule || 'Edit rule') : (i18n.addRule || 'Add rule'));
		$('#b-preset').val('');
		$('#ccwps-block-id').val(data.id || '');
		$('#b-source').val(data.script_source || '');
		$('#b-category').val(data.category || 'analytics');
		$('#b-is-regex').prop('checked', !!+data.is_regex);
		$('#ccwps-block-modal').show();
	}

	$(document).on('click', '#ccwps-add-block', () => openBlockModal());
	$(document).on('click', '.ccwps-edit-block', function () { openBlockModal($(this).data('row')); });

	$(document).on('click', '#ccwps-apply-block-preset', function () {
		const key = $('#b-preset').val();
		const presetRules = key ? (blockPresets[key] || []) : [];
		if (!presetRules.length) {
			alert(i18n.selectPreset || 'Vyberte predvoľbu.');
			return;
		}

		const existingBlockSignatures = getExistingBlockSignatures();
		const requestedRules = presetRules.filter((rule) => !existingBlockSignatures.has(getBlockSignature(rule)));

		if (!requestedRules.length) {
			showNotice(i18n.noNewPresets || 'Všetky vybrané predvoľby už existujú.', 'success');
			return;
		}

		runSequentialSaves(requestedRules, 'ccwps_save_block', (rule) => ({
			id: '',
			script_source: rule.script_source,
			category: rule.category,
			is_regex: rule.is_regex ? '1' : '',
		}), function () {
			showNotice(i18n.presetsAdded || 'Predvoľby boli pridané.');
			setTimeout(() => location.reload(), 600);
		});
	});

	$(document).on('click', '#ccwps-save-block', function () {
		const source = $('#b-source').val().trim();
		if (!source) { alert(i18n.enterScriptSource || 'Enter script source.'); return; }
		const d = {
			id: $('#ccwps-block-id').val(), script_source: source,
			category: $('#b-category').val(), is_regex: $('#b-is-regex').is(':checked') ? '1' : '',
		};
		ajaxPost('ccwps_save_block', d, function (res) {
			if (res.success) { $('#ccwps-block-modal').hide(); location.reload(); }
			else showNotice(res.data || i18n.error, 'error');
		});
	});

	$(document).on('click', '.ccwps-delete-block', function () {
		if (!confirm(i18n.confirmDelete)) return;
		ajaxPost('ccwps_delete_block', { id: $(this).data('id') }, function (res) {
			res.success ? location.reload() : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   MODAL CLOSE
	   ===================== */
	$(document).on('click', '.ccwps-modal-close', function () { $(this).closest('.ccwps-modal').hide(); });
	$(document).on('click', '.ccwps-modal', function (e) { if ($(e.target).hasClass('ccwps-modal')) $(this).hide(); });
	$(document).on('keydown', function (e) { if (e.key === 'Escape') $('.ccwps-modal:visible').hide(); });

	$(document).on('click', '.ccwps-spacing-trigger', function () {
		const focus = String($(this).attr('data-ccwps-spacing-focus') || '').trim();
		const $modal = $('#ccwps-spacing-modal');
		const $sections = $modal.find('[data-ccwps-spacing-group]');

		hydrateSpacingModalValues();

		if ($sections.length) {
			$sections.attr('hidden', true);
		}
		$modal.show();

		if (!focus) {
			$sections.first().removeAttr('hidden');
			return;
		}

		const $target = $modal.find('[data-ccwps-spacing-group="' + focus + '"]');
		if ($target.length) {
			$target.removeAttr('hidden');
			$target.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
			return;
		}

		$sections.first().removeAttr('hidden');
	});

	$(document).on('click', '.ccwps-reset-spacing-only', function () {
		if (!appearanceDefaults || typeof appearanceDefaults !== 'object') return;
		if (!confirm(i18n.confirmSpacingReset || 'Resetovať iba nastavenia vizuálnych medzier na predvolené hodnoty?')) return;

		spacingSettingKeys.forEach(function (key) {
			if (!Object.prototype.hasOwnProperty.call(appearanceDefaults, key)) return;
			var $field = $('#ccwps-settings-form').find('[name="' + key + '"]').first();
			if (!$field.length) return;
			$field.val(String(appearanceDefaults[key] ?? '')).trigger('change');
		});

		showNotice(i18n.spacingResetDone || 'Vizuálne medzery boli resetované.');
	});

	/* =====================
	   GTM SCREENSHOT LIGHTBOX
	   ===================== */
	function closeGtmLightbox() {
		const $lightbox = $('#ccwps-gtm-lightbox');
		$lightbox.attr('hidden', true);
		$lightbox.find('.ccwps-gtm-lightbox-image').attr({ src: '', alt: '' });
		$lightbox.find('.ccwps-gtm-lightbox-caption').text('');
		$('body').removeClass('ccwps-lightbox-open');
	}

	$(document).on('click', '.ccwps-gtm-shot-trigger', function () {
		const image = $(this).data('ccwps-lightbox-image');
		const caption = $(this).data('ccwps-lightbox-caption') || '';
		const $lightbox = $('#ccwps-gtm-lightbox');
		$lightbox.find('.ccwps-gtm-lightbox-image').attr({ src: image, alt: caption });
		$lightbox.find('.ccwps-gtm-lightbox-caption').text(caption);
		$lightbox.removeAttr('hidden');
		$('body').addClass('ccwps-lightbox-open');
	});

	$(document).on('click', '[data-ccwps-lightbox-close]', function () {
		closeGtmLightbox();
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && !$('#ccwps-gtm-lightbox').is('[hidden]')) {
			closeGtmLightbox();
		}
		if (e.key === 'Escape' && !$('#ccwps-matomo-lightbox').is('[hidden]')) {
			closeMatomoLightbox();
		}
	});

	/* =====================
	   MATOMO SCREENSHOT LIGHTBOX
	   ===================== */
	function closeMatomoLightbox() {
		const $lightbox = $('#ccwps-matomo-lightbox');
		$lightbox.attr('hidden', true);
		$lightbox.find('.ccwps-gtm-lightbox-image').attr({ src: '', alt: '' });
		$lightbox.find('.ccwps-gtm-lightbox-caption').text('');
		$('body').removeClass('ccwps-lightbox-open');
	}

	$(document).on('click', '.ccwps-matomo-shot-trigger', function () {
		const image = $(this).data('ccwps-matomo-image');
		const caption = $(this).data('ccwps-matomo-caption') || '';
		const $lightbox = $('#ccwps-matomo-lightbox');
		$lightbox.find('.ccwps-gtm-lightbox-image').attr({ src: image, alt: caption });
		$lightbox.find('.ccwps-gtm-lightbox-caption').text(caption);
		$lightbox.removeAttr('hidden');
		$('body').addClass('ccwps-lightbox-open');
	});

	$(document).on('click', '[data-ccwps-matomo-lightbox-close]', function () {
		closeMatomoLightbox();
	});

	/* =====================
	   PREVIEW BANNER / MODAL
	   ===================== */
	$(document).on('click', '#ccwps-preview-banner', function () {
		const url = siteUrl + '?ccwps_preview=banner&t=' + Date.now();
		window.open(url, '_blank', 'width=1200,height=800');
	});

	$(document).on('click', '#ccwps-preview-modal', function () {
		const url = siteUrl + '?ccwps_preview=modal&t=' + Date.now();
		window.open(url, '_blank', 'width=1200,height=800');
	});

	/* =====================
	   COOKIE LIST PREVIEW (in admin modal)
	   ===================== */
	$(document).on('click', '#ccwps-preview-cookie-list', function () {
		const cookiesData = window.ccwpsAdmin.cookies || {};
		let html = '';

		const catLabels = {
			necessary: i18n.catNecessary,
			analytics: i18n.catAnalytics,
			targeting: i18n.catTargeting,
			preferences: i18n.catPreferences,
		};

		Object.entries(cookiesData).forEach(([cat, list]) => {
			if (!list.length) return;
			html += `<h3 style="margin:14px 0 8px;font-size:15px;">${catLabels[cat] || cat}</h3>`;
			html += `<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:13px;">
				<thead><tr>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColName || ''}</th>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColDomain || ''}</th>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColExpiration || ''}</th>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColDescription || ''}</th>
				</tr></thead><tbody>`;
			list.forEach(ck => {
				html += `<tr>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;"><code>${ck.name}</code></td>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">${ck.domain || '—'}</td>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">${ck.expiration || '—'}</td>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">${ck.desc || '—'}</td>
				</tr>`;
			});
			html += '</tbody></table></div>';
		});

		if (!html) html = '<p style="padding:16px;color:#6b7280;">' + (i18n.cookieListEmpty || 'No cookies are declared.') + '</p>';
		$('#ccwps-cookie-list-preview').html(html);
		$('#ccwps-cookie-list-modal').show();
	});

	/* =====================
	   COPY SHORTCODE
	   ===================== */
	$(document).on('click', '.ccwps-copy-btn', function () {
		const text = $(this).data('copy');
		navigator.clipboard ? navigator.clipboard.writeText(text) : (() => {
			const ta = document.createElement('textarea');
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
		})();
		const $btn = $(this);
		$btn.text(i18n.copied || 'Copied!');
		setTimeout(() => $btn.text(i18n.copy || 'Copy'), 2000);
	});

	/* =====================
	   FILE DROP (Import)
	   ===================== */
	const $drop  = $('#ccwps-file-drop');
	const $input = $('#ccwps-import-file');
	const $btn   = $('#ccwps-import-btn');
	const $name  = $('#ccwps-file-name');

	if ($drop.length) {
		function handleFile(file) {
			if (!file) return;
			$name.text(file.name);
			$drop.addClass('has-file');
			$btn.prop('disabled', false);
		}
		$input.on('change', function () { handleFile(this.files[0]); });
		$drop.on('dragover dragenter', function (e) { e.preventDefault(); $(this).addClass('drag-over'); })
		     .on('dragleave drop',    function (e) {
			e.preventDefault();
			$(this).removeClass('drag-over');
			if (e.type === 'drop') {
				$input[0].files = e.originalEvent.dataTransfer.files;
				handleFile(e.originalEvent.dataTransfer.files[0]);
			}
		});
	}

	/* =====================
	   BULK ACTIONS (Cookies)
	   ===================== */
	function updateCookiesBulkUI() {
		const $checkboxes = $('.ccwps-cookie-checkbox:checked');
		const count = $checkboxes.length;
		const $actions = $('#ccwps-cookies-bulk-actions');
		if (count > 0) {
			$('#ccwps-cookies-bulk-count').text(`(${count} ${count === 1 ? 'položka' : 'položiek'})`);
			$actions.show();
		} else {
			$actions.hide();
		}
	}

	$(document).on('change', '.ccwps-cookie-checkbox', function () {
		updateCookiesBulkUI();
	});

	$(document).on('change', '.ccwps-cookies-select-all', function () {
		const checked = $(this).is(':checked');
		$('.ccwps-cookie-checkbox').prop('checked', checked);
		updateCookiesBulkUI();
	});

	$(document).on('click', '#ccwps-delete-cookies-bulk', function () {
		const ids = $('.ccwps-cookie-checkbox:checked').map((i, el) => $(el).data('id')).get();
		if (!ids.length) return;
		if (!confirm(i18n.confirmDelete || 'Naozaj?')) return;
		ajaxPost('ccwps_delete_cookies_bulk', { ids }, function (res) {
			res.success ? location.reload() : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   BULK ACTIONS (Blocks)
	   ===================== */
	function updateBlocksBulkUI() {
		const $checkboxes = $('.ccwps-block-checkbox:checked');
		const count = $checkboxes.length;
		const $actions = $('#ccwps-blocks-bulk-actions');
		if (count > 0) {
			$('#ccwps-blocks-bulk-count').text(`(${count} ${count === 1 ? 'položka' : 'položiek'})`);
			$actions.show();
		} else {
			$actions.hide();
		}
	}

	$(document).on('change', '.ccwps-block-checkbox', function () {
		updateBlocksBulkUI();
	});

	$(document).on('change', '.ccwps-blocks-select-all', function () {
		const checked = $(this).is(':checked');
		$('.ccwps-block-checkbox').prop('checked', checked);
		updateBlocksBulkUI();
	});

	$(document).on('click', '#ccwps-delete-blocks-bulk', function () {
		const ids = $('.ccwps-block-checkbox:checked').map((i, el) => $(el).data('id')).get();
		if (!ids.length) return;
		if (!confirm(i18n.confirmDelete || 'Naozaj?')) return;
		ajaxPost('ccwps_delete_blocks_bulk', { ids }, function (res) {
			res.success ? location.reload() : showNotice(i18n.error, 'error');
		});
	});

}(jQuery));

/* ============================================
   CUSTOM ICON UPLOAD (Media Library)
   ============================================ */
(function ($) {
	var i18n = (window.ccwpsAdmin || {}).i18n || {};

	/* Show/hide custom icon row based on icon type select */
	$(document).on('change', '.ccwps-icon-type-select', function () {
		var val = $(this).val();
		if (val === 'custom') {
			$('#ccwps-custom-icon-row').show();
		} else {
			$('#ccwps-custom-icon-row').hide();
		}
	});

	var mediaFrame = null;

	$(document).on('click', '#ccwps-icon-upload-btn', function (e) {
		e.preventDefault();

		// Reuse existing frame if open
		if (mediaFrame) {
			mediaFrame.open();
			return;
		}

		// Create WP media frame
		mediaFrame = wp.media({
			title:    i18n.mediaTitle || 'Select custom icon',
			button:   { text: i18n.mediaButton || 'Use this image' },
			multiple: false,
			library:  { type: [ 'image' ] }
		});

		mediaFrame.on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			var url = attachment.url;

			// Update hidden input
			$('#icon_custom_url').val(url);

			// Update preview
			var $preview = $('#ccwps-icon-preview');
			$preview
				.removeClass('ccwps-icon-preview--empty')
				.html('<img src="' + url + '" alt="' + (i18n.customIconAlt || 'Custom icon') + '">');

			// Show remove button
			$('#ccwps-icon-remove-btn').show();
		});

		mediaFrame.open();
	});

	$(document).on('click', '#ccwps-icon-remove-btn', function (e) {
		e.preventDefault();
		$('#icon_custom_url').val('');
		$('#ccwps-icon-preview')
			.addClass('ccwps-icon-preview--empty')
			.html('<span>' + (i18n.noImage || 'No image') + '</span>');
		$(this).hide();
		mediaFrame = null;
	});

}(jQuery));

/* ============================================
   BANNER LOGO UPLOAD (Media Library)
   ============================================ */
(function ($) {
	var i18n = (window.ccwpsAdmin || {}).i18n || {};

	/* Show/hide logo rows based on toggle */
	$(document).on('change', 'input[name="banner_logo_show"]', function () {
		var checked = $(this).is(':checked');
		$('#ccwps-banner-logo-fields, #ccwps-banner-logo-url-row, #ccwps-banner-logo-width-row').toggle(checked);
	});

	/* Show/hide legal link rows based on toggles */
	$(document).on('change', 'input[name="add_cookie_policy_link"]', function () {
		var checked = $(this).is(':checked');
		$('#ccwps-cookie-policy-link-row, #ccwps-cookie-policy-text-row').toggle(checked);
	});

	$(document).on('change', 'input[name="add_privacy_policy_link"]', function () {
		var checked = $(this).is(':checked');
		$('#ccwps-privacy-policy-link-row, #ccwps-privacy-policy-text-row').toggle(checked);
	});

	var logoMediaFrame = null;

	$(document).on('click', '#ccwps-banner-logo-upload-btn', function (e) {
		e.preventDefault();

		if (logoMediaFrame) {
			logoMediaFrame.open();
			return;
		}

		logoMediaFrame = wp.media({
			title:    i18n.mediaTitle || 'Select logo',
			button:   { text: i18n.mediaButton || 'Use this image' },
			multiple: false,
			library:  { type: [ 'image' ] }
		});

		logoMediaFrame.on('select', function () {
			var attachment = logoMediaFrame.state().get('selection').first().toJSON();
			var url = attachment.url;

			$('#banner_logo_url').val(url);

			var $preview = $('#ccwps-banner-logo-preview');
			$preview
				.removeClass('ccwps-icon-preview--empty')
				.html('<img src="' + url + '" alt="">');

			$('#ccwps-banner-logo-remove-btn').show();
		});

		logoMediaFrame.open();
	});

	$(document).on('click', '#ccwps-banner-logo-remove-btn', function (e) {
		e.preventDefault();
		$('#banner_logo_url').val('');
		$('#ccwps-banner-logo-preview')
			.addClass('ccwps-icon-preview--empty')
			.html('<span>' + (i18n.noImage || 'No image') + '</span>');
		$(this).hide();
		logoMediaFrame = null;
	});

}(jQuery));
