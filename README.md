# Solidres Payment Method Context Fix - README

## Probléma összefoglalása

A `confirmationform.php` fájlban a `payment_method_id` nem mindig jelent meg helyesen különböző context környezetekben (menü/hub vagy gyökér context). Az AJAX endpoint hívások 404 hibát adtak context mismatch miatt, különösen submenu és hub környezetekben.

## Megoldás

### Implementált komponensek

#### 1. **SolidresSessionContextHelper** (`components/com_solidres/helpers/sessioncontext.php`)
Központi helper osztály a session és context kezeléshez:
- Context paraméterek (Itemid, property_id, hub_id, site_id, reservation_id) kezelése
- Reservation details session management
- Context type detektálás (submenu/hub/root)
- Context-aware URL építés
- Session validáció

#### 2. **Főkomponens confirmationform.php** (`components/com_solidres/views/reservationasset/tmpl/confirmationform.php`)
Továbbfejlesztett confirmation form:
- Automatikus session context kezelés
- Fallback adatbázis lekérésre
- JavaScript URL builder funkciók
- Context-aware AJAX URL építés
- XSS védelem
- Debug információk

#### 3. **Plugin-specifikus confirmation formok**
- Qvik: `plugins/solidrespayment/qvik/tmpl/confirmationform.php`
- Revolut: `plugins/solidrespayment/revolut/tmpl/confirmationform.php`

Mindkettő használja a `SolidresSessionContextHelper`-t és implementálja a context-aware URL építést.

#### 4. **Language constants** (`language/hu-HU/hu-HU.com_solidres.ini`)
Magyar nyelvű fordítások a confirmation page-hez és hibaüzenetekhez.

## Főbb funkciók

### PHP Session Context kezelés

```php
// Context paraméterek lekérése
$contextParams = SolidresSessionContextHelper::getContextParams();

// Reservation details lekérése (session vagy DB-ből)
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();

// Context type detektálás
$contextType = SolidresSessionContextHelper::detectContextType();
// ['is_submenu' => bool, 'is_hub' => bool, 'is_root' => bool, 'current_path' => string]

// Context-aware URL építés
$url = SolidresSessionContextHelper::buildContextUrl('task.name', ['param' => 'value']);
```

### JavaScript URL Builder

```javascript
// Context-aware URL építés
const url = buildContextAwareUrl('index.php', {
    option: 'com_solidres',
    task: 'save'
});

// AJAX URL builder
const ajaxUrl = buildAjaxUrl('reservationasset.save', {
    format: 'json'
});
```

## Context támogatás

### Root Context
- URL: `https://example.com/index.php?option=com_solidres&...`
- AJAX: `https://example.com/index.php?option=com_solidres&task=...`

### Submenu Context
- URL: `https://example.com/foglalas/index.php?option=com_solidres&...`
- AJAX: `https://example.com/foglalas/index.php?option=com_solidres&task=...`
- **Automatikusan detektálva**: pathname.indexOf('index.php') > 0

### Hub Context
- URL: `https://example.com/index.php?option=com_solidres&hub_id=5&...`
- AJAX: `https://example.com/index.php?option=com_solidres&hub_id=5&task=...`
- **Hub ID megmarad**: minden URL-ben és session-ban

## Payment Method megjelenítés

A payment_method_id biztonságos és kontextusfüggetlen megjelenítése:

```php
$paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
$paymentMethodKey = 'SR_PAYMENT_METHOD_' . strtoupper($paymentMethodId);
$paymentMethodName = Text::_($paymentMethodKey);

// Ha nincs fordítás, formázott név
if ($paymentMethodName === $paymentMethodKey) {
    $paymentMethodName = ucfirst(str_replace('_', ' ', $paymentMethodId));
}

echo htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
```

## AJAX 404 hibák megelőzése

A JavaScript URL builder automatikusan detektálja és megőrzi a teljes path-ot:

```javascript
// Submenu context detection
if (pathname.indexOf('index.php') > 0) {
    // /submenu/index.php
    fullBaseUrl = origin + pathname.substring(0, pathname.lastIndexOf('/') + 1) + baseUrl;
} else {
    // /index.php
    fullBaseUrl = origin + '/' + baseUrl;
}
```

## Tesztelés

A `test_session_context.php` fájl tartalmazza az automatikus teszteket:

```bash
php test_session_context.php
```

**Teszt lefedettség:**
- ✅ Root context
- ✅ Submenu context
- ✅ Hub context
- ✅ Payment method megjelenítés
- ✅ Context-aware URL építés
- ✅ Session paraméter megőrzés

## Használat

### 1. Helper betöltése

```php
require_once JPATH_SITE . '/components/com_solidres/helpers/sessioncontext.php';
```

### 2. Context inicializálás

```php
$contextParams = SolidresSessionContextHelper::getContextParams();
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();
$contextType = SolidresSessionContextHelper::detectContextType();
```

### 3. Payment method kiírás

```php
$paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
$paymentMethodName = SolidresSessionContextHelper::getPaymentMethodName($paymentMethodId);
echo htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
```

### 4. Context-aware URL építés

```php
$redirectUrl = SolidresSessionContextHelper::buildContextUrl(
    'reservationasset.confirmation',
    ['reservation_id' => 123]
);
```

## Debug mód

Debug információk megtekintéséhez:
- Joomla Debug Mode bekapcsolása, vagy
- URL paraméter: `&debug=1`

Debug információk tartalmazzák:
- Context típus (Root/Submenu/Hub)
- Összes context paraméter
- Payment method ID
- Session validáció státusz
- Aktuális path

## Biztonság

- **XSS védelem**: Minden kimenetet `htmlspecialchars()` véd
- **SQL injection védelem**: Prepared statements használata
- **Session token**: CSRF védelem AJAX hívásokban
- **Input validáció**: Minden paraméter típus és érték ellenőrzése

## Kompatibilitás

- **Joomla**: 3.x és újabb verziók
- **PHP**: 7.4+
- **Browsers**: Modern browsers (Fetch API, URLSearchParams)
- **Solidres**: Kompatibilis a meglévő reservation struktúrával

## Telepítés

1. Másolja a fájlokat a megfelelő könyvtárakba:
   - `components/com_solidres/helpers/sessioncontext.php`
   - `components/com_solidres/views/reservationasset/tmpl/confirmationform.php`
   - `plugins/solidrespayment/*/tmpl/confirmationform.php`
   - `language/hu-HU/hu-HU.com_solidres.ini`

2. Ellenőrizze a fájl jogosultságokat (644 vagy 755)

3. Tisztítsa a Joomla cache-t

4. Tesztelje a confirmation page-et minden context-ben

## Támogatott fizetési módok

- Bankkártya (credit_card)
- Banki átutalás (bank_transfer)
- Qvik
- Revolut
- PayPal
- Készpénz (cash)
- Érkezéskor fizetés (on_arrival)

## Dokumentáció

Részletes dokumentáció: `PAYMENT_METHOD_CONTEXT_FIX.md`

## Verzió információ

- **Verzió**: 1.0.0
- **Dátum**: 2026-02-18
- **Szerző**: Solidres Development Team
- **Licenc**: GNU General Public License version 2 or later

## Következő lépések

- [ ] További payment plugin támogatás (PayPal, Stripe, stb.)
- [ ] Unit tesztek írása
- [ ] Integration tesztek Joomla környezetben
- [ ] Multi-language támogatás (en-GB, stb.)
- [ ] Admin felület context beállításokhoz
- [ ] Performance optimalizálás (cache stratégia)

## Támogatás

Kérdések vagy problémák esetén:
1. Ellenőrizze a debug információkat
2. Nézze át a dokumentációt
3. Futtassa a teszteket
4. Nyisson issue-t a repository-ban
