# Gyors Referencia - Qvik & Revolut Fizetési Pluginok

## Függvények Áttekintése

### buildBaseUrl()
**Visszatérési érték**: `string`  
**Paraméterek**: Nincs

**Példa**:
```javascript
var baseUrl = buildBaseUrl();
// Eredmény: "https://example.com/hu/szallasok/apartmanok"
```

**Használat**: Minden URL építés alapja. Megőrzi a teljes pathname-t és origin-t.

---

### buildAjaxUrl(task, additionalParams)
**Visszatérési érték**: `string`  
**Paraméterek**:
- `task` (string, kötelező) - A Solidres task neve (pl. "payment.initiate")
- `additionalParams` (object, opcionális) - További URL paraméterek

**Példa 1 - Egyszerű használat**:
```javascript
var url = buildAjaxUrl('payment.initiate');
// Eredmény: "https://example.com/hu/szallasok?option=com_solidres&task=payment.initiate&format=json&Itemid=123&property_id=456"
```

**Példa 2 - További paraméterekkel**:
```javascript
var url = buildAjaxUrl('payment.initiate', {
    payment_method: 'qvik',
    amount: 15000
});
// Eredmény: "...&payment_method=qvik&amount=15000"
```

**Használat**: AJAX kérések URL-jeinek építésére. Automatikusan megőrzi az Itemid, property_id, hub_id, site_id, reservation_id paramétereket.

---

### buildRedirectUrl(view, additionalParams)
**Visszatérési érték**: `string`  
**Paraméterek**:
- `view` (string, kötelező) - A Solidres view neve (pl. "reservationdetails")
- `additionalParams` (object, opcionális) - További URL paraméterek

**Példa 1 - Sikeres fizetés**:
```javascript
var url = buildRedirectUrl('reservationdetails', {
    reservation_id: 789,
    payment_success: '1'
});
// Eredmény: "https://example.com/hu/szallasok?option=com_solidres&view=reservationdetails&Itemid=123&reservation_id=789&payment_success=1"
```

**Példa 2 - Megszakított foglalás**:
```javascript
var url = buildRedirectUrl('reservationcancelled', {
    error: 'payment_failed'
});
```

**Használat**: Átirányítási URL-ek építésére. Automatikusan megőrzi az összes fontos paramétert.

---

## Használati Minták

### Minta 1: Fizetés Inicializálása

```javascript
function initiatePayment() {
    // 1. Gomb letiltása
    var button = document.getElementById('pay-button');
    button.disabled = true;
    button.textContent = 'Feldolgozás...';
    
    // 2. URL felépítése
    var ajaxUrl = buildAjaxUrl('payment.initiate', {
        payment_method: 'qvik'
    });
    
    // 3. Fetch kérés
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP hiba: ' + response.status);
        return response.json();
    })
    .then(function(data) {
        if (data.success && data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            alert('Hiba: ' + (data.message || 'Ismeretlen hiba'));
        }
    })
    .catch(function(error) {
        console.error('Hiba:', error);
        alert('Hiba történt a fizetés során.');
        button.disabled = false;
        button.textContent = 'Próbálja újra';
    });
}
```

### Minta 2: Form Adatok Küldése

```javascript
function submitGuestForm(event) {
    event.preventDefault();
    
    // 1. Adatok összegyűjtése
    var formData = {
        guest_firstname: document.getElementById('guest_firstname').value,
        guest_lastname: document.getElementById('guest_lastname').value,
        guest_email: document.getElementById('guest_email').value,
        guest_phone: document.getElementById('guest_phone').value
    };
    
    // 2. Validálás
    if (!formData.guest_firstname) {
        alert('Kérjük, adja meg a keresztnevet!');
        return;
    }
    
    // 3. URL felépítése
    var ajaxUrl = buildAjaxUrl('reservation.saveguestdata');
    
    // 4. Fetch kérés
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify(formData)
    })
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP hiba');
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            // Átirányítás a fizetési oldalra
            var paymentUrl = buildRedirectUrl('reservationpayment', {
                reservation_id: data.reservation_id
            });
            window.location.href = paymentUrl;
        } else {
            alert('Hiba: ' + data.message);
        }
    })
    .catch(function(error) {
        console.error('Hiba:', error);
        alert('Hiba történt az adatok mentése során.');
    });
}
```

### Minta 3: Callback Kezelése

```javascript
function handlePaymentCallback() {
    // 1. URL paraméterek ellenőrzése
    var params = new URLSearchParams(window.location.search);
    
    if (!params.has('payment_status')) {
        return; // Nem callback
    }
    
    // 2. Státusz lekérése
    var status = params.get('payment_status');
    var transactionId = params.get('transaction_id');
    
    // 3. Verifikáció URL felépítése
    var verifyUrl = buildAjaxUrl('payment.verify', {
        payment_method: 'qvik',
        transaction_id: transactionId,
        status: status
    });
    
    // 4. Verifikációs kérés
    fetch(verifyUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success && data.verified) {
            // Sikeres fizetés
            var successUrl = buildRedirectUrl('reservationdetails', {
                reservation_id: data.reservation_id,
                payment_success: '1'
            });
            window.location.href = successUrl;
        } else {
            // Sikertelen fizetés
            var failUrl = buildRedirectUrl('reservationcancelled', {
                error: 'verification_failed'
            });
            window.location.href = failUrl;
        }
    })
    .catch(function(error) {
        console.error('Callback hiba:', error);
        var errorUrl = buildRedirectUrl('reservationcancelled', {
            error: 'callback_error'
        });
        window.location.href = errorUrl;
    });
}
```

## Gyakran Használt Kódrészletek

### Email Validálás
```javascript
function validateEmail(email) {
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

// Használat
if (!validateEmail(formData.email)) {
    alert('Érvénytelen email cím!');
    return false;
}
```

### Gomb Állapotkezelés
```javascript
// Letiltás
function disableButton(buttonId, newText) {
    var button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        button.textContent = newText || 'Feldolgozás...';
    }
}

// Engedélyezés
function enableButton(buttonId, newText) {
    var button = document.getElementById(buttonId);
    if (button) {
        button.disabled = false;
        button.textContent = newText;
    }
}

// Használat
disableButton('pay-button', 'Fizetés folyamatban...');
// ... művelet ...
enableButton('pay-button', 'Fizetés');
```

### URL Paraméter Lekérése
```javascript
function getUrlParameter(paramName) {
    var params = new URLSearchParams(window.location.search);
    return params.get(paramName);
}

// Használat
var reservationId = getUrlParameter('reservation_id');
var hubId = getUrlParameter('hub_id');
```

### Loading Indikátor
```javascript
function showLoading(message) {
    var loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-indicator';
    loadingDiv.className = 'alert alert-info';
    loadingDiv.textContent = message || 'Feldolgozás...';
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    var loadingDiv = document.getElementById('loading-indicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Használat
showLoading('Fizetés inicializálása...');
// ... művelet ...
hideLoading();
```

## Hibakezelési Sablonok

### Alapvető Try-Catch
```javascript
try {
    // Kód végrehajtása
    var result = someFunction();
} catch (error) {
    console.error('Hiba:', error);
    alert('Hiba történt: ' + error.message);
}
```

### Fetch Hibakezelés Teljes Sablon
```javascript
fetch(url, options)
    .then(function(response) {
        // HTTP státusz ellenőrzés
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(function(data) {
        // API válasz ellenőrzés
        if (!data.success) {
            throw new Error(data.message || 'API hiba történt');
        }
        
        // Sikeres feldolgozás
        console.log('Siker:', data);
        return data;
    })
    .catch(function(error) {
        // Minden hiba kezelése
        console.error('Hiba:', error);
        
        // Felhasználói üzenet
        alert('Hiba történt: ' + error.message);
        
        // UI visszaállítás
        enableButton('submit-button', 'Próbálja újra');
    })
    .finally(function() {
        // Mindig lefut (sikertől függetlenül)
        hideLoading();
    });
```

## Debug Tippek

### Konzol Naplózás
```javascript
// URL naplózása
console.log('AJAX URL:', buildAjaxUrl('payment.initiate'));

// Paraméterek naplózása
var params = new URLSearchParams(window.location.search);
console.log('Aktuális paraméterek:', {
    Itemid: params.get('Itemid'),
    property_id: params.get('property_id'),
    hub_id: params.get('hub_id'),
    reservation_id: params.get('reservation_id')
});

// Response naplózása
fetch(url)
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
    });
```

### Network Monitor
1. Nyisd meg a böngésző Developer Tools-t (F12)
2. Válaszd a "Network" fület
3. Szűrd a XHR kéréseket
4. Kattints egy kérésre a részletek megtekintéséhez:
   - **Headers** - URL, paraméterek, fejlécek
   - **Response** - Szerver válasz
   - **Preview** - JSON formázott nézet

### Gyakori Problémák

**404 Not Found**
```javascript
// Ellenőrizd az Itemid paramétert
console.log('Itemid:', new URLSearchParams(window.location.search).get('Itemid'));
```

**CORS hiba**
```javascript
// Ellenőrizd a credentials beállítást
fetch(url, {
    credentials: 'same-origin'  // FONTOS!
})
```

**JSON parse hiba**
```javascript
// Nézd meg a nyers választ
fetch(url)
    .then(response => response.text())
    .then(text => {
        console.log('Raw response:', text);
        return JSON.parse(text);
    })
```

## Tesztelési Checklist

```javascript
// 1. Alap funkcionalitás
[ ] buildBaseUrl() helyes URL-t ad vissza
[ ] buildAjaxUrl() megőrzi az összes paramétert
[ ] buildRedirectUrl() megőrzi az összes paramétert

// 2. Fizetési folyamat
[ ] Fizetés gomb működik
[ ] Dupla kattintás védelem működik
[ ] Sikeres fizetés átirányít
[ ] Sikertelen fizetés hibaüzenetet ad

// 3. Form kezelés
[ ] Form validálás működik
[ ] Email validálás helyes
[ ] Adatok mentése működik
[ ] Átirányítás fizetéshez működik

// 4. Callback kezelés
[ ] Callback felismerése működik
[ ] Státusz verifikáció működik
[ ] Sikeres callback átirányít
[ ] Sikertelen callback kezelve van

// 5. Különböző környezetek
[ ] Egyszerű menüpont működik
[ ] Almenü működik
[ ] Hub működik
[ ] Többnyelvű működik
```

## Referencia Linkek

- **Fetch API**: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API
- **URLSearchParams**: https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
- **Promise**: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Promise
- **Joomla Docs**: https://docs.joomla.org/
- **Solidres Docs**: https://solidres.com/documentation

## Gyors Segítség

Ha valamivel elakadsz:
1. Nézd meg a böngésző konzolt (F12)
2. Ellenőrizd a Network fület az XHR kérésekhez
3. Nézd meg a README.md-t részletesebb magyarázatért
4. Nézd meg az IMPLEMENTATION_NOTES.md-t mélyebb technikai részletekért
