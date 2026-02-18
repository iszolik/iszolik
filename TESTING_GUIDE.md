# Qvik és Revolut Payment - Context Teszt Útmutató

## Tesztelési Forgatókönyv

Ez a dokumentum részletes útmutatót nyújt a Qvik és Revolut fizetési módok context-aware AJAX hívásainak teszteléséhez.

## 1. Előkészületek

### Tesztelt Környezetek

1. **Root Context** - Fő menüből elérhető foglalás
2. **Submenu Context** - Almenüből elérhető foglalás (pl. /hu/reservations/)
3. **Hub Context** - Multi-hub környezetben (pl. /hotel1/)

### Szükséges Eszközök

- Modern böngésző (Chrome, Firefox, Edge)
- Developer Tools (F12)
- Network tab a fetch hívások monitorozásához
- Console tab a debug log-ok megtekintéséhez

## 2. Root Context Teszt

### Teszt Lépések

#### 2.1. Navigáció
```
URL: https://example.com/index.php?option=com_solidres&view=reservationasset&layout=confirmationform&Itemid=123&property_id=5&reservation_id=789
```

#### 2.2. Ellenőrzés a Console-ban
```javascript
// Nyisd meg a Console-t (F12 → Console tab)
// Futtasd ezt a kódot:

console.log('Origin:', window.location.origin);
// Várt eredmény: https://example.com

console.log('Pathname:', window.location.pathname);
// Várt eredmény: /index.php

console.log('Search:', window.location.search);
// Várt eredmény: ?option=com_solidres&view=reservationasset&layout=confirmationform&Itemid=123&property_id=5&reservation_id=789

const urlParams = new URLSearchParams(window.location.search);
console.log('Itemid:', urlParams.get('Itemid'));
// Várt eredmény: 123

console.log('property_id:', urlParams.get('property_id'));
// Várt eredmény: 5
```

#### 2.3. Qvik Fizetés Teszt

1. Válaszd ki a "Qvik" fizetési módot
2. Kattints a "Fizetés" gombra
3. Nyisd meg a Network tab-ot (F12 → Network)
4. Szűrd a fetch/XHR kéréseket

**Várt eredmény:**
```
Request URL: https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json

Status: 200 OK
```

**HIBA jel:**
```
Status: 404 Not Found
vagy
Status: 500 Internal Server Error
```

#### 2.4. Revolut Fizetés Teszt

1. Válaszd ki a "Revolut" fizetési módot
2. Kattints a "Fizetés" gombra
3. Ellenőrizd a Network tab-ot

**Várt eredmény:**
```
Request URL: https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json

Status: 200 OK
```

## 3. Submenu Context Teszt

### Teszt Lépések

#### 3.1. Navigáció
```
URL: https://example.com/hu/reservations/index.php?option=com_solidres&view=reservationasset&layout=confirmationform&Itemid=123&property_id=5&reservation_id=789
```

**FONTOS:** Figyeld meg az URL-ben a `/hu/reservations/` részt!

#### 3.2. Ellenőrzés a Console-ban
```javascript
console.log('Origin:', window.location.origin);
// Várt eredmény: https://example.com

console.log('Pathname:', window.location.pathname);
// Várt eredmény: /hu/reservations/index.php
// ⚠️ NEM: /index.php

console.log('Full path:', window.location.origin + window.location.pathname);
// Várt eredmény: https://example.com/hu/reservations/index.php
```

#### 3.3. Qvik Fizetés Teszt (Kritikus!)

1. Válaszd ki a "Qvik" fizetési módot
2. Kattints a "Fizetés" gombra
3. **FIGYELMESEN** nézd meg a Network tab-ban a Request URL-t!

**✓ HELYES (context-aware implementáció):**
```
Request URL: https://example.com/hu/reservations/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
                                  ^^^^^^^^^^^^^^^^^^
                                  Megvan a submenu path!

Status: 200 OK ✓
```

**❌ HIBÁS (régi, hardcoded /index.php implementáció):**
```
Request URL: https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
                                  ^^^^^^^^^^
                                  Hiányzik a /hu/reservations/ !

Status: 404 Not Found ✗
Error: Cannot find menu item
```

#### 3.4. Revolut Fizetés Teszt (Kritikus!)

**✓ HELYES:**
```
Request URL: https://example.com/hu/reservations/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json

Status: 200 OK ✓
```

**❌ HIBÁS:**
```
Request URL: https://example.com/index.php?...
Status: 404 Not Found ✗
```

## 4. Hub Context Teszt (Ha Multi-Hub Környezet Van)

### Teszt Lépések

#### 4.1. Navigáció
```
URL: https://example.com/hotel1/index.php?option=com_solidres&view=reservationasset&layout=confirmationform&Itemid=123&hub_id=5&property_id=10&reservation_id=789
```

#### 4.2. Ellenőrzés a Console-ban
```javascript
console.log('Pathname:', window.location.pathname);
// Várt eredmény: /hotel1/index.php

const urlParams = new URLSearchParams(window.location.search);
console.log('hub_id:', urlParams.get('hub_id'));
// Várt eredmény: 5
```

#### 4.3. Fizetés Teszt

**✓ HELYES:**
```
Request URL: https://example.com/hotel1/index.php?Itemid=123&hub_id=5&property_id=10&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
                                  ^^^^^^^
                                  Hub path megvan!

Status: 200 OK ✓
```

## 5. Paraméterek Ellenőrzése

### Kritikus Paraméterek Checklist

Minden fetch kérésben a következő paramétereknek meg kell lenniük:

```javascript
// Ellenőrző script a Console-ban:

const url = new URL(document.querySelector('[data-payment-method="qvik"]')?.getAttribute('data-ajax-url') || 'about:blank');
const params = new URLSearchParams(url.search);

console.group('Parameter Check');
console.log('✓ Itemid:', params.get('Itemid') ? '✓' : '✗ MISSING');
console.log('✓ option:', params.get('option') ? '✓' : '✗ MISSING');
console.log('✓ task:', params.get('task') ? '✓' : '✗ MISSING');
console.log('✓ plugin:', params.get('plugin') ? '✓' : '✗ MISSING');
console.log('✓ reservation_id:', params.get('reservation_id') ? '✓' : '✗ MISSING');
console.log('✓ format:', params.get('format') ? '✓' : '✗ MISSING');

// Optional de fontos:
console.log('  hub_id:', params.get('hub_id') || 'N/A');
console.log('  property_id:', params.get('property_id') || 'N/A');
console.log('  site_id:', params.get('site_id') || 'N/A');
console.groupEnd();
```

**Várt eredmény:**
```
Parameter Check
  ✓ Itemid: ✓
  ✓ option: ✓
  ✓ task: ✓
  ✓ plugin: ✓
  ✓ reservation_id: ✓
  ✓ format: ✓
  hub_id: 5
  property_id: 10
  site_id: N/A
```

## 6. Error Handling Teszt

### Sikeres Válasz Teszt

1. Indítsd el a fizetést
2. Network tab → válaszd ki a fetch kérést
3. Nézd meg a Response tab-ot

**Várt JSON válasz:**
```json
{
    "success": true,
    "message": "Payment initialized successfully",
    "payment_url": "https://payment.gateway.com/pay/abc123",
    "transaction_id": "trans_12345",
    "reservation_id": "789"
}
```

### Hiba Válasz Teszt

**Szimulálj hibát** (pl. invalid reservation_id):

1. Modify the request (Chrome DevTools → Network → Right-click → Edit and Resend)
2. Változtasd meg a `reservation_id`-t egy invalid értékre
3. Küldd újra a kérést

**Várt JSON válasz:**
```json
{
    "success": false,
    "message": "Invalid reservation ID",
    "error_code": "INVALID_RESERVATION"
}
```

**Console kimenet ellenőrzése:**
```
Payment initialization error: Invalid reservation ID
```

## 7. Browser DevTools Használata

### Network Tab Szűrők

```
Szűrők beállítása:
1. Kattints a "Fetch/XHR" szűrőre
2. A keresőbe írd be: "solidres" vagy "payment"
3. Csak a releváns kérések maradnak láthatóak
```

### Request Headers Ellenőrzése

**Kritikus headerek:**
```
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

**Ellenőrzés:**
1. Network tab → Válaszd ki a kérést
2. Headers tab → Request Headers
3. Keresd meg a fenti headereket

### Response Ellenőrzése

1. Network tab → Válaszd ki a kérést
2. Response tab → JSON formátumban látható
3. Preview tab → Rendezett nézet

## 8. Automatizált Teszt Script

Másold be ezt a scriptet a Console-ba a gyors ellenőrzéshez:

```javascript
/**
 * Automatizált context teszt script
 */
function runContextTests() {
    console.group('🧪 Context Tests');
    
    // 1. Path ellenőrzés
    console.group('1. Path Check');
    const pathname = window.location.pathname;
    console.log('Pathname:', pathname);
    
    if (pathname === '/index.php') {
        console.log('✓ Root context');
    } else if (pathname.includes('/index.php')) {
        console.log('✓ Submenu/Hub context:', pathname);
    } else {
        console.warn('⚠️ Unexpected pathname format');
    }
    console.groupEnd();
    
    // 2. Paraméterek ellenőrzés
    console.group('2. Parameters Check');
    const urlParams = new URLSearchParams(window.location.search);
    
    const criticalParams = ['Itemid', 'option', 'view', 'reservation_id', 'property_id'];
    criticalParams.forEach(param => {
        const value = urlParams.get(param);
        if (value) {
            console.log(`✓ ${param}:`, value);
        } else {
            console.warn(`⚠️ ${param}: MISSING`);
        }
    });
    
    const optionalParams = ['hub_id', 'site_id'];
    optionalParams.forEach(param => {
        const value = urlParams.get(param);
        console.log(`  ${param}:`, value || 'N/A');
    });
    console.groupEnd();
    
    // 3. URL builder teszt
    console.group('3. URL Builder Test');
    const testUrl = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'test.task',
        'format': 'json'
    });
    console.log('Test URL:', testUrl);
    
    const testUrlObj = new URL(testUrl);
    if (testUrlObj.pathname === pathname) {
        console.log('✓ Path preserved correctly');
    } else {
        console.error('✗ Path NOT preserved!');
        console.error('Expected:', pathname);
        console.error('Got:', testUrlObj.pathname);
    }
    console.groupEnd();
    
    // 4. Összesítés
    console.group('4. Summary');
    console.log('Context:', pathname === '/index.php' ? 'Root' : 'Submenu/Hub');
    console.log('Ready for payment:', urlParams.get('Itemid') && urlParams.get('reservation_id') ? '✓ YES' : '✗ NO');
    console.groupEnd();
    
    console.groupEnd();
}

// Futtasd a teszteket
runContextTests();
```

## 9. Gyakori Hibák és Megoldásuk

### Hiba 1: 404 Not Found Submenu Contextben

**Tünet:**
```
Request URL: https://example.com/index.php?...
Status: 404 Not Found
```

**Ok:**
```javascript
// Hibás kód:
const url = window.location.origin + '/index.php?' + params;
```

**Megoldás:**
```javascript
// Helyes kód:
const url = window.location.origin + window.location.pathname + '?' + params;
```

### Hiba 2: Invalid Menu Item

**Tünet:**
```
Status: 200 OK
Response: { "success": false, "message": "Invalid menu item" }
```

**Ok:**
Hiányzik az `Itemid` paraméter a fetch URL-ből.

**Megoldás:**
```javascript
// Itemid hozzáadása:
const urlParams = new URLSearchParams(window.location.search);
const itemid = urlParams.get('Itemid');
// És használd a buildAjaxUrl()-t ami automatikusan hozzáadja
```

### Hiba 3: Context Lost (hub_id, property_id hiányzik)

**Tünet:**
```
Status: 200 OK
Response: { "success": false, "message": "Property not found" }
```

**Ok:**
A `property_id` vagy `hub_id` nem került átadásra.

**Megoldás:**
Használd a `buildAjaxUrl()` függvényt, ami automatikusan átadja ezeket a paramétereket.

## 10. Sikeres Teszt Checklist

Minden tesztet végezz el mindkét fizetési móddal (Qvik és Revolut):

### Root Context
- [ ] Qvik payment: 200 OK, proper URL
- [ ] Revolut payment: 200 OK, proper URL
- [ ] Path check: `/index.php`
- [ ] Itemid present in URL
- [ ] reservation_id present in URL

### Submenu Context
- [ ] Qvik payment: 200 OK, proper URL
- [ ] Revolut payment: 200 OK, proper URL
- [ ] Path check: `/[submenu]/index.php`
- [ ] Submenu path preserved in fetch URL
- [ ] Itemid present in URL
- [ ] reservation_id present in URL

### Hub Context (if applicable)
- [ ] Qvik payment: 200 OK, proper URL
- [ ] Revolut payment: 200 OK, proper URL
- [ ] Path check: `/[hub]/index.php`
- [ ] Hub path preserved in fetch URL
- [ ] hub_id present in URL
- [ ] Itemid present in URL
- [ ] reservation_id present in URL

### Error Handling
- [ ] HTTP error (404, 500) properly caught
- [ ] API error (success: false) properly handled
- [ ] Network error properly caught
- [ ] Error message displayed to user

### Code Quality
- [ ] buildAjaxUrl() function used consistently
- [ ] window.location.pathname used (not hardcoded)
- [ ] All critical parameters preserved
- [ ] Three-level error handling implemented
- [ ] X-Requested-With header present

**Ha minden ✓ → A Qvik és Revolut payment context-aware és minden környezetben működik!**
