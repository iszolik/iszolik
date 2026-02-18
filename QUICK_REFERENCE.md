# Fizetési Mód Context Fix - Gyors Referencia

## TL;DR (Rövid Összefoglaló)

**Probléma:** Fizetési mód választás context függő, 404 hibák AJAX-ban, session nem konzisztens.

**Megoldás:** 3 fájl módosítása/hozzáadása:
1. `payments.php` - template frissítés
2. `reservation.php` controller - `updatePaymentMethod()` metódus hozzáadása
3. `sessioncontext.php` helper - új session validator osztály

## Gyors Implementálás (5 perc)

### 1. Template (payments.php)
```bash
cd /path/to/joomla
cp patches/payments.php com_solidres/layouts/asset/payments.php
```

### 2. Controller (reservation.php)
Másold be a `patches/reservation_controller_patch.php` tartalmát a ReservationController osztályba.

### 3. Helper (sessioncontext.php)
```bash
cp patches/sessioncontext.php components/com_solidres/helpers/sessioncontext.php
```

### 4. Nyelvi konstansok
```ini
# hu-HU.com_solidres.ini
SR_PAYMENT_METHOD_SELECTION="Fizetési mód kiválasztása"
SR_INVALID_PAYMENT_METHOD="Érvénytelen fizetési mód"
SR_PAYMENT_METHOD_UPDATED_SUCCESSFULLY="Fizetési mód sikeresen frissítve"
```

## Kulcs Koncepciók

### URL Preservation Pattern
```javascript
// HELYES - Abszolút URL + pathname preservation
var baseUrl = window.location.origin + window.location.pathname;
if (window.location.pathname.indexOf('index.php') === -1) {
    baseUrl = window.location.origin + '/index.php';
}

// HELYTELEN - Relatív URL
var baseUrl = 'index.php'; // ❌ 404 submenu-ban
```

### Kritikus Paraméterek
Mindig őrizd meg:
- `Itemid` - Menü context
- `property_id` - Ingatlan azonosító
- `hub_id` - Hub context
- `site_id` - Site azonosító
- `reservation_id` - Foglalás azonosító

### Session Context Struktúra
```php
$reservationDetails = (object)[
    'guest' => [
        'payment_method_id' => 123  // Kiválasztott fizetési mód
    ],
    'context' => (object)[
        'property_id' => 1,
        'hub_id' => 5,
        'site_id' => 1,
        'Itemid' => 123,
        'reservation_id' => 456,
        'last_validated' => 1234567890
    ]
];
```

## Gyakori Hibák és Megoldások

### ❌ Hiba: 404 AJAX híváskor

**Ok:** Relatív URL használata submenu context-ben

**Javítás:**
```javascript
// Helyett:
fetch('/index.php?option=...')

// Használd:
var baseUrl = window.location.origin + window.location.pathname;
fetch(baseUrl + '?option=...')
```

### ❌ Hiba: Session elvész navigáláskor

**Ok:** Context validálás hiányzik

**Javítás:**
```php
// payments.php elején:
$session = JFactory::getSession();
$reservationDetails = $session->get('reservationdetails', null, 'sr');

// Vagy használd a helper-t:
JLoader::register('SolidresSessionContextValidator', JPATH_COMPONENT . '/helpers/sessioncontext.php');
$reservationDetails = SolidresSessionContextValidator::validateAndSync();
```

### ❌ Hiba: Context paraméterek elvesznek

**Ok:** AJAX FormData nem tartalmazza az URL paramétereket

**Javítás:**
```javascript
var params = new URLSearchParams(window.location.search);
var formData = new FormData();
['Itemid', 'property_id', 'hub_id', 'site_id', 'reservation_id'].forEach(function(key) {
    var value = params.get(key);
    if (value) {
        formData.append(key, value);
    }
});
```

## Tesztelési Parancsok

### Browser Konzol Debug
```javascript
// URL paraméterek ellenőrzése
new URLSearchParams(window.location.search).forEach((v, k) => console.log(k, '=', v));

// AJAX URL építés tesztelése
console.log(buildAjaxUrl());

// Event listener ellenőrzése
document.addEventListener('paymentMethodUpdated', (e) => console.log('Updated:', e.detail));
```

### PHP Debug Kód
```php
// Session tartalom megjelenítése
$session = JFactory::getSession();
echo '<pre>' . print_r($session->get('reservationdetails', null, 'sr'), true) . '</pre>';

// Context validator debug
JLoader::register('SolidresSessionContextValidator', JPATH_COMPONENT . '/helpers/sessioncontext.php');
echo '<pre>' . print_r(SolidresSessionContextValidator::getContextInfo(), true) . '</pre>';
```

## Validálási Gyors Checklist

```
Root Menü:
[ ] Fizetési mód kiválasztható
[ ] AJAX nem ad 404-et
[ ] Console: "Payment method update requested"
[ ] Console: "Fizetési mód sikeresen frissítve"

Hub Context:
[ ] hub_id megmarad AJAX-ban
[ ] Session tartalmazza hub_id-t
[ ] Nincs 404

Submenu:
[ ] Pathname preservation működik
[ ] /hotel/index.php vagy /index.php helyes URL
[ ] Nincs 404
[ ] Minden paraméter megmarad

Session Persistence:
[ ] Kiválasztott mód perzisztens
[ ] Navigálás után is checked
[ ] Context info helyes
```

## API Referencia

### JavaScript Funkciók

#### `getUrlParams()`
```javascript
/**
 * @returns {Object} Kritikus URL paraméterek
 */
function getUrlParams() { ... }
```

#### `buildAjaxUrl()`
```javascript
/**
 * @returns {string} Context-független abszolút URL
 */
function buildAjaxUrl() { ... }
```

#### `updatePaymentMethod(paymentMethodId)`
```javascript
/**
 * @param {number} paymentMethodId - Kiválasztott fizetési mód ID
 * @returns {Promise} Fetch promise
 */
function updatePaymentMethod(paymentMethodId) { ... }
```

### PHP Metódusok

#### `SolidresSessionContextValidator::validateAndSync()`
```php
/**
 * @return stdClass Validált és szinkronizált reservation details
 */
public static function validateAndSync() { ... }
```

#### `SolidresSessionContextValidator::isContextValid()`
```php
/**
 * @return bool True, ha context egyezik
 */
public static function isContextValid() { ... }
```

#### `SolidresSessionContextValidator::getPaymentMethodId()`
```php
/**
 * @return int Payment method ID vagy 0
 */
public static function getPaymentMethodId() { ... }
```

#### `ReservationController::updatePaymentMethod()`
```php
/**
 * AJAX endpoint a fizetési mód frissítésére
 * @return void JSON válasz és alkalmazás leállítása
 */
public function updatePaymentMethod() { ... }
```

## Bevált Minták (Best Practices)

### ✅ DO (Így csináld)

```javascript
// 1. Mindig abszolút URL
var url = window.location.origin + window.location.pathname + '?...';

// 2. Háromszintű error handling
fetch(url)
    .then(r => r.ok ? r.json() : Promise.reject('HTTP error'))
    .then(d => d.success ? handleSuccess(d) : handleError(d))
    .catch(e => handleNetworkError(e));

// 3. Paraméter megőrzés
var params = getUrlParams(); // Kritikus paraméterek
formData.append('property_id', params.property_id);
```

```php
// 1. Session validálás minden megjelenítés előtt
$reservationDetails = SolidresSessionContextValidator::validateAndSync();

// 2. Context tárolása session-ben
$reservationDetails->context->property_id = $propertyId;
$session->set('reservationdetails', $reservationDetails, 'sr');

// 3. Input validáció
$paymentMethodId = $app->input->getInt('payment_method_id', 0);
if ($paymentMethodId <= 0) {
    // Error handling
}
```

### ❌ DON'T (Ne csináld)

```javascript
// 1. Relatív URL-ek
fetch('index.php?...'); // ❌ Submenu-ban 404

// 2. Hiányos error handling
fetch(url).then(r => r.json()); // ❌ Nincs validáció

// 3. Hardcoded paraméterek
var url = '?Itemid=123&property_id=1'; // ❌ Context specifikus
```

```php
// 1. Session használata validálás nélkül
$data = $session->get('reservationdetails', null, 'sr');
$paymentId = $data->guest['payment_method_id']; // ❌ Lehet NULL

// 2. Context ignorálása
$reservationDetails->guest['payment_method_id'] = $id;
// ❌ Context nem frissül

// 3. Input validálás nélkül
$id = $_POST['payment_method_id']; // ❌ SQL injection rizikó
```

## Support

**Dokumentáció:** 
- `PAYMENT_METHOD_CONTEXT_ANALYSIS.md` - Részletes elemzés
- `IMPLEMENTATION_GUIDE.md` - Teljes implementációs útmutató

**Debug eszközök:**
- Browser DevTools Console
- Network tab (AJAX hívások)
- PHP error_log()

**Kapcsolat:**
- Issue tracker: GitHub Issues
- Dokumentáció: Repository README

---

**Verzió:** 1.0  
**Készítve:** 2026-02-18
