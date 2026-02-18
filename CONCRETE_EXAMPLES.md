# Qvik és Revolut - Konkrét Fetch URL/Payload Minták

## Összefoglaló

Ez a dokumentum konkrét, működő példákat tartalmaz a Qvik és Revolut fizetési módok context-aware fetch hívásaihoz.

## 1. ELŐTTE (❌ Hibás - 404 submenu contextben)

### Qvik Payment - Hibás Implementáció

```javascript
// ❌ HIBÁS - Hardcoded path, hiányzó paraméterek
function initializeQvikPaymentOLD(reservationId) {
    // HIBA 1: Hardcoded '/index.php'
    const url = window.location.origin + '/index.php?option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=' + reservationId + '&format=json';
    // Hiányzik: Itemid, hub_id, property_id, site_id
    // Hiányzik: window.location.pathname használata
    
    // HIBA 2: Egyszerű error handling
    fetch(url, {
        method: 'POST',
        body: JSON.stringify({ reservation_id: reservationId })
    })
    .then(response => response.json())
    .then(data => {
        // HIBA 3: Nincs success ellenőrzés
        window.location.href = data.payment_url;
    });
    // HIBA 4: Nincs catch block
}
```

### Probléma Root Contextben

```
Oldal URL:
https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=123&property_id=5&reservation_id=789

Fetch URL:
https://example.com/index.php?option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json

Státusz: 200 OK ✓ (működik, de hiányoznak paraméterek)
Potenciális probléma: Itemid, property_id hiányzik
```

### Probléma Submenu Contextben

```
Oldal URL:
https://example.com/hu/reservations/index.php?option=com_solidres&view=reservationasset&Itemid=123&property_id=5&reservation_id=789

Fetch URL (HIBÁS):
https://example.com/index.php?option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
                    ^^^^^^^^^^
                    Hiányzik: /hu/reservations/

Státusz: 404 Not Found ✗
Hiba: Cannot find the requested page
```

## 2. UTÁNA (✓ Helyes - Minden contextben működik)

### Qvik Payment - Helyes Implementáció

```javascript
// ✓ HELYES - Context-aware, teljes paraméter lista, proper error handling

/**
 * Context-aware URL builder
 */
function buildAjaxUrl(params) {
    // 1. Teljes path megőrzése (beleértve submenu-t is)
    const basePath = window.location.origin + window.location.pathname;
    
    // 2. Jelenlegi URL paraméterek
    const urlParams = new URLSearchParams(window.location.search);
    
    // 3. Kritikus paraméterek kinyerése
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        'property_id': urlParams.get('property_id'),
        'site_id': urlParams.get('site_id')
    };
    
    // 4. Összevonás
    const allParams = { ...criticalParams, ...params };
    
    // 5. URL építés
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    return basePath + '?' + newParams.toString();
}

/**
 * Qvik payment initialization - context-aware
 */
function initializeQvikPayment(reservationId) {
    console.log('Initializing Qvik payment for reservation:', reservationId);
    
    // Context-aware URL építés
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeQvikPayment',
        'plugin': 'qvik',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    console.log('Qvik payment URL:', url);
    
    // Fetch API három szintű error handling-gel
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            reservation_id: reservationId
        })
    })
    .then(response => {
        // 1. szint: HTTP status check
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // 2. szint: API response validation
        if (!data.success) {
            throw new Error(data.message || 'Payment initialization failed');
        }
        
        // Sikeres inicializálás
        console.log('Payment initialized successfully:', data);
        
        if (data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            throw new Error('No payment URL received');
        }
    })
    .catch(error => {
        // 3. szint: Network/parse errors
        console.error('Payment initialization error:', error);
        
        // User-friendly hibaüzenet
        const errorDiv = document.getElementById('payment-error');
        if (errorDiv) {
            errorDiv.textContent = 'Fizetési hiba: ' + error.message;
            errorDiv.style.display = 'block';
        } else {
            alert('Fizetési hiba: ' + error.message);
        }
    });
}
```

### Helyes Működés Root Contextben

```
Oldal URL:
https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=123&property_id=5&reservation_id=789

Fetch URL (buildAjaxUrl eredmény):
https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json

Paraméterek:
✓ Itemid=123 (megőrzött)
✓ property_id=5 (megőrzött)
✓ option=com_solidres (új)
✓ task=reservationasset.initializeQvikPayment (új)
✓ plugin=qvik (új)
✓ reservation_id=789 (új)
✓ format=json (új)

Státusz: 200 OK ✓
```

### Helyes Működés Submenu Contextben

```
Oldal URL:
https://example.com/hu/reservations/index.php?option=com_solidres&view=reservationasset&Itemid=123&property_id=5&reservation_id=789

Fetch URL (buildAjaxUrl eredmény):
https://example.com/hu/reservations/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
                    ^^^^^^^^^^^^^^^^^^
                    ✓ Submenu path megőrzött!

Paraméterek:
✓ Path: /hu/reservations/index.php (megőrzött!)
✓ Itemid=123 (megőrzött)
✓ property_id=5 (megőrzött)
✓ option=com_solidres (új)
✓ task=reservationasset.initializeQvikPayment (új)
✓ plugin=qvik (új)
✓ reservation_id=789 (új)
✓ format=json (új)

Státusz: 200 OK ✓ (Nincs többé 404!)
```

### Helyes Működés Hub Contextben

```
Oldal URL:
https://example.com/hotel1/index.php?option=com_solidres&view=reservationasset&Itemid=123&hub_id=5&property_id=10&reservation_id=789

Fetch URL (buildAjaxUrl eredmény):
https://example.com/hotel1/index.php?Itemid=123&hub_id=5&property_id=10&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
                    ^^^^^^^
                    ✓ Hub path megőrzött!

Paraméterek:
✓ Path: /hotel1/index.php (megőrzött!)
✓ Itemid=123 (megőrzött)
✓ hub_id=5 (megőrzött!)
✓ property_id=10 (megőrzött)
✓ option=com_solidres (új)
✓ task=reservationasset.initializeQvikPayment (új)
✓ plugin=qvik (új)
✓ reservation_id=789 (új)
✓ format=json (új)

Státusz: 200 OK ✓
```

## 3. Revolut Payment - Konkrét Példák

### Revolut - Helyes Implementáció

```javascript
/**
 * Revolut payment initialization - context-aware
 */
function initializeRevolutPayment(reservationId) {
    console.log('Initializing Revolut payment for reservation:', reservationId);
    
    // Ugyanazt a buildAjaxUrl-t használjuk
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeRevolutPayment',
        'plugin': 'revolut',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    console.log('Revolut payment URL:', url);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            reservation_id: reservationId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Payment initialization failed');
        }
        
        // Revolut widget megjelenítése
        if (data.public_id && window.RevolutCheckout) {
            RevolutCheckout(data.public_id).then(function(instance) {
                instance.payWithPopup({
                    onSuccess() {
                        console.log('Payment successful');
                        // Success redirect
                        window.location.href = buildRedirectUrl({
                            'view': 'reservation',
                            'layout': 'success',
                            'reservation_id': reservationId
                        });
                    },
                    onError(error) {
                        console.error('Payment error:', error);
                        showErrorMessage(error.message);
                    },
                    onCancel() {
                        console.log('Payment cancelled');
                        showErrorMessage('A fizetés megszakítva');
                    }
                });
            });
        } else {
            throw new Error('Revolut payment system not available');
        }
    })
    .catch(error => {
        console.error('Payment initialization error:', error);
        showErrorMessage(error.message);
    });
}

function showErrorMessage(message) {
    const errorDiv = document.getElementById('payment-error');
    if (errorDiv) {
        errorDiv.textContent = 'Fizetési hiba: ' + message;
        errorDiv.style.display = 'block';
    } else {
        alert('Fizetési hiba: ' + message);
    }
}
```

### Revolut - Példa URL-ek

**Root context:**
```
https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json
```

**Submenu context:**
```
https://example.com/hu/reservations/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json
```

**Hub context:**
```
https://example.com/hotel1/index.php?Itemid=123&hub_id=5&property_id=10&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json
```

## 4. Fetch Request Payload Példák

### POST Request Body (Qvik és Revolut)

```json
{
    "reservation_id": "789"
}
```

### Fetch Headers

```javascript
{
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
}
```

## 5. Server Response Példák

### Sikeres Qvik Inicializálás

```json
{
    "success": true,
    "message": "Payment initialized successfully",
    "payment_url": "https://qvik.gateway.com/pay?token=abc123def456",
    "transaction_id": "qvik_trans_12345",
    "reservation_id": "789",
    "amount": 15000,
    "currency": "HUF"
}
```

### Sikeres Revolut Inicializálás

```json
{
    "success": true,
    "message": "Payment initialized successfully",
    "public_id": "revolut_pub_abc123def456",
    "order_id": "revolut_order_12345",
    "reservation_id": "789",
    "amount": 15000,
    "currency": "HUF"
}
```

### Hiba Válasz (bármely payment method)

```json
{
    "success": false,
    "message": "Invalid reservation ID",
    "error_code": "INVALID_RESERVATION",
    "details": "Reservation 789 not found or already paid"
}
```

### HTTP 404 Hiba (régi implementációval submenu contextben)

```
Status: 404 Not Found
Content-Type: text/html

<!DOCTYPE html>
<html>
<head><title>404 Not Found</title></head>
<body>
<h1>404 Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body>
</html>
```

## 6. buildRedirectUrl() Függvény

```javascript
/**
 * Context-aware redirect URL builder
 */
function buildRedirectUrl(params) {
    const basePath = window.location.origin + window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        'property_id': urlParams.get('property_id'),
        'site_id': urlParams.get('site_id')
    };
    
    const allParams = { 
        ...criticalParams, 
        'option': 'com_solidres', 
        ...params 
    };
    
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    return basePath + '?' + newParams.toString();
}
```

### Redirect URL Példák

**Success redirect root contextben:**
```
https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&view=reservation&layout=success&reservation_id=789
```

**Success redirect submenu contextben:**
```
https://example.com/hu/reservations/index.php?Itemid=123&property_id=5&option=com_solidres&view=reservation&layout=success&reservation_id=789
```

## 7. Teljes HTML Példa

```html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Confirmation</title>
</head>
<body>
    <div id="payment-container">
        <h2>Válassz fizetési módot</h2>
        
        <div id="payment-error" style="display:none; color:red; margin:10px 0;"></div>
        
        <button 
            id="qvik-payment-button"
            data-payment-method="qvik"
            data-reservation-id="789">
            Qvik fizetés
        </button>
        
        <button 
            id="revolut-payment-button"
            data-payment-method="revolut"
            data-reservation-id="789">
            Revolut fizetés
        </button>
    </div>
    
    <script>
        // buildAjaxUrl függvény (lásd fent)
        function buildAjaxUrl(params) { /* ... */ }
        
        // Qvik payment
        function initializeQvikPayment(reservationId) { /* ... */ }
        
        // Revolut payment
        function initializeRevolutPayment(reservationId) { /* ... */ }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Qvik button
            document.getElementById('qvik-payment-button').addEventListener('click', function(e) {
                e.preventDefault();
                const reservationId = this.getAttribute('data-reservation-id');
                initializeQvikPayment(reservationId);
            });
            
            // Revolut button
            document.getElementById('revolut-payment-button').addEventListener('click', function(e) {
                e.preventDefault();
                const reservationId = this.getAttribute('data-reservation-id');
                initializeRevolutPayment(reservationId);
            });
        });
    </script>
</body>
</html>
```

## 8. Összehasonlító Táblázat

| Aspektus | Régi (Hibás) | Új (Helyes) |
|----------|--------------|-------------|
| **Path** | Hardcoded `/index.php` | `window.location.pathname` |
| **Itemid** | ❌ Hiányzik | ✓ Megőrzött |
| **hub_id** | ❌ Hiányzik | ✓ Megőrzött |
| **property_id** | ❌ Hiányzik | ✓ Megőrzött |
| **Error handling** | ❌ Nincs | ✓ Háromszintű |
| **Headers** | ❌ Hiányos | ✓ Teljes |
| **Root context** | ⚠️ Működik (részben) | ✓ Teljesen működik |
| **Submenu context** | ❌ 404 hiba | ✓ Működik |
| **Hub context** | ❌ 404 hiba | ✓ Működik |

## Következtetés

A **buildAjaxUrl()** függvény használata biztosítja, hogy:

1. ✓ Minden context path megőrzött (root, submenu, hub)
2. ✓ Minden kritikus paraméter átadódik (Itemid, hub_id, property_id, site_id)
3. ✓ Nincs több 404 hiba submenu contextben
4. ✓ Proper error handling minden szinten
5. ✓ Konzisztens implementáció Qvik és Revolut között

**A megoldás univerzális és minden Solidres AJAX hívásra alkalmazható!**
