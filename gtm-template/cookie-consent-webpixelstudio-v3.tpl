___INFO___

{
  "displayName": "Cookie Consent by Web Pixel Studio v3",
  "description": "Consent Mode v3 šablóna: nastaví default denied stav, aktivuje developer_id signál a okamžite aktualizuje súhlas podľa cookie ccwps_consent.",
  "id": "cvt_cookie_consent_wps_v3",
  "type": "TAG",
  "version": 1,
  "containerContexts": ["WEB"]
}


___TEMPLATE_PARAMETERS___

[
  {
    "type": "TEXT",
    "name": "cookieName",
    "displayName": "Názov consent cookie",
    "simpleValueType": true,
    "defaultValue": "ccwps_consent",
    "help": "Nemeňte, pokiaľ ste nezmenili názov cookie v plugine."
  },
  {
    "type": "TEXT",
    "name": "waitForUpdate",
    "displayName": "wait_for_update (ms)",
    "simpleValueType": true,
    "defaultValue": "500",
    "help": "Čas v milisekundách, ktorý GTM čaká na aktualizáciu súhlasu pred spustením tagov s predvoleným stavom."
  }
]


___SANDBOXED_JS_FOR_WEB_TEMPLATE___

var setDefaultConsentState = require('setDefaultConsentState');
var updateConsentState     = require('updateConsentState');
var getCookieValues        = require('getCookieValues');
var decodeUriComponent     = require('decodeUriComponent');
var makeInteger            = require('makeInteger');
var gtagSet                = require('gtagSet');
var JSON                   = require('JSON');

var cookieName = data.cookieName || 'ccwps_consent';
var parsedWait = makeInteger(data.waitForUpdate);
var waitMs     = (parsedWait === 0 || parsedWait) ? parsedWait : 500;

function hasFlagTrue(payload, key) {
  return payload.indexOf('"' + key + '":true') !== -1;
}

// 1. Nastaviť predvolený stav – všetko odmietnuté
setDefaultConsentState({
  'ad_storage':              'denied',
  'ad_user_data':            'denied',
  'ad_personalization':      'denied',
  'analytics_storage':       'denied',
  'functionality_storage':   'denied',
  'personalization_storage': 'denied',
  'security_storage':        'granted',
  'wait_for_update':         waitMs
});

// 1b. Consent Mode v3 signály (rovnaké správanie ako vo WordPress snippete)
gtagSet('ads_data_redaction', true);
gtagSet('url_passthrough', true);
gtagSet('developer_id.dZTNiMT', true);

// 2. Prečítať existujúci consent cookie a okamžite aktualizovať stav
var cookieValues = getCookieValues(cookieName);
if (cookieValues && cookieValues.length > 0) {
  var decoded = decodeUriComponent(cookieValues[0]);
  var compact = decoded ? decoded.split(' ').join('') : '';
  var looksLikeJson = compact && compact.indexOf('{') !== -1 && compact.indexOf('}') !== -1;

  if (looksLikeJson) {
    var targetingGranted   = hasFlagTrue(compact, 'targeting');
    var analyticsGranted   = hasFlagTrue(compact, 'analytics');
    var preferencesGranted = hasFlagTrue(compact, 'preferences');

    updateConsentState({
      'ad_storage':              targetingGranted   ? 'granted' : 'denied',
      'ad_user_data':            targetingGranted   ? 'granted' : 'denied',
      'ad_personalization':      targetingGranted   ? 'granted' : 'denied',
      'analytics_storage':       analyticsGranted   ? 'granted' : 'denied',
      'functionality_storage':   preferencesGranted ? 'granted' : 'denied',
      'personalization_storage': preferencesGranted ? 'granted' : 'denied',
      'security_storage':        'granted'
    });
  }
}

data.gtmOnSuccess();


___WEB_PERMISSIONS___

[
  {
    "instance": {
      "key": {
        "publicId": "write_data_layer",
        "versionId": "1"
      },
      "param": [
        {
          "key": "keyPatterns",
          "value": {
            "type": 2,
            "listItem": [
              {
                "type": 1,
                "string": "ads_data_redaction"
              },
              {
                "type": 1,
                "string": "url_passthrough"
              },
              {
                "type": 1,
                "string": "developer_id.dZTNiMT"
              }
            ]
          }
        }
      ]
    },
    "clientAnnotations": {
      "isEditedByUser": true
    },
    "isRequired": true
  },
  {
    "instance": {
      "key": {
        "publicId": "get_cookies",
        "versionId": "1"
      },
      "param": [
        {
          "key": "cookieAccess",
          "value": {
            "type": 1,
            "string": "specific"
          }
        },
        {
          "key": "cookieNames",
          "value": {
            "type": 2,
            "listItem": [
              {
                "type": 1,
                "string": "ccwps_consent"
              }
            ]
          }
        }
      ]
    },
    "clientAnnotations": {
      "isEditedByUser": true
    },
    "isRequired": true
  },
  {
    "instance": {
      "key": {
        "publicId": "access_consent",
        "versionId": "1"
      },
      "param": [
        {
          "key": "consentTypes",
          "value": {
            "type": 2,
            "listItem": [
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "ad_storage" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              },
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "ad_user_data" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              },
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "ad_personalization" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              },
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "analytics_storage" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              },
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "functionality_storage" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              },
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "personalization_storage" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              },
              {
                "type": 3,
                "mapKey": [
                  { "type": 1, "string": "consentType" },
                  { "type": 1, "string": "read" },
                  { "type": 1, "string": "write" }
                ],
                "mapValue": [
                  { "type": 1, "string": "security_storage" },
                  { "type": 8, "boolean": false },
                  { "type": 8, "boolean": true }
                ]
              }
            ]
          }
        }
      ]
    },
    "clientAnnotations": {
      "isEditedByUser": true
    },
    "isRequired": true
  }
]


___NOTES___

## Návod na použitie

1. GTM → Šablóny → Šablóny tagov → Nová
2. Tri bodky (⋮) → Importovať → nahrať súbor `cookie-consent-webpixelstudio-v3.tpl`
3. Tagy → Nový → zvoliť "Cookie Consent by Web Pixel Studio v3"
4. Trigger: **Inicializácia súhlasu – všetky stránky** (Consent Initialization – All Pages)
5. Uložiť a publikovať

## Ako to funguje

Šablóna beží PRED ostatnými tagmi (Consent Initialization fáza):
1. Nastaví všetky consent signály na `denied` (predvolené odmietnutie)
2. Nastaví v3 doplnkové signály `ads_data_redaction`, `url_passthrough` a `developer_id.dZTNiMT`
3. Ak návštevník už má uložený cookie `ccwps_consent`, okamžite aktualizuje stav podľa jeho preferencií – bez načítania stránky
4. Keď návštevník interaguje s bannerom, plugin zavolá `gtag('consent','update',…)` a GTM okamžite reaguje

## Mapovanie kategórií

| Cookie pole    | GTM Consent signal                                   |
|----------------|------------------------------------------------------|
| `analytics`    | `analytics_storage`                                  |
| `targeting`    | `ad_storage`, `ad_user_data`, `ad_personalization`   |
| `preferences`  | `functionality_storage`, `personalization_storage`   |
| *(vždy)*       | `security_storage` → `granted`                       |

## Odporúčané nastavenie GA4 tagu

V Google Analytics: GA4 Configuration tagu:
- Rozšírené nastavenia → Nastavenia súhlasu
- Zaškrtnúť: **Vyžadovať ďalší súhlas na spustenie tagu**
- Typ súhlasu: `analytics_storage`
