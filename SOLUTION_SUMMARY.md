# MEGOLDÁS - Qvik és Revolut 404 Hiba Almenü Contextben

## A Probléma Lényege

**Tünet:** Qvik és Revolut fizetési módok JS/AJAX fetch hívása 404 hibát ad almenü contextben.

**Ok:** Hardcoded `/index.php` használata, ami nem őrzi meg a submenu path-t (pl. `/hu/reservations/index.php`).

## A Megoldás (Egy Függvény)

### buildAjaxUrl() - Context-Aware URL Builder

```javascript
/**
 * Ez a függvény megoldja a 404 problémát MINDEN contextben!
 * Használat: const url = buildAjaxUrl({ option: '...', task: '...' });
 */
function buildAjaxUrl(params) {
    // 1. Megőrzi a teljes pathname-t (beleértve submenu-t is)
    const basePath = window.location.origin + window.location.pathname;
    
    // 2. Kinyeri a jelenlegi URL paramétereit
    const urlParams = new URLSearchParams(window.location.search);
    
    // 3. Megőrzi a kritikus context paramétereket
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),           // Joomla menu routing
        'hub_id': urlParams.get('hub_id'),           // Hub context
        'property_id': urlParams.get('property_id'), // Property context
        'site_id': urlParams.get('site_id')          // Site context
    };
    
    // 4. Összevonja a kritikus és új paramétereket
    const allParams = { ...criticalParams, ...params };
    
    // 5. Épít egy clean URL-t csak a nem-null értékekkel
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    // 6. Visszaadja a teljes, context-aware URL-t
    return basePath + '?' + newParams.toString();
}
```

## Használat - Qvik Payment

```javascript
function initializeQvikPayment(reservationId) {
    // Használd a buildAjaxUrl()-t minden fetch híváshoz
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeQvikPayment',
        'plugin': 'qvik',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    // Standard fetch három szintű error handling-gel
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ reservation_id: reservationId })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (!data.success) throw new Error(data.message);
        window.location.href = data.payment_url;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fizetési hiba: ' + error.message);
    });
}
```

## Használat - Revolut Payment

```javascript
function initializeRevolutPayment(reservationId) {
    // Ugyanaz a buildAjaxUrl(), csak más task és plugin
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeRevolutPayment',
        'plugin': 'revolut',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ reservation_id: reservationId })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (!data.success) throw new Error(data.message);
        
        // Revolut SDK widget
        if (data.public_id && window.RevolutCheckout) {
            RevolutCheckout(data.public_id).then(instance => {
                instance.payWithPopup({
                    onSuccess() { /* success */ },
                    onError(error) { /* error */ },
                    onCancel() { /* cancel */ }
                });
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fizetési hiba: ' + error.message);
    });
}
```

## Miért Működik Ez?

### Előtte (❌ HIBÁS)

```javascript
// Hardcoded path - NEM őrzi meg a submenu-t!
const url = window.location.origin + '/index.php?option=com_solidres&...';

// ROOT contextben:    https://example.com/index.php?... ✓
// SUBMENU contextben: https://example.com/index.php?... ✗ (kellene: /hu/reservations/index.php)
```

### Utána (✓ HELYES)

```javascript
// window.location.pathname - MEGŐRZI a submenu-t!
const basePath = window.location.origin + window.location.pathname;

// ROOT contextben:    https://example.com/index.php ✓
// SUBMENU contextben: https://example.com/hu/reservations/index.php ✓
// HUB contextben:     https://example.com/hotel1/index.php ✓
```

## URL Példák

### Root Context
```
Oldal:  https://example.com/index.php?option=com_solidres&Itemid=123
Fetch:  https://example.com/index.php?Itemid=123&option=com_solidres&task=...
Result: 200 OK ✓
```

### Submenu Context (A FIX!)
```
Oldal:  https://example.com/hu/reservations/index.php?option=com_solidres&Itemid=123
Fetch:  https://example.com/hu/reservations/index.php?Itemid=123&option=com_solidres&task=...
Result: 200 OK ✓ (NINCS TÖBBÉ 404!)
```

### Hub Context
```
Oldal:  https://example.com/hotel1/index.php?option=com_solidres&Itemid=123&hub_id=5
Fetch:  https://example.com/hotel1/index.php?Itemid=123&hub_id=5&option=com_solidres&task=...
Result: 200 OK ✓
```

## Implementációs Lépések

1. **Másold be a `buildAjaxUrl()` függvényt** a confirmationform template-be
2. **Cseréld le minden hardcoded URL-t** `buildAjaxUrl()` hívásra
3. **Használd háromszintű error handling-et** (HTTP status → API response → catch)
4. **Teszteld root contextben** → működnie kell
5. **Teszteld submenu contextben** → működnie kell (nincs 404!)

## Ellenőrző Lista

- [ ] `buildAjaxUrl()` függvény bemásolva
- [ ] Qvik fetch hívás `buildAjaxUrl()`-t használ
- [ ] Revolut fetch hívás `buildAjaxUrl()`-t használ
- [ ] Háromszintű error handling implementálva
- [ ] `X-Requested-With: XMLHttpRequest` header hozzáadva
- [ ] Tesztelve root contextben → 200 OK
- [ ] Tesztelve submenu contextben → 200 OK (nincs 404!)
- [ ] Tesztelve hub contextben (ha van) → 200 OK

## Gyors Teszt

Console-ban (F12):

```javascript
// Ellenőrizd a pathname-t
console.log('Path:', window.location.pathname);
// Root: /index.php
// Submenu: /hu/reservations/index.php ← Ez kell hogy megjelenjen!

// Teszteld a buildAjaxUrl()-t
const testUrl = buildAjaxUrl({
    option: 'com_solidres',
    task: 'test',
    format: 'json'
});
console.log('Test URL:', testUrl);
// Ellenőrizd, hogy tartalmazza a pathname-t és a paramétereket!
```

## Kritikus Paraméterek

| Paraméter | Miért fontos? |
|-----------|---------------|
| **Itemid** | Joomla menu routing - nélküle Invalid Menu Item hiba |
| **hub_id** | Multi-hub környezetben hub azonosítás |
| **property_id** | Ingatlan azonosítás |
| **site_id** | Multi-site környezetben site azonosítás |
| **reservation_id** | Foglalás azonosítás |

Mind automatikusan átadódik a `buildAjaxUrl()`-ben!

## Háromszintű Error Handling

```javascript
fetch(url, options)
    .then(response => {
        // 1. szint: HTTP status (404, 500, stb.)
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        // 2. szint: API response (success flag)
        if (!data.success) throw new Error(data.message);
        // Success handling
    })
    .catch(error => {
        // 3. szint: Network/parse errors
        console.error('Error:', error);
        // User-friendly error message
    });
```

## Összefoglalás - A Megoldás Egy Mondatban

**Használj `window.location.pathname`-t hardcoded `/index.php` helyett, és add át a kritikus paramétereket (Itemid, hub_id, property_id, site_id) minden AJAX hívásban a `buildAjaxUrl()` függvénnyel!**

## További Dokumentáció

- **[README.md](README.md)** - Teljes áttekintés
- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Gyors referencia
- **[CONCRETE_EXAMPLES.md](CONCRETE_EXAMPLES.md)** - Konkrét példák
- **[qvik-payment-example.js](qvik-payment-example.js)** - Teljes Qvik kód
- **[revolut-payment-example.js](revolut-payment-example.js)** - Teljes Revolut kód
- **[TESTING_GUIDE.md](TESTING_GUIDE.md)** - Tesztelési útmutató

---

**Ez a dokumentum a legfontosabb információkat tartalmazza tömören!**
