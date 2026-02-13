# Solidres Qvik és Revolut Fizetési Plugin - JS Javítások

## Áttekintés

Ez a dokumentáció ismerteti a Solidres Qvik és Revolut fizetési pluginok sablon fájljaiban végrehajtott JavaScript változtatásokat. A módosítások célja, hogy biztosítsák a hibátlan működést minden menüpont, almenü, hub és deep-link esetén, valamint hogy az AJAX kérések és átirányítások mindig a helyes útvonalra mutassanak.

## Érintett Fájlok

### Qvik Plugin
- `plugins/solidrespayment/qvik/tmpl/guestform.php` - Vendég űrlap sablon
- `plugins/solidrespayment/qvik/tmpl/confirmationform.php` - Megerősítési űrlap sablon

### Revolut Plugin
- `plugins/solidrespayment/revolut/tmpl/guestform.php` - Vendég űrlap sablon
- `plugins/solidrespayment/revolut/tmpl/confirmationform.php` - Megerősítési űrlap sablon

## Fő Változtatások

### 1. URL Paraméterek Megőrzése

**Probléma**: Az eredeti implementációban az URL paraméterek (hub_id, property_id, site_id, Itemid) elvesztek az AJAX kérések és átirányítások során.

**Megoldás**: Minden sablon tartalmaz egy `getUrlParams()` függvényt, amely kinyeri és megőrzi ezeket a paramétereket:

```javascript
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        hub_id: params.get('hub_id') || '',
        property_id: params.get('property_id') || '',
        site_id: params.get('site_id') || '',
        Itemid: params.get('Itemid') || '',
        reservation_id: params.get('reservation_id') || ''
    };
}
```

### 2. Teljes Pathname Megőrzés

**Probléma**: Az almenük és hub kontextusok elvesztek, mert csak az origin-t használták.

**Megoldás**: A `window.location.pathname` explicit használata minden URL építő függvényben:

```javascript
const origin = window.location.origin;
const pathname = window.location.pathname;
const fullUrl = origin + pathname + '?' + queryParams.join('&');
```

Ez biztosítja, hogy az URL szerkezet (pl. `/hu/booking/hotels/property`) megmaradjon.

### 3. AJAX URL Építés (confirmationform.php)

**Új függvény**: `buildAjaxUrl(task, format)`

Ez a függvény felelős az AJAX kérések URL-jeinek helyes összeállításáért:

```javascript
function buildAjaxUrl(task, format) {
    format = format || 'json';
    const urlParams = getUrlParams();
    const origin = window.location.origin;
    const pathname = window.location.pathname;
    
    let queryParams = [];
    queryParams.push('option=com_solidres');
    queryParams.push('task=' + task);
    queryParams.push('format=' + format);
    
    // Minden paraméter hozzáadása
    if (urlParams.hub_id) queryParams.push('hub_id=' + urlParams.hub_id);
    if (urlParams.property_id) queryParams.push('property_id=' + urlParams.property_id);
    if (urlParams.site_id) queryParams.push('site_id=' + urlParams.site_id);
    if (urlParams.Itemid) queryParams.push('Itemid=' + urlParams.Itemid);
    if (urlParams.reservation_id) queryParams.push('reservation_id=' + urlParams.reservation_id);
    
    return origin + pathname + '?' + queryParams.join('&');
}
```

**Használat**:
```javascript
const ajaxUrl = buildAjaxUrl('payment.processQvikPayment', 'json');
// vagy
const ajaxUrl = buildAjaxUrl('payment.processRevolutPayment', 'json');
```

### 4. Redirect URL Építés

**Új függvény**: `buildRedirectUrl(view, layout)`

Ez a függvény kezeli az átirányítások URL-jeinek helyes összeállítását:

```javascript
function buildRedirectUrl(view, layout) {
    const urlParams = getUrlParams();
    const origin = window.location.origin;
    const pathname = window.location.pathname;
    
    let queryParams = [];
    queryParams.push('option=com_solidres');
    queryParams.push('view=' + view);
    if (layout) {
        queryParams.push('layout=' + layout);
    }
    
    // Minden fontos paraméter megőrzése
    if (urlParams.hub_id) queryParams.push('hub_id=' + urlParams.hub_id);
    if (urlParams.property_id) queryParams.push('property_id=' + urlParams.property_id);
    if (urlParams.site_id) queryParams.push('site_id=' + urlParams.site_id);
    if (urlParams.Itemid) queryParams.push('Itemid=' + urlParams.Itemid);
    if (urlParams.reservation_id) queryParams.push('reservation_id=' + urlParams.reservation_id);
    
    return origin + pathname + '?' + queryParams.join('&');
}
```

**Használat**:
```javascript
const successUrl = buildRedirectUrl('reservation', 'complete');
window.location.href = successUrl;
```

### 5. Fetch API Implementáció

**Probléma**: Az AJAX kérések nem megfelelően kezelték a hibákat és a válaszokat.

**Megoldás**: Modern Fetch API használata promise-alapú hibakezeléssel:

```javascript
fetch(ajaxUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        reservation_id: urlParams.reservation_id,
        payment_method: 'qvik' // vagy 'revolut'
    })
})
.then(function(response) {
    if (!response.ok) {
        throw new Error('Network response was not ok');
    }
    return response.json();
})
.then(function(data) {
    toggleLoader(false);
    
    if (data.success) {
        showSuccess(data.message);
        // Átirányítás teljes kontextus megőrzéssel
        setTimeout(function() {
            const successUrl = buildRedirectUrl('reservation', 'complete');
            window.location.href = successUrl;
        }, 2000);
    } else {
        showError(data.message);
    }
})
.catch(function(error) {
    toggleLoader(false);
    console.error('Payment error:', error);
    showError('Fizetési hiba történt');
});
```

## guestform.php Fájlok

### Funkciók

1. **Vendég adatok megjelenítése**: név, email, telefon, összeg
2. **Fizetés indítása gomb**: átirányít a confirmationform-ra
3. **Mégse gomb**: visszairányít a foglalási oldalra

### URL Építő Függvény

```javascript
function buildFullUrl(view, task) {
    const urlParams = getUrlParams();
    const origin = window.location.origin;
    const pathname = window.location.pathname;
    
    let queryParams = [];
    queryParams.push('option=com_solidres');
    queryParams.push('view=' + view);
    if (task) {
        queryParams.push('task=' + task);
    }
    
    // Kötelező paraméterek hozzáadása
    if (urlParams.hub_id) queryParams.push('hub_id=' + urlParams.hub_id);
    if (urlParams.property_id) queryParams.push('property_id=' + urlParams.property_id);
    if (urlParams.site_id) queryParams.push('site_id=' + urlParams.site_id);
    if (urlParams.Itemid) queryParams.push('Itemid=' + urlParams.Itemid);
    if (urlParams.reservation_id) queryParams.push('reservation_id=' + urlParams.reservation_id);
    
    return origin + pathname + '?' + queryParams.join('&');
}
```

### Eseménykezelők

```javascript
// Fizetés indítása
document.getElementById('qvik-payment-btn').addEventListener('click', function(e) {
    e.preventDefault();
    const confirmUrl = buildFullUrl('reservation', 'confirmPayment');
    window.location.href = confirmUrl;
});

// Mégse
document.getElementById('qvik-cancel-btn').addEventListener('click', function(e) {
    e.preventDefault();
    const cancelUrl = buildFullUrl('reservation', 'cancel');
    window.location.href = cancelUrl;
});
```

## confirmationform.php Fájlok

### Funkciók

1. **Foglalás összegzésének megjelenítése**: reservation_id, összeg
2. **Megerősítés gomb**: AJAX kéréssel feldolgozza a fizetést
3. **Vissza gomb**: visszairányít a guest form-ra
4. **Státusz üzenetek**: sikeres/sikertelen fizetés jelzése
5. **Betöltő animáció**: feldolgozás közben

### Segéd Függvények

```javascript
// Sikeres üzenet
function showSuccess(message) {
    const statusDiv = document.getElementById('payment-status');
    statusDiv.className = 'alert alert-success';
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
}

// Hiba üzenet
function showError(message) {
    const statusDiv = document.getElementById('payment-status');
    statusDiv.className = 'alert alert-danger';
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
}

// Betöltő kezelése
function toggleLoader(show) {
    const loader = document.getElementById('payment-loader');
    loader.style.display = show ? 'block' : 'none';
}
```

## Magyar Nyelvű Kommentek

Minden JavaScript függvény és fontos kódrészlet részletes magyar nyelvű kommenteket tartalmaz:

- **Függvény cél**: Mit csinál a függvény
- **Paraméterek**: Mit várunk inputként
- **Visszatérési érték**: Mit ad vissza
- **Működés**: Hogyan éri el a célját

**Példa**:
```javascript
/**
 * AJAX URL építése teljes pathname megőrzéssel
 * Ez biztosítja, hogy az AJAX kérések a helyes végpontra menjenek,
 * figyelembe véve az összes almenü és hub szegmenst az útvonalban
 * 
 * @param {string} task - A végrehajtandó task
 * @param {string} format - Válasz formátum (alapértelmezett: 'json')
 * @returns {string} - Teljes AJAX URL
 */
```

## Tesztelési Forgatókönyvek

### 1. Alapvető Működés
- [ ] Vendég űrlap megjelenik helyes adatokkal
- [ ] Megerősítési űrlap megjelenik
- [ ] AJAX kérés sikeres
- [ ] Sikeres fizetés után helyes átirányítás

### 2. Menüpont Kontextus
- [ ] Főmenü linkről indított foglalás: Itemid megmarad
- [ ] Almenü linkről indított foglalás: Itemid és pathname megmarad
- [ ] Fizetés után vissza a helyes menüponthoz

### 3. Hub Kontextus
- [ ] hub_id paraméter megmarad a teljes folyamat alatt
- [ ] Hub specifikus átirányítások működnek
- [ ] Hub szegmensek megmaradnak a pathname-ben

### 4. Deep-link Kontextus
- [ ] property_id megmarad
- [ ] site_id megmarad
- [ ] Minden egyedi paraméter megmarad

### 5. Hibakezelés
- [ ] Hálózati hiba esetén hibaüzenet megjelenik
- [ ] Sikertelen fizetés esetén hibaüzenet megjelenik
- [ ] Felhasználó maradhat az oldalon hiba után

## Implementációs Megjegyzések

1. **Minden template önálló**: Mindegyik tartalmazza a saját JavaScript logikáját
2. **Nincs függőség külső library-re**: Pure JavaScript implementáció
3. **Modern API-k**: Fetch API, URLSearchParams használata
4. **Backward compatibility**: function() syntax arrow function helyett régebbi böngészőkhöz
5. **IIFE használata**: `(function() { ... })()` a globális névtér szennyezésének elkerülésére

## Kulcs Előnyök

✅ **Teljes kontextus megőrzés**: Minden URL paraméter és path szegmens megmarad  
✅ **Hub támogatás**: Multi-site és hub architektúra teljes támogatása  
✅ **Deep-link kompatibilis**: Bármely belépési pont működik  
✅ **Menürendszer aware**: Joomla menük és almenük helyes kezelése  
✅ **Hibamentes navigáció**: Nincs elveszett kontextus vagy hibás átirányítás  
✅ **Érthető kód**: Részletes magyar kommentekkel  
✅ **Karbantartható**: Tiszta, jól strukturált kód  

## Következő Lépések

1. **Telepítés**: Másolja a fájlokat a megfelelő plugin könyvtárakba
2. **Tesztelés**: Futtassa le az összes tesztelési forgatókönyvet
3. **Validálás**: Ellenőrizze minden menüpont és hub kombinációban
4. **Dokumentálás**: Frissítse a felhasználói dokumentációt ha szükséges

## Megjegyzések

- A fájlok PHP és JavaScript kódot egyaránt tartalmaznak
- A JavaScript kód `<script>` tageken belül van
- A PHP változók (pl. `$this->reservation`) elérhetők a JavaScript részben is JText használatával
- Az összes string a Joomla nyelvi rendszeren keresztül van kezelve (JText::_())

---

**Verzió**: 1.0  
**Utolsó módosítás**: 2026-02-13  
**Szerző**: Solidres Development Team
