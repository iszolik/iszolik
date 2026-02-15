# Qvik/Revolut Fizetési AJAX Patch

> **Teljes körű JavaScript megoldás magyar kommentekkel**  
> Abszolút `/index.php` végpont AJAX kommunikáció + Dinamikus átirányítás

## 📋 Tartalom

1. [Áttekintés](#áttekintés)
2. [Főbb Funkciók](#főbb-funkciók)
3. [Fájlok Listája](#fájlok-listája)
4. [Gyors Kezdés](#gyors-kezdés)
5. [Részletes Telepítés](#részletes-telepítés)
6. [Testreszabás](#testreszabás)
7. [Tesztelés](#tesztelés)
8. [Hibaelhárítás](#hibaelhárítás)
9. [Technikai Részletek](#technikai-részletek)
10. [GYIK](#gyik)

---

## 🎯 Áttekintés

Ez a JavaScript patch biztosítja, hogy a Qvik és Revolut fizetési rendszerek:

1. **AJAX kérések** mindig az **abszolút `/index.php`** végpontra POST-olnak (domain gyökérből!)
2. **Átirányítás** dinamikusan az aktuális útvonal `guestform` végződését `confirmationform`-ra cseréli
3. **Testreszabható** ID és class nevek használatával
4. **Magyar kommentekkel** ellátva, könnyen érthető

### Miért fontos ez?

- **Konzisztens URL kezelés**: Függetlenül az aktuális útvonaltól, mindig ugyanaz az AJAX végpont
- **Paraméter megőrzés**: Minden fontos URL paraméter (Itemid, property_id, stb.) megmarad
- **Hibakezelés**: Háromszintű hibakezelés a megbízható működésért
- **Karbantarthatóság**: Magyar kommentek és testreszabható kód

---

## ✨ Főbb Funkciók

### 1. Abszolút AJAX Végpont ✅

```javascript
// AJAX URL mindig: https://domain.hu/index.php
const ajaxUrl = buildAjaxUrl();
// Eredmény: "https://yourdomain.com/index.php"
```

**Előnyök:**
- Nem függ az aktuális útvonaltól
- Mindig a domain gyökérből indul
- Elkerüli a relatív útvonal problémákat

### 2. Dinamikus Átirányítás 🔄

```javascript
// Eredeti URL: https://domain.hu/foglalas/menu/guestform?id=123
// Új URL:      https://domain.hu/foglalas/menu/confirmationform?id=123
const redirectUrl = buildRedirectUrl();
```

**Előnyök:**
- Megőrzi a teljes útvonal struktúrát
- Minden URL paramétert átvisz
- Hub és submenu struktúrák megmaradnak

### 3. Testreszabható Selectorok 🎨

```javascript
const CONFIG = {
    selectors: {
        paymentForm: '#payment-form',           // Form ID
        paymentButton: '.payment-submit-btn',   // Gomb class
        errorContainer: '#payment-error-message', // Hiba elem
        loadingIndicator: '#payment-loading'    // Töltés jelző
    }
};
```

**Előnyök:**
- Könnyen adaptálható bármely HTML struktúrához
- Nem kell kódot módosítani, csak a CONFIG-ot
- Több verzió párhuzamos használata lehetséges

### 4. Háromszintű Hibakezelés 🛡️

```javascript
// 1. szint: HTTP státusz ellenőrzés
if (!response.ok) throw new Error('HTTP hiba');

// 2. szint: API válasz validálás
if (data.error) handleError(data.message);

// 3. szint: Hálózati hibák
.catch(error => handleNetworkError(error));
```

**Előnyök:**
- Minden hibatípust megfelelően kezel
- Felhasználóbarát hibaüzenetek magyarul
- Debug információk a konzolban

### 5. URL Paraméter Megőrzés 🔒

```javascript
preserveParams: [
    'Itemid',
    'property_id',
    'hub_id',
    'site_id',
    'reservation_id',
    'option',
    'view',
    'layout'
]
```

**Előnyök:**
- Joomla/Solidres kompatibilitás
- Minden kritikus paraméter megmarad
- Bővíthető egyedi paraméterekkel

---

## 📦 Fájlok Listája

### 1. **payment-ajax-patch.js** (Fő patch fájl)
   - **Méret**: ~18 KB
   - **Leírás**: A teljes JavaScript implementáció magyar kommentekkel
   - **Használat**: Ezt kell beilleszteni a PHP fájlokba

### 2. **INTEGRATION_GUIDE.md** (Integrációs útmutató)
   - **Méret**: ~13 KB
   - **Leírás**: Részletes telepítési és konfigurációs útmutató
   - **Tartalom**: Példák, hibaelhárítás, testreszabás

### 3. **example-guestform-integration.php** (Példa sablon - guestform)
   - **Méret**: ~11 KB
   - **Leírás**: Teljes példa sablon a guestform.php integrációhoz
   - **Tartalom**: HTML struktúra, CSS, beillesztési pont

### 4. **example-confirmationform-integration.php** (Példa sablon - confirmation)
   - **Méret**: ~12 KB
   - **Leírás**: Teljes példa sablon a confirmationform.php integrációhoz
   - **Tartalom**: Megerősítő oldal példa, beillesztési pont

### 5. **README.md** (Ez a fájl)
   - **Leírás**: Központi dokumentáció magyarul

---

## 🚀 Gyors Kezdés

### 5 Lépéses Telepítés

#### 1. Fájl Letöltése

Másolja a `payment-ajax-patch.js` fájlt a megfelelő helyre:

```
/path/to/joomla/plugins/solidrespayment/qvik/tmpl/
  ├── guestform.php
  ├── confirmationform.php
  └── payment-ajax-patch.js  ← Itt kell lennie
```

#### 2. Guestform.php Módosítása

Nyissa meg a `guestform.php` fájlt és a **fájl végére** illessze be:

```php
?>
<script>
<?php include __DIR__ . '/payment-ajax-patch.js'; ?>
</script>
```

#### 3. Confirmationform.php Módosítása

Ugyanígy a `confirmationform.php` fájl végére:

```php
?>
<script>
<?php include __DIR__ . '/payment-ajax-patch.js'; ?>
</script>
```

#### 4. HTML Ellenőrzése

Győződjön meg róla, hogy a HTML tartalmazza:

```html
<form id="payment-form">...</form>
<button class="payment-submit-btn">Fizetés</button>
<div id="payment-error-message"></div>
<div id="payment-loading"></div>
```

#### 5. Tesztelés

- Nyissa meg a guestform oldalt
- Nyomja meg F12-t (böngésző konzol)
- Ellenőrizze a logokat: `[Payment Handler] Inicializálás...`

---

## 📖 Részletes Telepítés

### Előfeltételek

- **Joomla 3.x vagy 4.x** (vagy kompatibilis CMS)
- **Solidres komponens** (opcionális, de ajánlott)
- **Modern böngésző** (Chrome, Firefox, Safari, Edge)
- **PHP 7.0+** (a fájl beágyazásához)

### Lépésről Lépésre

#### 1. Repository Klónozása vagy Fájlok Letöltése

```bash
git clone https://github.com/yourusername/payment-ajax-patch.git
cd payment-ajax-patch
```

VAGY manuálisan töltse le a fájlokat.

#### 2. Fájlok Elhelyezése

**Qvik plugin esetén:**

```bash
cp payment-ajax-patch.js /path/to/joomla/plugins/solidrespayment/qvik/tmpl/
```

**Revolut plugin esetén:**

```bash
cp payment-ajax-patch.js /path/to/joomla/plugins/solidrespayment/revolut/tmpl/
```

#### 3. Guestform.php Integráció

**Lépések:**

1. Nyissa meg: `plugins/solidrespayment/qvik/tmpl/guestform.php`
2. Görgessen a fájl **legaljára**
3. Ha van `?>` záró tag, az **után** illessze be
4. Ha nincs `?>`, akkor közvetlenül a `</html>` után

**Beillesztendő kód:**

```php
<!-- PAYMENT AJAX PATCH - START -->
<script>
<?php
$patchPath = __DIR__ . '/payment-ajax-patch.js';
if (file_exists($patchPath)) {
    include $patchPath;
} else {
    // Fallback útvonal
    include JPATH_ROOT . '/plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js';
}
?>
</script>
<!-- PAYMENT AJAX PATCH - END -->
```

#### 4. Confirmationform.php Integráció

Ugyanígy, mint a guestform.php esetében:

1. Nyissa meg: `plugins/solidrespayment/qvik/tmpl/confirmationform.php`
2. Görgessen a fájl végére
3. Illessze be a fenti kódot

#### 5. HTML Struktúra Ellenőrzése

**Kötelező elemek a HTML-ben:**

```html
<!-- 1. Fizetési Form - ID: payment-form -->
<form id="payment-form" method="post" action="">
    <!-- Form mezők -->
</form>

<!-- 2. Fizetés Gomb - CLASS: payment-submit-btn -->
<button type="submit" class="payment-submit-btn">
    Fizetés
</button>

<!-- 3. Hibaüzenet Konténer - ID: payment-error-message -->
<div id="payment-error-message" style="display:none;">
</div>

<!-- 4. Betöltés Jelző - ID: payment-loading -->
<div id="payment-loading" style="display:none;">
    Feldolgozás...
</div>
```

**Ha eltérő ID-k/classok:**

Módosítsa a `payment-ajax-patch.js` fájl `CONFIG` objektumát:

```javascript
const CONFIG = {
    selectors: {
        paymentForm: '#your-custom-form-id',
        paymentButton: '.your-custom-button-class',
        errorContainer: '#your-error-div-id',
        loadingIndicator: '#your-loading-div-id'
    }
};
```

#### 6. Tesztelés és Validálás

**Browser Console (F12):**

```
[Payment Handler] Inicializálás...
[Payment Handler] Form eseménykezelő regisztrálva: #payment-form
```

**Kattintson a Fizetés gombra:**

```
[Payment AJAX] URL épül: https://yourdomain.com/index.php
[Payment AJAX] Kérés küldése: https://yourdomain.com/index.php
[Payment AJAX] Adatok: {reservation_id: 123, ...}
```

**Sikeres fizetés után:**

```
[Payment AJAX] Sikeres válasz: {success: true, ...}
[Payment Redirect] Átirányítás 1000ms múlva: .../confirmationform?id=123
```

---

## 🎨 Testreszabás

### 1. ID és Class Nevek

```javascript
// payment-ajax-patch.js fájlban:
const CONFIG = {
    selectors: {
        paymentForm: '#qvik-form',              // Egyedi form ID
        paymentButton: '.qvik-btn',             // Egyedi gomb class
        errorContainer: '.error-box',           // Egyedi hiba elem
        loadingIndicator: '.spinner'            // Egyedi töltés elem
    }
};
```

### 2. Hibaüzenetek Testreszabása

```javascript
const CONFIG = {
    messages: {
        networkError: 'Nincs internetkapcsolat!',
        timeoutError: 'A művelet túl sokáig tart.',
        serverError: 'Szerver probléma. Hívjon minket: +36-1-234-5678',
        validationError: 'Hibás adatok!',
        generalError: 'Valami hiba történt.'
    }
};
```

### 3. Átirányítási Késleltetés

```javascript
const CONFIG = {
    redirect: {
        fromPath: 'guestform',
        toPath: 'confirmationform',
        delay: 3000  // 3 másodperc (milliszekundumban)
    }
};
```

### 4. Időtúllépési Határ

```javascript
const CONFIG = {
    ajax: {
        endpoint: '/index.php',
        method: 'POST',
        timeout: 60000  // 60 másodperc (alapértelmezett: 30000)
    }
};
```

### 5. További URL Paraméterek

```javascript
const CONFIG = {
    preserveParams: [
        'Itemid',
        'property_id',
        'hub_id',
        'site_id',
        'reservation_id',
        'option',
        'view',
        'layout',
        // Saját paraméterek:
        'custom_param',
        'tracking_id'
    ]
};
```

---

## 🧪 Tesztelés

### Manuális Teszt - Lépésről Lépésre

#### 1. Előkészület

- Nyissa meg a böngészőt (Chrome vagy Firefox ajánlott)
- Nyomja meg **F12** a fejlesztői eszközök megnyitásához
- Menjen a **Console** fülre

#### 2. Guestform Oldal Tesztelése

1. Navigáljon a guestform oldalra
2. Ellenőrizze a konzol logokat:
   ```
   [Payment Handler] Inicializálás...
   [Payment Handler] Form eseménykezelő regisztrálva: #payment-form
   ```
3. Töltse ki a form mezőket
4. Kattintson a "Fizetés" gombra

#### 3. AJAX Kérés Ellenőrzése

Konzolban keresse:

```
[Payment AJAX] URL épül: https://yourdomain.com/index.php
[Payment AJAX] Kérés küldése: https://yourdomain.com/index.php
[Payment AJAX] Adatok: {option: "com_solidres", ...}
```

**Network tab-ban (F12):**
- Keresse meg az `/index.php` kérést
- Ellenőrizze, hogy POST metódus
- Nézze meg a Request Payload-ot

#### 4. Átirányítás Ellenőrzése

Sikeres válasz után:

```
[Payment AJAX] Sikeres válasz: {...}
[Payment Redirect] Eredeti útvonal: /booking/guestform
[Payment Redirect] Új útvonal: /booking/confirmationform
[Payment Redirect] Átirányítás 1000ms múlva: .../confirmationform?id=123
```

#### 5. Confirmationform Oldal

- Az oldal automatikusan betöltődik
- Ellenőrizze az URL-t: `confirmationform` szerepel
- Ellenőrizze, hogy a paraméterek megmaradtak

### Automatizált Teszt (Opcionális)

Ha van tesztelési infrastruktúra, példa teszt:

```javascript
// Példa Jasmine/Jest teszt
describe('Payment AJAX Handler', () => {
    it('should build absolute AJAX URL', () => {
        const url = window.PaymentAjaxHandler.buildAjaxUrl();
        expect(url).toMatch(/^https?:\/\/.*\/index\.php$/);
    });
    
    it('should replace guestform with confirmationform', () => {
        // Mock window.location
        Object.defineProperty(window, 'location', {
            value: {
                origin: 'https://example.com',
                pathname: '/booking/guestform',
                search: '?id=123'
            }
        });
        
        const url = window.PaymentAjaxHandler.buildRedirectUrl();
        expect(url).toContain('confirmationform');
        expect(url).toContain('id=123');
    });
});
```

---

## 🔧 Hibaelhárítás

### Probléma 1: A Patch Nem Töltődik Be

**Tünet:** Nincs log a konzolban: `[Payment Handler] Inicializálás...`

**Lehetséges okok és megoldások:**

1. **Rossz fájl útvonal**
   ```php
   // Ellenőrizze:
   <?php
   $patchPath = __DIR__ . '/payment-ajax-patch.js';
   var_dump(file_exists($patchPath));  // Legyen: bool(true)
   ?>
   ```

2. **PHP syntax hiba**
   - Nézze meg a PHP error log-ot
   - Ellenőrizze, hogy nincs-e hiányzó `?>` vagy extra `<?php`

3. **JavaScript hiba**
   - Nézze meg a konzol "Errors" fülét
   - Keresse a piros hibaüzeneteket

**Megoldás:**
```php
<!-- Debug verzió -->
<script>
<?php
$patchPath = __DIR__ . '/payment-ajax-patch.js';
echo "// Patch útvonal: " . $patchPath . "\n";
echo "// Létezik: " . (file_exists($patchPath) ? 'IGEN' : 'NEM') . "\n";
if (file_exists($patchPath)) {
    include $patchPath;
}
?>
</script>
```

### Probléma 2: AJAX Kérés Nem Indul

**Tünet:** Kattintás a gombra → normál form submit (oldal újratöltődik)

**Lehetséges okok:**

1. **Rossz form ID**
   ```javascript
   // Ellenőrizze a CONFIG-ban:
   paymentForm: '#payment-form'  // Egyezik a HTML-ben lévő ID-val?
   ```

2. **Form már el van küldve mielőtt a patch betöltődne**
   ```javascript
   // Győződjön meg róla, hogy a patch a </body> előtt van
   ```

3. **Másik JavaScript override-olja az eseménykezelőt**
   ```javascript
   // Nézze meg, van-e másik submit handler
   ```

**Megoldás:**
```html
<!-- Győződjön meg, hogy a form ID egyezik -->
<form id="payment-form">
    <!-- ... -->
</form>

<script>
// Debug: ellenőrizze manuálisan
console.log('Form elem:', document.querySelector('#payment-form'));
</script>
```

### Probléma 3: Hibás Átirányítási URL

**Tünet:** Az átirányítás nem a megfelelő oldalra megy

**Debug:**
```javascript
// Konzolban futtassa:
window.PaymentAjaxHandler.buildRedirectUrl()
// Ellenőrizze a visszaadott URL-t
```

**Lehetséges okok:**

1. **Az aktuális URL nem tartalmazza a "guestform" szót**
   ```
   Eredeti: /payment/guest  (nincs "guestform" szó!)
   ```

2. **Többszörös csere**
   ```
   Eredeti: /guestform/guestform
   Új:      /confirmationform/confirmationform
   ```

**Megoldás:**
```javascript
// Módosítsa a CONFIG-ot egyedi útvonalakhoz:
const CONFIG = {
    redirect: {
        fromPath: 'guest',           // 'guestform' helyett
        toPath: 'confirmation',      // 'confirmationform' helyett
        delay: 1000
    }
};
```

### Probléma 4: Hibaüzenet Nem Jelenik Meg

**Tünet:** Hiba esetén nem látszik üzenet

**Ellenőrzés:**
```html
<!-- Van-e ilyen elem a HTML-ben? -->
<div id="payment-error-message" style="display:none;"></div>
```

**Megoldás:**

Ha nincs, adja hozzá:
```html
<div id="payment-error-message" style="display:none; color:red; padding:10px; margin:10px 0; border:1px solid red; background:#ffe6e6;">
</div>
```

VAGY használja az alert fallback-et (már van a patch-ben):
```javascript
// Ha nincs error container, alert()-et használ
if (!errorContainer) {
    window.alert(message);
}
```

### Probléma 5: CORS vagy Hálózati Hiba

**Tünet:** `Network error` vagy `CORS policy` hiba a konzolban

**Lehetséges okok:**

1. **Eltérő domain**
   ```
   Oldal: https://example.com
   AJAX: https://api.example.com/index.php  ← CORS!
   ```

2. **HTTP vs HTTPS**
   ```
   Oldal: https://example.com
   AJAX: http://example.com/index.php  ← Mixed content!
   ```

**Megoldás:**
```javascript
// A buildAjaxUrl() mindig window.location.origin-t használ,
// így automatikusan helyes kell legyen.
// Ha mégsem, ellenőrizze:
console.log('Origin:', window.location.origin);
console.log('AJAX URL:', window.PaymentAjaxHandler.buildAjaxUrl());
// Ezeknek meg kell egyezniük a protokoll és domain szempontjából
```

---

## 🔬 Technikai Részletek

### Architektúra

```
┌─────────────────────────────────────────┐
│         Fizetési Form (HTML)            │
│  ┌───────────────────────────────────┐  │
│  │  <form id="payment-form">         │  │
│  │    [Vendég adatok mezői]          │  │
│  │    <button class="payment-submit- │  │
│  │            btn">Fizetés</button>  │  │
│  │  </form>                          │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│    Payment AJAX Handler (JavaScript)    │
│  ┌───────────────────────────────────┐  │
│  │  1. buildAjaxUrl()                │  │
│  │     → /index.php                  │  │
│  │                                   │  │
│  │  2. sendPaymentRequest()          │  │
│  │     → POST /index.php             │  │
│  │     → FormData                    │  │
│  │     → Fetch API                   │  │
│  │                                   │  │
│  │  3. Hibakezelés (3 szint)        │  │
│  │     → HTTP status                 │  │
│  │     → API response                │  │
│  │     → Network errors              │  │
│  │                                   │  │
│  │  4. buildRedirectUrl()            │  │
│  │     → guestform → confirmationform│  │
│  │                                   │  │
│  │  5. redirectToConfirmation()      │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│         Backend (PHP/Joomla)            │
│  ┌───────────────────────────────────┐  │
│  │  POST /index.php                  │  │
│  │  option=com_solidres              │  │
│  │  task=payment.process             │  │
│  │  reservation_id=123               │  │
│  │  ...                              │  │
│  │                                   │  │
│  │  → Fizetés feldolgozás            │  │
│  │  → Válasz JSON-ban                │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│      Confirmationform (HTML)            │
│  ┌───────────────────────────────────┐  │
│  │  ✅ Sikeres fizetés!              │  │
│  │  Foglalási ID: 123                │  │
│  │  Összeg: 50,000 HUF               │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### Fő Függvények

#### buildAjaxUrl()

```javascript
/**
 * Abszolút AJAX URL építése
 * @returns {string} https://domain.hu/index.php
 */
function buildAjaxUrl() {
    const origin = window.location.origin;  // https://domain.hu
    const endpoint = CONFIG.ajax.endpoint;  // /index.php
    return origin + endpoint;               // https://domain.hu/index.php
}
```

**Példák:**
```javascript
// Ha a jelenlegi oldal: https://example.com/booking/menu/guestform?id=123
buildAjaxUrl()  // → https://example.com/index.php

// Ha a jelenlegi oldal: https://example.com/subfolder/payment
buildAjaxUrl()  // → https://example.com/index.php

// Mindig ugyanaz az eredmény!
```

#### buildRedirectUrl()

```javascript
/**
 * Átirányítási URL építése
 * @returns {string} .../confirmationform?id=123
 */
function buildRedirectUrl() {
    const origin = window.location.origin;       // https://example.com
    const pathname = window.location.pathname;   // /booking/guestform
    const search = window.location.search;       // ?id=123
    
    // guestform → confirmationform csere
    const newPathname = pathname.replace('guestform', 'confirmationform');
    
    return origin + newPathname + search;
    // → https://example.com/booking/confirmationform?id=123
}
```

**Példák:**
```javascript
// Eredeti: https://example.com/booking/guestform?id=123
buildRedirectUrl()  
// → https://example.com/booking/confirmationform?id=123

// Eredeti: https://example.com/menu/sub/guestform?prop=5&hub=2
buildRedirectUrl()  
// → https://example.com/menu/sub/confirmationform?prop=5&hub=2

// Komplex: https://example.com/hotel/room-guestform/details?id=99
buildRedirectUrl()  
// → https://example.com/hotel/room-confirmationform/details?id=99
```

#### sendPaymentRequest()

```javascript
/**
 * AJAX kérés küldése Fetch API-val
 * @param {Object} paymentData - Fizetési adatok
 * @param {Function} onSuccess - Sikeres callback
 * @param {Function} onError - Hiba callback
 */
function sendPaymentRequest(paymentData, onSuccess, onError) {
    const ajaxUrl = buildAjaxUrl();
    const formData = new FormData();
    
    // Adatok hozzáadása
    Object.keys(paymentData).forEach(key => {
        formData.append(key, paymentData[key]);
    });
    
    // Fetch kérés
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            onSuccess(data);
        } else {
            onError({error: 'api_error', message: data.message});
        }
    })
    .catch(error => {
        onError({error: 'network_error', message: error.message});
    });
}
```

### Fetch API vs XMLHttpRequest

A patch a **Fetch API**-t használja (nem XMLHttpRequest), mert:

| Funkció | Fetch API | XMLHttpRequest |
|---------|-----------|----------------|
| Szintaxis | Modern, promise-based | Régi, callback-based |
| Olvashatóság | ✅ Egyszerű | ❌ Bonyolult |
| Promise támogatás | ✅ Natív | ❌ Nincs |
| Error handling | ✅ Tiszta | ❌ Nehézkes |
| Browser támogatás | ✅ 95%+ | ✅ 100% |

**Browser kompatibilitás:**
- Chrome 42+ ✅
- Firefox 39+ ✅
- Safari 10.1+ ✅
- Edge 14+ ✅
- IE 11 ❌ (nincs natív Fetch, polyfill szükséges)

### Időtúllépés Kezelése

A Fetch API **nem támogatja** natívan az időtúllépést, ezért manuálisan implementáltuk:

```javascript
// Timeout beállítása
const timeoutId = setTimeout(() => {
    onError({error: 'timeout', message: 'Időtúllépés'});
}, CONFIG.ajax.timeout);  // 30000 ms

// Fetch kérés
fetch(ajaxUrl, {...})
    .then(response => {
        clearTimeout(timeoutId);  // Töröljük a timeout-ot
        // ... folytatás
    });
```

### IIFE Pattern (Immediately Invoked Function Expression)

A patch egy IIFE-ben fut, hogy ne szennyezze a globális névteret:

```javascript
(function() {
    'use strict';
    
    // Privát változók és függvények
    const CONFIG = {...};
    function buildAjaxUrl() {...}
    
    // ... implementáció
    
    // Csak a szükséges API-t exportáljuk
    window.PaymentAjaxHandler = {
        buildAjaxUrl: buildAjaxUrl,
        // ...
    };
})();
```

**Előnyök:**
- Privát scope
- Nincs változó ütközés
- Tiszta globális névtér
- Modern best practice

---

## ❓ GYIK (Gyakran Ismételt Kérdések)

### 1. Miért kell az AJAX-nak a `/index.php`-ra mennie?

**Válasz:** A Joomla routing rendszere az `index.php`-t használja belépési pontnak. Minden kérést ezen keresztül kell irányítani, hogy a megfelelő komponens és task végrehajtódjon.

**Példa:**
```
❌ Rossz: /booking/guestform (relatív, lehet hogy 404)
✅ Jó:    /index.php (abszolút, mindig működik)
```

### 2. Mi történik, ha az útvonal nem tartalmazza a "guestform" szót?

**Válasz:** A `buildRedirectUrl()` funkció ellenőrzi ezt, és ha nem találja, akkor hozzáfűzi a végére:

```javascript
if (pathname.indexOf('guestform') === -1) {
    return origin + pathname + '/confirmationform' + search;
}
```

**Konzol warning:**
```
[Payment Redirect] Az aktuális útvonal nem tartalmazza a "guestform" szót
```

### 3. Lehet egyszerre több fizetési form az oldalon?

**Válasz:** Igen, de módosítani kell a selector-t:

```javascript
// Opció 1: Egyedi ID-k
<form id="qvik-payment-form">...</form>
<form id="revolut-payment-form">...</form>

// Opció 2: Közös class
<form class="payment-form" data-provider="qvik">...</form>
<form class="payment-form" data-provider="revolut">...</form>

// JavaScript:
document.querySelectorAll('.payment-form').forEach(form => {
    form.addEventListener('submit', handlePaymentSubmit);
});
```

### 4. Támogatja az IE11-et?

**Válasz:** Nem natívan, de polyfill-lel igen:

```html
<!-- Fetch polyfill IE11-hez -->
<script src="https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/dist/fetch.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js"></script>

<!-- Utána jön a payment patch -->
<script>
<?php include __DIR__ . '/payment-ajax-patch.js'; ?>
</script>
```

### 5. Hogyan lehet több paraméter megőrzése?

**Válasz:** Egyszerűen bővítse a `CONFIG.preserveParams` tömböt:

```javascript
const CONFIG = {
    preserveParams: [
        'Itemid',
        'property_id',
        // ... alapértelmezett paraméterek
        
        // Saját paraméterek:
        'utm_source',
        'utm_campaign',
        'affiliate_id',
        'tracking_code'
    ]
};
```

### 6. Mi történik, ha nincs #payment-error-message elem?

**Válasz:** A patch automatikusan fallback-et használ:

```javascript
if (errorContainer) {
    errorContainer.textContent = message;  // Megjelenik a div-ben
} else {
    window.alert(message);  // Alert fallback
}
```

### 7. Lehet másik HTTP metódust használni (GET, PUT)?

**Válasz:** Igen, de nem ajánlott. A fizetési adatok POST-tal biztonságosabbak:

```javascript
const CONFIG = {
    ajax: {
        endpoint: '/index.php',
        method: 'GET',  // ❌ Nem ajánlott fizetéshez!
        timeout: 30000
    }
};
```

**Figyelem:** GET-tel a fizetési adatok az URL-ben szerepelnek, ami biztonsági kockázat!

### 8. Hogyan lehet debug üzemmódot engedélyezni?

**Válasz:** A konzol logok már beépítettek. További debug info:

```javascript
// A payment-ajax-patch.js tetején:
const DEBUG = true;  // Állítsa true-ra

// Majd a kódban:
if (DEBUG) {
    console.log('[DEBUG] Részletes info:', data);
}
```

VAGY környezeti változóval:
```javascript
const DEBUG = window.location.hostname === 'localhost' || 
              window.location.search.includes('debug=1');
```

### 9. Kompatibilis más CMS-ekkel (WordPress, Drupal)?

**Válasz:** Igen, a patch CMS-független, de módosítani kell:

**WordPress:**
```javascript
const CONFIG = {
    ajax: {
        endpoint: '/wp-admin/admin-ajax.php',  // WP AJAX endpoint
        // ...
    }
};
```

**Drupal:**
```javascript
const CONFIG = {
    ajax: {
        endpoint: '/payment/ajax',  // Drupal route
        // ...
    }
};
```

### 10. Lehet több nyelv támogatása?

**Válasz:** Igen, dinamikusan:

```php
<!-- PHP-ban detektáljuk a nyelvet -->
<?php
$lang = JFactory::getLanguage()->getTag();  // pl. 'hu-HU', 'en-GB'

$messages = [
    'hu-HU' => [
        'networkError' => 'Hálózati hiba történt.',
        'timeoutError' => 'Időtúllépés.',
        // ...
    ],
    'en-GB' => [
        'networkError' => 'Network error occurred.',
        'timeoutError' => 'Timeout.',
        // ...
    ]
];

$currentMessages = $messages[$lang] ?? $messages['hu-HU'];
?>

<script>
// Beágyazzuk a patch-et
<?php include __DIR__ . '/payment-ajax-patch.js'; ?>

// Felülírjuk a hibaüzeneteket
window.PaymentAjaxHandler.config.messages = <?php echo json_encode($currentMessages); ?>;
</script>
```

---

## 📝 Licensz

Ez a JavaScript patch **MIT licensz** alatt érhető el.

```
MIT License

Copyright (c) 2026

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🤝 Közreműködés

Ha találsz hibát vagy javaslatod van, nyiss egy Issue-t vagy Pull Request-et a GitHub-on.

---

## 📞 Támogatás

Ha bármilyen kérdésed van vagy segítségre van szükséged:

1. **Nézd át a dokumentációt**: README.md, INTEGRATION_GUIDE.md
2. **Ellenőrizd a példa fájlokat**: example-*.php
3. **Konzol logok**: F12 → Console fül
4. **GitHub Issues**: Nyiss egy issue-t a repository-ban

---

## ✅ Checklist - Telepítés Utáni Ellenőrzés

- [ ] A `payment-ajax-patch.js` fájl a megfelelő helyen van
- [ ] A guestform.php fájl végére beillesztve a patch
- [ ] A confirmationform.php fájl végére beillesztve a patch
- [ ] A HTML tartalmazza a kötelező elemeket (#payment-form, .payment-submit-btn, stb.)
- [ ] A böngésző konzolban látszik: `[Payment Handler] Inicializálás...`
- [ ] Form submit → AJAX kérés indul (nem töltődik újra az oldal)
- [ ] AJAX URL helyes: `/index.php`
- [ ] Sikeres fizetés után átirányítás a confirmationform-ra
- [ ] Az URL paraméterek megmaradnak az átirányítás után
- [ ] Hibaüzenetek megjelennek, ha szükséges
- [ ] Betöltés jelző látszik AJAX kérés közben

Ha minden ✅, akkor sikeres a telepítés! 🎉

---

**Verzió:** 1.0.0  
**Utolsó frissítés:** 2026-02-15  
**Szerző:** Payment Integration Team  
**Nyelv:** Magyar 🇭🇺
