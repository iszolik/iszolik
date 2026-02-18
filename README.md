# Qvik és Revolut Fizetési Módok - Context-Aware AJAX Megoldás

## Áttekintés

Ez a repository tartalmazza a Qvik és Revolut fizetési módok JavaScript/AJAX context-aware implementációjának vizsgálatát és megoldását, amely kijavítja a **404 hibákat almenü (submenu) contextben**.

## A Probléma

A `payment_method_id` minden contextben helyesen jelenik meg a confirmationform-ban (session, context átadás, megjelenítés működik), **VISZONT**: csak a Qvik és Revolut fizetési mód JS/AJAX fetch hívása produkál **404 hibát almenü contextben**.

### Példa a Problémára

**Root contextben (működik):**
```
URL: https://example.com/index.php?option=com_solidres&...
Fetch: https://example.com/index.php?option=com_solidres&task=...
Státusz: 200 OK ✓
```

**Submenu contextben (404 hiba):**
```
URL: https://example.com/hu/reservations/index.php?option=com_solidres&...
Fetch: https://example.com/index.php?option=com_solidres&task=...  ← HIBA: hiányzik /hu/reservations/
Státusz: 404 Not Found ✗
```

## A Megoldás

A megoldás egy **context-aware URL builder függvény** használata, amely:

1. Megőrzi a teljes pathname-t (beleértve submenu path-okat is)
2. Átadja a kritikus Joomla/Solidres paramétereket (Itemid, hub_id, property_id, site_id)
3. Biztosítja, hogy minden contextben (root, submenu, hub) működjön az AJAX hívás

### Kulcs Függvény: buildAjaxUrl()

```javascript
function buildAjaxUrl(params) {
    const basePath = window.location.origin + window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        'property_id': urlParams.get('property_id'),
        'site_id': urlParams.get('site_id')
    };
    
    const allParams = { ...criticalParams, ...params };
    
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    return basePath + '?' + newParams.toString();
}
```

## Dokumentáció Struktúra

### 📄 Fő Dokumentumok

1. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Gyors referencia, kezdd ezzel!
   - TL;DR összefoglaló
   - Kritikus függvény és használata
   - URL példák kontextusok szerint
   - Gyors teszt és implementáció

2. **[QVIK_REVOLUT_AJAX_CONTEXT_INVESTIGATION.md](QVIK_REVOLUT_AJAX_CONTEXT_INVESTIGATION.md)** - Teljes vizsgálat
   - Részletes probléma leírás
   - Endpoint-ok és paraméterek magyarázata
   - Gyökér vs almenü context különbségek
   - Beépített vs Qvik/Revolut különbségek
   - Context-helyes fetch URL/payload minták
   - Tesztelési forgatókönyv
   - Gyakori hibák és megoldások

3. **[CONCRETE_EXAMPLES.md](CONCRETE_EXAMPLES.md)** - Konkrét példák
   - Előtte/utána kód összehasonlítás
   - Teljes működő Qvik implementáció
   - Teljes működő Revolut implementáció
   - Request/Response példák
   - HTML implementáció példa
   - Összehasonlító táblázat

4. **[BUILTIN_VS_QVIK_REVOLUT_COMPARISON.md](BUILTIN_VS_QVIK_REVOLUT_COMPARISON.md)** - Összehasonlítás
   - Beépített (banki) vs Qvik/Revolut működés
   - URL építés különbségek
   - Paraméter átadás összehasonlítás
   - Error handling különbségek
   - Routing magyarázat
   - 404 hiba anatómiája

5. **[TESTING_GUIDE.md](TESTING_GUIDE.md)** - Tesztelési útmutató
   - Lépésről-lépésre teszt forgatókönyvek
   - Root, submenu, és hub context tesztek
   - Browser DevTools használat
   - Automatizált teszt scriptek
   - Paraméterek ellenőrzése
   - Gyakori hibák diagnosztizálása

### 💻 Kód Példák

6. **[qvik-payment-example.js](qvik-payment-example.js)** - Qvik teljes implementáció
   - Context-aware URL builder
   - Payment initialization
   - Status checking
   - Error handling
   - Event listeners
   - Debug utilities

7. **[revolut-payment-example.js](revolut-payment-example.js)** - Revolut teljes implementáció
   - Context-aware URL builder
   - Revolut SDK integration
   - Payment initialization
   - Widget handling
   - Helyes vs hibás összehasonlítás
   - Debug utilities

## Gyors Start

### 1. Olvasd el a gyors referenciát
```bash
cat QUICK_REFERENCE.md
```

### 2. Nézd meg a konkrét példákat
```bash
cat CONCRETE_EXAMPLES.md
```

### 3. Implementáld a megoldást

Másold át a `buildAjaxUrl()` függvényt és használd minden fetch híváshoz:

```javascript
// Régi (hibás):
const url = window.location.origin + '/index.php?option=com_solidres&...';

// Új (helyes):
const url = buildAjaxUrl({
    option: 'com_solidres',
    task: 'payment.process',
    reservation_id: reservationId,
    format: 'json'
});
```

### 4. Teszteld

```bash
# Olvasd el a tesztelési útmutatót
cat TESTING_GUIDE.md

# Teszteld root contextben
# Teszteld submenu contextben
# Ellenőrizd, hogy nincs 404 hiba
```

## Kulcs Megállapítások

### Mi okozza a 404 hibát?

1. **Hardcoded `/index.php` path** - nem őrzi meg a submenu path-t
2. **Hiányzó Itemid paraméter** - Joomla routing nem találja a menu item-et
3. **Hiányzó context paraméterek** - hub_id, property_id elvész

### Mi a megoldás?

1. **`window.location.pathname` használata** - megőrzi a teljes path-t
2. **Context-aware URL builder** - automatikusan átadja a kritikus paramétereket
3. **Háromszintű error handling** - HTTP status, API response, catch block

## Implementációs Ellenőrző Lista

- [ ] `buildAjaxUrl()` függvény implementálva
- [ ] Minden fetch hívás `buildAjaxUrl()`-t használ
- [ ] `window.location.pathname` használva (nem hardcoded path)
- [ ] Itemid paraméter mindig átadva
- [ ] hub_id, property_id, site_id paraméterek átadva (ha vannak)
- [ ] Háromszintű error handling minden fetch-nél
- [ ] `X-Requested-With: XMLHttpRequest` header minden kérésben
- [ ] Tesztelve root contextben
- [ ] Tesztelve submenu contextben
- [ ] Tesztelve hub contextben (ha alkalmazható)
- [ ] Nincs több 404 hiba!

## Kritikus Paraméterek

| Paraméter | Célja | Forrása |
|-----------|-------|---------|
| `Itemid` | Joomla menu routing | Jelenlegi URL |
| `hub_id` | Multi-hub context | Jelenlegi URL |
| `property_id` | Ingatlan context | Jelenlegi URL |
| `site_id` | Multi-site context | Jelenlegi URL |
| `reservation_id` | Foglalás azonosítás | Függvény paraméter |
| `option` | Joomla component | Konstans: `com_solidres` |
| `task` | Controller action | Függvény szerint |
| `plugin` | Payment plugin | `qvik` vagy `revolut` |
| `format` | Response formátum | Konstans: `json` |

## URL Példák Minden Kontextusban

### Root Context
```
https://example.com/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
```

### Submenu Context
```
https://example.com/hu/reservations/index.php?Itemid=123&property_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
```

### Hub Context
```
https://example.com/hotel1/index.php?Itemid=123&hub_id=5&property_id=10&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json
```

**Figyeld meg:** Minden URL tartalmazza a teljes path-t és az összes kritikus paramétert!

## Háromszintű Error Handling

```javascript
fetch(url, options)
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
            throw new Error(data.message || 'Operation failed');
        }
        // Success handling
        handleSuccess(data);
    })
    .catch(error => {
        // 3. szint: Network/parse errors
        console.error('Error:', error);
        handleError(error.message);
    });
```

## Tesztelés

### Gyors Ellenőrzés Console-ban

```javascript
// Másold be a Console-ba (F12)
console.log('Path:', window.location.pathname);
console.log('Itemid:', new URLSearchParams(window.location.search).get('Itemid'));

const testUrl = buildAjaxUrl({
    option: 'com_solidres',
    task: 'test',
    format: 'json'
});
console.log('Test URL:', testUrl);
```

### Automata Teszt

Lásd: [TESTING_GUIDE.md](TESTING_GUIDE.md) - tartalmaz egy teljes automatizált teszt scriptet.

## Előnyök

✓ **Univerzális megoldás** - minden Solidres AJAX hívásra alkalmazható
✓ **Context-aware** - működik root, submenu, hub contextben
✓ **Paraméter biztonságos** - minden kritikus paraméter automatikusan átadódik
✓ **Error handling** - háromszintű hibakezelés
✓ **Konzisztens** - ugyanaz a pattern Qvik és Revolut esetén
✓ **Jövőbiztos** - új payment module-ok is használhatják

## Gyakran Ismételt Kérdések

**Q: Miért nem működik a beépített fizetési módoknál?**
A: A beépített módok Joomla `JRoute::_()` függvényt használnak PHP-ban, ami automatikusan kezeli a routing-ot. A Qvik/Revolut JavaScript fetch-et használ, ami manuális URL kezelést igényel.

**Q: Kell-e módosítani a PHP backend kódot?**
A: Nem! Ez csak JavaScript/AJAX hívások javítása. A backend változatlan marad.

**Q: Működik multi-hub környezetben?**
A: Igen! A `buildAjaxUrl()` automatikusan átadja a `hub_id` paramétert.

**Q: Mi van multi-site esetén?**
A: Ugyanúgy működik, a `site_id` is automatikusan átadódik.

**Q: Kell-e változtatni a Revolut SDK integrációt?**
A: Nem, csak az inicializáló fetch hívást kell context-aware-é tenni.

## Támogatás és További Kérdések

Ha bármilyen kérdés merül fel:

1. Olvasd el a [QUICK_REFERENCE.md](QUICK_REFERENCE.md) gyors referenciát
2. Nézd meg a [CONCRETE_EXAMPLES.md](CONCRETE_EXAMPLES.md) konkrét példákat
3. Kövesd a [TESTING_GUIDE.md](TESTING_GUIDE.md) tesztelési útmutatót
4. Hasonlítsd össze a [BUILTIN_VS_QVIK_REVOLUT_COMPARISON.md](BUILTIN_VS_QVIK_REVOLUT_COMPARISON.md) dokumentummal

## Összefoglalás

A Qvik és Revolut fizetési módok 404 hibája almenü contextben egyszerűen javítható:

**Használj `window.location.pathname`-t hardcoded `/index.php` helyett!**

A `buildAjaxUrl()` függvény biztosítja, hogy minden kritikus paraméter átadódik és minden contextben működjön az AJAX hívás.

---

**Verzió:** 1.0  
**Utolsó frissítés:** 2026-02-18  
**Szerző:** iszolik  
**Státusz:** Production Ready ✓
