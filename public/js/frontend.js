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
	var initialAdminLang = C.currentFrontendLang; // Track if lang changed from admin_lang

	// Update i18n if visitor language was detected
	if (C.detectVisitorLanguage && activeFrontendLang !== initialAdminLang) {
		i18n = resolveFrontendI18n();
	}

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

	function getStableAlwaysOnLabel() {
		var labels = {
			sk: 'Vždy aktívne',
			en: 'Always active',
			cs: 'Vždy aktivní',
			de: 'Immer aktiv',
			fr: 'Toujours actif',
			es: 'Siempre activo',
			pl: 'Zawsze aktywne',
			hu: 'Mindig aktív',
			it: 'Sempre attivo'
		};
		var normalized = normalizeLangCode(activeFrontendLang);
		var base = normalized.split('-')[0];
		return labels[normalized] || labels[base] || labels.en;
	}

	function getPresetCookieDescriptionTranslations() {
		return {
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
				'__Secure-1PAPISID / 3PAPISID': 'Používa sa na personalizáciu reklám a meranie interakcií. Súvisí s vaším Google účtom.',
				'__Secure-1PSID / 3PSID': 'Obsahujú šifrované informácie o vašom Google ID a poslednom čase prihlásenia. Kľúčové pre vašu identitu.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Slúžia na zabezpečenie a ukladanie preferencií používateľa pri zobrazovaní reklám Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Časová pečiatka a zabezpečenie; pomáhajú detegovať podozrivú aktivitu a predchádzať podvodom.',
				'__Secure-BUCKET': 'Interný identifikátor na priraďovanie používateľa k experimentálnym funkciám (Google A/B testy).',
				ADS_VISITOR_ID: 'Identifikátor návštevníka na sledovanie interakcie s reklamami.',
				APISID: 'Používa sa na identifikáciu používateľa pri načítaní vložených Google služieb (napr. Maps alebo YouTube) na iných weboch.',
				SAPISID: 'Bezpečná API verzia identity cookie pre požiadavky na Google služby a prevenciu podvodov.',
				SID: 'Podpísaný a šifrovaný identifikátor Google účtu používaný na udržiavanie prihlásenej relácie.',
				SSID: 'Príbuzná cookie k SID nastavená iba pre HTTPS na ochranu relácie v zabezpečenom kontexte.',
				HSID: 'Používa sa na overenie identity používateľa a prevenciu podvodného použitia prihlasovacích údajov.',
				NID: 'Ukladá jedinečné ID, preferencie vyhľadávania a informácie o personalizácii reklám.',
				OTZ: 'Súhrnné štatistiky o návštevnosti a informácie o verzii vyhľadávacieho nástroja.',
				S: 'Identifikátor relácie pre platobné služby Google (billing UI), ktorý drží stav nákupu alebo fakturácie.',
				SEARCH_SAMESITE: 'Pomáha chrániť proti CSRF útokom tým, že cookies sú odosielané iba v bezpečnom kontexte.',
				SIDCC: 'Bezpečnostná cookie pre doručovanie služieb Google a ochranu pred podvodmi.'
			},
			en: {
				AEC: 'Used to ensure that requests within a session are made by the user and not by other pages.',
				SOCS: 'Stores the user\'s state regarding their cookie choices.',
				'^_ga_': 'Used by Google Analytics to collect data on how many times a user has visited the website, including first and last visit dates.',
				_ga: 'Registers a unique ID used to generate statistical data on how the visitor uses the website.',
				'_gat_gtag_': 'Used by Google Analytics to limit the number of requests.',
				_gid: 'Tracks visitor behavior across sessions so Google Analytics can analyze traffic and interactions.',
				IDE: 'Used by Google DoubleClick to track user behavior and personalize ads based on previous interactions.',
				_gcl_au: 'Used by Google AdSense to track ad interactions and optimize ad content.',
				_fbp: 'Facebook Pixel marketing cookie used to identify visitors and track them across websites using Facebook ads.',
				last_pys_landing_page: 'Pixel Your Site (PYS). Stores the last landing page to support ad conversion attribution.',
				last_pysTrafficSource: 'Pixel Your Site. Tracks the latest traffic source (for example where the visit came from before purchase).',
				pys_first_visit: 'Pixel Your Site. Identifies whether this is the first visit, which is key for customer journey tracking.',
				pys_landing_page: 'Pixel Your Site. Stores the URL of the very first page through which the visitor entered the website.',
				pys_session_limit: 'Pixel Your Site. Technical session-length tracking for pixel behavior analysis.',
				pys_start_session: 'Pixel Your Site. Identifies session start to sync data with Facebook and Google pixels.',
				pysTrafficSource: 'Pixel Your Site. Stores the original traffic source (for example "facebook_ad") for remarketing attribution.',
				'^_pk_id': 'Stores a unique visitor ID used by Matomo Analytics to recognize returning visitors.',
				'^_pk_ses': 'Matomo Analytics session cookie that temporarily stores page view data during the current visit.',
				'^_pk_ref': 'Stores attribution details (referrer/campaign) for Matomo Analytics reports.',
				mtm_consent: 'Stores Matomo consent status for this visitor.',
				mtm_cookie_consent: 'Stores Matomo cookie-consent status when cookie-consent mode is used.',
				ccwps_consent: 'Stores visitor consent choices in this plugin so selected categories remain respected on future page loads.',
				ccwps_version: 'Stores consent configuration version in the plugin to detect changes and request consent again when needed.',
				'__Secure-1PAPISID / 3PAPISID': 'Used for ad personalization and interaction measurement. Linked to your Google account.',
				'__Secure-1PSID / 3PSID': 'Contains encrypted details about your Google account identity and recent sign-in time.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Used for security and storing user ad-related preferences in Google services.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Timestamp and security cookie helping detect suspicious activity and reduce fraud.',
				'__Secure-BUCKET': 'Internal identifier used to assign users to Google experimental features (A/B tests).',
				ADS_VISITOR_ID: 'Visitor identifier used to track interactions with ads.',
				APISID: 'Used to identify users when embedded Google services (like Maps or YouTube) are loaded on other sites.',
				SAPISID: 'Secure API identity cookie used for background Google service requests and fraud prevention.',
				SID: 'Signed and encrypted Google account identifier used to maintain logged-in sessions.',
				SSID: 'Secure-only companion cookie used for identity verification over HTTPS.',
				HSID: 'Used to verify user identity and help prevent fraudulent use of login credentials.',
				NID: 'Stores unique identifier, search preferences, and ad personalization information.',
				OTZ: 'Aggregated usage statistics and search engine version indicators.',
				S: 'Session identifier used by Google billing interfaces to keep purchase or billing state.',
				SEARCH_SAMESITE: 'Helps protect against CSRF attacks by ensuring cookies are sent only in safe contexts.',
				SIDCC: 'Security cookie tied to Google services and fraud protection.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Používá se pro personalizaci reklam a měření interakcí. Souvisí s vaším Google účtem.',
				'__Secure-1PSID / 3PSID': 'Obsahují šifrované informace o vašem Google ID a posledním čase přihlášení. Klíčové pro vaši identitu.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Slouží k zabezpečení a ukládání uživatelských preferencí při zobrazování reklam Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Časové razítko a zabezpečení; pomáhají odhalovat podezřelou aktivitu a předcházet podvodům.',
				'__Secure-BUCKET': 'Interní identifikátor pro zařazení uživatele do experimentálních funkcí Google (A/B testy).',
				ADS_VISITOR_ID: 'Identifikátor návštěvníka pro sledování interakce s reklamami.',
				APISID: 'Používá se k identifikaci uživatele při načtení vložených služeb Google (např. Maps nebo YouTube) na jiných webech.',
				SAPISID: 'Bezpečná API verze identity cookie pro požadavky na služby Google a prevenci podvodů.',
				SID: 'Podepsaný a šifrovaný identifikátor Google účtu používaný k udržení přihlášené relace.',
				SSID: 'Související cookie k SID nastavená pouze přes HTTPS pro ochranu relace v bezpečném kontextu.',
				HSID: 'Používá se k ověření identity uživatele a prevenci podvodného použití přihlašovacích údajů.',
				NID: 'Ukládá jedinečné ID, preference vyhledávání a informace o personalizaci reklam.',
				OTZ: 'Souhrnné statistiky návštěvnosti a informace o verzi vyhledávače.',
				S: 'Identifikátor relace pro platební služby Google (billing UI), který drží stav nákupu nebo fakturace.',
				SEARCH_SAMESITE: 'Pomáhá chránit proti CSRF útokům tím, že cookies jsou odesílány pouze v bezpečném kontextu.',
				SIDCC: 'Bezpečnostní cookie pro doručování služeb Google a ochranu proti podvodům.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Wird zur Personalisierung von Anzeigen und zur Messung von Interaktionen verwendet. Sie ist mit Ihrem Google-Konto verknüpft.',
				'__Secure-1PSID / 3PSID': 'Enthält verschlüsselte Informationen über Ihre Google-ID und den letzten Anmeldezeitpunkt. Wichtig für Ihre Identität.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Dient der Sicherheit und Speicherung von Nutzerpräferenzen bei der Anzeige von Google-Werbung.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Zeitstempel und Sicherheitsinformationen; helfen, verdächtige Aktivitäten zu erkennen und Betrug zu verhindern.',
				'__Secure-BUCKET': 'Interner Identifikator, um Nutzer experimentellen Google-Funktionen (A/B-Tests) zuzuordnen.',
				ADS_VISITOR_ID: 'Besucher-ID zur Nachverfolgung von Interaktionen mit Anzeigen.',
				APISID: 'Wird zur Identifizierung genutzt, wenn eingebettete Google-Dienste (z. B. Maps oder YouTube) auf anderen Websites geladen werden.',
				SAPISID: 'Sichere API-Identitäts-Cookie für Google-Dienstanfragen und Betrugsprävention.',
				SID: 'Signierte und verschlüsselte Google-Konto-ID zur Aufrechterhaltung angemeldeter Sitzungen.',
				SSID: 'Verwandte Cookie zu SID, nur über HTTPS gesetzt, um Sitzungen in sicheren Kontexten zu schützen.',
				HSID: 'Dient zur Überprüfung der Nutzeridentität und zur Verhinderung missbräuchlicher Nutzung von Anmeldedaten.',
				NID: 'Speichert eindeutige ID, Suchpräferenzen und Informationen zur Anzeigenpersonalisierung.',
				OTZ: 'Zusammengefasste Nutzungsstatistiken und Informationen zur Version der Suchmaschine.',
				S: 'Sitzungskennzeichen für Google-Abrechnungsdienste (Billing UI), das Kauf- oder Rechnungsstatus hält.',
				SEARCH_SAMESITE: 'Hilft beim Schutz vor CSRF-Angriffen, indem Cookies nur in sicheren Kontexten gesendet werden.',
				SIDCC: 'Sicherheits-Cookie für Google-Dienste und Betrugsschutz.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Utilisee pour personnaliser les annonces et mesurer les interactions. Liee a votre compte Google.',
				'__Secure-1PSID / 3PSID': 'Contient des informations chiffrees sur votre identifiant Google et l\'heure de votre derniere connexion.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Sert a la securite et au stockage des preferences utilisateur pour l\'affichage des annonces Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Horodatage et securite ; aide a detecter les activites suspectes et a prevenir la fraude.',
				'__Secure-BUCKET': 'Identifiant interne pour affecter les utilisateurs a des fonctionnalites experimentales Google (tests A/B).',
				ADS_VISITOR_ID: 'Identifiant visiteur pour suivre les interactions avec les annonces.',
				APISID: 'Utilisee pour identifier l\'utilisateur lorsque des services Google integres (Maps, YouTube) sont charges sur d\'autres sites.',
				SAPISID: 'Version securisee de cookie d\'identite API pour les requetes Google et la prevention de la fraude.',
				SID: 'Identifiant de compte Google signe et chiffre pour maintenir la session connectee.',
				SSID: 'Cookie associe a SID, transmis uniquement via HTTPS pour proteger la session.',
				HSID: 'Utilisee pour verifier l\'identite utilisateur et prevenir l\'utilisation frauduleuse des identifiants.',
				NID: 'Stocke un identifiant unique, les preferences de recherche et des informations de personnalisation publicitaire.',
				OTZ: 'Statistiques d\'usage agregees et informations sur la version du moteur de recherche.',
				S: 'Identifiant de session pour les services de facturation Google (billing UI), conservant l\'etat achat/facturation.',
				SEARCH_SAMESITE: 'Aide a proteger contre les attaques CSRF en n\'autorisant l\'envoi des cookies que dans un contexte securise.',
				SIDCC: 'Cookie de securite pour les services Google et la protection contre la fraude.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Se utiliza para personalizar anuncios y medir interacciones. Esta vinculada a tu cuenta de Google.',
				'__Secure-1PSID / 3PSID': 'Contiene informacion cifrada sobre tu ID de Google y el ultimo inicio de sesion.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Se usa para seguridad y para guardar preferencias del usuario en la publicidad de Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Marca temporal y seguridad; ayuda a detectar actividad sospechosa y prevenir fraude.',
				'__Secure-BUCKET': 'Identificador interno para asignar usuarios a funciones experimentales de Google (A/B tests).',
				ADS_VISITOR_ID: 'Identificador de visitante para rastrear interacciones con anuncios.',
				APISID: 'Se usa para identificar al usuario cuando se cargan servicios Google incrustados (Maps o YouTube) en otros sitios.',
				SAPISID: 'Version segura de cookie de identidad API para solicitudes de Google y prevencion de fraude.',
				SID: 'Identificador de cuenta Google firmado y cifrado para mantener sesiones iniciadas.',
				SSID: 'Cookie relacionada con SID, enviada solo por HTTPS para proteger la sesion.',
				HSID: 'Se utiliza para verificar la identidad del usuario y prevenir el uso fraudulento de credenciales.',
				NID: 'Guarda ID unica, preferencias de busqueda e informacion de personalizacion publicitaria.',
				OTZ: 'Estadisticas agregadas de uso e informacion de version del motor de busqueda.',
				S: 'Identificador de sesion para servicios de facturacion de Google (billing UI), mantiene estado de compra/facturacion.',
				SEARCH_SAMESITE: 'Ayuda a proteger contra ataques CSRF asegurando que las cookies se envien solo en contextos seguros.',
				SIDCC: 'Cookie de seguridad para servicios de Google y proteccion antifraude.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Uzywana do personalizacji reklam i pomiaru interakcji. Powiazana z Twoim kontem Google.',
				'__Secure-1PSID / 3PSID': 'Zawiera zaszyfrowane informacje o Twoim identyfikatorze Google i czasie ostatniego logowania.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Sluzy do zabezpieczen i zapisywania preferencji uzytkownika przy wyswietlaniu reklam Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Znacznik czasu i zabezpieczenia; pomagaja wykrywac podejrzana aktywnosc i zapobiegac oszustwom.',
				'__Secure-BUCKET': 'Wewnetrzny identyfikator przypisujacy uzytkownika do eksperymentalnych funkcji Google (testy A/B).',
				ADS_VISITOR_ID: 'Identyfikator odwiedzajacego do sledzenia interakcji z reklamami.',
				APISID: 'Sluzy do identyfikacji uzytkownika, gdy osadzone uslugi Google (np. Maps lub YouTube) laduja sie na innych stronach.',
				SAPISID: 'Bezpieczna wersja ciasteczka tozsamosci API dla zapytan Google i ochrony przed naduzyciami.',
				SID: 'Podpisany i szyfrowany identyfikator konta Google utrzymujacy zalogowana sesje.',
				SSID: 'Powiazane z SID ciasteczko wysylane tylko przez HTTPS dla ochrony sesji.',
				HSID: 'Uzywane do weryfikacji tozsamosci uzytkownika i zapobiegania oszustwom z danymi logowania.',
				NID: 'Przechowuje unikalny ID, preferencje wyszukiwania i informacje o personalizacji reklam.',
				OTZ: 'Zbiorcze statystyki ruchu i informacje o wersji wyszukiwarki.',
				S: 'Identyfikator sesji dla uslug rozliczeniowych Google (billing UI), przechowuje stan zakupu/rozliczenia.',
				SEARCH_SAMESITE: 'Pomaga chronic przed atakami CSRF, zapewniajac wysylanie cookies tylko w bezpiecznym kontekscie.',
				SIDCC: 'Ciasteczko bezpieczenstwa dla uslug Google i ochrony przed naduzyciami.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Hirdetesek szemelyre szabasa es interakciok merese celjabol hasznaljak. Osszefugg a Google-fiokkal.',
				'__Secure-1PSID / 3PSID': 'Titkositott informaciokat tartalmaz a Google-azonositorol es az utolso bejelentkezes idejerol.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Biztonsagi es felhasznaloi preferencia tarolasi celokat szolgal a Google-hirdeteseknel.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Idobelyeg es biztonsagi funkcio; segit a gyanus tevekenyseg felismereseben es a csalások megelozeseben.',
				'__Secure-BUCKET': 'Belso azonosito, amely a felhasznalot Google kiserleti funkciokhoz (A/B tesztekhez) rendeli.',
				ADS_VISITOR_ID: 'Latogatoi azonosito a hirdetes-interakciok kovetesehez.',
				APISID: 'A felhasznalo azonositasa celjabol hasznaljak, amikor beagyazott Google-szolgaltatasok (pl. Maps vagy YouTube) toltodnek be mas oldalakon.',
				SAPISID: 'Biztonsagos API-identitas cookie Google-keresekhez es csalásmegelozeshez.',
				SID: 'Alairt es titkositott Google-fiokazonosito a bejelentkezett munkamenet fenntartasahoz.',
				SSID: 'SID-hez kapcsolodo cookie, amelyet csak HTTPS-en kuldenek a munkamenet vedelme erdekeben.',
				HSID: 'A felhasznalo azonossaganak ellenorzesere es a bejelentkezesi adatokkal valo visszaeles megelozesere szolgal.',
				NID: 'Egyedi azonosítot, keresesi preferenciakat es hirdetes-szemelyreszabasi informaciokat tarol.',
				OTZ: 'Osszesitett hasznalati statisztikak es a keresomotor verzioinformacioi.',
				S: 'Munkamenet-azonosito a Google szamlazasi feluleteihez (billing UI), a vasarlasi/szamlazasi allapot tarolasahoz.',
				SEARCH_SAMESITE: 'Segit vedeni a CSRF tamadasok ellen azzal, hogy a cookie-k csak biztonsagos kontextusban kuldhetok.',
				SIDCC: 'Biztonsagi cookie a Google szolgaltatasokhoz es a csalás elleni vedelemhez.'
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
				'__Secure-1PAPISID / 3PAPISID': 'Usato per la personalizzazione degli annunci e la misurazione delle interazioni. Collegato al tuo account Google.',
				'__Secure-1PSID / 3PSID': 'Contiene informazioni cifrate sul tuo ID Google e sull\'ora dell\'ultimo accesso.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Serve per sicurezza e salvataggio delle preferenze utente nella pubblicita Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Timestamp e sicurezza; aiuta a rilevare attivita sospette e prevenire frodi.',
				'__Secure-BUCKET': 'Identificatore interno per assegnare gli utenti a funzionalita sperimentali Google (test A/B).',
				ADS_VISITOR_ID: 'Identificatore visitatore per tracciare le interazioni con gli annunci.',
				APISID: 'Usato per identificare l\'utente quando servizi Google incorporati (come Maps o YouTube) vengono caricati su altri siti.',
				SAPISID: 'Versione sicura del cookie di identita API per richieste ai servizi Google e prevenzione frodi.',
				SID: 'Identificatore account Google firmato e cifrato usato per mantenere la sessione autenticata.',
				SSID: 'Cookie correlato a SID inviato solo via HTTPS per proteggere la sessione.',
				HSID: 'Usato per verificare l\'identita utente e prevenire l\'uso fraudolento delle credenziali di accesso.',
				NID: 'Memorizza ID univoco, preferenze di ricerca e informazioni di personalizzazione pubblicitaria.',
				OTZ: 'Statistiche aggregate di utilizzo e informazioni sulla versione del motore di ricerca.',
				S: 'Identificatore di sessione per i servizi di fatturazione Google (billing UI), mantiene lo stato di acquisto/fatturazione.',
				SEARCH_SAMESITE: 'Aiuta a proteggere dagli attacchi CSRF garantendo che i cookie siano inviati solo in contesti sicuri.',
				SIDCC: 'Cookie di sicurezza per i servizi Google e la protezione antifrode.'
			},
			ro: {
				AEC: 'Utilizat pentru a se asigura că solicitările din cadrul unei sesiuni sunt efectuate de utilizator și nu de alte pagini.',
				SOCS: 'Stochează starea utilizatorului în ceea ce privește alegerile sale privind cookie-urile.',
				'^_ga_': 'Utilizat de Google Analytics pentru a colecta date despre de câte ori un utilizator a vizitat site-ul web, inclusiv datele primei și ultimei vizite.',
				_ga: 'Înregistrează un ID unic utilizat pentru a genera date statistice despre modul în care vizitatorul folosește site-ul web.',
				'_gat_gtag_': 'Utilizat de Google Analytics pentru a limita numărul de solicitări.',
				_gid: 'Urmărește comportamentul vizitatorilor pe mai multe sesiuni, astfel încât Google Analytics să poată analiza traficul și interacțiunile.',
				IDE: 'Utilizat de Google DoubleClick pentru a urmări comportamentul utilizatorilor și a personaliza anunțurile pe baza interacțiunilor anterioare.',
				_gcl_au: 'Utilizat de Google AdSense pentru a urmări interacțiunile cu anunțurile și a optimiza conținutul publicitar.',
				_fbp: 'Cookie de marketing Facebook Pixel utilizat pentru a identifica vizitatorii și a-i urmări pe site-urile care folosesc reclame Facebook.',
				last_pys_landing_page: 'Pixel Your Site (PYS). Stochează ultima pagină de destinație pentru a atribui conversiile reclamelor.',
				last_pysTrafficSource: 'Pixel Your Site. Urmărește ultima sursă de trafic (de exemplu de unde ați venit înainte de achiziție).',
				pys_first_visit: 'Pixel Your Site. Identifică dacă aceasta este prima vizită, esențial pentru urmărirea parcursului clientului.',
				pys_landing_page: 'Pixel Your Site. Stochează URL-ul primei pagini prin care vizitatorul a intrat pe site.',
				pys_session_limit: 'Pixel Your Site. Urmărirea tehnică a duratei sesiunii pentru analiza comportamentului pentru pixeli.',
				pys_start_session: 'Pixel Your Site. Identifică începutul sesiunii pentru a sincroniza datele cu pixelii Facebook și Google.',
				pysTrafficSource: 'Pixel Your Site. Stochează sursa originală de trafic (de exemplu "facebook_ad") pentru atribuirea remarketingului.',
				'^_pk_id': 'Stochează un ID unic al vizitatorului utilizat de Matomo Analytics pentru a recunoaște vizitatorii care revin.',
				'^_pk_ses': 'Cookie de sesiune Matomo Analytics care stochează temporar datele despre vizualizările de pagini din vizita curentă.',
				'^_pk_ref': 'Stochează detaliile de atribuire (referitor/campanie) pentru rapoartele Matomo Analytics.',
				mtm_consent: 'Stochează starea de consimțământ Matomo pentru acest vizitator.',
				mtm_cookie_consent: 'Stochează starea de consimțământ pentru cookie-uri Matomo când se utilizează modul cookie-consent.',
				ccwps_consent: 'Stochează alegerile de consimțământ ale vizitatorului în acest plugin, astfel încât categoriile selectate rămân respectate la încărcările ulterioare ale paginii.',
				ccwps_version: 'Stochează versiunea configurației de consimțământ în plugin pentru a detecta modificările și a solicita din nou consimțământul când este necesar.',
				'__Secure-1PAPISID / 3PAPISID': 'Utilizat pentru personalizarea anunțurilor și măsurarea interacțiunilor. Legat de contul dvs. Google.',
				'__Secure-1PSID / 3PSID': 'Conține detalii criptate despre identitatea contului dvs. Google și ora recentă de conectare.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Utilizat pentru securitate și stocarea preferințelor utilizatorului legate de anunțurile Google.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Marcaj de timp și securitate; ajută la detectarea activității suspecte și reducerea fraudelor.',
				'__Secure-BUCKET': 'Identificator intern utilizat pentru a atribui utilizatorii funcțiilor experimentale Google (teste A/B).',
				ADS_VISITOR_ID: 'Identificator al vizitatorului utilizat pentru a urmări interacțiunile cu anunțurile.',
				APISID: 'Utilizat pentru a identifica utilizatorii când serviciile Google încorporate (cum ar fi Maps sau YouTube) sunt încărcate pe alte site-uri.',
				SAPISID: 'Cookie de identitate API securizat utilizat pentru solicitările de servicii Google și prevenirea fraudelor.',
				SID: 'Identificator de cont Google semnat și criptat utilizat pentru menținerea sesiunilor autentificate.',
				SSID: 'Cookie companion transmis numai prin HTTPS pentru verificarea identității în conexiuni securizate.',
				HSID: 'Utilizat pentru a verifica identitatea utilizatorului și a preveni utilizarea frauduloasă a acreditivelor de conectare.',
				NID: 'Stochează identificator unic, preferințe de căutare și informații de personalizare a anunțurilor.',
				OTZ: 'Statistici agregate de utilizare și indicatori ai versiunii motorului de căutare.',
				S: 'Identificator de sesiune utilizat de interfețele de facturare Google pentru a menține starea de achiziție sau facturare.',
				SEARCH_SAMESITE: 'Ajută la protecția împotriva atacurilor CSRF asigurând că cookie-urile sunt trimise numai în contexte sigure.',
				SIDCC: 'Cookie de securitate legat de serviciile Google și protecția împotriva fraudelor.'
			},
			nl: {
				AEC: 'Wordt gebruikt om te zorgen dat verzoeken binnen een sessie worden gedaan door de gebruiker en niet door andere pagina\'s.',
				SOCS: 'Slaat de status van de gebruiker op met betrekking tot zijn cookiekeuzes.',
				'^_ga_': 'Wordt gebruikt door Google Analytics om gegevens te verzamelen over hoe vaak een gebruiker de website heeft bezocht, inclusief de datums van het eerste en laatste bezoek.',
				_ga: 'Registreert een uniek ID dat wordt gebruikt om statistische gegevens te genereren over hoe de bezoeker de website gebruikt.',
				'_gat_gtag_': 'Wordt gebruikt door Google Analytics om het aantal verzoeken te beperken.',
				_gid: 'Volgt bezoekergedrag over meerdere sessies zodat Google Analytics verkeer en interacties kan analyseren.',
				IDE: 'Wordt gebruikt door Google DoubleClick om gebruikersgedrag te volgen en advertenties te personaliseren op basis van eerdere interacties.',
				_gcl_au: 'Wordt gebruikt door Google AdSense om advertentie-interacties te volgen en advertentie-inhoud te optimaliseren.',
				_fbp: 'Facebook Pixel marketingcookie gebruikt om bezoekers te identificeren en te volgen op websites die Facebook-advertenties gebruiken.',
				last_pys_landing_page: 'Pixel Your Site (PYS). Slaat de laatste landingspagina op om advertentieconversies toe te wijzen.',
				last_pysTrafficSource: 'Pixel Your Site. Volgt de laatste verkeersbron (bijvoorbeeld waar het bezoek vandaan kwam vóór een aankoop).',
				pys_first_visit: 'Pixel Your Site. Identificeert of dit het eerste bezoek is, wat essentieel is voor het volgen van de klantreis.',
				pys_landing_page: 'Pixel Your Site. Slaat de URL op van de allereerste pagina waardoor de bezoeker de website heeft betreden.',
				pys_session_limit: 'Pixel Your Site. Technische sessieduurregistratie voor gedragsanalyse via pixels.',
				pys_start_session: 'Pixel Your Site. Identificeert het begin van de sessie om gegevens te synchroniseren met Facebook- en Google-pixels.',
				pysTrafficSource: 'Pixel Your Site. Slaat de oorspronkelijke verkeersbron op (bijvoorbeeld "facebook_ad") voor remarketing-attributie.',
				'^_pk_id': 'Slaat een uniek bezoekers-ID op dat door Matomo Analytics wordt gebruikt om terugkerende bezoekers te herkennen.',
				'^_pk_ses': 'Matomo Analytics sessiecookie die tijdelijk paginaweergavegegevens opslaat tijdens het huidige bezoek.',
				'^_pk_ref': 'Slaat attributiegegevens op (verwijzer/campagne) voor Matomo Analytics-rapporten.',
				mtm_consent: 'Slaat de Matomo-toestemmingsstatus op voor deze bezoeker.',
				mtm_cookie_consent: 'Slaat de Matomo cookie-toestemmingsstatus op wanneer de cookie-consent modus wordt gebruikt.',
				ccwps_consent: 'Slaat de toestemmingskeuzes van de bezoeker op in deze plugin zodat geselecteerde categorieën bij toekomstige paginaweergaven worden gerespecteerd.',
				ccwps_version: 'Slaat de toestemmingsconfiguratie-versie op in de plugin om wijzigingen te detecteren en indien nodig opnieuw toestemming te vragen.',
				'__Secure-1PAPISID / 3PAPISID': 'Wordt gebruikt voor advertentiepersonalisatie en interactiemeting. Gekoppeld aan uw Google-account.',
				'__Secure-1PSID / 3PSID': 'Bevat versleutelde details over uw Google-accountidentiteit en recente aanmelddatum.',
				'__Secure-1PSIDCC / 3PSIDCC': 'Wordt gebruikt voor beveiliging en het opslaan van gebruikersvoorkeuren met betrekking tot Google-advertenties.',
				'__Secure-1PSIDTS / 3PSIDTS': 'Tijdstempel en beveiligingscookie helpen bij het detecteren van verdachte activiteit en het verminderen van fraude.',
				'__Secure-BUCKET': 'Interne identificator gebruikt om gebruikers toe te wijzen aan experimentele Google-functies (A/B-tests).',
				ADS_VISITOR_ID: 'Bezoekersidentificator gebruikt om interacties met advertenties te volgen.',
				APISID: 'Wordt gebruikt om gebruikers te identificeren wanneer ingebedde Google-services (zoals Maps of YouTube) worden geladen op andere sites.',
				SAPISID: 'Beveiligde API-identiteitscookie gebruikt voor achtergrond Google-serviceverzoeken en fraudepreventie.',
				SID: 'Ondertekende en versleutelde Google-accountidentificator gebruikt om ingelogde sessies te onderhouden.',
				SSID: 'Companion-cookie die alleen via HTTPS wordt verzonden voor identiteitsverificatie in beveiligde verbindingen.',
				HSID: 'Wordt gebruikt om de identiteit van de gebruiker te verifiëren en frauduleus gebruik van inloggegevens te voorkomen.',
				NID: 'Slaat unieke identificator, zoekvoorkeuren en advertentiepersonalisatie-informatie op.',
				OTZ: 'Geaggregeerde gebruiksstatistieken en indicatoren voor de versie van de zoekmachine.',
				S: 'Sessie-identificator gebruikt door Google-factureringsinterfaces om aankoop- of factureringsstatus bij te houden.',
				SEARCH_SAMESITE: 'Helpt beschermen tegen CSRF-aanvallen door te zorgen dat cookies alleen in veilige contexten worden verzonden.',
				SIDCC: 'Beveiligingscookie gekoppeld aan Google-services en fraudebescherming.'
			}
		};
	}

	function getAddonPresetCookieDescriptionTranslations() {
		return {
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
				sbjs_migrations: 'Pomocná cookie používaná na správne spracovanie a migráciu analytických údajov medzi verziami sledovania.'
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
				sbjs_migrations: 'Helper cookie used to process and migrate Sourcebuster tracking data between versions.'
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
				sbjs_migrations: 'Pomocná cookie pro správné zpracování a migraci analytických dat mezi verzemi sledování.'
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
				sbjs_migrations: 'Hilfs-Cookie zur korrekten Verarbeitung und Migration von Sourcebuster-Trackingdaten zwischen Versionen.'
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
				sbjs_migrations: 'Cookie d’assistance utilisé pour traiter et migrer correctement les données de suivi entre versions.'
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
				sbjs_migrations: 'Cookie auxiliar para el procesamiento y migración correctos de datos de seguimiento entre versiones.'
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
				sbjs_migrations: 'Pomocnicze cookie do prawidłowego przetwarzania i migracji danych śledzenia między wersjami.'
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
				sbjs_migrations: 'Segedcookie a kovetesi adatok verzioik kozotti helyes feldolgozasahoz es migraciojahoz.'
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
				sbjs_migrations: 'Cookie di supporto usato per la corretta elaborazione e migrazione dei dati di tracciamento tra versioni.'
			},
			ro: {
				'^_hjSessionUser_': 'Cookie Hotjar care recunoaște utilizatorul la vizitele repetate pentru analiză și îmbunătățirea experienței utilizatorului.',
				'^_hjSession_': 'Cookie Hotjar utilizat pentru a identifica sesiunea curentă a utilizatorului și a analiza utilizarea site-ului.',
				'^_hjIncludedInSessionSample_?$': 'Cookie Hotjar care determină dacă vizita utilizatorului este inclusă în eșantionul de măsurare analitică.',
				_hjOptOut: 'Cookie Hotjar care stochează informația că utilizatorul nu dorește să fie urmărit de instrumentele analitice Hotjar.',
				sbjs_first: 'Date despre prima sursă de trafic (de unde a venit vizitatorul inițial).',
				sbjs_current: 'Stochează informații despre sursa curentă de trafic a site-ului (de exemplu căutare, reclamă sau vizită directă).',
				sbjs_first_add: 'Metadate suplimentare despre prima vizită (dată, număr de pagini, URL de intrare).',
				sbjs_current_add: 'Metadate suplimentare despre vizita curentă (la fel ca first_add, dar pentru sesiunea activă).',
				sbjs_udata: 'Stochează date tehnice și de navigare anonimizate pentru analiza traficului.',
				sbjs_session: 'Determină dacă o vizită este încă activă sau a început o nouă sesiune.',
				sbjs_migrations: 'Cookie ajutător utilizat pentru a procesa și migra corect datele de urmărire Sourcebuster între versiuni.'
			},
			nl: {
				'^_hjSessionUser_': 'Hotjar-cookie die de gebruiker herkent bij herhaalde bezoeken voor analyse en verbetering van de gebruikerservaring.',
				'^_hjSession_': 'Hotjar-cookie gebruikt om de huidige gebruikerssessie te identificeren en websitegebruik te analyseren.',
				'^_hjIncludedInSessionSample_?$': 'Hotjar-cookie die bepaalt of het bezoek van de gebruiker wordt opgenomen in het analytisch meetmonster.',
				_hjOptOut: 'Hotjar-cookie die opslaat dat de gebruiker niet gevolgd wil worden door Hotjar-analysetools.',
				sbjs_first: 'Gegevens over de allereerste verkeersbron (waar de bezoeker oorspronkelijk vandaan kwam).',
				sbjs_current: 'Slaat informatie op over de huidige websiteverkeersbron (bijvoorbeeld zoekmachine, advertentie of direct bezoek).',
				sbjs_first_add: 'Aanvullende metagegevens over het eerste bezoek (datum, aantal pagina\'s, invoer-URL).',
				sbjs_current_add: 'Aanvullende metagegevens over het huidige bezoek (hetzelfde als first_add, maar voor de actieve sessie).',
				sbjs_udata: 'Slaat geanonimiseerde technische en navigatiegegevens op voor verkeersanalyse.',
				sbjs_session: 'Bepaalt of een bezoek nog actief is of een nieuwe sessie is gestart.',
				sbjs_migrations: 'Hulpcookie gebruikt om Sourcebuster-trackinggegevens correct te verwerken en te migreren tussen versies.'
			}
		};
	}

	function getPresetCookieExpirationTranslations() {
		return {
			'1 year':      { sk: '1 rok', en: '1 year', cs: '1 rok', de: '1 Jahr', fr: '1 an', es: '1 año', pl: '1 rok', hu: '1 év', it: '1 anno', ro: '1 an', nl: '1 jaar' },
			'1 hour':      { sk: '1 hodina', en: '1 hour', cs: '1 hodina', de: '1 Stunde', fr: '1 heure', es: '1 hora', pl: '1 godzina', hu: '1 ora', it: '1 ora', ro: '1 oră', nl: '1 uur' },
			'1 month':     { sk: '1 mesiac', en: '1 month', cs: '1 měsíc', de: '1 Monat', fr: '1 mois', es: '1 mes', pl: '1 miesiąc', hu: '1 hónap', it: '1 mese', ro: '1 lună', nl: '1 maand' },
			'2 years':     { sk: '2 roky', en: '2 years', cs: '2 roky', de: '2 Jahre', fr: '2 ans', es: '2 años', pl: '2 lata', hu: '2 év', it: '2 anni', ro: '2 ani', nl: '2 jaar' },
			'3 months':    { sk: '3 mesiace', en: '3 months', cs: '3 měsíce', de: '3 Monate', fr: '3 mois', es: '3 meses', pl: '3 miesiące', hu: '3 hónap', it: '3 mesi', ro: '3 luni', nl: '3 maanden' },
			'6 months':    { sk: '6 mesiacov', en: '6 months', cs: '6 měsíců', de: '6 Monate', fr: '6 mois', es: '6 meses', pl: '6 miesięcy', hu: '6 hónap', it: '6 mesi', ro: '6 luni', nl: '6 maanden' },
			'13 months':   { sk: '13 mesiacov', en: '13 months', cs: '13 měsíců', de: '13 Monate', fr: '13 mois', es: '13 meses', pl: '13 miesięcy', hu: '13 hónap', it: '13 mesi', ro: '13 luni', nl: '13 maanden' },
			'30 minutes':  { sk: '30 minút', en: '30 minutes', cs: '30 minut', de: '30 Minuten', fr: '30 minutes', es: '30 minutos', pl: '30 minut', hu: '30 perc', it: '30 minuti', ro: '30 de minute', nl: '30 minuten' },
			'Session':     { sk: 'Relácia', en: 'Session', cs: 'Relace', de: 'Sitzung', fr: 'Session', es: 'Sesión', pl: 'Sesja', hu: 'Munkamenet', it: 'Sessione', ro: 'Sesiune', nl: 'Sessie' }
		};
	}

	function getLocalizedCookieDescription(cookie) {
		if (!cookie || !cookie.name) return cookie && cookie.desc ? cookie.desc : '';

		var allTranslations = getPresetCookieDescriptionTranslations();
		var addonTranslations = getAddonPresetCookieDescriptionTranslations();
		var normalized = normalizeLangCode(activeFrontendLang);
		var base = normalized.split('-')[0];
		var byLang = allTranslations[normalized] || allTranslations[base] || allTranslations.en || {};
		var byLangAddon = addonTranslations[normalized] || addonTranslations[base] || addonTranslations.en || {};

		return byLang[cookie.name] || byLangAddon[cookie.name] || cookie.desc || '';
	}

	function getLocalizedCookieExpiration(rawExpiration) {
		var value = String(rawExpiration || '').trim();
		if (!value) return '';

		var all = getPresetCookieExpirationTranslations();
		var variants = all[value] || null;
		if (!variants) return value;

		var normalized = normalizeLangCode(activeFrontendLang);
		var base = normalized.split('-')[0];
		return variants[normalized] || variants[base] || variants.en || value;
	}

	function localizeShortcodeCookieListTables() {
		var tables = document.querySelectorAll('.ccwps-cl-table');
		if (!tables || !tables.length) return;

		for (var ti = 0; ti < tables.length; ti++) {
			var table = tables[ti];
			
			// Get parent container with data attributes
			var container = table.closest('[data-ccwps-cookie-list="true"]');
			var catLabels = {};
			if (container) {
				catLabels.necessary = container.getAttribute('data-label-necessary');
				catLabels.analytics = container.getAttribute('data-label-analytics');
				catLabels.targeting = container.getAttribute('data-label-targeting');
				catLabels.preferences = container.getAttribute('data-label-preferences');
			}

			// Localize category titles
			var catTitles = table.parentElement.querySelector('.ccwps-cl-cat-title');
			if (catTitles) {
				var catTitles = table.closest('[data-ccwps-cat]');
				if (catTitles) {
					var cat = catTitles.getAttribute('data-ccwps-cat');
					if (cat && catLabels[cat]) {
						catTitles.textContent = catLabels[cat];
					}
				}
			}
			
			// Localize column headers
			var ths = table.querySelectorAll('thead th');
			if (ths.length >= 4) {
				ths[0].textContent = i18n.cookieName || ths[0].textContent;
				ths[1].textContent = i18n.cookieDomain || ths[1].textContent;
				ths[2].textContent = i18n.cookieExpiration || ths[2].textContent;
				ths[3].textContent = i18n.cookieDescription || ths[3].textContent;
			}

			var rows = table.querySelectorAll('tbody tr');
			for (var ri = 0; ri < rows.length; ri++) {
				var cells = rows[ri].children;
				if (!cells || cells.length < 4) continue;

				var codeEl = cells[0].querySelector('code');
				var cookieName = codeEl ? String(codeEl.textContent || '').trim() : String(cells[0].textContent || '').trim();

				var expText = String(cells[2].textContent || '').trim();
				if (expText && '—' !== expText) {
					cells[2].textContent = getLocalizedCookieExpiration(expText);
				}

				var descText = String(cells[3].textContent || '').trim();
				if (descText && '—' !== descText) {
					cells[3].textContent = getLocalizedCookieDescription({ name: cookieName, desc: descText });
				}
			}
		}
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

	function getSafeLinkUrl(url) {
		var value = String(url || '').trim();
		if (!value) return '';

		if (value.charAt(0) === '/') {
			return value;
		}

		try {
			var parsed = new URL(value, location.href);
			if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
				return parsed.href;
			}
		} catch (e) {
			return '';
		}

		return '';
	}

	function buildLegalLinksHtml(extraClass) {
		var legal = C.legalLinks || {};
		var links = [];

		if (legal.cookiePolicy && legal.cookiePolicy.enabled) {
			var cookieUrl = getSafeLinkUrl(legal.cookiePolicy.url);
			if (cookieUrl) {
				links.push('<a href="' + esc(cookieUrl) + '" target="_blank" rel="noopener">' + esc(legal.cookiePolicy.text || 'Cookie Policy') + '</a>');
			}
		}

		if (legal.privacyPolicy && legal.privacyPolicy.enabled) {
			var privacyUrl = getSafeLinkUrl(legal.privacyPolicy.url);
			if (privacyUrl) {
				links.push('<a href="' + esc(privacyUrl) + '" target="_blank" rel="noopener">' + esc(legal.privacyPolicy.text || 'Privacy Policy') + '</a>');
			}
		}

		if (!links.length) return '';

		return '<div class="ccwps-legal-links ' + esc(extraClass || '') + '">' + links.join('<span class="ccwps-legal-links-sep"> | </span>') + '</div>';
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
		var spacing = C.spacing || {};
		var bannerSpacing = spacing.banner || {};
		var modalSpacing = spacing.modal || {};

		// Compute darker hover for primary if not explicitly set
		var primBg   = col.btnPrimaryBg   || col.primary || '#1a73e8';
		var primBgHv = col.btnPrimaryBgHv || primBg;
		var primTxt  = col.btnPrimaryTxt  || col.btnText || '#ffffff';
		var primTxtHv = col.btnText || primTxt;

		var ghostBg     = col.btnGhostBg     || '#f0f2f5';
		var ghostBgHv   = col.btnGhostBgHv   || '#e5e7eb';
		var ghostTxt    = col.btnGhostTxt    || col.text || '#111827';
		var ghostTxtHv  = col.btnGhostTxtHv  || ghostTxt;

		var outlineBg     = col.btnOutlineBg     || 'transparent';
		var outlineBgHv   = col.btnOutlineBgHv   || col.primary || '#1a73e8';
		var outlineTxt    = col.btnOutlineTxt    || col.primary || '#1a73e8';
		var outlineBorder = col.btnOutlineBorder || col.primary || '#1a73e8';

		var modalBg       = col.modalBg       || col.bg      || '#ffffff';
		var modalHeaderBg = col.modalHeaderBg || col.bg      || '#ffffff';
		var modalFooterBg = col.modalFooterBg || '#f9fafb';
		var modalBorder   = col.modalBorder   || '#e5e7eb';
		var modalTxt      = col.modalText     || col.text    || '#111827';
		var modalConsentId = col.modalConsentId || modalTxt;

		var catBg   = col.catHeaderBg    || '#f9fafb';
		var catBgHv = col.catHeaderBgHv  || '#f0f2f5';
		var toggleC = col.toggleOnColor  || col.primary || '#1a73e8';
		var alwaysC = col.alwaysOnColor  || col.primary || '#1a73e8';
		var floatIconBg   = col.floatingIconBg   || col.primary || '#1a73e8';
		var floatIconBgHv = col.floatingIconBgHv || floatIconBg;
		var floatIconC    = col.floatingIconColor || '#ffffff';
		var floatIconMarginY = intOr(C.iconMarginY, 0);
		var floatIconMarginYMobile = intOr(C.iconMarginYMobile, 0);
		var floatPopupBg  = col.floatingPopupBg   || col.bg || '#ffffff';
		var floatPopupTxt = col.floatingPopupText || col.text || '#111827';
		var cloudBgOpacity = parseInt(col.cloudBgOpacity, 10);
		if (isNaN(cloudBgOpacity)) cloudBgOpacity = 70;
		cloudBgOpacity = Math.max(0, Math.min(100, cloudBgOpacity));

		function intOr(value, fallback) {
			var n = parseInt(value, 10);
			return isNaN(n) ? fallback : n;
		}

		var bannerAcceptBtnPt = intOr(bannerSpacing.acceptBtnPaddingT, 11);
		var bannerAcceptBtnPr = intOr(bannerSpacing.acceptBtnPaddingR, 20);
		var bannerAcceptBtnPb = intOr(bannerSpacing.acceptBtnPaddingB, 11);
		var bannerAcceptBtnPl = intOr(bannerSpacing.acceptBtnPaddingL, 20);
		var bannerAcceptBtnMt = intOr(bannerSpacing.acceptBtnMarginT, 0);
		var bannerAcceptBtnMr = intOr(bannerSpacing.acceptBtnMarginR, 0);
		var bannerAcceptBtnMb = intOr(bannerSpacing.acceptBtnMarginB, 0);
		var bannerAcceptBtnMl = intOr(bannerSpacing.acceptBtnMarginL, 0);

		var bannerRejectBtnPt = intOr(bannerSpacing.rejectBtnPaddingT, 11);
		var bannerRejectBtnPr = intOr(bannerSpacing.rejectBtnPaddingR, 20);
		var bannerRejectBtnPb = intOr(bannerSpacing.rejectBtnPaddingB, 11);
		var bannerRejectBtnPl = intOr(bannerSpacing.rejectBtnPaddingL, 20);
		var bannerRejectBtnMt = intOr(bannerSpacing.rejectBtnMarginT, 0);
		var bannerRejectBtnMr = intOr(bannerSpacing.rejectBtnMarginR, 0);
		var bannerRejectBtnMb = intOr(bannerSpacing.rejectBtnMarginB, 0);
		var bannerRejectBtnMl = intOr(bannerSpacing.rejectBtnMarginL, 0);

		var bannerManageBtnPt = intOr(bannerSpacing.manageBtnPaddingT, 11);
		var bannerManageBtnPr = intOr(bannerSpacing.manageBtnPaddingR, 20);
		var bannerManageBtnPb = intOr(bannerSpacing.manageBtnPaddingB, 11);
		var bannerManageBtnPl = intOr(bannerSpacing.manageBtnPaddingL, 20);
		var bannerManageBtnMt = intOr(bannerSpacing.manageBtnMarginT, 0);
		var bannerManageBtnMr = intOr(bannerSpacing.manageBtnMarginR, 0);
		var bannerManageBtnMb = intOr(bannerSpacing.manageBtnMarginB, 0);
		var bannerManageBtnMl = intOr(bannerSpacing.manageBtnMarginL, 0);

		var modalSaveBtnPt = intOr(modalSpacing.saveBtnPaddingT, 11);
		var modalSaveBtnPr = intOr(modalSpacing.saveBtnPaddingR, 20);
		var modalSaveBtnPb = intOr(modalSpacing.saveBtnPaddingB, 11);
		var modalSaveBtnPl = intOr(modalSpacing.saveBtnPaddingL, 20);
		var modalSaveBtnMt = intOr(modalSpacing.saveBtnMarginT, 0);
		var modalSaveBtnMr = intOr(modalSpacing.saveBtnMarginR, 0);
		var modalSaveBtnMb = intOr(modalSpacing.saveBtnMarginB, 0);
		var modalSaveBtnMl = intOr(modalSpacing.saveBtnMarginL, 0);

		var modalRejectBtnPt = intOr(modalSpacing.rejectBtnPaddingT, 11);
		var modalRejectBtnPr = intOr(modalSpacing.rejectBtnPaddingR, 20);
		var modalRejectBtnPb = intOr(modalSpacing.rejectBtnPaddingB, 11);
		var modalRejectBtnPl = intOr(modalSpacing.rejectBtnPaddingL, 20);
		var modalRejectBtnMt = intOr(modalSpacing.rejectBtnMarginT, 0);
		var modalRejectBtnMr = intOr(modalSpacing.rejectBtnMarginR, 0);
		var modalRejectBtnMb = intOr(modalSpacing.rejectBtnMarginB, 0);
		var modalRejectBtnMl = intOr(modalSpacing.rejectBtnMarginL, 0);

		var modalAcceptBtnPt = intOr(modalSpacing.acceptBtnPaddingT, 11);
		var modalAcceptBtnPr = intOr(modalSpacing.acceptBtnPaddingR, 20);
		var modalAcceptBtnPb = intOr(modalSpacing.acceptBtnPaddingB, 11);
		var modalAcceptBtnPl = intOr(modalSpacing.acceptBtnPaddingL, 20);
		var modalAcceptBtnMt = intOr(modalSpacing.acceptBtnMarginT, 0);
		var modalAcceptBtnMr = intOr(modalSpacing.acceptBtnMarginR, 0);
		var modalAcceptBtnMb = intOr(modalSpacing.acceptBtnMarginB, 0);
		var modalAcceptBtnMl = intOr(modalSpacing.acceptBtnMarginL, 0);

		var style = document.createElement('style');
		style.id  = 'ccwps-vars';
		style.textContent = [
			':root {',
			// Base
			'--ccwps-primary:    ' + (col.primary || '#1a73e8') + ';',
			'--ccwps-text:       ' + (col.text    || '#111827') + ';',
			'--ccwps-title-text: ' + (col.titleText || '#111827') + ';',
			'--ccwps-desc-text:  ' + (col.descText  || col.text || '#111827') + ';',
			'--ccwps-bg:         ' + (col.bg      || '#ffffff') + ';',
			'--ccwps-cloud-bg-opacity: ' + cloudBgOpacity + '%;',
			'--ccwps-float-icon-bg:    ' + floatIconBg + ';',
			'--ccwps-float-icon-bg-hv: ' + floatIconBgHv + ';',
			'--ccwps-float-icon-color: ' + floatIconC + ';',
			'--ccwps-float-icon-my:    ' + floatIconMarginY + 'px;',
			'--ccwps-float-icon-my-mobile: ' + floatIconMarginYMobile + 'px;',
			'--ccwps-float-tip-bg:     ' + floatPopupBg + ';',
			'--ccwps-float-tip-text:   ' + floatPopupTxt + ';',
			'--ccwps-banner-text-pt: ' + intOr(bannerSpacing.textPaddingT, 0) + 'px;',
			'--ccwps-banner-text-pr: ' + intOr(bannerSpacing.textPaddingR, 0) + 'px;',
			'--ccwps-banner-text-pb: ' + intOr(bannerSpacing.textPaddingB, 0) + 'px;',
			'--ccwps-banner-text-pl: ' + intOr(bannerSpacing.textPaddingL, 0) + 'px;',
			'--ccwps-banner-text-mt: ' + intOr(bannerSpacing.textMarginT, 0) + 'px;',
			'--ccwps-banner-text-mr: ' + intOr(bannerSpacing.textMarginR, 0) + 'px;',
			'--ccwps-banner-text-mb: ' + intOr(bannerSpacing.textMarginB, 5) + 'px;',
			'--ccwps-banner-text-ml: ' + intOr(bannerSpacing.textMarginL, 0) + 'px;',
			'--ccwps-banner-links-pt: ' + intOr(bannerSpacing.linksPaddingT, 0) + 'px;',
			'--ccwps-banner-links-pr: ' + intOr(bannerSpacing.linksPaddingR, 0) + 'px;',
			'--ccwps-banner-links-pb: ' + intOr(bannerSpacing.linksPaddingB, 0) + 'px;',
			'--ccwps-banner-links-pl: ' + intOr(bannerSpacing.linksPaddingL, 0) + 'px;',
			'--ccwps-banner-links-mt: ' + intOr(bannerSpacing.linksMarginT, 0) + 'px;',
			'--ccwps-banner-links-mr: ' + intOr(bannerSpacing.linksMarginR, 0) + 'px;',
			'--ccwps-banner-links-mb: ' + intOr(bannerSpacing.linksMarginB, 30) + 'px;',
			'--ccwps-banner-links-ml: ' + intOr(bannerSpacing.linksMarginL, 0) + 'px;',
			'--ccwps-banner-accept-btn-pt: ' + bannerAcceptBtnPt + 'px;',
			'--ccwps-banner-accept-btn-pr: ' + bannerAcceptBtnPr + 'px;',
			'--ccwps-banner-accept-btn-pb: ' + bannerAcceptBtnPb + 'px;',
			'--ccwps-banner-accept-btn-pl: ' + bannerAcceptBtnPl + 'px;',
			'--ccwps-banner-accept-btn-mt: ' + bannerAcceptBtnMt + 'px;',
			'--ccwps-banner-accept-btn-mr: ' + bannerAcceptBtnMr + 'px;',
			'--ccwps-banner-accept-btn-mb: ' + bannerAcceptBtnMb + 'px;',
			'--ccwps-banner-accept-btn-ml: ' + bannerAcceptBtnMl + 'px;',
			'--ccwps-banner-reject-btn-pt: ' + bannerRejectBtnPt + 'px;',
			'--ccwps-banner-reject-btn-pr: ' + bannerRejectBtnPr + 'px;',
			'--ccwps-banner-reject-btn-pb: ' + bannerRejectBtnPb + 'px;',
			'--ccwps-banner-reject-btn-pl: ' + bannerRejectBtnPl + 'px;',
			'--ccwps-banner-reject-btn-mt: ' + bannerRejectBtnMt + 'px;',
			'--ccwps-banner-reject-btn-mr: ' + bannerRejectBtnMr + 'px;',
			'--ccwps-banner-reject-btn-mb: ' + bannerRejectBtnMb + 'px;',
			'--ccwps-banner-reject-btn-ml: ' + bannerRejectBtnMl + 'px;',
			'--ccwps-banner-manage-btn-pt: ' + bannerManageBtnPt + 'px;',
			'--ccwps-banner-manage-btn-pr: ' + bannerManageBtnPr + 'px;',
			'--ccwps-banner-manage-btn-pb: ' + bannerManageBtnPb + 'px;',
			'--ccwps-banner-manage-btn-pl: ' + bannerManageBtnPl + 'px;',
			'--ccwps-banner-manage-btn-mt: ' + bannerManageBtnMt + 'px;',
			'--ccwps-banner-manage-btn-mr: ' + bannerManageBtnMr + 'px;',
			'--ccwps-banner-manage-btn-mb: ' + bannerManageBtnMb + 'px;',
			'--ccwps-banner-manage-btn-ml: ' + bannerManageBtnMl + 'px;',
			'--ccwps-banner-btn-gap: ' + intOr(bannerSpacing.btnGap, 10) + 'px;',
			'--ccwps-modal-body-pt: ' + intOr(modalSpacing.bodyPaddingT, 24) + 'px;',
			'--ccwps-modal-body-pr: ' + intOr(modalSpacing.bodyPaddingR, 28) + 'px;',
			'--ccwps-modal-body-pb: ' + intOr(modalSpacing.bodyPaddingB, 24) + 'px;',
			'--ccwps-modal-body-pl: ' + intOr(modalSpacing.bodyPaddingL, 28) + 'px;',
			'--ccwps-modal-body-mt: ' + intOr(modalSpacing.bodyMarginT, 0) + 'px;',
			'--ccwps-modal-body-mr: ' + intOr(modalSpacing.bodyMarginR, 0) + 'px;',
			'--ccwps-modal-body-mb: ' + intOr(modalSpacing.bodyMarginB, 0) + 'px;',
			'--ccwps-modal-body-ml: ' + intOr(modalSpacing.bodyMarginL, 0) + 'px;',
			'--ccwps-modal-save-btn-pt: ' + modalSaveBtnPt + 'px;',
			'--ccwps-modal-save-btn-pr: ' + modalSaveBtnPr + 'px;',
			'--ccwps-modal-save-btn-pb: ' + modalSaveBtnPb + 'px;',
			'--ccwps-modal-save-btn-pl: ' + modalSaveBtnPl + 'px;',
			'--ccwps-modal-save-btn-mt: ' + modalSaveBtnMt + 'px;',
			'--ccwps-modal-save-btn-mr: ' + modalSaveBtnMr + 'px;',
			'--ccwps-modal-save-btn-mb: ' + modalSaveBtnMb + 'px;',
			'--ccwps-modal-save-btn-ml: ' + modalSaveBtnMl + 'px;',
			'--ccwps-modal-reject-btn-pt: ' + modalRejectBtnPt + 'px;',
			'--ccwps-modal-reject-btn-pr: ' + modalRejectBtnPr + 'px;',
			'--ccwps-modal-reject-btn-pb: ' + modalRejectBtnPb + 'px;',
			'--ccwps-modal-reject-btn-pl: ' + modalRejectBtnPl + 'px;',
			'--ccwps-modal-reject-btn-mt: ' + modalRejectBtnMt + 'px;',
			'--ccwps-modal-reject-btn-mr: ' + modalRejectBtnMr + 'px;',
			'--ccwps-modal-reject-btn-mb: ' + modalRejectBtnMb + 'px;',
			'--ccwps-modal-reject-btn-ml: ' + modalRejectBtnMl + 'px;',
			'--ccwps-modal-accept-btn-pt: ' + modalAcceptBtnPt + 'px;',
			'--ccwps-modal-accept-btn-pr: ' + modalAcceptBtnPr + 'px;',
			'--ccwps-modal-accept-btn-pb: ' + modalAcceptBtnPb + 'px;',
			'--ccwps-modal-accept-btn-pl: ' + modalAcceptBtnPl + 'px;',
			'--ccwps-modal-accept-btn-mt: ' + modalAcceptBtnMt + 'px;',
			'--ccwps-modal-accept-btn-mr: ' + modalAcceptBtnMr + 'px;',
			'--ccwps-modal-accept-btn-mb: ' + modalAcceptBtnMb + 'px;',
			'--ccwps-modal-accept-btn-ml: ' + modalAcceptBtnMl + 'px;',
			'--ccwps-modal-btn-gap: ' + intOr(modalSpacing.btnGap, 10) + 'px;',
			'--ccwps-modal-desc-mt: ' + intOr(modalSpacing.descMarginT, 0) + 'px;',
			'--ccwps-modal-desc-mr: ' + intOr(modalSpacing.descMarginR, 0) + 'px;',
			'--ccwps-modal-desc-mb: ' + intOr(modalSpacing.descMarginB, 6) + 'px;',
			'--ccwps-modal-desc-ml: ' + intOr(modalSpacing.descMarginL, 0) + 'px;',
			'--ccwps-muted:      #6b7280;',
			'--ccwps-border:     ' + modalBorder + ';',
			'--ccwps-surface:    ' + catBg + ';',
			'--ccwps-surface2:   ' + catBgHv + ';',
			'--ccwps-font:       ' + (C.fontFamily || '\'Poppins\', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif') + ';',
			// Border radii
			'--ccwps-r:          ' + rBn + 'px;',
			'--ccwps-r-sm:       ' + r + 'px;',
			// Primary button
			'--ccwps-btn-primary-bg:     ' + primBg    + ';',
			'--ccwps-btn-primary-bg-hv:  ' + primBgHv  + ';',
			'--ccwps-btn-primary-txt:    ' + primTxt   + ';',
			'--ccwps-btn-primary-txt-hv: ' + primTxtHv + ';',
			// Ghost button
			'--ccwps-btn-ghost-bg:       ' + ghostBg     + ';',
			'--ccwps-btn-ghost-bg-hv:    ' + ghostBgHv   + ';',
			'--ccwps-btn-ghost-txt:      ' + ghostTxt    + ';',
			'--ccwps-btn-ghost-txt-hv:   ' + ghostTxtHv  + ';',
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
			'--ccwps-modal-consent-id:   ' + modalConsentId + ';',
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
	function isInternalPluginScript(src) {
		if (!src) return false;
		var pluginAssetsPath = C.pluginAssetsPath || '';
		if (!pluginAssetsPath) return false;
		try {
			var u = new URL(src, location.href);
			return String(u.pathname || '').indexOf(pluginAssetsPath) !== -1;
		} catch (e) {
			return String(src).indexOf(pluginAssetsPath) !== -1;
		}
	}

	function matchRule(src, rule) {
		if (!src || isInternalPluginScript(src)) return false;
		if (rule.isRegex) {
			try { return new RegExp(rule.source, 'i').test(src); } catch (e) { return false; }
		}
		return src.toLowerCase().indexOf(rule.source.toLowerCase()) !== -1;
	}

	function normalizeHost(value) {
		return String(value || '').replace(/^www\./i, '').toLowerCase();
	}

	function isMatomoScriptAllowedWithoutConsent(src, category) {
		if (category !== 'analytics') return false;
		if (!C.matomoAnonymousWithoutConsent) return false;
		var matomoHost = normalizeHost(C.matomoHost);
		if (!matomoHost || !src) return false;

		try {
			var parsed = new URL(src, location.href);
			return normalizeHost(parsed.hostname) === matomoHost;
		} catch (e) {
			return String(src).toLowerCase().indexOf(matomoHost) !== -1;
		}
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
			if (isInternalPluginScript(src)) continue;
			for (var j = 0; j < rules.length; j++) {
				if (rules[j].cat === 'necessary') continue;
				if (consent && consent[rules[j].cat]) continue;
				if (isMatomoScriptAllowedWithoutConsent(src, rules[j].cat)) continue;
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

	function applyConsent(prefs, oldPrefs, options) {
		options = options || {};
		updateConsentMode(prefs);
		unblockScripts(prefs);
		if (oldPrefs) {
			var cats = ['analytics', 'targeting', 'preferences'];
			for (var i = 0; i < cats.length; i++) {
				if (oldPrefs[cats[i]] && !prefs[cats[i]]) clearCat(cats[i]);
			}
		}
		replayConsentGrantedEvents(prefs, oldPrefs);
		if (!options.silentUpdateEvent) {
			window.dispatchEvent(new CustomEvent('ccwps:consent-updated', {
				detail: {
					prefs: prefs,
					oldPrefs: oldPrefs || null
				}
			}));
		}
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
		var isBar    = layout === 'bar';

		var logoHtml = '';
		if (C.bannerLogoShow && C.bannerLogoUrl) {
			var w = parseInt(C.bannerLogoWidth) || 40;
			var imgTag = '<img src="' + esc(C.bannerLogoUrl) + '" alt="" style="width:' + w + 'px;height:auto;" loading="lazy">';
			if (C.bannerLogoLinkUrl) {
				logoHtml = '<div class="ccwps-banner-logo"><a href="' + esc(C.bannerLogoLinkUrl) + '" target="_blank" rel="noopener">' + imgTag + '</a></div>';
			} else {
				logoHtml = '<div class="ccwps-banner-logo">' + imgTag + '</div>';
			}
		}

		var el = document.createElement('div');
		el.className = 'ccwps-banner layout-' + layout + ' pos-' + position;
		el.setAttribute('role', 'dialog');
		el.setAttribute('aria-modal', 'true');
		el.setAttribute('aria-label', i18n.bannerTitle || 'Cookie Consent');

		var textHtml = '';
		var bannerLegalLinks = buildLegalLinksHtml('ccwps-legal-links-banner');

		if (isBar) {
			textHtml =
				'<div class="ccwps-banner-text">' +
					'<div class="ccwps-banner-headline">' +
						logoHtml +
						'<div class="ccwps-banner-title-wrap">' +
							'<div class="ccwps-banner-title">' + esc(i18n.bannerTitle) + '</div>' +
						'</div>' +
					'</div>' +
					'<div class="ccwps-banner-desc">' + (i18n.bannerDescription || '') + '</div>' +
					bannerLegalLinks +
				'</div>';
		} else {
			textHtml =
				logoHtml +
				'<div class="ccwps-banner-text">' +
					'<div class="ccwps-banner-title">' + esc(i18n.bannerTitle) + '</div>' +
					'<div class="ccwps-banner-desc">' + (i18n.bannerDescription || '') + '</div>' +
					bannerLegalLinks +
				'</div>';
		}

		el.innerHTML =
			textHtml +
			'<div class="ccwps-banner-actions">' +
				'<button class="ccwps-btn ccwps-btn-primary" id="ccwps-btn-accept">' + esc(i18n.acceptAll) + '</button>' +
				'<button class="ccwps-btn ccwps-btn-ghost"   id="ccwps-btn-reject">' + esc(i18n.rejectAll) + '</button>' +
				   '<button class="ccwps-btn ccwps-btn-outline" id="ccwps-btn-pref" data-ccwps-manage-consent="1">'   + esc(i18n.managePreferences) + '</button>' +
			'</div>';

		var titleEl = el.querySelector('.ccwps-banner-title');
		if (titleEl) {
			titleEl.style.color = col.titleText || '#111827';
		}

		var descEl = el.querySelector('.ccwps-banner-desc');
		if (descEl) {
			descEl.style.color = col.descText || col.text || '#111827';
		}

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
				? '<span class="ccwps-always-on">' + esc(i18n.alwaysOn || getStableAlwaysOnLabel()) + '</span>'
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
						'<td>' + (esc(getLocalizedCookieExpiration(ck.expiration)) || '—') + '</td>' +
						'<td>' + (esc(getLocalizedCookieDescription(ck)) || '—') + '</td>' +
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
						'<div class="ccwps-cat-top">' +
							'<div class="ccwps-cat-name">' + esc(cat.title) + '</div>' +
							'<div class="ccwps-cat-right">' +
								toggle +
								(cookies.length ? '<span class="ccwps-chevron">' + ICONS.chevron + '</span>' : '') +
							'</div>' +
						'</div>' +
						'<div class="ccwps-cat-desc">' + esc(cat.desc) + '</div>' +
					'</div>' +
					(cookies.length ? '<div class="ccwps-ck-list">' + tableHtml + '</div>' : '') +
				'</div>';
		}

		var overlay = document.createElement('div');
		overlay.className = 'ccwps-modal-overlay';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');

		var modalLegalLinks = buildLegalLinksHtml('ccwps-legal-links-modal');

		overlay.innerHTML =
			'<div class="ccwps-modal-box">' +
				'<div class="ccwps-modal-head">' +
					'<h2>' + esc(i18n.managePreferences) + '</h2>' +
					'<button class="ccwps-modal-close-btn" id="ccwps-modal-x" aria-label="' + esc(i18n.close || 'Zavrieť') + '">×</button>' +
				'</div>' +
				'<div class="ccwps-modal-body">' +
					idHtml +
					catsHtml +
					modalLegalLinks +
				'</div>' +
				'<div class="ccwps-modal-foot">' +
					'<button class="ccwps-btn ccwps-btn-primary" id="ccwps-modal-save">' + esc(i18n.savePreferences) + '</button>' +
					'<button class="ccwps-btn ccwps-btn-ghost"   id="ccwps-modal-reject">' + esc(i18n.rejectAll) + '</button>' +
					'<button class="ccwps-btn ccwps-btn-primary" id="ccwps-modal-accept">' + esc(i18n.acceptAll) + '</button>' +
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

		var poweredLabel = normalizePoweredByLabel(i18n.poweredBy);
		var tipPowered = C.showPoweredByLink
			? '<div class="ccwps-tip-powered"><a href="https://wps.sk" target="_blank" rel="noopener">Powered by ' + esc(poweredLabel) + '</a></div>'
			: '';

		tipEl = document.createElement('div');
		tipEl.className = 'ccwps-tip-popup';
		tipEl.innerHTML =
			'<div class="ccwps-tip-label">' + esc(i18n.consentIdLabel || 'ID vášho súhlasu') + '</div>' +
			idHtml +
			'<div class="ccwps-tip-btns">' +
				'<button class="ccwps-btn ccwps-btn-ghost ccwps-btn-sm" id="ccwps-tip-close">' + esc(i18n.close || 'Zavrieť') + '</button>' +
				'<button class="ccwps-btn ccwps-btn-primary ccwps-btn-sm" id="ccwps-tip-manage">' + esc(i18n.managePreferences || 'Spravovať súhlas') + '</button>' +
			'</div>' +
			tipPowered;

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

	function ensureUiShell() {
		if (!document.getElementById('ccwps-banner-wrap')) {
			var bannerWrap = document.createElement('div');
			bannerWrap.id = 'ccwps-banner-wrap';
			bannerWrap.setAttribute('aria-hidden', 'true');
			document.body.appendChild(bannerWrap);
		}

		if (!document.getElementById('ccwps-modal-wrap')) {
			var modalWrap = document.createElement('div');
			modalWrap.id = 'ccwps-modal-wrap';
			modalWrap.setAttribute('aria-hidden', 'true');
			document.body.appendChild(modalWrap);
		}

		if (!document.getElementById('ccwps-tip-wrap')) {
			var tipWrap = document.createElement('div');
			tipWrap.id = 'ccwps-tip-wrap';
			document.body.appendChild(tipWrap);
		}

		if (C.showFloatingIcon && !document.getElementById('ccwps-floating-icon')) {
			var icon = document.createElement('div');
			icon.id = 'ccwps-floating-icon';
			icon.setAttribute('aria-label', i18n.managePreferences || 'Cookie settings');
			icon.setAttribute('role', 'button');
			icon.setAttribute('tabindex', '0');
			icon.style.display = 'none';
			document.body.appendChild(icon);
		}
	}

	function showBanner() {
		ensureUiShell();
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
		ensureUiShell();
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

		ensureUiShell();
		injectVars();
		blockScripts();
		renderConsentIdTargets();
		bindManageConsentButtons();
		localizeShortcodeCookieListTables();

		var consent = getConsent();
		if (consent) {
			window.__ccwpsConsentState = consent;
			var prefs = {
				necessary:   true,
				analytics:   !!consent.analytics,
				targeting:   !!consent.targeting,
				preferences: !!consent.preferences
			};
			applyConsent(prefs, null, { silentUpdateEvent: true });
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
