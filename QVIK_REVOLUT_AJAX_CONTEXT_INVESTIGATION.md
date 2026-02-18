# Qvik és Revolut Fizetési Módok AJAX Context Vizsgálat

## Probléma összefoglalás

A `payment_method_id` minden contextben helyesen jelenik meg a confirmationform-ban (session, context átadás, megjelenítés működik).

**Viszont**: csak a Qvik és Revolut fizetési mód JS/AJAX fetch hívása produkál **404 hibát almenü contextben**.

## 1. Milyen endpoint-ot hívnak a Qvik és Revolut JS fetch hívások?

### Jelenlegi (hibás) megközelítés példa:

```javascript
// HIBÁS - nem őrzi meg a submenu path-t
const url = window.location.origin + '/index.php?' + params;
// Eredmény root contextben: https://example.com/index.php?option=com_solidres&task=...
// Eredmény submenu contextben: https://example.com/index.php?option=com_solidres&task=...
// PROBLÉMA: submenu context elvész! (/hu/reservations/index.php helyett /index.php)
```

### Helyes megközelítés:

```javascript
// HELYES - megőrzi a teljes path-t
const url = window.location.origin + window.location.pathname + '?' + params;
// Eredmény root contextben: https://example.com/index.php?option=com_solidres&task=...
// Eredmény submenu contextben: https://example.com/hu/reservations/index.php?option=com_solidres&task=...
// OK: submenu path megmarad!
```

## 2. Milyen paraméterek mennek a fetch URL-ben?

### Kritikus context paraméterek (MINDEN fetch-ben kötelező):

```javascript
// Ezeket MINDIG át kell adni:
const criticalParams = {
    'option': 'com_solidres',
    'task': 'reservationasset.processPayment',  // vagy más task
    'plugin': 'qvik',  // vagy 'revolut'
    'Itemid': getCurrentItemid(),               // ← KRITIKUS!
    'hub_id': getCurrentHubId(),                // ← KRITIKUS!
    'property_id': getCurrentPropertyId(),      // ← KRITIKUS!
    'site_id': getCurrentSiteId(),              // ← KRITIKUS!
    'reservation_id': getReservationId(),       // ← KRITIKUS!
    'format': 'json'
};
```

### Miért kritikusak ezek a paraméterek?

- **Itemid**: Joomla menu item azonosító - nélküle nem tudja a routing, hogy melyik menüponthoz tartozik a kérés
- **hub_id**: Solidres hub context - multi-hub esetén szükséges
- **property_id**: Melyik ingatlanhoz tartozik a foglalás
- **site_id**: Multi-site esetén melyik site-hoz tartozik
- **reservation_id**: Melyik foglaláshoz tartozik a fizetés

## 3. Mi különbözik gyökér és almenü context között?

### Root context (működik):
```
URL path: /index.php
Teljes URL: https://example.com/index.php?option=com_solidres&...
```

### Submenu context (404 hiba a régi kódban):
```
URL path: /hu/reservations/index.php
Teljes URL: https://example.com/hu/reservations/index.php?option=com_solidres&...
```

### A probléma oka:

Ha csak `window.location.origin + '/index.php'` használjuk, akkor:
- Root contextben: `/index.php` ✓ OK
- Submenu contextben: `/index.php` ✗ HIBA (kellene: `/hu/reservations/index.php`)

## 4. Mi különbözik a beépített fizetési módok fetch-étől?

### Beépített (pl. banki) fizetési módok:

```javascript
// Gyakran használnak egyszerűbb form submit-ot:
form.action = 'index.php?option=com_solidres&task=...';
form.submit();

// Vagy ha AJAX-t használnak, akkor Joomla core URL builder-t:
const url = Joomla.getOptions('system.paths').base + '/index.php?...';
```

### Qvik és Revolut (custom AJAX):

```javascript
// Fetch API-t használnak, ami részletesebb URL kezelést igényel:
fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})
```

**A különbség**: A beépített módok gyakran támaszkodnak a Joomla belső routing-jára, míg a Qvik/Revolut explicit URL-t épít JavaScript-ben.

## 5. Context-helyes fetch URL/payload minták

### buildAjaxUrl() függvény (context-aware):

```javascript
/**
 * Épít egy context-aware AJAX URL-t, ami minden contextben működik
 * @param {Object} params - URL paraméterek
 * @returns {string} Teljes URL
 */
function buildAjaxUrl(params) {
    // 1. Megőrizzük a teljes path-t (beleértve submenu-t is)
    const basePath = window.location.origin + window.location.pathname;
    
    // 2. Kinyerjük a jelenlegi URL paramétereket
    const urlParams = new URLSearchParams(window.location.search);
    
    // 3. Kritikus paraméterek a jelenlegi URL-ből
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        'property_id': urlParams.get('property_id'),
        'site_id': urlParams.get('site_id')
    };
    
    // 4. Összevonjuk az új paraméterekkel
    const allParams = { ...criticalParams, ...params };
    
    // 5. Építünk egy új URLSearchParams-t
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    // 6. Visszaadjuk a teljes URL-t
    return basePath + '?' + newParams.toString();
}
```

### Konkrét példa - Qvik payment initialization:

```javascript
// HELYES implementáció
function initializeQvikPayment(reservationId) {
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeQvikPayment',
        'plugin': 'qvik',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
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
        // 1. HTTP status ellenőrzés
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // 2. API válasz validálás
        if (!data.success) {
            throw new Error(data.message || 'Payment initialization failed');
        }
        // 3. Sikeres feldolgozás
        handlePaymentSuccess(data);
    })
    .catch(error => {
        // 3. Hálózati/parse hibák
        console.error('Payment error:', error);
        showErrorMessage(error.message);
    });
}
```

### Root context URL példa:

```
https://example.com/index.php?option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&Itemid=123&hub_id=1&property_id=5&site_id=1&reservation_id=789&format=json
```

### Submenu context URL példa:

```
https://example.com/hu/reservations/index.php?option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&Itemid=123&hub_id=1&property_id=5&site_id=1&reservation_id=789&format=json
```

## 6. Ellenőrző lista (Checklist)

Minden Qvik/Revolut AJAX hívásnak meg kell felelnie:

- [ ] Használja a `window.location.pathname`-t (nem csak `/index.php`)
- [ ] Használja a `window.location.origin`-t
- [ ] Átadja az `Itemid` paramétert
- [ ] Átadja a `hub_id` paramétert (ha van)
- [ ] Átadja a `property_id` paramétert
- [ ] Átadja a `site_id` paramétert (ha van)
- [ ] Átadja a `reservation_id` paramétert
- [ ] Használ háromszintű error handling-et (HTTP status, API response, catch)
- [ ] Tartalmazza az `X-Requested-With: XMLHttpRequest` header-t

## 7. Tesztelési forgatókönyv

### Root contextben:
1. Navigálj a confirmation form-ra: `https://example.com/index.php?option=com_solidres&view=reservationasset&layout=confirmationform&...`
2. Válaszd ki a Qvik vagy Revolut fizetési módot
3. Nyomd meg a "Fizetés" gombot
4. Ellenőrizd a Network tab-ban a fetch hívás URL-jét
5. Státusz: 200 OK ✓

### Submenu contextben:
1. Navigálj a confirmation form-ra: `https://example.com/hu/reservations/index.php?option=com_solidres&view=reservationasset&layout=confirmationform&...`
2. Válaszd ki a Qvik vagy Revolut fizetési módot
3. Nyomd meg a "Fizetés" gombot
4. Ellenőrizd a Network tab-ban a fetch hívás URL-jét
5. Státusz: 200 OK ✓ (nem 404!)

## 8. Gyakori hibák

### ❌ HIBA 1: Hardcoded /index.php
```javascript
const url = window.location.origin + '/index.php?' + params;
// Submenu contextben 404!
```

### ❌ HIBA 2: Hiányzó Itemid
```javascript
const url = buildUrl({
    option: 'com_solidres',
    task: 'payment.process'
    // Itemid hiányzik!
});
// Joomla routing hiba!
```

### ❌ HIBA 3: Relatív URL
```javascript
const url = 'index.php?' + params;
// Bizonytalan, hogy melyik index.php-t hívja!
```

### ✓ HELYES: Teljes context-aware URL
```javascript
const url = buildAjaxUrl({
    option: 'com_solidres',
    task: 'payment.process',
    // Itemid automatikusan hozzáadódik a buildAjaxUrl-ben
});
```

## 9. Összefoglalás

A Qvik és Revolut fizetési módok 404 hibájának oka az volt, hogy a JavaScript fetch hívások:

1. **Nem őrizték meg a submenu path-t** - csak `/index.php`-t használtak `window.location.pathname` helyett
2. **Nem adták át a kritikus context paramétereket** - különösen az `Itemid`-t
3. **Nem használtak context-aware URL builder függvényt** - hardcoded path-ok voltak

A megoldás:
- `buildAjaxUrl()` függvény használata minden fetch híváshoz
- `window.location.pathname` használata a teljes path megőrzéséhez
- Kritikus paraméterek automatikus átadása a jelenlegi URL-ből
- Háromszintű error handling

Ezzel a megoldással a Qvik és Revolut fizetési módok **minden contextben működnek**, legyen az root vagy bármilyen mélységű submenu!
