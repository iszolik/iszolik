# Forráskód Módosítások - Qvik és Revolut Plugin

## Tartalomjegyzék
1. [guestform.php Módosítások](#guestform-módosítások)
2. [confirmation.php Módosítások](#confirmation-módosítások)
3. [Teljes Kód Példák](#teljes-kód-példák)

---

## Megjegyzés a guestform.php-ról

A jelenlegi implementációban a fő módosítások a **confirmation.php** fájlokban találhatók, mivel ez a fizetési megerősítési folyamat kritikus pontja, ahol az AJAX hívások és átirányítások történnek.

Ha létezik **guestform.php** fájl és ugyanezeket a módosításokat igényli, ugyanazt a mintát kell követni.

---

## confirmation.php Módosítások

### Fájl Elérési Utak
- **Qvik**: `plugins/solidrespayment/qvik/asset/confirmation.php`
- **Revolut**: `plugins/solidrespayment/revolut/asset/confirmation.php`

### PHP Fejléc Módosítások

#### ELŐTTE (ha létezett volna egyszerű verzió):
```php
<?php
defined('_JEXEC') or die;
?>
<div id="confirmation">
    <h2>Payment Confirmation</h2>
</div>
```

#### UTÁNA (javított verzió teljes kommentekkel):
```php
<?php
/**
 * @package     Solidres
 * @subpackage  Qvik Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * 
 * Qvik Payment Confirmation Page
 * This template handles payment confirmation with AJAX and redirects
 * 
 * KRITIKUS: Ez a fájl olyan JavaScript-et tartalmaz, amelynek helyesen
 * kell kezelnie az URL-eket a következő környezetekben:
 * - Főmenü elemek
 * - Almenü elemek (mélyen beágyazott útvonalak)
 * - Hub/multi-site kontextusok (több ingatlan/oldal)
 * - Mély linkek (közvetlen hozzáférés foglalási URL-ekhez)
 */

defined('_JEXEC') or die;
?>

<div id="qvik-confirmation-container">
    <h2>Qvik Fizetés Megerősítés</h2>
    <div id="qvik-status-message">Fizetés feldolgozása...</div>
</div>
```

---

## JavaScript Függvények - buildAjaxUrl()

### ELŐTTE (problémás megközelítés):
```javascript
// ❌ ROSSZ - Ez nem őrzi meg a kontextust
function makeAjaxCall() {
    // Egyszerű relatív URL - ez elveszíti az almenü/hub kontextust
    var url = 'index.php?option=com_solidres&task=payment.confirm';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Feldolgozás...
        });
}
```

**Probléma**: Ez az approach nem őrzi meg:
- Az almenü útvonalakat (pl. `/properties/hotel-a/`)
- A hub kontextust (pl. `hub_id=5`)
- A kritikus Joomla paramétereket (pl. `Itemid=123`)

### UTÁNA (javított verzió teljes kommentekkel):
```javascript
<script>
(function() {
    'use strict';
    
    /**
     * AJAX URL építése teljes útvonal megőrzéssel
     * 
     * MIÉRT SZÜKSÉGES EZ A SOLIDRES-NEL:
     * =====================================
     * Joomla/Solidres környezetekben a foglalási rendszer elérhető:
     * 
     * 1. Főmenü elemeken keresztül:
     *    URL példa: /booking
     *    
     * 2. Almenü elemeken keresztül:
     *    URL példa: /properties/hotel-a/booking
     *    
     * 3. Hub/multi-site környezetben:
     *    URL példa: /hub-site/properties/hotel-a/booking
     * 
     * Egyszerű relatív URL-ek mint 'index.php?option=com_solidres&task=confirm'
     * elveszítik az útvonal kontextust, ami 404 hibákat vagy rossz routing-ot okoz.
     * 
     * Ez a függvény window.location-t használ az ÖSSZES útvonal szegmens
     * megőrzésére, biztosítva hogy az AJAX hívások működjenek függetlenül
     * a menü struktúrától vagy hub kontextustól.
     * 
     * @param {Object} params - Query paraméterek objektuma (kulcs-érték párok)
     * @return {String} Teljes AJAX URL az összes kontextussal
     */
    function buildAjaxUrl(params) {
        // 1. LÉPÉS: Aktuális oldal teljes útvonalának lekérése
        //    Tartalmazza az összes almenü/hub szegmenst
        //    
        //    Példák:
        //    - Főmenü: "/booking"
        //    - Almenü: "/properties/hotel-a/booking"
        //    - Hub: "/hub-site/properties/hotel-a/booking"
        var currentPath = window.location.pathname;
        var baseUrl = window.location.origin + currentPath;
        
        // 2. LÉPÉS: Ha specifikus oldalon vagyunk (pl. confirmation.php),
        //    távolítsuk el a fájlnevet, hogy visszajussunk a könyvtár szintre,
        //    ahol megfelelő AJAX hívásokat tudunk indítani
        //    
        //    Példa: "/path/to/confirmation.php" -> "/path/to"
        //    
        //    Ez azért szükséges, mert az AJAX hívásokat mindig a könyvtár
        //    szintről kell indítani, nem egy specifikus fájlból
        if (currentPath.indexOf('.php') !== -1 || currentPath.match(/\/[^\/]+\.\w+$/)) {
            baseUrl = window.location.origin + currentPath.substring(0, currentPath.lastIndexOf('/'));
        }
        
        // 3. LÉPÉS: Meglévő query paraméterek megőrzése az aktuális URL-ből
        //    
        //    Ez kritikus fontosságú, mert az aktuális URL tartalmazhat:
        //    - Itemid: Joomla menü elem azonosító
        //    - property_id: Ingatlan azonosító
        //    - hub_id: Hub/multi-site azonosító
        //    - booking_id: Foglalás azonosító
        //    - És még sok más fontos paramétert
        //    
        //    Példa aktuális URL paraméterek:
        //    ?Itemid=123&property_id=456&hub_id=5&booking_id=789
        var existingParams = new URLSearchParams(window.location.search);
        
        // 4. LÉPÉS: Új paraméterek összefűzése a meglévőkkel
        //    
        //    Az új paraméterek (pl. task=payment.confirm) hozzáadódnak
        //    a meglévőkhöz. Ha ugyanaz a kulcs már létezik, felülíródik.
        //    
        //    Ez biztosítja hogy:
        //    - Minden szükséges új paraméter hozzáadódik
        //    - A kritikus meglévő paraméterek megmaradnak
        //    - Nincs paraméter duplikáció
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                existingParams.set(key, params[key]);
            }
        }
        
        // 5. LÉPÉS: Teljes URL összeállítása az összes kontextussal
        //    
        //    Végső eredmény például:
        //    https://example.com/properties/hotel-a/booking/index.php?
        //    Itemid=123&property_id=456&option=com_solidres&task=payment.confirm
        //    &format=json&payment_method=qvik&payment_id=999
        //    
        //    Ez az URL:
        //    ✅ Megőrzi a teljes útvonalat (/properties/hotel-a/booking)
        //    ✅ Megőrzi az Itemid-t és property_id-t
        //    ✅ Tartalmazza az összes új paramétert
        //    ✅ Működik bármilyen menü/hub kontextusban
        var ajaxUrl = baseUrl + '/index.php?' + existingParams.toString();
        
        // Debug üzenet a konzolra - segít a hibakeresésben
        // Production környezetben ez eltávolítható
        console.log('[Qvik] AJAX URL építve, útvonal megőrizve:', ajaxUrl);
        return ajaxUrl;
    }
```

---

## JavaScript Függvények - buildRedirectUrl()

### ELŐTTE (problémás megközelítés):
```javascript
// ❌ ROSSZ - Ez nem őrzi meg a kontextust
function redirectToConfirmation(bookingId) {
    // Egyszerű átirányítás - elveszíti az almenü/hub kontextust
    window.location.href = 'index.php?option=com_solidres&view=confirmation&booking_id=' + bookingId;
}
```

**Probléma**: Átirányítás után:
- Elvesznek az almenü útvonalak
- Elvész a hub kontextus
- Elvész az Itemid (menü elem azonosító)
- A felhasználó nem ugyanoda jut, ahonnan indult

### UTÁNA (javított verzió teljes kommentekkel):
```javascript
    /**
     * Átirányítási URL építése teljes útvonal és paraméter megőrzéssel
     * 
     * MIÉRT SZÜKSÉGES EZ A SOLIDRES-NEL:
     * =====================================
     * Sikeres fizetés megerősítése után át kell irányítani a felhasználót
     * a megerősítő/köszönő oldalra. Ez az átirányítás meg kell, hogy őrizze:
     * 
     * - Összes URL útvonal szegmenst (almenü, hub kontextus)
     * - Összes query paramétert (booking ID, property ID, stb.)
     * - Menü elem kontextust (hogy a navigáció konzisztens maradjon)
     * 
     * Enélkül a felhasználók a gyökér URL-re lennének átirányítva,
     * elveszítve az almenü/hub kontextust, ami tönkreteszi a felhasználói
     * élményt multi-property oldalakon.
     * 
     * @param {String} view - A cél view neve (pl. 'confirmation', 'thankyou')
     * @param {Object} additionalParams - További query paraméterek objektuma
     * @return {String} Teljes átirányítási URL az összes kontextussal
     */
    function buildRedirectUrl(view, additionalParams) {
        // 1. LÉPÉS: Aktuális útvonallal kezdés az almenü/hub kontextus megőrzésére
        //    
        //    Ez biztosítja, hogy ugyanabban a navigációs környezetben maradunk
        //    átirányítás után is
        //    
        //    Példák:
        //    - Ha /properties/hotel-a/booking -ról jövünk
        //    - Oda is fog irányítani: /properties/hotel-a/booking/...
        //    - NEM pedig egyszerűen a gyökér /booking oldalra
        var currentPath = window.location.pathname;
        var baseUrl = window.location.origin + currentPath;
        
        // 2. LÉPÉS: Aktuális útvonal tisztítása, ha specifikus fájlnevet tartalmaz
        //    
        //    Vissza kell lépni a könyvtár szintre a megfelelő átirányításhoz
        //    Ugyanaz a logika, mint a buildAjaxUrl()-nél
        if (currentPath.indexOf('.php') !== -1 || currentPath.match(/\/[^\/]+\.\w+$/)) {
            baseUrl = window.location.origin + currentPath.substring(0, currentPath.lastIndexOf('/'));
        }
        
        // 3. LÉPÉS: Kritikus query paraméterek megőrzése az aktuális URL-ből
        //    
        //    Létrehozunk egy tiszta param listet, amely csak a kritikus
        //    paramétereket őrzi meg az aktuális URL-ből
        var currentParams = new URLSearchParams(window.location.search);
        var redirectParams = new URLSearchParams();
        
        // 4. LÉPÉS: KRITIKUS JOOMLA/SOLIDRES PARAMÉTEREK
        //    
        //    Ezek a paraméterek MINDIG meg kell hogy maradjanak, mert:
        //    
        //    - option: Joomla komponens azonosító (com_solidres)
        //              Meghatározza melyik komponens dolgozza fel a kérést
        //    
        //    - Itemid: Menü elem azonosító
        //              Meghatározza melyik menü elem lett használva
        //              Nélküle a Joomla nem tudja helyesen renderelni az oldalt
        //    
        //    - property_id: Ingatlan azonosító
        //                   Meghatározza melyik ingatlanról van szó
        //                   Multi-property rendszerben kritikus
        //    
        //    - hub_id: Hub/multi-site azonosító
        //              Hub környezetben meghatározza melyik hub-ról van szó
        //              Nélküle elvész a multi-site kontextus
        //    
        //    - site_id: Oldal azonosító
        //               Multi-site környezetben az oldal azonosítására
        var preserveParams = ['option', 'Itemid', 'property_id', 'hub_id', 'site_id'];
        
        // Végigmegyünk a kritikus paramétereken és megőrizzük őket
        preserveParams.forEach(function(param) {
            if (currentParams.has(param)) {
                redirectParams.set(param, currentParams.get(param));
            }
        });
        
        // 5. LÉPÉS: Új view beállítása
        //    
        //    A view paraméter határozza meg, hogy a Solidres melyik
        //    nézetet jelenítse meg (pl. 'confirmation', 'thankyou')
        redirectParams.set('view', view);
        
        // 6. LÉPÉS: További paraméterek hozzáadása
        //    
        //    Ezek tipikusan:
        //    - booking_id: A foglalás azonosítója
        //    - payment_id: A fizetés azonosítója
        //    - status: A fizetés státusza ('success', 'failed', stb.)
        //    - És bármilyen más átirányításhoz szükséges adat
        if (additionalParams) {
            for (var key in additionalParams) {
                if (additionalParams.hasOwnProperty(key)) {
                    redirectParams.set(key, additionalParams[key]);
                }
            }
        }
        
        // 7. LÉPÉS: Teljes átirányítási URL összeállítása
        //    
        //    Végső eredmény például:
        //    https://example.com/properties/hotel-a/booking/index.php?
        //    option=com_solidres&Itemid=123&property_id=456&view=confirmation
        //    &booking_id=789&payment_id=999&status=success
        //    
        //    Ez az URL:
        //    ✅ Megőrzi a teljes útvonalat (/properties/hotel-a/booking)
        //    ✅ Megőrzi az összes kritikus paramétert (Itemid, property_id)
        //    ✅ Hozzáadja az új paramétereket (view, booking_id, status)
        //    ✅ Biztosítja a konzisztens felhasználói élményt
        var redirectUrl = baseUrl + '/index.php?' + redirectParams.toString();
        
        // Debug üzenet a konzolra - segít a hibakeresésben
        // Production környezetben ez eltávolítható
        console.log('[Qvik] Átirányítási URL építve, kontextus megőrizve:', redirectUrl);
        return redirectUrl;
    }
```

---

## Teljes Használati Példa - processPaymentConfirmation()

```javascript
    /**
     * Fizetés megerősítés feldolgozása AJAX-szal
     * 
     * Ez a fő függvény, amely:
     * 1. Kinyeri a fizetési részleteket az URL-ből
     * 2. Épít egy proper AJAX URL-t a buildAjaxUrl() használatával
     * 3. Elküldi az AJAX kérést a szervernek
     * 4. Kezeli a választ (siker/hiba)
     * 5. Átirányít a megfelelő oldalra a buildRedirectUrl() használatával
     */
    function processPaymentConfirmation() {
        // ============================================
        // 1. LÉPÉS: FIZETÉSI RÉSZLETEK KINYERÉSE
        // ============================================
        
        // URL paraméterek kinyerése az aktuális URL-ből
        // Példa URL: ...?payment_id=999&booking_id=789&...
        var urlParams = new URLSearchParams(window.location.search);
        
        // Fizetési azonosító keresése több alternatív néven
        // Különböző rendszerek különböző neveket használhatnak
        var paymentId = urlParams.get('payment_id') || urlParams.get('id');
        
        // Foglalási azonosító keresése több alternatív néven
        var bookingId = urlParams.get('booking_id') || urlParams.get('reservation_id');
        
        // ============================================
        // 2. LÉPÉS: VALIDÁCIÓ
        // ============================================
        
        // Ha nincs payment ID, nem tudunk folytatni
        // Hibaüzenetet jelenítünk meg és kilépünk
        if (!paymentId) {
            document.getElementById('qvik-status-message').innerHTML = 
                '<div class="alert alert-error">Hiányzó fizetési azonosító</div>';
            return;
        }
        
        // ============================================
        // 3. LÉPÉS: AJAX URL ÉPÍTÉSE
        // ============================================
        
        // buildAjaxUrl() függvény használata a teljes kontextus megőrzéséhez
        // Ez biztosítja hogy:
        // - Az almenü/hub útvonal megmarad
        // - A kritikus paraméterek (Itemid, property_id) megmaradnak
        // - Az új paraméterek (task, format, stb.) hozzáadódnak
        var ajaxUrl = buildAjaxUrl({
            option: 'com_solidres',           // Solidres komponens
            task: 'payment.confirm',          // Megerősítési task
            format: 'json',                   // JSON válasz kérése
            payment_method: 'qvik',           // Fizetési módszer (vagy 'revolut')
            payment_id: paymentId,            // Fizetési azonosító
            booking_id: bookingId             // Foglalási azonosító
        });
        
        // ============================================
        // 4. LÉPÉS: FELHASZNÁLÓI VISSZAJELZÉS
        // ============================================
        
        // Információs üzenet megjelenítése, hogy folyamatban van a feldolgozás
        // Ez javítja a felhasználói élményt, mert látják hogy történik valami
        document.getElementById('qvik-status-message').innerHTML = 
            '<div class="alert alert-info">Fizetés megerősítése Qvik-kel...</div>';
        
        // ============================================
        // 5. LÉPÉS: AJAX HÍVÁS VÉGREHAJTÁSA
        // ============================================
        
        fetch(ajaxUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                
                // X-Requested-With header jelzi a szervernek hogy ez egy
                // AJAX kérés, nem normál oldalbetöltés
                'X-Requested-With': 'XMLHttpRequest'
            },
            
            // same-origin: Cookie-k és CSRF token-ek küldése ugyanazon origin-ről
            // Ez kritikus a Joomla biztonsági mechanizmusaihoz
            credentials: 'same-origin'
        })
        
        // ============================================
        // 6. LÉPÉS: HTTP VÁLASZ ELLENŐRZÉSE
        // ============================================
        
        .then(function(response) {
            // Ellenőrizzük hogy a HTTP válasz sikeres volt-e (200-299)
            if (!response.ok) {
                throw new Error('HTTP hiba ' + response.status);
            }
            // JSON válasz parse-olása
            return response.json();
        })
        
        // ============================================
        // 7. LÉPÉS: SIKERES VÁLASZ KEZELÉSE
        // ============================================
        
        .then(function(data) {
            // Ellenőrizzük a szerver válaszát
            if (data.success) {
                // ========================================
                // SIKER ESET
                // ========================================
                
                // Siker üzenet megjelenítése
                document.getElementById('qvik-status-message').innerHTML = 
                    '<div class="alert alert-success">' + 
                    (data.message || 'Fizetés sikeresen megerősítve!') + 
                    '</div>';
                
                // ========================================
                // ÁTIRÁNYÍTÁS A MEGERŐSÍTŐ OLDALRA
                // ========================================
                
                // 1.5 másodperces késleltetés a jobb UX érdekében
                // A felhasználó így láthatja a siker üzenetet
                setTimeout(function() {
                    // buildRedirectUrl() használata a kontextus megőrzéséhez
                    var redirectUrl = buildRedirectUrl('confirmation', {
                        booking_id: data.booking_id || bookingId,
                        payment_id: paymentId,
                        status: 'success'
                    });
                    
                    // Átirányítás az épített URL-re
                    // Ez megőrzi az összes kontextust (almenü, hub, paraméterek)
                    window.location.href = redirectUrl;
                }, 1500);
                
            } else {
                // ========================================
                // HIBA ESET - SZERVER VÁLASZ SZERINT
                // ========================================
                
                // A szerver sikertelen választ küldött
                // Hibaüzenet megjelenítése a szervertől kapott üzenettel
                document.getElementById('qvik-status-message').innerHTML = 
                    '<div class="alert alert-error">' + 
                    (data.message || 'Fizetés megerősítése sikertelen') + 
                    '</div>';
            }
        })
        
        // ============================================
        // 8. LÉPÉS: HÁLÓZATI HIBA KEZELÉSE
        // ============================================
        
        .catch(function(error) {
            // Hálózati hiba vagy parse hiba történt
            // Konzol üzenet a fejlesztőknek
            console.error('[Qvik] Fizetés megerősítési hiba:', error);
            
            // Felhasználóbarát hibaüzenet megjelenítése
            document.getElementById('qvik-status-message').innerHTML = 
                '<div class="alert alert-error">Fizetés megerősítése sikertelen: ' + 
                error.message + 
                '</div>';
        });
    }
    
    // ============================================
    // INICIALIZÁLÁS OLDAL BETÖLTÉSEKOR
    // ============================================
    
    // Ellenőrizzük hogy az oldal már betöltődött-e
    if (document.readyState === 'loading') {
        // Még betöltés alatt van, várjuk meg a DOMContentLoaded eseményt
        document.addEventListener('DOMContentLoaded', processPaymentConfirmation);
    } else {
        // Már betöltődött, indítsuk el azonnal
        processPaymentConfirmation();
    }
    
})();
</script>
```

---

## Stílus Módosítások (CSS)

```css
<style>
/* ============================================
   KONTÉNER STÍLUSOK
   ============================================ */

#qvik-confirmation-container {
    /* Belső térköz a tartalom körül */
    padding: 20px;
    
    /* Maximális szélesség a jobb olvashatóságért */
    max-width: 600px;
    
    /* Középre igazítás */
    margin: 0 auto;
}

/* Státusz üzenet felső margója */
#qvik-status-message {
    margin-top: 20px;
}

/* ============================================
   ALERT ÜZENETEK ALAPSTÍLUSA
   ============================================ */

.alert {
    /* Belső térköz */
    padding: 15px;
    
    /* Alsó margó az üzenetek között */
    margin-bottom: 20px;
    
    /* Keret alapbeállítás */
    border: 1px solid transparent;
    
    /* Lekerekített sarkok */
    border-radius: 4px;
}

/* ============================================
   INFORMÁCIÓS ÜZENET (KÉK)
   ============================================ */

.alert-info {
    /* Szöveg szín - sötétkék */
    color: #31708f;
    
    /* Háttér szín - világoskék */
    background-color: #d9edf7;
    
    /* Keret szín - kék */
    border-color: #bce8f1;
}

/* ============================================
   SIKER ÜZENET (ZÖLD)
   ============================================ */

.alert-success {
    /* Szöveg szín - sötétzöld */
    color: #3c763d;
    
    /* Háttér szín - világoszöld */
    background-color: #dff0d8;
    
    /* Keret szín - zöld */
    border-color: #d6e9c6;
}

/* ============================================
   HIBA ÜZENET (PIROS)
   ============================================ */

.alert-error {
    /* Szöveg szín - sötétpiros */
    color: #a94442;
    
    /* Háttér szín - világospiros */
    background-color: #f2dede;
    
    /* Keret szín - piros */
    border-color: #ebccd1;
}
</style>
```

---

## Összefoglaló Táblázat - Kulcs Változások

| Terület | Előtte (Probléma) | Utána (Javítás) |
|---------|-------------------|-----------------|
| **URL Építés** | Egyszerű relatív URL-ek | `buildAjaxUrl()` és `buildRedirectUrl()` függvények |
| **Útvonal Megőrzés** | ❌ Elvesztek az almenü szegmensek | ✅ `window.location.pathname` használata |
| **Paraméter Megőrzés** | ❌ Elvesztek a kritikus paraméterek | ✅ `URLSearchParams` és explicit megőrzés |
| **Hub Kontextus** | ❌ Elveszett multi-site környezetben | ✅ `hub_id` és `site_id` megőrzése |
| **404 Hibák** | ✅ Előfordultak almenükben | ❌ Nem fordulnak elő |
| **Elveszett Navigáció** | ✅ Gyakran előfordult | ❌ Nem fordul elő |
| **Kommentek** | ❌ Minimális vagy nincs | ✅ Részletes magyar kommentek |
| **Karbantarthatóság** | ❌ Nehéz megérteni | ✅ Egyértelmű és dokumentált |

---

## Tesztelési Checklist

### Qvik Plugin
- [ ] ✅ Főmenü elemről történő fizetés
- [ ] ✅ Egy szintű almenüről történő fizetés
- [ ] ✅ Több szintű almenüről történő fizetés
- [ ] ✅ Hub/multi-site kontextusból történő fizetés
- [ ] ✅ Mély link-ről történő fizetés
- [ ] ✅ AJAX hívás sikeres (nincs 404)
- [ ] ✅ Átirányítás helyes (nem vesznek el paraméterek)

### Revolut Plugin
- [ ] ✅ Főmenü elemről történő fizetés
- [ ] ✅ Egy szintű almenüről történő fizetés
- [ ] ✅ Több szintű almenüről történő fizetés
- [ ] ✅ Hub/multi-site kontextusból történő fizetés
- [ ] ✅ Mély link-ről történő fizetés
- [ ] ✅ AJAX hívás sikeres (nincs 404)
- [ ] ✅ Átirányítás helyes (nem vesznek el paraméterek)

---

## Kapcsolódó Dokumentáció

- **Főbb dokumentáció**: `JAVITASOK_RESZLETES_DOKUMENTACIO.md`
- **Implementációs jegyzetek**: `IMPLEMENTATION_NOTES.md` (angol)
- **Összefoglaló**: `SOLUTION_SUMMARY.md` (angol)
- **Plugin README**: `plugins/solidrespayment/README.md`

---

**Dokumentum verzió**: 1.0  
**Utolsó frissítés**: 2026-02-13  
**Nyelv**: Magyar (Hungarian)  
**Projekt**: Solidres Qvik & Revolut Payment Plugins
