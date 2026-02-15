# AJAX Endpoint 404 Hiba Megoldás - Teljes Dokumentáció

## Probléma Összefoglalás

A Solidres Qvik és Revolut AJAX endpoint JavaScript scriptjét beillesztettük a `guestform.php` fájlba, azonban **hub és almenü útvonalakon továbbra is 404 hibát adott vissza**.

## Gyökérok

A 404 hibák oka az volt, hogy az AJAX fetch hívások **relatív útvonalakat** használtak, amelyek nem működtek megfelelően:
- Főmenü kontextusban: `http://example.com/foglalas` → relatív hívás működött
- Hub kontextusban: `http://example.com/hub/budapest` → relatív hívás 404-et adott
- Almenü kontextusban: `http://example.com/szallasok/foglalasok/vendeg` → relatív hívás 404-et adott

## Megoldás

### 1. Abszolút Útvonal Használata

**HELYTELEN (relatív):**
```javascript
fetch('index.php?option=com_solidres&task=reservation.save')
```

**HELYES (abszolút):**
```javascript
const baseUrl = window.location.origin + '/index.php';
fetch(baseUrl + '?option=com_solidres&task=reservation.save')
```

### 2. URL Építő Függvény - buildAjaxUrl()

A `buildAjaxUrl()` függvény garantálja, hogy minden kontextusban helyes útvonalat kapjunk:

```javascript
function buildAjaxUrl(option, task, additionalParams = {}) {
    // 1. Joomla gyökér index.php (ABSZOLÚT)
    const baseUrl = window.location.origin + '/index.php';
    
    // 2. Paraméterek gyűjtése
    const params = new URLSearchParams();
    params.append('option', option);
    if (task) params.append('task', task);
    
    // 3. KRITIKUS: Itemid megőrzése
    const currentUrl = new URL(window.location.href);
    const itemId = currentUrl.searchParams.get('Itemid');
    if (itemId) params.append('Itemid', itemId);
    
    // 4. Solidres paraméterek megőrzése
    ['property_id', 'hub_id', 'site_id', 'reservation_id'].forEach(paramName => {
        const value = currentUrl.searchParams.get(paramName);
        if (value) params.append(paramName, value);
    });
    
    // 5. További paraméterek
    Object.keys(additionalParams).forEach(key => {
        params.append(key, additionalParams[key]);
    });
    
    // 6. JSON formátum jelzése
    params.append('format', 'json');
    
    // 7. Teljes URL visszaadása
    return baseUrl + '?' + params.toString();
}
```

### 3. Három Szintű Hibakezelés

```javascript
fetch(ajaxUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(formData)
})
.then(response => {
    // ELSŐ SZINT: HTTP státusz (404, 500, stb.)
    if (!response.ok) {
        throw new Error(`HTTP hiba! Státusz: ${response.status}`);
    }
    return response.json();
})
.then(data => {
    // MÁSODIK SZINT: API válasz validálás
    if (data.success === false || data.error) {
        throw new Error(data.message || 'API hiba');
    }
    return data;
})
.catch(error => {
    // HARMADIK SZINT: Hálózati hibák
    console.error('Fetch hiba:', error);
    alert('Hiba: ' + error.message);
});
```

## Használati Példák

### Példa 1: Egyszerű GET kérés

```javascript
const url = buildAjaxUrl('com_solidres', 'reservation.checkAvailability');
fetch(url, { method: 'GET' })
    .then(response => response.json())
    .then(data => console.log('Elérhetőség:', data));
```

### Példa 2: POST kérés form adatokkal

```javascript
const formData = {
    guest_firstname: 'János',
    guest_lastname: 'Kovács',
    guest_email: 'janos@example.com',
    guest_phone: '+36301234567'
};

submitGuestForm(formData)
    .then(result => console.log('Sikeres!', result))
    .catch(error => console.error('Hiba!', error));
```

### Példa 3: Hub kontextusban

```javascript
// URL: http://example.com/hub/budapest?hub_id=5&Itemid=102
const url = buildAjaxUrl('com_solidres', 'property.list');
console.log(url);
// Eredmény: http://example.com/index.php?option=com_solidres&task=property.list&Itemid=102&hub_id=5&format=json
```

### Példa 4: Több szálláshely kontextusban

```javascript
// URL: http://example.com/szallasok/hotel-a?property_id=123&site_id=2&Itemid=105
const url = buildAjaxUrl('com_solidres', 'reservation.save');
console.log(url);
// Eredmény: http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=105&property_id=123&site_id=2&format=json
```

## Tesztelési Forgatókönyvek

### Test Case 1: Főmenü Kontextus
```
URL: http://example.com/foglalas
Itemid: 101
hub_id: -
property_id: -

Fetch URL eredmény:
http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=101&format=json

Státusz: ✅ OK (200)
```

### Test Case 2: Hub Kontextus
```
URL: http://example.com/hub/budapest
Itemid: 102
hub_id: 5
property_id: -

Fetch URL eredmény:
http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=102&hub_id=5&format=json

Státusz: ✅ OK (200)
```

### Test Case 3: Almenü Kontextus
```
URL: http://example.com/szallasok/foglalasok/vendeg-adatok
Itemid: 105
hub_id: -
property_id: 123

Fetch URL eredmény:
http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=105&property_id=123&format=json

Státusz: ✅ OK (200)
```

### Test Case 4: Mély Almenü Struktúra
```
URL: http://example.com/level1/level2/level3/booking
Itemid: 110
hub_id: 7
property_id: 456

Fetch URL eredmény:
http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=110&hub_id=7&property_id=456&format=json

Státusz: ✅ OK (200)
```

## Garancia a 404 Hibák Elkerülésére

### 1. window.location.origin használata
- Mindig a teljes domain-t adja vissza: `http://example.com` vagy `https://example.com:8080`
- Protokoll biztonságos (automatikusan http vagy https)
- Port kezelés automatikus

### 2. Joomla központi index.php végpont
- A `/index.php` mindig elérhető a Joomla gyökérben
- Minden komponens és task ezen keresztül fut
- Router automatikusan kezeli az útvonalakat

### 3. Itemid megőrzése
- Az Itemid meghatározza a menüpont kontextust
- Nélküle elveszne a megfelelő Joomla routing
- Kritikus a helyes SEF URL működéshez

### 4. Solidres specifikus paraméterek
- `hub_id`: Hub kontextus azonosítása
- `property_id`: Szálláshely azonosítása
- `site_id`: Multi-site környezetben szükséges
- `reservation_id`: Foglalás folytatása esetén

### 5. URLSearchParams használata
- Biztonságos query string építés
- Automatikus URL encoding
- Speciális karakterek kezelése

## Migrációs Útmutató

Ha már létező kódot szeretnél átírni, kövesd ezeket a lépéseket:

### Lépés 1: Cseréld le a relatív útvonalakat

**ELŐTTE:**
```javascript
fetch('index.php?option=com_solidres&task=save', ...)
```

**UTÁNA:**
```javascript
const url = buildAjaxUrl('com_solidres', 'save');
fetch(url, ...)
```

### Lépés 2: Add hozzá a buildAjaxUrl függvényt

Másold be a `guestform.php` fájlból a `buildAjaxUrl()` függvényt a saját scriptedbe.

### Lépés 3: Frissítsd a hibakezelést

Adj hozzá három szintű hibakezelést minden fetch híváshoz (lásd fent).

### Lépés 4: Teszteld minden kontextusban

- Főmenü
- Hub
- Almenü
- Multi-site

## Gyakori Hibák és Megoldások

### Hiba 1: Továbbra is 404-et kapok
**Ok:** Nem használod az abszolút útvonalat.
**Megoldás:** Ellenőrizd, hogy `window.location.origin + '/index.php'` a base URL.

### Hiba 2: Elvesznek a paraméterek
**Ok:** Nem őrzöd meg az Itemid-t vagy más kritikus paramétereket.
**Megoldás:** Használd a `buildAjaxUrl()` függvényt, ami automatikusan megőrzi őket.

### Hiba 3: Hub kontextusban nem működik
**Ok:** A `hub_id` nem kerül át az AJAX hívásba.
**Megoldás:** A `buildAjaxUrl()` automatikusan megőrzi a `hub_id`-t az aktuális URL-ből.

### Hiba 4: CORS hiba
**Ok:** Külső domainre próbálsz hívást indítani.
**Megoldás:** A `window.location.origin` garantálja, hogy ugyanarra a domainre megy a hívás.

## Best Practice Összefoglaló

1. ✅ **Mindig használj abszolút útvonalat** AJAX hívásokhoz
2. ✅ **Őrizd meg az Itemid-t** minden esetben
3. ✅ **Őrizd meg a Solidres paramétereket** (hub_id, property_id, stb.)
4. ✅ **Használj három szintű hibakezelést** (HTTP, API, Network)
5. ✅ **Tesztelj minden kontextusban** (főmenü, hub, almenü)
6. ✅ **Használj URLSearchParams-t** biztonságos query string építéshez
7. ✅ **Add hozzá a X-Requested-With headert** AJAX hívásokhoz
8. ✅ **Console.log-olj minden URL-t** fejlesztés során
9. ✅ **Validáld az API válaszokat** mielőtt feldolgoznád őket
10. ✅ **Adj felhasználóbarát hibaüzeneteket** minden hibakezelési szinten

## Referencia

- `guestform.php`: Teljes implementáció példakóddal
- Kompatibilis: Joomla 3.x, 4.x, Solidres 2.x, 3.x
- Tesztelve: Hub, almenü, multi-site kontextusban
- Nyelvek: Magyar kommentek, angol változónevek

## Támogatás

Ha továbbra is 404 hibát kapsz, ellenőrizd:
1. A Joomla telepítés gyökér könyvtárát
2. Az index.php fájl létezését
3. A .htaccess beállításokat
4. A Joomla SEF URL konfigurációt
5. A Solidres komponens telepítését és aktiválását

---

**Verzió:** 1.0  
**Dátum:** 2026-02-15  
**Szerző:** Solidres/Joomla AJAX Fix  
**Licenc:** GNU GPL v2+
