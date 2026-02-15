# Qvik/Revolut Fizetési AJAX Patch - Integrációs Útmutató

## Áttekintés

Ez a dokumentum részletesen bemutatja, hogyan kell integrálni a `payment-ajax-patch.js` fájlt a Qvik és Revolut fizetési rendszerekbe.

## Fő Funkciók

### 1. Abszolút AJAX Végpont
- **Cél**: Minden AJAX kérés a helyes index.php végpontra POST-ol, megőrizve az almenü struktúrát
- **Implementáció**: `buildAjaxUrl()` függvény
- **Eredmény**: 
  - Gyökér menüből: `https://domain.hu/index.php`
  - Almenüből: `https://domain.hu/demo-tobbszallashely/index.php`
- **Előny**: Automatikusan detektálja az index.php pozícióját az útvonalban, így elkerüli a 404 hibákat

### 2. Dinamikus Átirányítás
- **Cél**: Az aktuális útvonalban `guestform` → `confirmationform` csere
- **Implementáció**: `buildRedirectUrl()` függvény
- **Példa**: 
  - Eredeti: `https://domain.hu/foglalás/guestform?id=123`
  - Új: `https://domain.hu/foglalás/confirmationform?id=123`

### 3. Testreszabhatóság
- ID és class nevek konfigurálhatók a `CONFIG` objektumban
- Hibaüzenetek magyarul, személyre szabhatóan

## Telepítés

### 1. Fájl Elhelyezése

Másolja a `payment-ajax-patch.js` fájlt a megfelelő helyre:

```
/path/to/joomla/
  └── plugins/
      └── solidrespayment/
          ├── qvik/
          │   └── tmpl/
          │       ├── guestform.php
          │       ├── confirmationform.php
          │       └── payment-ajax-patch.js  ← IDE
          └── revolut/
              └── tmpl/
                  ├── guestform.php
                  ├── confirmationform.php
                  └── payment-ajax-patch.js  ← IDE
```

### 2. Integráció guestform.php-ba

**Opció A: Beágyazott JavaScript (Ajánlott)**

Nyissa meg a `guestform.php` fájlt és a **fájl végén** (a `?>` után vagy ha nincs `?>`, akkor a legvégén) illessze be:

```php
<?php
// ... meglévő guestform.php kód ...
?>

<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - START -->
<script>
<?php
// Beágyazzuk a JavaScript patch-et
$patchPath = __DIR__ . '/payment-ajax-patch.js';
if (file_exists($patchPath)) {
    include $patchPath;
} else {
    // Alternatív útvonal, ha máshol van
    include JPATH_ROOT . '/plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js';
}
?>
</script>
<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - END -->
```

**Opció B: Külső Fájl Betöltése**

```php
<?php
// ... meglévő guestform.php kód ...
?>

<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH -->
<script src="<?php echo JUri::base(); ?>plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js"></script>
```

### 3. Integráció confirmationform.php-ba

Ugyanúgy, mint a `guestform.php` esetében, a fájl végére:

**Opció A: Beágyazott (Ajánlott)**

```php
<?php
// ... meglévő confirmationform.php kód ...
?>

<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - START -->
<script>
<?php
$patchPath = __DIR__ . '/payment-ajax-patch.js';
if (file_exists($patchPath)) {
    include $patchPath;
} else {
    include JPATH_ROOT . '/plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js';
}
?>
</script>
<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - END -->
```

**Opció B: Külső Fájl**

```php
<?php
// ... meglévő confirmationform.php kód ...
?>

<script src="<?php echo JUri::base(); ?>plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js"></script>
```

## Konfiguráció

### HTML Követelmények

A patch működéséhez a következő HTML elemeknek léteznie kell az oldalon:

```html
<!-- Fizetési Form -->
<form id="payment-form" method="post">
    <!-- Form mezők -->
    
    <!-- Fizetés Gomb -->
    <button type="submit" class="payment-submit-btn">
        Fizetés
    </button>
</form>

<!-- Hibaüzenet Konténer -->
<div id="payment-error-message" style="display:none; color:red;">
    <!-- Ide kerülnek a hibaüzenetek -->
</div>

<!-- Betöltés Jelző -->
<div id="payment-loading" style="display:none;">
    Feldolgozás folyamatban...
</div>
```

### Testreszabás

Ha a HTML struktúrája eltér, módosítsa a `payment-ajax-patch.js` fájl `CONFIG` objektumát:

```javascript
const CONFIG = {
    selectors: {
        // Cserélje ki ezeket a saját ID-jaira/class-aira
        paymentForm: '#payment-form',              // vagy '.your-form-class'
        paymentButton: '.payment-submit-btn',      // vagy '#your-button-id'
        errorContainer: '#payment-error-message',  // vagy '.error-box'
        loadingIndicator: '#payment-loading'       // vagy '.spinner'
    },
    // ... többi konfiguráció
};
```

## Példa: Teljes Integráció

### guestform.php - Végső Változat

```php
<?php
defined('_JEXEC') or die;

// ... meglévő PHP kód ...
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendég Információk</title>
    <!-- ... CSS linkek ... -->
</head>
<body>
    
    <h1>Fizetési Információk</h1>
    
    <!-- Fizetési Form -->
    <form id="payment-form" method="post" action="">
        <div class="form-group">
            <label>Név:</label>
            <input type="text" name="guest_name" required />
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="guest_email" required />
        </div>
        
        <!-- Rejtett mezők -->
        <input type="hidden" name="option" value="com_solidres" />
        <input type="hidden" name="task" value="payment.process" />
        <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>" />
        
        <!-- Hibaüzenet Konténer -->
        <div id="payment-error-message" class="alert alert-danger" style="display:none;">
            <!-- Hibaüzenetek itt jelennek meg -->
        </div>
        
        <!-- Betöltés Jelző -->
        <div id="payment-loading" class="text-center" style="display:none;">
            <i class="fa fa-spinner fa-spin"></i> Feldolgozás folyamatban...
        </div>
        
        <!-- Fizetés Gomb -->
        <button type="submit" class="payment-submit-btn btn btn-primary">
            Fizetés
        </button>
    </form>
    
</body>
</html>

<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - START -->
<script>
<?php
$patchPath = __DIR__ . '/payment-ajax-patch.js';
if (file_exists($patchPath)) {
    include $patchPath;
} else {
    // Fallback útvonal
    $fallbackPath = JPATH_ROOT . '/plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js';
    if (file_exists($fallbackPath)) {
        include $fallbackPath;
    } else {
        // Ha egyik sem található, logoljuk a hibát
        JLog::add('Payment AJAX patch not found: ' . $patchPath, JLog::WARNING);
    }
}
?>
</script>
<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - END -->
```

### confirmationform.php - Végső Változat

```php
<?php
defined('_JEXEC') or die;

// ... meglévő PHP kód ...
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fizetés Megerősítése</title>
    <!-- ... CSS linkek ... -->
</head>
<body>
    
    <h1>Fizetés Megerősítve</h1>
    
    <div class="confirmation-message">
        <p>Köszönjük! A fizetés sikeresen megtörtént.</p>
        <p>Foglalási azonosító: <strong><?php echo $reservationId; ?></strong></p>
    </div>
    
    <!-- Visszalépés gomb (opcionális) -->
    <a href="<?php echo JRoute::_('index.php?option=com_solidres&view=reservations'); ?>" 
       class="btn btn-primary">
        Vissza a foglalásokhoz
    </a>
    
</body>
</html>

<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - START -->
<script>
<?php
$patchPath = __DIR__ . '/payment-ajax-patch.js';
if (file_exists($patchPath)) {
    include $patchPath;
}
?>
</script>
<!-- QVIK/REVOLUT FIZETÉSI AJAX PATCH - END -->
```

## Testreszabási Példák

### 1. Egyedi Form ID és Gomb Class

Ha a form ID-ja `#qvik-payment-form` és a gomb class-a `.qvik-pay-btn`:

```javascript
// A payment-ajax-patch.js fájlban:
const CONFIG = {
    selectors: {
        paymentForm: '#qvik-payment-form',
        paymentButton: '.qvik-pay-btn',
        errorContainer: '#payment-error-message',
        loadingIndicator: '#payment-loading'
    },
    // ...
};
```

### 2. Egyedi Hibaüzenetek

```javascript
const CONFIG = {
    messages: {
        networkError: 'Nem sikerült csatlakozni a szerverhez. Ellenőrizze az internetkapcsolatot!',
        timeoutError: 'A művelet túl sokáig tartott. Próbálja újra!',
        serverError: 'A szerver nem válaszol. Lépjen kapcsolatba velünk a +36-1-234-5678 számon.',
        validationError: 'Hibás adatok! Kérjük, töltse ki az összes mezőt helyesen.',
        generalError: 'Hiba történt. Kérjük, próbálja újra később.'
    },
    // ...
};
```

### 3. Hosszabb Átirányítási Késleltetés

Ha 3 másodperces késleltetést szeretne a sikeres fizetés után:

```javascript
const CONFIG = {
    redirect: {
        fromPath: 'guestform',
        toPath: 'confirmationform',
        delay: 3000  // 3000 ms = 3 másodperc
    },
    // ...
};
```

### 4. További URL Paraméterek Megőrzése

Ha további paramétereket is meg szeretne őrizni:

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
        // Egyedi paraméterek:
        'custom_param_1',
        'custom_param_2'
    ],
    // ...
};
```

## Hibaelhárítás

### 1. A Form Nem Küldi el az AJAX Kérést

**Probléma**: A form normálisan submit-ol, nem AJAX-szal.

**Megoldás**:
- Ellenőrizze, hogy a form ID megegyezik-e a CONFIG-ban megadottal
- Nyissa meg a böngésző konzolt (F12) és nézze meg a logokat
- Ellenőrizze, hogy nincs-e JavaScript hiba

```javascript
// Konzol logok keresése:
[Payment Handler] Inicializálás...
[Payment Handler] Form eseménykezelő regisztrálva: #payment-form
```

### 2. Az AJAX Kérés Nem a Helyes URL-re Megy

**Probléma**: Az AJAX kérés nem `/index.php`-ra megy.

**Megoldás**:
- Nézze meg a konzol logokat:
  ```
  [Payment AJAX] URL épül: https://domain.hu/index.php
  ```
- Ha nem ez jelenik meg, ellenőrizze a `buildAjaxUrl()` függvényt

### 3. Az Átirányítás Nem Működik

**Probléma**: Sikeres fizetés után nem irányít át a confirmationform-ra.

**Megoldás**:
- Ellenőrizze, hogy az aktuális URL tartalmazza-e a "guestform" szót
- Nézze meg a konzol logokat:
  ```
  [Payment Redirect] Eredeti útvonal: /booking/guestform
  [Payment Redirect] Új útvonal: /booking/confirmationform
  ```

### 4. Hibaüzenet Nem Jelenik Meg

**Probléma**: Hiba esetén nem látszik a hibaüzenet.

**Megoldás**:
- Ellenőrizze, hogy létezik-e a `#payment-error-message` elem
- Ha nem, akkor a patch alert()-et fog használni
- Adja hozzá a hibaüzenet konténert a HTML-hez

## Technikai Részletek

### Fetch API

A patch a modern Fetch API-t használja az AJAX kérésekhez:

```javascript
fetch(ajaxUrl, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
})
```

**Böngésző támogatás**: 
- Chrome 42+
- Firefox 39+
- Safari 10.1+
- Edge 14+

### Háromszintű Hibakezelés

1. **HTTP státusz ellenőrzés**: `response.ok`
2. **API válasz validálás**: `data.success` és `data.error`
3. **Hálózati hibák**: `.catch()` blokk

### Időtúllépés Kezelés

Az AJAX kéréseknek 30 másodperces időtúllépési határideje van (konfigurálható):

```javascript
const CONFIG = {
    ajax: {
        timeout: 30000  // 30 másodperc (ms-ban)
    }
};
```

## Globális API (Fejlesztőknek)

A patch exportál egy globális `PaymentAjaxHandler` objektumot, amely külső szkriptekből is elérhető:

```javascript
// AJAX URL lekérése
var ajaxUrl = window.PaymentAjaxHandler.buildAjaxUrl();
console.log(ajaxUrl); // https://domain.hu/index.php

// Redirect URL lekérése
var redirectUrl = window.PaymentAjaxHandler.buildRedirectUrl();
console.log(redirectUrl); // https://domain.hu/booking/confirmationform?id=123

// Hibaüzenet megjelenítése
window.PaymentAjaxHandler.showError('Egyedi hibaüzenet');

// Azonnali átirányítás (késleltetés nélkül)
window.PaymentAjaxHandler.redirectToConfirmation(0);

// Konfiguráció elérése
console.log(window.PaymentAjaxHandler.config);
```

## Biztonsági Megfontolások

1. **CSRF védelem**: Győződjön meg róla, hogy a Joomla/Solidres CSRF token kezelése aktív
2. **SSL/TLS**: Használjon HTTPS-t éles környezetben
3. **Bemeneti validáció**: A szerver oldalon mindig validálja a bejövő adatokat
4. **Hibaüzenetek**: Ne adjon ki érzékeny információkat a hibaüzenetekben

## Changelog

### v1.1.0 (2026-02-15)
- **FIX**: 404 hiba javítása almenü struktúrákban
- `buildAjaxUrl()` most automatikusan detektálja az index.php pozícióját
- Támogatja mind a gyökér (/index.php), mind az almenü (/path/index.php) útvonalakat
- Megőrzi a teljes pathname-et beleértve az almenü szegmenseket
- Részletes debug logolás hozzáadva (pathname, ajax path, full URL)

### v1.0.0 (2026-02-15)
- Kezdeti kiadás
- AJAX végpont: abszolút `/index.php`
- Dinamikus átirányítás: `guestform` → `confirmationform`
- Teljes magyar kommentálás
- Testreszabható konfigurációk
- Háromszintű hibakezelés
- Fetch API alapú kommunikáció
- Globális API exportálás

## Támogatás

Ha bármilyen kérdése vagy problémája van az integrációval kapcsolatban:

1. Ellenőrizze a böngésző konzolt (F12) a részletes logokért
2. Nézze át a Hibaelhárítás szakaszt
3. Olvassa el a JavaScript fájl kommentjeit
4. Nézze meg a példa kódokat ebben a dokumentumban

## Licensz

Ez a patch nyílt forráskódú és szabadon felhasználható a Qvik és Revolut fizetési rendszerek integrációjához.
