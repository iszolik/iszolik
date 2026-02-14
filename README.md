# Solidres Qvik és Revolut Fizetési Megoldások - JS Fetch+Redirect Logika

## Áttekintés

Ez a repository tartalmazza a véglegesített JavaScript fetch+redirect logikát a Solidres Qvik és Revolut fizetési pluginokhoz. A kód biztosítja a helyes működést minden menüpont, almenü és hub/multiszálláshely esetében, megszüntetve a 404 hibákat és elvesző útvonalakat.

## Könyvtárstruktúra

```
plugins/
└── solidrespayment/
    ├── qvik/
    │   └── tmpl/
    │       ├── confirmationform.php    # Fizetés megerősítő oldal logikája
    │       └── guestform.php           # Vendég adatlap űrlap logikája
    └── revolut/
        └── tmpl/
            ├── confirmationform.php    # Fizetés megerősítő oldal logikája
            └── guestform.php           # Vendég adatlap űrlap logikája
```

## Fő Funkciók

### 1. URL Megőrzési Mechanizmus

A kód három fő függvényt használ az URL-ek helyes felépítéséhez:

#### `buildBaseUrl()`
- **Cél**: A teljes alap URL felépítése az aktuális kontextus megőrzésével
- **Működés**: 
  - `window.location.origin` - tartalmazza a protocol + domain + port-ot
  - `window.location.pathname` - tartalmazza a teljes elérési utat, beleértve az almenüket is
- **Példák**:
  - `https://example.com/hu/szallasok/apartmanok`
  - `http://localhost:8080/hub/property-name`

#### `buildAjaxUrl(task, additionalParams)`
- **Cél**: AJAX URL felépítése a Solidres API hívásokhoz
- **Megőrzött paraméterek**:
  - `Itemid` - Joomla menüpont azonosító (KRITIKUS az útvonal megőrzéséhez)
  - `property_id` - Szálláshely azonosító
  - `hub_id` - Hub/multiszálláshely azonosító
  - `site_id` - Oldal azonosító (több nyelv/site esetén)
  - `reservation_id` - Foglalás azonosító
- **Példa használat**:
  ```javascript
  var ajaxUrl = buildAjaxUrl('payment.initiate', {
      payment_method: 'qvik'
  });
  ```

#### `buildRedirectUrl(view, additionalParams)`
- **Cél**: Redirect URL felépítése sikeres/sikertelen fizetés után
- **Biztosítja**: A visszairányítás után is megmarad a teljes kontextus
- **Példa használat**:
  ```javascript
  var successUrl = buildRedirectUrl('reservationdetails', {
      reservation_id: data.reservation_id,
      payment_success: '1'
  });
  ```

### 2. Fetch API Használata

A kód modern Fetch API-t használ promise-alapú hibakezeléssel:

```javascript
fetch(ajaxUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
})
.then(function(response) {
    if (!response.ok) {
        throw new Error('HTTP hiba: ' + response.status);
    }
    return response.json();
})
.then(function(data) {
    // Sikeres válasz kezelése
})
.catch(function(error) {
    // Hibakezelés
});
```

### 3. Fizetési Folyamat (confirmationform.php)

#### Qvik Fizetés Inicializálása
1. **Gomb letiltása** - Dupla kattintás ellen
2. **AJAX URL felépítése** - `payment.initiate` task-kal
3. **Fetch kérés küldése** - POST metódussal
4. **Válasz feldolgozása**:
   - Sikeres: Átirányítás a Qvik fizetési oldalra
   - Sikertelen: Hibaüzenet + átirányítás a megszakított foglalás oldalra

#### Qvik Callback Kezelése
1. **URL paraméterek ellenőrzése** - `qvik_status` vagy `payment_status`
2. **Fizetés státusz megerősítése** - `payment.verify` task-kal
3. **Átirányítás** a megfelelő oldalra:
   - Sikeres: `reservationdetails` view
   - Sikertelen: `reservationcancelled` view

### 4. Vendég Adatlap (guestform.php)

#### Form Validálás
- Kötelező mezők ellenőrzése (keresztnév, vezetéknév, email, telefon)
- Email formátum validálás regex-szel
- Magyar nyelvű hibaüzenetek

#### Adatmentés és Továbblépés
1. **Form adatok összegyűjtése**
2. **Validálás futtatása**
3. **AJAX kérés** - `reservation.saveguestdata` task-kal
4. **Sikeres mentés után**: Átirányítás a fizetési oldalra
5. **Automatikus űrlap kitöltés** - Korábban mentett adatokból

## Implementációs Részletek

### Paraméter Megőrzés Stratégia

A kód minden URL építésnél megőrzi a következő paramétereket:

```javascript
var currentParams = new URLSearchParams(window.location.search);

// Itemid megőrzése
if (currentParams.has('Itemid')) {
    params.set('Itemid', currentParams.get('Itemid'));
}

// property_id megőrzése
if (currentParams.has('property_id')) {
    params.set('property_id', currentParams.get('property_id'));
}

// hub_id megőrzése - KRITIKUS multi-property esetén
if (currentParams.has('hub_id')) {
    params.set('hub_id', currentParams.get('hub_id'));
}

// site_id megőrzése
if (currentParams.has('site_id')) {
    params.set('site_id', currentParams.get('site_id'));
}

// reservation_id megőrzése
if (currentParams.has('reservation_id')) {
    params.set('reservation_id', currentParams.get('reservation_id'));
}
```

### Hibakezelés

A kód többszintű hibakezelést alkalmaz:

1. **HTTP státusz ellenőrzés**:
   ```javascript
   if (!response.ok) {
       throw new Error('HTTP hiba: ' + response.status);
   }
   ```

2. **API válasz ellenőrzés**:
   ```javascript
   if (data.success && data.payment_url) {
       // Sikeres művelet
   } else {
       // Hibakezelés
   }
   ```

3. **Hálózati hiba kezelés**:
   ```javascript
   .catch(function(error) {
       console.error('Hiba:', error);
       alert('Hiba történt. Kérjük, próbálja újra később.');
   });
   ```

## Használati Útmutató

### Qvik Plugin Használata

1. **confirmationform.php** bemásolása:
   ```
   /path/to/joomla/plugins/solidrespayment/qvik/tmpl/confirmationform.php
   ```

2. **guestform.php** bemásolása:
   ```
   /path/to/joomla/plugins/solidrespayment/qvik/tmpl/guestform.php
   ```

### Revolut Plugin Használata

1. **confirmationform.php** bemásolása:
   ```
   /path/to/joomla/plugins/solidrespayment/revolut/tmpl/confirmationform.php
   ```

2. **guestform.php** bemásolása:
   ```
   /path/to/joomla/plugins/solidrespayment/revolut/tmpl/guestform.php
   ```

## Tesztelési Szcenáriók

### 1. Egyszerű Menüpont Teszt
- Navigálás egy menüpontra
- Foglalás indítása
- Ellenőrzés: Az Itemid paraméter megmarad minden lépésben

### 2. Almenü Teszt
- Navigálás egy almenüre (pl. `/hu/szallasok/apartmanok`)
- Foglalás indítása
- Ellenőrzés: A teljes pathname megmarad az URL-ekben

### 3. Hub/Multiszálláshely Teszt
- Navigálás egy hub tulajdonságra
- Foglalás indítása
- Ellenőrzés: A hub_id paraméter megmarad minden lépésben

### 4. Fizetési Folyamat Teszt
- Vendég adatok kitöltése
- Továbblépés fizetéshez
- Fizetés inicializálása
- Visszatérés a callback URL-ről
- Ellenőrzés: Nincs 404 hiba, minden paraméter megmaradt

## Megjegyzések

### Miért működik ez a megoldás?

1. **window.location.origin + pathname** használata biztosítja, hogy minden URL szegmens megmaradjon
2. **URLSearchParams API** használata modern és biztonságos paraméter kezelést biztosít
3. **Feltételes paraméter megőrzés** csak akkor ad hozzá paramétereket, ha léteznek
4. **Promise-alapú hibakezelés** robusztus hibakezelést biztosít

### Ismert Korlátozások

- A kód feltételezi, hogy a Solidres API válaszai JSON formátumúak
- CSRF token kezelés a `credentials: 'same-origin'` beállításra támaszkodik
- A kód nem kezeli a böngésző "vissza" gombjának használatát

### Jövőbeli Fejlesztési Lehetőségek

- Explicit CSRF token kezelés hozzáadása
- Böngésző history API használata jobb navigációért
- Loading indikátorok hozzáadása jobb UX-ért
- Retry logika hozzáadása hálózati hibák esetén

## Licensz

Ez a kód a Solidres kereskedelmi pluginjaihoz készült. Használata a Solidres licenc feltételeinek megfelelően történhet.

## Kapcsolat

Ha kérdése van a kóddal kapcsolatban, kérjük, lépjen kapcsolatba a Solidres támogatással.
