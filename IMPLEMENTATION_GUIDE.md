# Solidres Qvik és Revolut Fizetési Pluginok - JS Fetch/Redirect Logika Dokumentáció

## Áttekintés

A Solidres Qvik és Revolut fizetési pluginok JS fetch/redirect logikája kijavításra került minden menüpont (almenü/hub/multisite) alatt. Ez a dokumentum részletezi a végleges JavaScript és sablon változtatásokat a `guestform.php` és `confirmationform.php` fájlok esetében.

## A Probléma

A korábbi implementációban a következő problémák jelentkeztek:
- **404 hibák**: Az URL-ek elvesztették a kontextust almenü/hub/multisite környezetekben
- **Útvonalvesztés**: A pathname nem tartalmazta az összes szegmenst
- **Paraméter elvesztés**: A hub_id, property_id, site_id, Itemid paraméterek nem kerültek megőrzésre
- **Hibás navigáció**: A redirect URL-ek nem mutattak a megfelelő helyre

## A Megoldás - Kulcs Elemek

### 1. Teljes Útvonal Kontextus Megőrzése

**Alapelv**: `window.location.origin + window.location.pathname` használata

```javascript
// ✅ HELYES - Teljes kontextus megőrzése
var baseUrl = window.location.origin + window.location.pathname;
// Példa: https://example.com/submenu1/submenu2/index.php

// ❌ HELYTELEN - Kontextus elvesztése
var baseUrl = window.location.origin + '/index.php';
// Példa: https://example.com/index.php (almenü szegmensek elvesznek)
```

**Részletek**:
- `window.location.origin`: protocol + domain + port (pl. `https://example.com:8080`)
- `window.location.pathname`: teljes útvonal minden szegmenssel (pl. `/hub/property/submenu/index.php`)

### 2. URL Paraméterek Megőrzése

**Paraméterek**, amelyeket mindig meg kell őrizni:
- `hub_id`: Hub azonosító multisite környezetben
- `property_id`: Ingatlan azonosító
- `site_id`: Oldal azonosító
- `Itemid`: Joomla menü azonosító (kritikus az almenükhöz)
- `reservation_id`: Foglalás azonosító (fizetési oldalon)

**Implementáció**:

```javascript
function buildAjaxUrl() {
    var baseUrl = window.location.origin + window.location.pathname;
    
    // Paraméterek kinyerése rejtett mezőkből
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

### 3. Fetch API Használata Promise-Alapú Hibakezeléssel

**Miért Fetch API és nem XMLHttpRequest?**
- Modern, tiszta promise-alapú API
- Jobb hibakezelés
- Könnyebb async/await használat
- Natív JSON támogatás

**Implementáció**:

```javascript
fetch(buildAjaxUrl(), {
    method: 'POST',
    body: formData,
    credentials: 'same-origin', // Cookie-k küldése (session)
    headers: {
        'X-Requested-With': 'XMLHttpRequest' // AJAX jelzés Joomla-nak
    }
})
.then(function(response) {
    // HTTP státusz ellenőrzése
    if (!response.ok) {
        throw new Error('HTTP error! status: ' + response.status);
    }
    return response.json();
})
.then(function(data) {
    // Sikeres válasz feldolgozása
    if (data.success) {
        if (data.redirectUrl) {
            window.location.href = data.redirectUrl;
        }
    } else {
        // Szerver oldali hiba kezelése
        showStatus(data.message, 'danger');
    }
})
.catch(function(error) {
    // Hálózati vagy parse hiba kezelése
    console.error('Error:', error);
    showStatus('Hálózati hiba történt', 'danger');
});
```

## Fájl Specifikus Változtatások

### guestform.php

**Felelősség**: Vendég adatok gyűjtése és küldése

**Kulcs JavaScript Funkciók**:

1. **buildSubmitUrl()**: URL építés az űrlap beküldéséhez
   - Teljes pathname megőrzése
   - Paraméterek hozzáadása

2. **Űrlap submit kezelő**:
   - HTML5 validáció (`form.checkValidity()`)
   - Dupla beküldés megelőzése (gomb letiltás)
   - Fetch API használat
   - Átirányítás sikeres feldolgozás után

**Kód részlet**:

```javascript
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validáció
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Submit gomb letiltása
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Feldolgozás...';
    }
    
    // AJAX kérés
    var formData = new FormData(form);
    fetch(buildSubmitUrl(), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        } else {
            // Hiba kezelés
            alert(data.message || 'Hiba történt');
            submitBtn.disabled = false;
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('Hálózati hiba');
        submitBtn.disabled = false;
    });
});
```

### confirmationform.php

**Felelősség**: Fizetés feldolgozása és átirányítás

**Kulcs JavaScript Funkciók**:

1. **buildAjaxUrl()**: AJAX URL építés fizetés feldolgozáshoz
   - Teljes pathname + origin megőrzése
   - Minden paraméter hozzáadása
   - JSON formátum kérése

2. **buildRedirectUrl(action)**: Átirányítási URL építés
   - Sikeres/sikertelen fizetés oldalhoz
   - Paraméterek megőrzése

3. **showStatus(message, type)**: Státusz üzenet megjelenítése

4. **Fizetési űrlap submit kezelő**:
   - Gomb letiltás dupla beküldés ellen
   - Fetch API használat
   - Gateway átirányítás kezelése (3D Secure)
   - Belső átirányítás sikeres fizetés után

5. **Callback kezelés**: URL paraméterek ellenőrzése gateway visszatéréskor

**Kód részletek**:

```javascript
// AJAX URL építés
function buildAjaxUrl() {
    var baseUrl = window.location.origin + window.location.pathname;
    
    var hubId = document.getElementById('hubId').value;
    var propertyId = document.getElementById('propertyId').value;
    var siteId = document.getElementById('siteId').value;
    var itemId = document.getElementById('itemId').value;
    var reservationId = document.getElementById('reservationId').value;
    
    var params = new URLSearchParams();
    params.append('option', 'com_solidres');
    params.append('task', 'reservationasset.processPayment');
    params.append('format', 'json');
    
    if (hubId && hubId !== '0') params.append('hub_id', hubId);
    if (propertyId && propertyId !== '0') params.append('property_id', propertyId);
    if (siteId && siteId !== '0') params.append('site_id', siteId);
    if (itemId && itemId !== '0') params.append('Itemid', itemId);
    if (reservationId && reservationId !== '0') params.append('reservation_id', reservationId);
    
    return baseUrl + '?' + params.toString();
}

// Redirect URL építés
function buildRedirectUrl(action) {
    var baseUrl = window.location.origin + window.location.pathname;
    
    var params = new URLSearchParams();
    params.append('option', 'com_solidres');
    params.append('view', 'reservationasset');
    params.append('layout', action); // 'success' vagy 'cancel'
    
    // Paraméterek hozzáadása...
    
    return baseUrl + '?' + params.toString();
}

// Fizetés feldolgozás
fetch(buildAjaxUrl(), {
    method: 'POST',
    body: formData,
    credentials: 'same-origin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
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
        showStatus('Sikeres fizetés', 'success');
        
        // Gateway átirányítás (pl. 3D Secure)
        if (data.redirectUrl) {
            window.location.href = data.redirectUrl;
        } else {
            // Belső átirányítás
            setTimeout(function() {
                window.location.href = buildRedirectUrl('success');
            }, 1500);
        }
    } else {
        showStatus(data.message, 'danger');
        paymentBtn.disabled = false;
    }
})
.catch(function(error) {
    console.error('Payment error:', error);
    showStatus('Hálózati hiba', 'danger');
    paymentBtn.disabled = false;
});

// Callback kezelés gateway visszatéréskor
var urlParams = new URLSearchParams(window.location.search);
var paymentStatus = urlParams.get('payment_status');

if (paymentStatus === 'success') {
    showStatus('Sikeres fizetés', 'success');
    setTimeout(function() {
        window.location.href = buildRedirectUrl('success');
    }, 2000);
} else if (paymentStatus === 'failed') {
    showStatus('Sikertelen fizetés', 'danger');
}
```

## PHP Sablon Részletek

### Rejtett Mezők

**Kritikus**: Minden űrlapban rejtett mezőkként tárolni a paramétereket

```php
<!-- Paraméterek megőrzése -->
<input type="hidden" name="hub_id" id="hubId" value="<?php echo $hubId; ?>" />
<input type="hidden" name="property_id" id="propertyId" value="<?php echo $propertyId; ?>" />
<input type="hidden" name="site_id" id="siteId" value="<?php echo $siteId; ?>" />
<input type="hidden" name="Itemid" id="itemId" value="<?php echo $itemId; ?>" />
<input type="hidden" name="reservation_id" id="reservationId" value="<?php echo $reservationId; ?>" />
```

### Paraméterek Kinyerése

```php
// URL paraméterek biztonságos kinyerése
$hubId       = $this->app->input->getInt('hub_id', 0);
$propertyId  = $this->app->input->getInt('property_id', 0);
$siteId      = $this->app->input->getInt('site_id', 0);
$itemId      = $this->app->input->getInt('Itemid', 0);
$reservationId = $this->app->input->getInt('reservation_id', 0);
```

## Tesztelési Forgatókönyvek

### 1. Almenü Teszt
- Navigálás: Főmenü → Almenü1 → Almenü2 → Foglalás
- Ellenőrzés: URL tartalmazza `/submenu1/submenu2/`
- Eredmény: ✅ Sikeres fizetés, helyes átirányítás

### 2. Hub Teszt
- Navigálás: Hub választás → Ingatlan → Foglalás
- Ellenőrzés: `hub_id` paraméter minden URL-ben
- Eredmény: ✅ Hub kontextus megmarad

### 3. Multisite Teszt
- Navigálás: Site választás → Foglalás
- Ellenőrzés: `site_id` paraméter minden URL-ben
- Eredmény: ✅ Site kontextus megmarad

### 4. Gateway Átirányítás Teszt
- Folyamat: Fizetés → 3D Secure → Visszatérés
- Ellenőrzés: `payment_status` paraméter kezelése
- Eredmény: ✅ Helyes callback feldolgozás

## Gyakori Hibák és Megoldásuk

### Hiba 1: 404 Not Found

**Probléma**: `window.location.pathname` nem használt
**Megoldás**: 
```javascript
// ❌ var baseUrl = window.location.origin + '/index.php';
// ✅
var baseUrl = window.location.origin + window.location.pathname;
```

### Hiba 2: Paraméter Elvesztés

**Probléma**: Paraméterek nem kerülnek hozzáadásra az URL-hez
**Megoldás**: Rejtett mezők használata és explicit hozzáadás
```javascript
if (hubId && hubId !== '0') {
    params.append('hub_id', hubId);
}
```

### Hiba 3: AJAX Hiba

**Probléma**: `credentials` vagy `X-Requested-With` header hiányzik
**Megoldás**:
```javascript
fetch(url, {
    credentials: 'same-origin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
})
```

## Összefoglalás

A kijavított implementáció biztosítja, hogy:
- ✅ Nincs 404 hiba
- ✅ Nincs útvonalvesztés
- ✅ Minden paraméter megmarad
- ✅ Multisite/hub/almenü környezetekben is működik
- ✅ Modern Fetch API használat
- ✅ Promise-alapú hibakezelés
- ✅ Gateway átirányítások helyes kezelése

**Kulcs Elvek**:
1. **Mindig** `window.location.origin + window.location.pathname`
2. **Mindig** megőrizni az összes paramétert (hub_id, property_id, site_id, Itemid, reservation_id)
3. **Mindig** Fetch API-t használni promise-alapú hibakezeléssel
4. **Mindig** rejtett mezőkben tárolni a paramétereket az űrlapokban
