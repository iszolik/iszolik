# Qvik/Revolut AJAX Context Fix - Gyors Referencia

## TL;DR - A Probléma és a Megoldás

**Probléma:** Qvik és Revolut fizetési módok 404 hibát adnak almenü contextben.

**Ok:** Hardcoded `/index.php` használata JavaScript-ben, ami nem őrzi meg a submenu path-t.

**Megoldás:** `window.location.pathname` használata context-aware URL builder függvényben.

## Gyors Áttekintés

### ❌ Régi (Hibás)

```javascript
const url = window.location.origin + '/index.php?option=com_solidres&...';
// Submenu contextben: /index.php → 404 hiba!
```

### ✓ Új (Helyes)

```javascript
const url = buildAjaxUrl({
    option: 'com_solidres',
    task: 'payment.process'
});
// Submenu contextben: /hu/reservations/index.php → működik!
```

## Kritikus Függvény: buildAjaxUrl()

```javascript
function buildAjaxUrl(params) {
    // 1. Path megőrzés
    const basePath = window.location.origin + window.location.pathname;
    
    // 2. Jelenlegi paraméterek
    const urlParams = new URLSearchParams(window.location.search);
    
    // 3. Kritikus paraméterek
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
```

## Használat Példa

### Qvik Payment

```javascript
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
        alert('Hiba: ' + error.message);
    });
}
```

### Revolut Payment

```javascript
function initializeRevolutPayment(reservationId) {
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
        if (data.public_id && window.RevolutCheckout) {
            RevolutCheckout(data.public_id).then(instance => {
                instance.payWithPopup({
                    onSuccess() { /* success handling */ },
                    onError(error) { /* error handling */ },
                    onCancel() { /* cancel handling */ }
                });
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Hiba: ' + error.message);
    });
}
```

## URL Példák Kontextusok Szerint

### Root Context
```
Oldal: https://example.com/index.php?option=com_solidres&Itemid=123
Fetch: https://example.com/index.php?Itemid=123&option=com_solidres&task=...
Státusz: 200 OK ✓
```

### Submenu Context
```
Oldal: https://example.com/hu/reservations/index.php?option=com_solidres&Itemid=123
Fetch: https://example.com/hu/reservations/index.php?Itemid=123&option=com_solidres&task=...
Státusz: 200 OK ✓ (nem 404!)
```

### Hub Context
```
Oldal: https://example.com/hotel1/index.php?option=com_solidres&Itemid=123&hub_id=5
Fetch: https://example.com/hotel1/index.php?Itemid=123&hub_id=5&option=com_solidres&task=...
Státusz: 200 OK ✓
```

## Kritikus Paraméterek

Minden fetch hívásban ezeknek meg kell lenniük:

| Paraméter | Miért fontos? | Honnan? |
|-----------|---------------|---------|
| **Itemid** | Joomla routing, menu item azonosítás | Jelenlegi URL |
| **hub_id** | Multi-hub környezet, hub context | Jelenlegi URL |
| **property_id** | Melyik ingatlanhoz tartozik | Jelenlegi URL |
| **site_id** | Multi-site környezet | Jelenlegi URL |
| **reservation_id** | Melyik foglalás | Paraméter |

## Háromszintű Error Handling

```javascript
fetch(url, options)
    .then(response => {
        // 1. szint: HTTP status
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        // 2. szint: API válasz
        if (!data.success) throw new Error(data.message);
        // Success handling
    })
    .catch(error => {
        // 3. szint: Network/parse errors
        console.error('Error:', error);
    });
```

## Gyors Teszt

Másold be a Console-ba (F12):

```javascript
// Ellenőrizd a context-et
console.log('Path:', window.location.pathname);
console.log('Itemid:', new URLSearchParams(window.location.search).get('Itemid'));

// Teszteld az URL builder-t
const testUrl = buildAjaxUrl({
    option: 'com_solidres',
    task: 'test',
    format: 'json'
});
console.log('Test URL:', testUrl);
```

**Várt eredmény:**
- Path: teljes pathname (pl. `/hu/reservations/index.php`)
- Itemid: létező érték (pl. `123`)
- Test URL: teljes URL a pathname-mel

## Gyakori Hibák

### Hiba 1: 404 Submenu Contextben
**Ok:** Hardcoded `/index.php`
**Megoldás:** Használd `window.location.pathname`

### Hiba 2: Invalid Menu Item
**Ok:** Hiányzó `Itemid`
**Megoldás:** Használd `buildAjaxUrl()`-t

### Hiba 3: Context Lost
**Ok:** Hiányzó `hub_id`, `property_id`
**Megoldás:** Használd `buildAjaxUrl()`-t

## Ellenőrző Lista

- [ ] `buildAjaxUrl()` implementálva
- [ ] `window.location.pathname` használva
- [ ] Itemid átadva
- [ ] hub_id átadva (ha van)
- [ ] property_id átadva
- [ ] site_id átadva (ha van)
- [ ] Háromszintű error handling
- [ ] `X-Requested-With: XMLHttpRequest` header
- [ ] Tesztelve root contextben
- [ ] Tesztelve submenu contextben

## További Dokumentáció

Részletes információkért lásd:

1. **QVIK_REVOLUT_AJAX_CONTEXT_INVESTIGATION.md** - Teljes vizsgálat
2. **qvik-payment-example.js** - Qvik teljes implementáció
3. **revolut-payment-example.js** - Revolut teljes implementáció
4. **BUILTIN_VS_QVIK_REVOLUT_COMPARISON.md** - Beépített vs custom összehasonlítás
5. **TESTING_GUIDE.md** - Részletes tesztelési útmutató
6. **CONCRETE_EXAMPLES.md** - Konkrét példák minden kontextusra

## Gyors Implementáció 5 Lépésben

1. **Másold át a `buildAjaxUrl()` függvényt** a confirmation form template-be
2. **Cseréld le a hardcoded URL-eket** `buildAjaxUrl()` hívásokra
3. **Add hozzá a háromszintű error handling-et** minden fetch híváshoz
4. **Teszteld root contextben** - kell hogy működjön
5. **Teszteld submenu contextben** - kell hogy működjön (nincs 404!)

## Összefoglalás

```javascript
// EZ A MEGOLDÁS:
const url = window.location.origin + window.location.pathname + '?' + params;

// NEM EZ:
const url = window.location.origin + '/index.php?' + params;
```

**A `window.location.pathname` használata megoldja a 404 hibát minden contextben!**

---

*Ez a dokumentum egy gyors referencia. Részletekért lásd a többi dokumentációs fájlt.*
