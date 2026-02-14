# Implementációs Megjegyzések - Qvik & Revolut Fizetési Pluginok

## Kritikus Implementációs Pontok

### 1. URL Felépítés - Miért Így Működik?

#### A Probléma
A korábbi implementációkban gyakori probléma volt, hogy:
- Az almenük URL szegmensei elvesztek
- A hub_id paraméter nem került továbbításra
- Az Itemid hiánya miatt 404 hibák keletkeztek
- A többnyelvű oldalak esetén a site_id elveszett

#### A Megoldás
```javascript
function buildBaseUrl() {
    var origin = window.location.origin;  // pl. "https://example.com"
    var pathname = window.location.pathname;  // pl. "/hu/szallasok/apartmanok"
    return origin + pathname;
}
```

**Miért jó ez?**
- Az `origin` tartalmazza a protocol-t, domain-t és port-ot
- A `pathname` tartalmazza MINDEN URL szegmenst, beleértve:
  - Nyelvi prefixeket: `/hu/`, `/en/`
  - Menü struktúrákat: `/szallasok/apartmanok`
  - Hub útvonalakat: `/hub/property-name`
  - SEF URL szegmenseket

### 2. Paraméter Megőrzés - Mit és Miért?

#### Itemid Paraméter
```javascript
if (currentParams.has('Itemid')) {
    params.set('Itemid', currentParams.get('Itemid'));
}
```

**Miért KRITIKUS?**
- A Joomla az Itemid alapján tudja, melyik menüponthoz tartozik az oldal
- Itemid nélkül a Joomla nem tudja betölteni a helyes template-et
- Itemid nélkül a menü aktív állapota elvész
- Itemid nélkül gyakran 404 hiba keletkezik

#### hub_id Paraméter
```javascript
if (currentParams.has('hub_id')) {
    params.set('hub_id', currentParams.get('hub_id'));
}
```

**Miért FONTOS?**
- Multi-property (hub) környezetben azonosítja a hub-ot
- Nélküle a rendszer nem tudja, melyik hub kontextusban van
- A fizetési callback-nél kritikus, hogy a helyes hub-hoz rendelje a foglalást

#### property_id Paraméter
```javascript
if (currentParams.has('property_id')) {
    params.set('property_id', currentParams.get('property_id'));
}
```

**Miért SZÜKSÉGES?**
- Azonosítja a konkrét szálláshelyet
- A foglalási folyamat minden lépésében szükséges
- Nélküle a rendszer nem tudja, melyik property-hez tartozik a foglalás

#### site_id Paraméter
```javascript
if (currentParams.has('site_id')) {
    params.set('site_id', currentParams.get('site_id'));
}
```

**Miért HASZNOS?**
- Többnyelvű/multi-site környezetben azonosítja az oldalt
- Biztosítja, hogy a helyes nyelven/oldalon maradjunk

#### reservation_id Paraméter
```javascript
if (currentParams.has('reservation_id')) {
    params.set('reservation_id', currentParams.get('reservation_id'));
}
```

**Miért ELENGEDHETETLEN?**
- Azonosítja a konkrét foglalást
- Minden fizetési művelethez szükséges
- Callback-nél kritikus, hogy a helyes foglaláshoz rendelje a fizetést

### 3. Fetch API vs XMLHttpRequest

#### Miért Fetch API?

**Régi módszer (XMLHttpRequest)**:
```javascript
var xhr = new XMLHttpRequest();
xhr.open('POST', url, true);
xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
        if (xhr.status === 200) {
            // Sikeres válasz
        } else {
            // Hiba
        }
    }
};
xhr.send(data);
```

**Új módszer (Fetch API)**:
```javascript
fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => {
    // Sikeres válasz
})
.catch(error => {
    // Hiba
});
```

**Előnyök**:
1. **Promise-alapú** - Könnyebb hibakezelés
2. **Tisztább szintaxis** - Olvashatóbb kód
3. **Láncolt then()** - Több lépés kezelése
4. **Beépített JSON parsing** - Automatikus response.json()
5. **Modern standard** - Jövőbiztos

### 4. Hibakezelési Stratégia

#### Többszintű Hibakezelés

**1. Szint - HTTP Státusz**:
```javascript
.then(function(response) {
    if (!response.ok) {
        throw new Error('HTTP hiba: ' + response.status);
    }
    return response.json();
})
```

**Miért fontos?**
- A Fetch API nem dob hibát 404, 500 stb. státusznál
- Manuálisan kell ellenőrizni a response.ok értékét
- Ha nem OK, akkor dobjunk hibát a catch() ágba

**2. Szint - API Válasz Ellenőrzés**:
```javascript
.then(function(data) {
    if (data.success && data.payment_url) {
        // Sikeres művelet
    } else {
        // API szintű hiba
        var errorMsg = data.message || 'Ismeretlen hiba történt';
        alert('Fizetési hiba: ' + errorMsg);
    }
})
```

**Miért fontos?**
- Az API 200 OK státuszt is küldhet sikertelen művelet esetén
- Az API válaszban lévő `success` mező jelzi a tényleges sikert
- Mindig ellenőrizzük a szükséges mezők létét (pl. `payment_url`)

**3. Szint - Hálózati Hiba**:
```javascript
.catch(function(error) {
    console.error('Hiba:', error);
    alert('Hiba történt. Kérjük, próbálja újra később.');
    
    // Felhasználói felület visszaállítása
    if (button) {
        button.disabled = false;
        button.textContent = 'Próbálja újra';
    }
})
```

**Miért fontos?**
- Kezeli a hálózati hibákat (nincs internet, timeout stb.)
- Kezeli a JavaScript hibákat a kódban
- Visszaállítja a UI-t használható állapotba

### 5. UI Állapotkezelés

#### Gomb Letiltása
```javascript
if (payButton) {
    payButton.disabled = true;
    payButton.textContent = 'Feldolgozás...';
}
```

**Miért kritikus?**
- Megakadályozza a dupla kattintást
- Vizuális visszajelzést ad a felhasználónak
- Megelőzi a többszörös fizetési kéréseket

#### Gomb Újra Engedélyezése
```javascript
if (payButton) {
    payButton.disabled = false;
    payButton.textContent = 'Fizetés';
}
```

**Mikor szükséges?**
- Hiba esetén MINDIG
- Sikeres művelet esetén NEM (átirányítás következik)

### 6. Form Validálás

#### Email Validálás
```javascript
var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
if (!emailPattern.test(formData.guest_email)) {
    alert('Kérjük, adjon meg egy érvényes email címet!');
    return false;
}
```

**Regex magyarázat**:
- `^` - String kezdete
- `[^\s@]+` - Legalább 1 karakter, ami nem szóköz és nem @
- `@` - @ jel
- `[^\s@]+` - Legalább 1 karakter, ami nem szóköz és nem @
- `\.` - Pont karakter (escapelve)
- `[^\s@]+` - Legalább 1 karakter, ami nem szóköz és nem @
- `$` - String vége

### 7. Callback Kezelés

#### URL Paraméter Ellenőrzés
```javascript
var currentParams = new URLSearchParams(window.location.search);
if (currentParams.has('qvik_status') || currentParams.has('payment_status')) {
    // Ez egy callback
}
```

**Miért has()?**
- A fizetési provider különböző paraméter neveket használhat
- Rugalmas ellenőrzés több lehetőséget is támogat

#### Státusz Megerősítés
```javascript
var verifyUrl = buildAjaxUrl('payment.verify', {
    payment_method: 'qvik',
    transaction_id: transactionId,
    status: status
});
```

**Miért szükséges?**
- A callback URL manipulálható (security)
- Mindig ellenőrizni kell szerver oldalon is
- A transaction_id alapján verifikálható a fizetés

## Gyakori Hibák és Megoldások

### Hiba 1: 404 Not Found

**Oka**: Hiányzó Itemid paraméter

**Megoldás**:
```javascript
if (currentParams.has('Itemid')) {
    params.set('Itemid', currentParams.get('Itemid'));
}
```

### Hiba 2: Elveszett Hub Kontextus

**Oka**: Hiányzó hub_id paraméter

**Megoldás**:
```javascript
if (currentParams.has('hub_id')) {
    params.set('hub_id', currentParams.get('hub_id'));
}
```

### Hiba 3: Rossz Template/Menü

**Oka**: Nem teljes URL használata

**Megoldás**:
```javascript
var baseUrl = window.location.origin + window.location.pathname;
```

### Hiba 4: Dupla Fizetés

**Oka**: Nincs gomb letiltás

**Megoldás**:
```javascript
payButton.disabled = true;
```

### Hiba 5: Nem Működő Callback

**Oka**: Paraméterek elvesznek a redirect során

**Megoldás**: Mindig használd a `buildRedirectUrl()` függvényt

## Tesztelési Checklist

- [ ] Egyszerű menüpont működik
- [ ] Almenü működik (2+ szint mély)
- [ ] Hub/multi-property működik
- [ ] Többnyelvű működik
- [ ] Fizetés inicializálás működik
- [ ] Fizetés callback működik
- [ ] Sikeres fizetés átirányítás működik
- [ ] Sikertelen fizetés kezelés működik
- [ ] Hálózati hiba kezelés működik
- [ ] Dupla kattintás védelem működik
- [ ] Form validálás működik
- [ ] Automatikus űrlap kitöltés működik

## Telepítési Útmutató

1. **Backup készítése** az eredeti fájlokról
2. **Fájlok másolása** a megfelelő helyekre
3. **Paraméterek ellenőrzése** a plugin beállításokban
4. **Tesztelés** minden szcenárióban
5. **Monitoring** az első éles használat során

## Támogatás

Ha problémába ütközöl:
1. Ellenőrizd a böngésző konzolt (F12)
2. Nézd meg a hálózati fülön a kéréseket/válaszokat
3. Ellenőrizd, hogy minden paraméter megvan-e az URL-ekben
4. Teszteld egyszerűbb környezetben (pl. menü nélkül)

## Verzió Információ

- **Verzió**: 1.0.0
- **Utolsó módosítás**: 2026-02-14
- **Kompatibilitás**: Joomla 3.x, 4.x, 5.x
- **Solidres verzió**: 2.x, 3.x
