# Solidres Qvik és Revolut Fizetési Pluginok - Végleges JS és Sablon Változtatások

## Bevezetés

A Solidres Qvik és Revolut fizetési pluginok JavaScript fetch/redirect logikája már teljes mértékben ki van javítva minden menüpont (almenü/hub/multisite) alatt. Ez a dokumentum bemutatja a **végleges kódot magyar kommentekkel** a `guestform.php` és `confirmationform.php` fájlok esetében.

## 1. QVIK Plugin - guestform.php

### Teljes Fájl Útvonal
```
plugins/solidrespayment/qvik/tmpl/guestform.php
```

### Kulcs JavaScript Részletek

#### URL Építő Függvény
```javascript
/**
 * URL építő függvény - Teljes útvonal kontextus megőrzése
 * 
 * Ez a függvény biztosítja, hogy minden URL tartalmazza:
 * - A teljes origin-t (protocol + domain)
 * - A teljes pathname-et (beleértve az almenü szegmenseket is)
 * - Az összes szükséges paramétert (hub_id, property_id, site_id, Itemid)
 * 
 * Így elkerülhetők a 404 hibák és az útvonalvesztés multisite/hub/almenü környezetekben.
 */
function buildSubmitUrl() {
    // Teljes útvonal megőrzése (origin + pathname)
    var baseUrl = window.location.origin + window.location.pathname;
    
    // URL paraméterek kinyerése az űrlapból
    var form = document.getElementById('qvikGuestForm');
    var hubId = form.querySelector('input[name="hub_id"]').value;
    var propertyId = form.querySelector('input[name="property_id"]').value;
    var siteId = form.querySelector('input[name="site_id"]').value;
    var itemId = form.querySelector('input[name="Itemid"]').value;
    
    // URL paraméterek összeállítása
    var params = new URLSearchParams();
    params.append('option', 'com_solidres');
    params.append('task', 'reservationasset.processGuest');
    
    // Csak akkor adjuk hozzá a paramétereket, ha nem 0 értékűek
    if (hubId && hubId !== '0') {
        params.append('hub_id', hubId);
    }
    if (propertyId && propertyId !== '0') {
        params.append('property_id', propertyId);
    }
    if (siteId && siteId !== '0') {
        params.append('site_id', siteId);
    }
    if (itemId && itemId !== '0') {
        params.append('Itemid', itemId);
    }
    
    // Teljes URL visszaadása
    return baseUrl + '?' + params.toString();
}
```

#### Fetch API Használat
```javascript
/**
 * Űrlap beküldés kezelése AJAX-szal
 * 
 * A Fetch API-t használjuk promise-alapú hibakezeléssel, nem XMLHttpRequest-et.
 * Ez modern, tiszta kódot eredményez és jobban kezeli az aszinkron műveleteket.
 */
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Űrlap validáció
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Submit gomb letiltása dupla beküldés elkerülésére
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Feldolgozás...';
    }
    
    // FormData objektum létrehozása
    var formData = new FormData(form);
    
    // AJAX kérés a Fetch API-val
    fetch(buildSubmitUrl(), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin', // Cookie-k küldése
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // AJAX jelzés Joomla-nak
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            // Sikeres feldolgozás - átirányítás a fizetési oldalra
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            // Hiba történt a szerveroldalon
            alert(data.message || 'Hiba történt a feldolgozás során');
            submitBtn.disabled = false;
        }
    })
    .catch(function(error) {
        // Hálózati vagy parse hiba
        console.error('Error processing guest form:', error);
        alert('Hálózati hiba történt');
        submitBtn.disabled = false;
    });
});
```

#### PHP Rejtett Mezők
```php
<!-- Rejtett mezők a paraméterek megőrzéséhez -->
<input type="hidden" name="hub_id" value="<?php echo $hubId; ?>" />
<input type="hidden" name="property_id" value="<?php echo $propertyId; ?>" />
<input type="hidden" name="site_id" value="<?php echo $siteId; ?>" />
<input type="hidden" name="Itemid" value="<?php echo $itemId; ?>" />
```

---

## 2. QVIK Plugin - confirmationform.php

### Teljes Fájl Útvonal
```
plugins/solidrespayment/qvik/tmpl/confirmationform.php
```

### Kulcs JavaScript Részletek

#### AJAX URL Építő Függvény
```javascript
/**
 * AJAX URL építő függvény
 * 
 * Ez a függvény felelős azért, hogy minden AJAX kérés URL-je tartalmazza:
 * - A teljes origin-t (window.location.origin)
 * - A teljes pathname-et (window.location.pathname) - ez magában foglalja
 *   az összes almenü szegmenst is (pl. /submenu1/submenu2/index.php)
 * - Az összes szükséges URL paramétert
 * 
 * Ez biztosítja, hogy multisite, hub és almenü környezetekben is helyesen
 * működjön a fizetési folyamat, és ne legyen 404 vagy útvonalvesztés.
 */
function buildAjaxUrl() {
    // Teljes útvonal kontextus megőrzése
    var baseUrl = window.location.origin + window.location.pathname;
    
    // Paraméterek kinyerése az űrlapból
    var hubId = document.getElementById('hubId').value;
    var propertyId = document.getElementById('propertyId').value;
    var siteId = document.getElementById('siteId').value;
    var itemId = document.getElementById('itemId').value;
    var reservationId = document.getElementById('reservationId').value;
    
    // URLSearchParams használata
    var params = new URLSearchParams();
    params.append('option', 'com_solidres');
    params.append('task', 'reservationasset.processPayment');
    params.append('format', 'json');
    
    // Paraméterek hozzáadása, ha értékük nem 0
    if (hubId && hubId !== '0') {
        params.append('hub_id', hubId);
    }
    if (propertyId && propertyId !== '0') {
        params.append('property_id', propertyId);
    }
    if (siteId && siteId !== '0') {
        params.append('site_id', siteId);
    }
    if (itemId && itemId !== '0') {
        params.append('Itemid', itemId);
    }
    if (reservationId && reservationId !== '0') {
        params.append('reservation_id', reservationId);
    }
    
    return baseUrl + '?' + params.toString();
}
```

#### Redirect URL Építő Függvény
```javascript
/**
 * Átirányítási URL építő függvény
 * 
 * Hasonló logika, mint az AJAX URL-nél, de ez a sikeres fizetés utáni
 * átirányításhoz használatos. Biztosítja, hogy a sikeres fizetés oldal
 * URL-je is tartalmazza az összes szükséges paramétert.
 */
function buildRedirectUrl(action) {
    var baseUrl = window.location.origin + window.location.pathname;
    
    var hubId = document.getElementById('hubId').value;
    var propertyId = document.getElementById('propertyId').value;
    var siteId = document.getElementById('siteId').value;
    var itemId = document.getElementById('itemId').value;
    var reservationId = document.getElementById('reservationId').value;
    
    var params = new URLSearchParams();
    params.append('option', 'com_solidres');
    params.append('view', 'reservationasset');
    params.append('layout', action); // 'success' vagy 'cancel'
    
    if (hubId && hubId !== '0') {
        params.append('hub_id', hubId);
    }
    if (propertyId && propertyId !== '0') {
        params.append('property_id', propertyId);
    }
    if (siteId && siteId !== '0') {
        params.append('site_id', siteId);
    }
    if (itemId && itemId !== '0') {
        params.append('Itemid', itemId);
    }
    if (reservationId && reservationId !== '0') {
        params.append('reservation_id', reservationId);
    }
    
    return baseUrl + '?' + params.toString();
}
```

#### Fizetés Feldolgozás Fetch API-val
```javascript
/**
 * Fizetési űrlap beküldés kezelése
 * 
 * A Fetch API-t használjuk XMLHttpRequest helyett, mert:
 * - Modern, tiszta promise-alapú API
 * - Jobb hibakezelés
 * - Könnyebb async/await használat
 * - Natív JSON támogatás
 */
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Gomb letiltása dupla beküldés ellen
    if (paymentBtn) {
        paymentBtn.disabled = true;
        paymentBtn.textContent = 'Fizetés feldolgozása...';
    }
    
    showStatus('Fizetés feldolgozása...', 'info');
    
    // FormData létrehozása
    var formData = new FormData(form);
    
    // Fetch API használata promise-alapú hibakezeléssel
    fetch(buildAjaxUrl(), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin', // Cookie-k küldése
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // AJAX jelzés
        }
    })
    .then(function(response) {
        // HTTP státusz kód ellenőrzése
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        // Sikeres válasz feldolgozása
        if (data.success) {
            showStatus('Sikeres fizetés!', 'success');
            
            // Ha a gateway átirányítást igényel (pl. 3D Secure)
            if (data.redirectUrl) {
                window.location.href = data.redirectUrl;
            } else {
                // Belső átirányítás (sikeres fizetés oldalra)
                setTimeout(function() {
                    window.location.href = buildRedirectUrl('success');
                }, 1500);
            }
        } else {
            // Szerver oldali hiba
            showStatus(data.message || 'Fizetési hiba', 'danger');
            paymentBtn.disabled = false;
        }
    })
    .catch(function(error) {
        // Hálózati vagy parse hiba kezelése
        console.error('Payment processing error:', error);
        showStatus('Hálózati hiba történt', 'danger');
        paymentBtn.disabled = false;
    });
});
```

#### Gateway Callback Kezelés
```javascript
/**
 * Callback kezelés külső gateway-től való visszatéréskor
 * 
 * Ha a Qvik gateway átirányít vissza az oldalra (pl. 3D Secure után),
 * akkor URL paramétereket kell ellenőriznünk
 */
var urlParams = new URLSearchParams(window.location.search);
var paymentStatus = urlParams.get('payment_status');

if (paymentStatus === 'success') {
    showStatus('Sikeres fizetés!', 'success');
    setTimeout(function() {
        window.location.href = buildRedirectUrl('success');
    }, 2000);
} else if (paymentStatus === 'failed') {
    showStatus('Sikertelen fizetés', 'danger');
} else if (paymentStatus === 'cancelled') {
    showStatus('Fizetés megszakítva', 'warning');
}
```

#### PHP Rejtett Mezők
```php
<!-- Paraméterek megőrzése -->
<input type="hidden" name="hub_id" id="hubId" value="<?php echo $hubId; ?>" />
<input type="hidden" name="property_id" id="propertyId" value="<?php echo $propertyId; ?>" />
<input type="hidden" name="site_id" id="siteId" value="<?php echo $siteId; ?>" />
<input type="hidden" name="Itemid" id="itemId" value="<?php echo $itemId; ?>" />
<input type="hidden" name="reservation_id" id="reservationId" value="<?php echo $reservationId; ?>" />
```

---

## 3. REVOLUT Plugin Változtatások

A **Revolut plugin** változtatásai **azonosak** a Qvik plugin változtatásaival, csak a következő nevek változnak:

### Fájl Útvonalak:
- `plugins/solidrespayment/revolut/tmpl/guestform.php`
- `plugins/solidrespayment/revolut/tmpl/confirmationform.php`

### Név Változtatások a Kódban:
- `qvikGuestForm` → `revolutGuestForm`
- `qvikPaymentForm` → `revolutPaymentForm`
- `qvik-guest-form-container` → `revolut-guest-form-container`
- `qvik-payment-confirmation` → `revolut-payment-confirmation`

**Minden más logika, függvény, és implementáció teljesen azonos!**

---

## 4. Kulcs Elvek Összefoglalása

### ✅ Mindig Használandó Minták

#### 1. Teljes Útvonal Megőrzése
```javascript
// HELYES ✅
var baseUrl = window.location.origin + window.location.pathname;

// HELYTELEN ❌
var baseUrl = '/index.php';
var baseUrl = window.location.origin + '/index.php';
```

#### 2. URLSearchParams Használata
```javascript
// HELYES ✅
var params = new URLSearchParams();
params.append('option', 'com_solidres');
params.append('hub_id', hubId);
return baseUrl + '?' + params.toString();

// HELYTELEN ❌
var url = baseUrl + '?option=com_solidres&hub_id=' + hubId;
```

#### 3. Fetch API Promise Lánc
```javascript
// HELYES ✅
fetch(url, options)
    .then(response => {
        if (!response.ok) throw new Error('HTTP error');
        return response.json();
    })
    .then(data => { /* feldolgozás */ })
    .catch(error => { /* hibakezelés */ });

// HELYTELEN ❌ (XMLHttpRequest használata)
var xhr = new XMLHttpRequest();
xhr.open('POST', url);
xhr.send(formData);
```

#### 4. Paraméterek Feltételes Hozzáadása
```javascript
// HELYES ✅
if (hubId && hubId !== '0') {
    params.append('hub_id', hubId);
}

// HELYTELEN ❌
params.append('hub_id', hubId); // mindig hozzáadja, még ha 0 is
```

---

## 5. Hibakezelés

### HTTP Státusz Ellenőrzés
```javascript
.then(function(response) {
    // Mindig ellenőrizni kell a HTTP státuszt
    if (!response.ok) {
        throw new Error('HTTP error! status: ' + response.status);
    }
    return response.json();
})
```

### Hálózati Hiba Kezelés
```javascript
.catch(function(error) {
    // Részletes hiba naplózás
    console.error('Error:', error);
    // Felhasználóbarát hibaüzenet
    alert('Hálózati hiba történt');
    // Felület visszaállítása
    submitBtn.disabled = false;
})
```

---

## 6. Tesztelési Ellenőrző Lista

- ✅ Főmenü → Foglalás → Fizetés
- ✅ Almenü → Foglalás → Fizetés  
- ✅ Hub választás → Foglalás → Fizetés
- ✅ Multisite → Foglalás → Fizetés
- ✅ 3D Secure átirányítás → Visszatérés
- ✅ Sikeres fizetés átirányítás
- ✅ Sikertelen fizetés kezelés
- ✅ Hálózati hiba kezelés

---

## 7. Fontos Megjegyzések

### Session és Cookie Kezelés
```javascript
fetch(url, {
    credentials: 'same-origin' // KÖTELEZŐ a session megőrzéséhez
})
```

### AJAX Jelzés Joomla-nak
```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest' // KÖTELEZŐ
}
```

### Dupla Beküldés Megelőzése
```javascript
// Gomb letiltása submit előtt
submitBtn.disabled = true;

// Gomb visszaengedélyezése hiba esetén
submitBtn.disabled = false;
```

---

## Összegzés

A végleges implementáció **teljesen kijavítja** a következő problémákat:

1. ❌ 404 hibák → ✅ Teljes pathname megőrzése
2. ❌ Útvonalvesztés → ✅ Origin + pathname használat  
3. ❌ Paraméter elvesztés → ✅ Minden paraméter explicit megőrzése
4. ❌ Hibás navigáció → ✅ buildAjaxUrl() és buildRedirectUrl() függvények
5. ❌ XMLHttpRequest → ✅ Modern Fetch API promise-alapú hibakezeléssel

**Minden menüpont alatt működik**: főmenü, almenü, hub, multisite! 🎉
