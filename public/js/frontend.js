/**
 * Cookie Consent WPS — Frontend Engine
 * Consent Mode v2/v3, GDPR compliant
 */
(function () {
	'use strict';

	var C     = window.ccwpsConfig || {};
	var col   = C.colors || {};
	var activeFrontendLang = resolveFrontendLanguage();
	var i18n  = resolveFrontendI18n();

	/* ---------- SVG icons ---------- */
	var ICONS = {
		cookie:  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="8.5" cy="10.5" r="1.5" fill="currentColor" stroke="none"/><circle cx="14.5" cy="8.5" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="15" r="1.5" fill="currentColor" stroke="none"/><circle cx="9" cy="15.5" r="1" fill="currentColor" stroke="none"/></svg>',
		shield:  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
		settings:'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
		lock:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
		chevron: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
	};

	var COOKIE_NAME = 'ccwps_consent';
	var VER_NAME    = 'ccwps_version';

	function normalizeLangCode(lang) {
		return String(lang || '').toLowerCase().replace(/_/g, '-').trim();
	}

	function resolveFrontendLanguage() {
		var presets = C.frontendLanguagePresets || {};
		var keys = Object.keys(presets);
		var fallback = presets.en ? 'en' : (C.frontendLanguageFallback || keys[0] || 'en');

		if (!C.detectVisitorLanguage) {
			return C.currentFrontendLang || fallback;
		}

		var requested = [];
		if (Array.isArray(navigator.languages) && navigator.languages.length) {
			requested = navigator.languages.slice();
		} else if (navigator.language) {
			requested = [navigator.language];
		}

		for (var i = 0; i < requested.length; i++) {
			var normalized = normalizeLangCode(requested[i]);
			if (!normalized) continue;

			if (presets[normalized]) return normalized;

			var base = normalized.split('-')[0];
			if (presets[base]) return base;
		}

		return fallback;
	}

	function resolveFrontendI18n() {
		var presets = C.frontendLanguagePresets || {};
		var resolved = activeFrontendLang;

		if (resolved && presets[resolved]) {
			return presets[resolved];
		}

		if (C.i18n) {
			return C.i18n;
		}

		return presets.en || {};
	}

	function getActiveLocale() {
		if (activeFrontendLang === 'en') return 'en-GB';
		return activeFrontendLang || navigator.language || 'sk';
	}

	function normalizePoweredByLabel(value) {
		var label = String(value || '').replace(/^powered\s+by\s+/i, '').trim();
		if (!label || /^cookie consent$/i.test(label)) {
			return 'Web Pixel Studio';
		}
		return label;
	}

	/* ============================================
	   UTILITIES
	   ============================================ */
	function uid() {
		return 'cc-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
	}

	function getCookie(name) {
		var m = document.cookie.match('(?:^|;)\\s*' + encodeURIComponent(name) + '=([^;]*)');
		return m ? decodeURIComponent(m[1]) : null;
	}

	function setCookie(name, val, days) {
		var d = new Date();
		d.setTime(d.getTime() + days * 86400000);
		var domain = C.cookieDomain ? ';domain=' + C.cookieDomain : '';
		var path   = ';path=' + (C.cookiePath || '/');
		document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(val)
			+ ';expires=' + d.toUTCString() + path + domain + ';SameSite=Lax';
	}

	function delCookie(name) {
		var domain = C.cookieDomain ? ';domain=' + C.cookieDomain : '';
		document.cookie = encodeURIComponent(name) + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT'
			+ ';path=' + (C.cookiePath || '/') + domain;
	}

	function getDomainVariants(domain) {
		var variants = [];
		var hostname = location.hostname || '';
		var source = domain || C.cookieDomain || hostname;
		if (!source) return variants;
		source = String(source).replace(/^\./, '');
		var parts = source.split('.');
		for (var i = 0; i < parts.length - 1; i++) {
			var candidate = parts.slice(i).join('.');
			variants.push(candidate);
			variants.push('.' + candidate);
		}
		if (hostname && variants.indexOf(hostname) === -1) variants.push(hostname);
		if (hostname && variants.indexOf('.' + hostname) === -1) variants.push('.' + hostname);
		return variants.filter(function (value, index, arr) { return value && arr.indexOf(value) === index; });
	}

	function getPathVariants(path) {
		var variants = ['/'];
		var pathname = location.pathname || '/';
		var source = path || C.cookiePath || pathname || '/';
		if (source.charAt(0) !== '/') source = '/' + source;
		variants.push(source);
		var parts = source.split('/').filter(Boolean);
		var current = '';
		for (var i = 0; i < parts.length; i++) {
			current += '/' + parts[i];
			variants.push(current);
			variants.push(current + '/');
		}
		return variants.filter(function (value, index, arr) { return value && arr.indexOf(value) === index; });
	}

	function deleteCookieEverywhere(name, path, domain) {
		var encodedName = encodeURIComponent(name);
		var paths = getPathVariants(path);
		var domains = [''].concat(getDomainVariants(domain));
		for (var pi = 0; pi < paths.length; pi++) {
			for (var di = 0; di < domains.length; di++) {
				var domainPart = domains[di] ? ';domain=' + domains[di] : '';
				document.cookie = encodedName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[pi] + domainPart + ';SameSite=Lax';
			}
		}
	}

	function getConsent() {
		var raw = getCookie(COOKIE_NAME);
		if (!raw) return null;
		try { return JSON.parse(raw); } catch (e) { return null; }
	}

	function saveConsent(prefs) {
		var existing = getConsent();
		var id   = (existing && existing.id) ? existing.id : uid();
		var data = { id: id, necessary: prefs.necessary, analytics: prefs.analytics, targeting: prefs.targeting, preferences: prefs.preferences, ts: Date.now() };
		setCookie(COOKIE_NAME, JSON.stringify(data), C.cookieExpiration || 182);
		window.__ccwpsConsentState = data;
		return data;
	}

	function esc(s) {
		if (!s) return '';
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function fmtDate(ts) {
		if (!ts) return '';
		var d = new Date(+ts);
		var locale = getActiveLocale();
		return d.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' })
			+ ' ' + d.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' });
	}

	function renderConsentIdTargets() {
		var consent = getConsent();
		var targets = document.querySelectorAll('[data-ccwps-consent-id]');
		for (var i = 0; i < targets.length; i++) {
			var target = targets[i];
			var emptyLabel = target.getAttribute('data-empty-label') || 'Súhlas nebol udelený.';
			if (consent && consent.id) {
				target.innerHTML = '<code class="ccwps-sc-id-code">' + esc(consent.id) + '</code>';
			} else {
				target.innerHTML = '<em class="ccwps-sc-id-empty">' + esc(emptyLabel) + '</em>';
			}
		}
	}

	function bindManageConsentButtons() {
		var buttons = document.querySelectorAll('[data-ccwps-manage-consent]');
		for (var i = 0; i < buttons.length; i++) {
			var button = buttons[i];
			if (button.dataset.ccwpsBound) continue;
			button.dataset.ccwpsBound = '1';
			button.addEventListener('click', function () {
				openModal();
			});
		}
	}

	/* ============================================
	   CSS VARIABLES — inject into :root
	   ============================================ */
	function injectVars() {
		var r   = parseInt(col.btnRadius)     || 8;
		var rBn = parseInt(col.bannerBorderRadius) || Math.max(r, 8);
		var rMd = parseInt(col.modalBorderRadius)  || Math.max(r, 8);

		// Compute darker hover for primary if not explicitly set
		var primBg   = col.btnPrimaryBg   || col.primary || '#1a73e8';
		var primBgHv = col.btnPrimaryBgHv || primBg;
		var primTxt  = col.btnPrimaryTxt  || col.btnText || '#ffffff';

		var ghostBg     = col.btnGhostBg     || '#f0f2f5';
		var ghostBgHv   = col.btnGhostBgHv   || '#e5e7eb';
		var ghostTxt    = col.btnGhostTxt    || col.text || '#111827';
		var ghostBorder = col.btnGhostBorder || '#e5e7eb';

		var outlineBg     = col.btnOutlineBg     || 'transparent';
		var outlineBgHv   = col.btnOutlineBgHv   || col.primary || '#1a73e8';
		var outlineTxt    = col.btnOutlineTxt    || col.primary || '#1a73e8';
		var outlineBorder = col.btnOutlineBorder || col.primary || '#1a73e8';

		var modalBg       = col.modalBg       || col.bg      || '#ffffff';
		var modalHeaderBg = col.modalHeaderBg || col.bg      || '#ffffff';
		var modalFooterBg = col.modalFooterBg || '#f9fafb';
		var modalBorder   = col.modalBorder   || '#e5e7eb';
		var modalTxt      = col.modalText     || col.text    || '#111827';

		var catBg   = col.catHeaderBg    || '#f9fafb';
		var catBgHv = col.catHeaderBgHv  || '#f0f2f5';
		var toggleC = col.toggleOnColor  || col.primary || '#1a73e8';
		var alwaysC = col.alwaysOnColor  || col.primary || '#1a73e8';

		var style = document.createElement('style');
		style.id  = 'ccwps-vars';
		style.textContent = [
			':root {',
			// Base
			'--ccwps-primary:    ' + (col.primary || '#1a73e8') + ';',
			'--ccwps-text:       ' + (col.text    || '#111827') + ';',
			'--ccwps-bg:         ' + (col.bg      || '#ffffff') + ';',
			'--ccwps-muted:      #6b7280;',
			'--ccwps-border:     ' + modalBorder + ';',
			'--ccwps-surface:    ' + catBg + ';',
			'--ccwps-surface2:   ' + catBgHv + ';',
			'--ccwps-font:       ' + (C.fontFamily || '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif') + ';',
			// Border radii
			'--ccwps-r:          ' + rBn + 'px;',
			'--ccwps-r-sm:       ' + r + 'px;',
			// Primary button
			'--ccwps-btn-primary-bg:     ' + primBg    + ';',
			'--ccwps-btn-primary-bg-hv:  ' + primBgHv  + ';',
			'--ccwps-btn-primary-txt:    ' + primTxt   + ';',
			// Ghost button
			'--ccwps-btn-ghost-bg:       ' + ghostBg     + ';',
			'--ccwps-btn-ghost-bg-hv:    ' + ghostBgHv   + ';',
			'--ccwps-btn-ghost-txt:      ' + ghostTxt    + ';',
			'--ccwps-btn-ghost-border:   ' + ghostBorder + ';',
			// Outline button
			'--ccwps-btn-outline-bg:     ' + outlineBg     + ';',
			'--ccwps-btn-outline-bg-hv:  ' + outlineBgHv   + ';',
			'--ccwps-btn-outline-txt:    ' + outlineTxt    + ';',
			'--ccwps-btn-outline-border: ' + outlineBorder + ';',
			// Modal
			'--ccwps-modal-bg:           ' + modalBg       + ';',
			'--ccwps-modal-header-bg:    ' + modalHeaderBg + ';',
			'--ccwps-modal-footer-bg:    ' + modalFooterBg + ';',
			'--ccwps-modal-border:       ' + modalBorder   + ';',
			'--ccwps-modal-r:            ' + rMd + 'px;',
			'--ccwps-modal-txt:          ' + modalTxt + ';',
			// Category rows
			'--ccwps-cat-bg:             ' + catBg   + ';',
			'--ccwps-cat-bg-hv:          ' + catBgHv + ';',
			// Toggle & accents
			'--ccwps-toggle-on:          ' + toggleC + ';',
			'--ccwps-always-on:          ' + alwaysC + ';',
			'}'
		].join('\n');
		document.head.appendChild(style);
	}

	/* ============================================
	   CONSENT MODE v2/v3
	   ============================================ */
	function updateConsentMode(prefs) {
		if (!C.consentModeEnabled) return;
		if (typeof window.gtag === 'function') {
			window.gtag('consent', 'update', {
				'ad_storage':              prefs.targeting   ? 'granted' : 'denied',
				'ad_user_data':            prefs.targeting   ? 'granted' : 'denied',
				'ad_personalization':      prefs.targeting   ? 'granted' : 'denied',
				'analytics_storage':       prefs.analytics   ? 'granted' : 'denied',
				'functionality_storage':   prefs.preferences ? 'granted' : 'denied',
				'personalization_storage': prefs.preferences ? 'granted' : 'denied'
			});
		}
		// Push dataLayer event so GTM triggers can react immediately (v2 + v3)
		if (window.dataLayer) {
			window.dataLayer.push({
				event:             'ccwps_consent_update',
				ccwps_analytics:   prefs.analytics   ? 'granted' : 'denied',
				ccwps_targeting:   prefs.targeting   ? 'granted' : 'denied',
				ccwps_preferences: prefs.preferences ? 'granted' : 'denied'
			});
		}
	}

	/* ============================================
	   SCRIPT BLOCKING / UNBLOCKING
	   ============================================ */
	function matchRule(src, rule) {
		if (!src) return false;
		if (rule.isRegex) {
			try { return new RegExp(rule.source, 'i').test(src); } catch (e) { return false; }
		}
		return src.toLowerCase().indexOf(rule.source.toLowerCase()) !== -1;
	}

	function getUrlParam(url, key) {
		try {
			return new URL(url, location.href).searchParams.get(key);
		} catch (e) {
			var match = String(url).match(new RegExp('[?&]' + key + '=([^&#]+)', 'i'));
			return match ? decodeURIComponent(match[1]) : null;
		}
	}

	function isGtagScript(url) {
		return /googletagmanager\.com\/gtag\/js/i.test(String(url || ''));
	}

	function hasDataLayerConfig(measurementId) {
		if (!measurementId || !Array.isArray(window.dataLayer)) return false;
		for (var i = 0; i < window.dataLayer.length; i++) {
			var entry = window.dataLayer[i];
			if (entry && typeof entry.length === 'number' && entry[0] === 'config' && entry[1] === measurementId) {
				return true;
			}
		}
		return false;
	}

	function ensureGtagConfigForScript(url) {
		if (!isGtagScript(url)) return;
		var measurementId = getUrlParam(url, 'id');
		if (!measurementId) return;

		window.__ccwpsGtagConfigured = window.__ccwpsGtagConfigured || {};
		if (window.__ccwpsGtagConfigured[measurementId]) return;

		window.dataLayer = window.dataLayer || [];
		if (typeof window.gtag !== 'function') {
			window.gtag = function(){ window.dataLayer.push(arguments); };
		}

		if (!hasDataLayerConfig(measurementId)) {
			window.gtag('js', new Date());
			window.gtag('config', measurementId, {
				page_title: document.title,
				page_location: location.href,
				page_path: location.pathname + location.search
			});
		}

		window.__ccwpsGtagConfigured[measurementId] = true;
	}

	function blockScripts() {
		if (!C.manageScriptTags) return;
		var consent = getConsent() || window.__ccwpsConsentState || null;
		var rules = C.blockingRules || [];
		var scripts = document.querySelectorAll('script[src]');
		for (var i = 0; i < scripts.length; i++) {
			var el = scripts[i];
			if (el.dataset.ccwpsHandled) continue;
			var src = el.getAttribute('src');
			for (var j = 0; j < rules.length; j++) {
				if (rules[j].cat === 'necessary') continue;
				if (consent && consent[rules[j].cat]) continue;
				if (matchRule(src, rules[j])) {
					el.dataset.ccwpsCat     = rules[j].cat;
					el.dataset.ccwpsOrigSrc = src;
					el.type = 'text/plain';
					el.removeAttribute('src');
					el.dataset.ccwpsHandled = '1';
					break;
				}
			}
		}
	}

	function unblockScripts(prefs) {
		if (!C.manageScriptTags) return;
		var blocked = document.querySelectorAll('[data-ccwps-orig-src]');
		for (var i = 0; i < blocked.length; i++) {
			var el  = blocked[i];
			var cat = el.dataset.ccwpsCat;
			if (cat && prefs[cat]) {
				var n   = document.createElement('script');
				var originalSrc = el.dataset.ccwpsOrigSrc;
				for (var ai = 0; ai < el.attributes.length; ai++) {
					var attr = el.attributes[ai];
					if (!attr || !attr.name) continue;
					if (/^data-ccwps/i.test(attr.name) || 'type' === attr.name || 'src' === attr.name) continue;
					n.setAttribute(attr.name, attr.value);
				}
				if (isGtagScript(originalSrc)) {
					n.addEventListener('load', function () {
						ensureGtagConfigForScript(this.src);
					}.bind({ src: originalSrc }));
				}
				n.src   = originalSrc;
				el.parentNode.replaceChild(n, el);
			}
		}
	}

	function clearCat(cat) {
		if (!C.autoClearCookies) return;
		var list = (C.cookies && C.cookies[cat]) ? C.cookies[cat] : [];
		for (var i = 0; i < list.length; i++) {
			var ck = list[i];
			if (ck.isRegex) {
				var parts = document.cookie.split(';');
				for (var j = 0; j < parts.length; j++) {
					var n = parts[j].split('=')[0].trim();
					try { if (new RegExp(ck.name).test(n)) deleteCookieEverywhere(n, ck.path, ck.domain); } catch (e) {}
				}
			} else {
				deleteCookieEverywhere(ck.name, ck.path, ck.domain);
			}
		}
	}

	function replayConsentGrantedEvents(prefs, oldPrefs) {
		var hadAnalytics = !!(oldPrefs && oldPrefs.analytics);
		var hasAnalytics = !!prefs.analytics;
		var hadTargeting = !!(oldPrefs && oldPrefs.targeting);
		var hasTargeting = !!prefs.targeting;

		if (hasAnalytics && !hadAnalytics) {
			if (typeof window.gtag === 'function') {
				window.gtag('event', 'page_view', {
					page_title: document.title,
					page_location: location.href,
					page_path: location.pathname + location.search
				});
			}

			if (window.dataLayer) {
				window.dataLayer.push({
					event: 'ccwps_page_view_after_consent',
					ccwps_page_title: document.title,
					ccwps_page_location: location.href,
					ccwps_page_path: location.pathname + location.search
				});
			}
		}

		if (hasTargeting && !hadTargeting && window.dataLayer) {
			window.dataLayer.push({
				event: 'ccwps_targeting_granted',
				ccwps_page_location: location.href
			});
		}

		if ((hasAnalytics && !hadAnalytics) || (hasTargeting && !hadTargeting)) {
			window.dispatchEvent(new CustomEvent('ccwps:consent-granted', {
				detail: {
					analytics: hasAnalytics,
					targeting: hasTargeting,
					preferences: !!prefs.preferences,
					necessary: true
				}
			}));
		}
	}

	function applyConsent(prefs, oldPrefs) {
		updateConsentMode(prefs);
		unblockScripts(prefs);
		if (oldPrefs) {
			var cats = ['analytics', 'targeting', 'preferences'];
			for (var i = 0; i < cats.length; i++) {
				if (oldPrefs[cats[i]] && !prefs[cats[i]]) clearCat(cats[i]);
			}
		}
		replayConsentGrantedEvents(prefs, oldPrefs);
	}

	/* ============================================
	   RECORD CONSENT
	   ============================================ */
	function recordConsent(prefs, id) {
		if (!C.recordConsents || !C.ajaxUrl) return;
		var fd = new FormData();
		fd.append('action',     'ccwps_save_consent');
		fd.append('nonce',      C.nonce);
		fd.append('consent_id', id);
		fd.append('url',        location.href);
		fd.append('analytics',   prefs.analytics   ? 1 : 0);
		fd.append('targeting',   prefs.targeting   ? 1 : 0);
		fd.append('preferences', prefs.preferences ? 1 : 0);
		if (window.fetch) {
			fetch(C.ajaxUrl, { method: 'POST', body: fd }).catch(function () {});
		}
	}

	/* ============================================
	   BUILD BANNER HTML
	   ============================================ */
	function buildBanner() {
		var layout   = C.bannerLayout   || 'box';
		var position = C.bannerPosition || 'bottom-left';

		var poweredLabel = normalizePoweredByLabel(i18n.poweredBy);
		var powered = '<div class="ccwps-banner-powered"><a href="https://wps.sk" target="_blank" rel="noopener">Powered by ' + esc(poweredLabel) + '</a></div>';

		var el = document.createElement('div');
		el.className = 'ccwps-banner layout-' + layout + ' pos-' + position;
		el.setAttribute('role', 'dialog');
		el.setAttribute('aria-modal', 'true');
		el.setAttribute('aria-label', i18n.bannerTitle || 'Cookie Consent');

		el.innerHTML =
			'<div class="ccwps-banner-text">' +
				'<div class="ccwps-banner-title">' + esc(i18n.bannerTitle) + '</div>' +
				'<div class="ccwps-banner-desc">' + (i18n.bannerDescription || '') + '</div>' +
			'</div>' +
			'<div class="ccwps-banner-actions">' +
				'<button class="ccwps-btn ccwps-btn-primary" id="ccwps-btn-accept">' + esc(i18n.acceptAll) + '</button>' +
				'<button class="ccwps-btn ccwps-btn-ghost"   id="ccwps-btn-reject">' + esc(i18n.rejectAll) + '</button>' +
				'<button class="ccwps-btn ccwps-btn-outline" id="ccwps-btn-pref">'   + esc(i18n.managePreferences) + '</button>' +
			'</div>' +
			powered;

		return el;
	}

	/* ============================================
	   BUILD PREFERENCES MODAL HTML
	   ============================================ */
	function buildModal() {
		var currentPrefs = getConsent();
		var cats = [
			{ key: 'necessary',   always: true,  title: i18n.necessaryTitle,   desc: i18n.necessaryDesc },
			{ key: 'analytics',   always: false, title: i18n.analyticsTitle,   desc: i18n.analyticsDesc },
			{ key: 'targeting',   always: false, title: i18n.targetingTitle,   desc: i18n.targetingDesc },
			{ key: 'preferences', always: false, title: i18n.preferencesTitle, desc: i18n.preferencesDesc }
		];

		/* Consent ID strip */
		var idHtml = '';
		if (currentPrefs && currentPrefs.id) {
			idHtml =
				'<div class="ccwps-consent-id-strip">' +
					'<div class="ccwps-consent-id-label">' + esc(i18n.consentIdLabel || 'ID súhlasu') + '</div>' +
					'<div class="ccwps-consent-id-val">'   + esc(currentPrefs.id) + '</div>' +
				'</div>';
		}

		/* Category rows */
		var catsHtml = '';
		for (var ci = 0; ci < cats.length; ci++) {
			var cat     = cats[ci];
			var cookies = (C.cookies && C.cookies[cat.key]) ? C.cookies[cat.key] : [];
			if (C.hideEmptyCategories && !cat.always && !cookies.length) continue;

			var checked = cat.always ? true : !!(currentPrefs && currentPrefs[cat.key]);

			var toggle = cat.always
				? '<span class="ccwps-always-on">' + esc(i18n.alwaysOn || 'Vždy aktívne') + '</span>'
				: '<label class="ccwps-sw">' +
					'<input type="checkbox" data-cat="' + cat.key + '"' + (checked ? ' checked' : '') + '>' +
					'<span class="ccwps-sw-track"></span>' +
				'</label>';

			var tableHtml = '';
			if (cookies.length) {
				var rows = '';
				for (var ki = 0; ki < cookies.length; ki++) {
					var ck = cookies[ki];
					rows += '<tr>' +
						'<td>' + esc(ck.name) + '</td>' +
						'<td>' + (esc(ck.domain) || '—') + '</td>' +
						'<td>' + (esc(ck.expiration) || '—') + '</td>' +
						'<td>' + (esc(ck.desc) || '—') + '</td>' +
					'</tr>';
				}
				tableHtml =
					'<table class="ccwps-ck-table">' +
						'<thead><tr>' +
							'<th>' + esc(i18n.cookieName        || 'Názov') + '</th>' +
							'<th>' + esc(i18n.cookieDomain      || 'Doména') + '</th>' +
							'<th>' + esc(i18n.cookieExpiration  || 'Platnosť') + '</th>' +
							'<th>' + esc(i18n.cookieDescription || 'Popis') + '</th>' +
						'</tr></thead>' +
						'<tbody>' + rows + '</tbody>' +
					'</table>';
			}

			catsHtml +=
				'<div class="ccwps-category">' +
					'<div class="ccwps-cat-header">' +
						'<div class="ccwps-cat-info">' +
							'<div class="ccwps-cat-name">' + esc(cat.title) + '</div>' +
							'<div class="ccwps-cat-desc">' + esc(cat.desc) + '</div>' +
						'</div>' +
						'<div class="ccwps-cat-right">' +
							toggle +
							(cookies.length ? '<span class="ccwps-chevron">' + ICONS.chevron + '</span>' : '') +
						'</div>' +
					'</div>' +
					(cookies.length ? '<div class="ccwps-ck-list">' + tableHtml + '</div>' : '') +
				'</div>';
		}

		var overlay = document.createElement('div');
		overlay.className = 'ccwps-modal-overlay';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');

		overlay.innerHTML =
			'<div class="ccwps-modal-box">' +
				'<div class="ccwps-modal-head">' +
					'<h2>' + esc(i18n.managePreferences) + '</h2>' +
					'<button class="ccwps-modal-close-btn" id="ccwps-modal-x" aria-label="' + esc(i18n.close || 'Zavrieť') + '">×</button>' +
				'</div>' +
				'<div class="ccwps-modal-body">' +
					idHtml +
					catsHtml +
				'</div>' +
				'<div class="ccwps-modal-foot">' +
					'<button class="ccwps-btn ccwps-btn-primary" id="ccwps-modal-save">' + esc(i18n.savePreferences) + '</button>' +
					'<button class="ccwps-btn ccwps-btn-ghost"   id="ccwps-modal-reject">' + esc(i18n.rejectAll) + '</button>' +
					'<button class="ccwps-btn ccwps-btn-outline" id="ccwps-modal-accept">' + esc(i18n.acceptAll) + '</button>' +
				'</div>' +
			'</div>';

		return overlay;
	}

	/* ============================================
	   TIP POPUP (above floating icon)
	   ============================================ */
	var tipEl   = null;
	var iconRef = null;

	function openTip(iconEl) {
		if (tipEl) { closeTip(); return; }
		iconRef = iconEl;

		var consent = getConsent();
		var id      = consent ? consent.id : null;
		var ts      = consent ? consent.ts : null;
		var dateStr = ts ? fmtDate(ts) : null;

		var idHtml = id
			? '<div class="ccwps-tip-id">' + esc(id) + '</div>' +
			  '<div class="ccwps-tip-date">' + esc(i18n.consentDateLabel || 'Dátum a čas') + ': <strong>' + esc(dateStr || '—') + '</strong></div>'
			: '<div class="ccwps-tip-date" style="margin-bottom:16px;">' + esc(i18n.noConsentYet || 'Súhlas nebol udelený.') + '</div>';

		tipEl = document.createElement('div');
		tipEl.className = 'ccwps-tip-popup';
		tipEl.innerHTML =
			'<div class="ccwps-tip-label">' + esc(i18n.consentIdLabel || 'ID vášho súhlasu') + '</div>' +
			idHtml +
			'<div class="ccwps-tip-btns">' +
				'<button class="ccwps-btn ccwps-btn-ghost ccwps-btn-sm" id="ccwps-tip-close">' + esc(i18n.close || 'Zavrieť') + '</button>' +
				'<button class="ccwps-btn ccwps-btn-primary ccwps-btn-sm" id="ccwps-tip-manage">' + esc(i18n.managePreferences || 'Spravovať súhlas') + '</button>' +
			'</div>';

		document.body.appendChild(tipEl);
		positionTip(iconEl);

		tipEl.querySelector('#ccwps-tip-close').addEventListener('click',  closeTip);
		tipEl.querySelector('#ccwps-tip-manage').addEventListener('click', function () { closeTip(); openModal(); });

		setTimeout(function () {
			document.addEventListener('click', outsideClick);
		}, 80);
	}

	function positionTip(iconEl) {
		if (!tipEl) return;
		var rect   = iconEl.getBoundingClientRect();
		var tipW   = tipEl.offsetWidth  || 300;
		var tipH   = tipEl.offsetHeight || 160;
		var gap    = 12;
		var iconPos = C.iconPosition || 'bottom-right';
		var isRight = iconPos.indexOf('right') !== -1;

		/* Vertical: above the icon by default */
		var top = rect.top - tipH - gap;
		if (top < 10) top = rect.bottom + gap; /* if no space above, go below */

		/* Horizontal: align right edge (for right icons) or left edge (for left icons) */
		var left;
		if (isRight) {
			left = rect.right - tipW;
		} else {
			left = rect.left;
		}

		/* Clamp to viewport */
		left = Math.max(10, Math.min(left, window.innerWidth - tipW - 10));
		top  = Math.max(10, top);

		tipEl.style.top  = top  + 'px';
		tipEl.style.left = left + 'px';

		/* Arrow direction */
		if (!isRight) {
			tipEl.classList.add('tip-arrow-left');
		}
	}

	function closeTip() {
		if (!tipEl) return;
		tipEl.remove();
		tipEl   = null;
		iconRef = null;
		document.removeEventListener('click', outsideClick);
	}

	function outsideClick(e) {
		if (tipEl && !tipEl.contains(e.target) && e.target !== iconRef) {
			closeTip();
		}
	}

	/* ============================================
	   MODAL OPEN / CLOSE
	   ============================================ */
	var modalEl = null;

	function openModal() {
		if (modalEl) return;
		modalEl = buildModal();

		var wrap = document.getElementById('ccwps-modal-wrap');
		if (!wrap) { document.body.appendChild(modalEl); }
		else        { wrap.appendChild(modalEl); }

		/* Accordion */
		var headers = modalEl.querySelectorAll('.ccwps-cat-header');
		for (var i = 0; i < headers.length; i++) {
			headers[i].addEventListener('click', function (e) {
				if (e.target.closest('.ccwps-sw') || e.target.closest('.ccwps-always-on')) return;
				var list    = this.nextElementSibling;
				var chevron = this.querySelector('.ccwps-chevron');
				if (list && list.classList.contains('ccwps-ck-list')) {
					list.classList.toggle('open');
					if (chevron) chevron.classList.toggle('open');
				}
			});
		}

		var btnX      = modalEl.querySelector('#ccwps-modal-x');
		var btnSave   = modalEl.querySelector('#ccwps-modal-save');
		var btnReject = modalEl.querySelector('#ccwps-modal-reject');
		var btnAccept = modalEl.querySelector('#ccwps-modal-accept');

		if (btnX)      btnX.addEventListener('click', closeModal);
		if (btnReject) btnReject.addEventListener('click', function () { doReject(); closeModal(); });
		if (btnAccept) btnAccept.addEventListener('click', function () { doAcceptAll(); closeModal(); });
		if (btnSave)   btnSave.addEventListener('click',   function () {
			var prefs = { necessary: true, analytics: false, targeting: false, preferences: false };
			var cbs   = modalEl.querySelectorAll('input[data-cat]');
			for (var i = 0; i < cbs.length; i++) prefs[cbs[i].dataset.cat] = cbs[i].checked;
			doSave(prefs);
			closeModal();
		});

		/* Close on overlay click */
		modalEl.addEventListener('click', function (e) { if (e.target === modalEl) closeModal(); });
		document.addEventListener('keydown', escHandler);

		var firstBtn = modalEl.querySelector('button');
		if (firstBtn) firstBtn.focus();
	}

	function closeModal() {
		if (!modalEl) return;
		modalEl.remove();
		modalEl = null;
		document.removeEventListener('keydown', escHandler);
	}

	function escHandler(e) {
		if (e.key === 'Escape') { closeModal(); closeTip(); }
	}

	/* ============================================
	   SAVE / ACCEPT / REJECT
	   ============================================ */
	function doSave(prefs) {
		var old  = getConsent();
		var oldP = old ? { analytics: !!old.analytics, targeting: !!old.targeting, preferences: !!old.preferences } : null;
		var data = saveConsent(prefs);
		window.__ccwpsConsentState = data;
		saveVersion();
		applyConsent(prefs, oldP);
		recordConsent(prefs, data.id);
		hideBanner();
		showIcon();
		renderConsentIdTargets();
	}

	function doAcceptAll() {
		doSave({ necessary: true, analytics: true,  targeting: true,  preferences: true  });
	}

	function doReject() {
		doSave({ necessary: true, analytics: false, targeting: false, preferences: false });
	}

	/* ============================================
	   BANNER SHOW / HIDE
	   ============================================ */
	var bannerEl = null;

	function showBanner() {
		if (bannerEl) return;
		bannerEl = buildBanner();

		var wrap = document.getElementById('ccwps-banner-wrap');
		if (!wrap) { document.body.appendChild(bannerEl); }
		else        { wrap.appendChild(bannerEl); }

		bannerEl.querySelector('#ccwps-btn-accept').addEventListener('click', doAcceptAll);
		bannerEl.querySelector('#ccwps-btn-reject').addEventListener('click', doReject);
		bannerEl.querySelector('#ccwps-btn-pref').addEventListener('click',   openModal);
	}

	function hideBanner() {
		if (!bannerEl) return;
		bannerEl.remove();
		bannerEl = null;
	}

	/* ============================================
	   FLOATING ICON
	   ============================================ */
	function showIcon() {
		if (!C.showFloatingIcon) return;
		var el = document.getElementById('ccwps-floating-icon');
		if (!el) return;

		var iconType = C.iconType || 'cookie';

		if (iconType === 'custom' && C.iconCustomUrl) {
			// Custom image icon — render as <img>, disable background circle color
			var img = document.createElement('img');
			img.src   = C.iconCustomUrl;
			img.alt   = '';
			img.style.cssText = 'width:50px;height:50px;border-radius:50%;object-fit:cover;display:block;';
			el.innerHTML = '';
			el.appendChild(img);
			// Remove primary color background so image shows naturally
			el.style.background = 'transparent';
			el.style.boxShadow  = 'none';
		} else {
			el.innerHTML = ICONS[iconType] || ICONS.cookie;
			el.style.background = '';
			el.style.boxShadow  = '';
		}

		/* Remove old position classes */
		el.className = '';
		el.className = 'icon-pos-' + (C.iconPosition || 'bottom-right');
		el.style.display = 'flex';

		/* Only bind once */
		if (!el.dataset.ccwpsBound) {
			el.dataset.ccwpsBound = '1';
			el.addEventListener('click', function (e) {
				e.stopPropagation();
				openTip(el);
			});
			el.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openTip(el); }
			});
		}
	}

	/* ============================================
	   RECONSENT
	   ============================================ */
	function needsReconsent() {
		if (!C.reconsent) return false;
		return getCookie(VER_NAME) !== hashCookies();
	}
	function saveVersion() {
		setCookie(VER_NAME, hashCookies(), C.cookieExpiration || 182);
	}
	function hashCookies() {
		var s = C.cookies ? JSON.stringify(C.cookies) : '';
		var h = 0;
		for (var i = 0; i < s.length; i++) h = (Math.imul(31, h) + s.charCodeAt(i)) | 0;
		return h.toString(36);
	}

	/* ============================================
	   BOT DETECTION
	   ============================================ */
	function isBot() {
		if (!C.hideFromBots) return false;
		return /bot|crawl|spider|slurp|mediapartners|googlebot|bingbot|yandex|baidu|duckduck|phantomjs|headless|selenium|webdriver/i
			.test(navigator.userAgent);
	}

	/* ============================================
	   PREVIEW MODE (admin)
	   ============================================ */
	function handlePreview() {
		var params  = new URLSearchParams(location.search);
		var preview = params.get('ccwps_preview');
		if (!preview) return false;
		injectVars();
		if (preview === 'banner') {
			setTimeout(showBanner, 200);
		} else if (preview === 'modal') {
			setTimeout(openModal, 400);
		}
		return true;
	}

	/* ============================================
	   INIT
	   ============================================ */
	function init() {
		if (isBot()) return;
		if (handlePreview()) return;

		injectVars();
		blockScripts();
		renderConsentIdTargets();
		bindManageConsentButtons();

		var consent = getConsent();
		if (consent) {
			window.__ccwpsConsentState = consent;
			var prefs = {
				necessary:   true,
				analytics:   !!consent.analytics,
				targeting:   !!consent.targeting,
				preferences: !!consent.preferences
			};
			applyConsent(prefs, null);
			updateConsentMode(prefs);
			if (needsReconsent()) {
				saveVersion();
				setTimeout(showBanner, C.delay || 0);
			} else {
				showIcon();
			}
		} else {
			window.__ccwpsConsentState = null;
			if (C.autorun) setTimeout(showBanner, C.delay || 0);
		}

		/* Public API */
		window.CookieConsentWPS = {
			openModal:  openModal,
			closeModal: closeModal,
			acceptAll:  doAcceptAll,
			rejectAll:  doReject,
			getConsent: getConsent,
			showBanner: showBanner,
			hideBanner: hideBanner,
			openTip:    openTip,
			closeTip:   closeTip
		};
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
