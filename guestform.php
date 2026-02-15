<?php
/**
 * Solidres Vendég Adatlap - Guest Form
 * AJAX endpoint integrációval hub, almenü és több szálláshely kontextusban
 * 
 * Ez a fájl biztosítja, hogy az AJAX fetch hívások minden Joomla menüpont,
 * hub és almenü struktúrában abszolút útvonalat használjanak, elkerülve a 404 hibákat.
 * 
 * @package     Solidres
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Ne engedjük a közvetlen hozzáférést
defined('_JEXEC') or die('Restricted access');
?>

<script>
/**
 * AJAX URL építő függvény - Abszolút Joomla gyökérből
 * 
 * Ez a függvény garantálja, hogy az AJAX fetch hívás minden kontextusban
 * (főmenü, hub, almenü, több szálláshely) helyes útvonalat használjon.
 * 
 * Kulcs funkciók:
 * - window.location.origin: Teljes domain (protocol + host + port)
 * - Joomla gyökér index.php végpont használata
 * - Minden fontos paraméter megőrzése (Itemid, property_id, hub_id, site_id)
 * - URL query string építés biztonságosan
 * 
 * @param {string} option - Joomla komponens neve (pl. 'com_solidres')
 * @param {string} task - Végrehajtandó feladat (pl. 'reservation.save')
 * @param {object} additionalParams - További paraméterek objektum formátumban
 * @returns {string} Teljes, abszolút URL
 */
function buildAjaxUrl(option, task, additionalParams = {}) {
    // 1. Kezdjük a Joomla gyökér index.php-val (abszolút útvonal)
    // Ez biztosítja, hogy minden hub/almenü/multisite kontextusban működjön
    const baseUrl = window.location.origin + '/index.php';
    
    // 2. URL paraméterek gyűjtése
    const params = new URLSearchParams();
    
    // 3. Alapvető Joomla komponens paraméterek
    params.append('option', option);
    if (task) {
        params.append('task', task);
    }
    
    // 4. KRITIKUS: Itemid megőrzése a jelenlegi URL-ből
    // Az Itemid biztosítja a megfelelő menüpont kontextust
    const currentUrl = new URL(window.location.href);
    const itemId = currentUrl.searchParams.get('Itemid');
    if (itemId) {
        params.append('Itemid', itemId);
    }
    
    // 5. Solidres specifikus paraméterek megőrzése
    // Ezek a paraméterek kritikusak a hub és több szálláshely működéshez
    const preserveParams = ['property_id', 'hub_id', 'site_id', 'reservation_id'];
    preserveParams.forEach(paramName => {
        const value = currentUrl.searchParams.get(paramName);
        if (value) {
            params.append(paramName, value);
        }
    });
    
    // 6. További paraméterek hozzáadása (ha vannak)
    if (additionalParams && typeof additionalParams === 'object') {
        Object.keys(additionalParams).forEach(key => {
            params.append(key, additionalParams[key]);
        });
    }
    
    // 7. AJAX formátum jelzése
    params.append('format', 'json');
    
    // 8. Teljes URL összeállítása
    return baseUrl + '?' + params.toString();
}

/**
 * Redirect URL építő függvény - Teljes path megőrzéssel
 * 
 * Akkor használjuk, amikor sikeres AJAX válasz után átirányításra van szükség.
 * Megtartja az eredeti URL path-ját (beleértve az almenü szegmenseket is).
 * 
 * @param {string} option - Joomla komponens neve
 * @param {string} view - Nézet neve (pl. 'reservationasset')
 * @param {object} additionalParams - További paraméterek
 * @returns {string} Teljes redirect URL
 */
function buildRedirectUrl(option, view, additionalParams = {}) {
    // 1. Teljes path megőrzése (beleértve almenü szegmenseket)
    const basePath = window.location.origin + window.location.pathname;
    
    // 2. URL paraméterek gyűjtése
    const params = new URLSearchParams();
    params.append('option', option);
    if (view) {
        params.append('view', view);
    }
    
    // 3. Itemid és Solidres paraméterek megőrzése
    const currentUrl = new URL(window.location.href);
    const itemId = currentUrl.searchParams.get('Itemid');
    if (itemId) {
        params.append('Itemid', itemId);
    }
    
    const preserveParams = ['property_id', 'hub_id', 'site_id', 'reservation_id'];
    preserveParams.forEach(paramName => {
        const value = currentUrl.searchParams.get(paramName);
        if (value) {
            params.append(paramName, value);
        }
    });
    
    // 4. További paraméterek
    if (additionalParams && typeof additionalParams === 'object') {
        Object.keys(additionalParams).forEach(key => {
            params.append(key, additionalParams[key]);
        });
    }
    
    return basePath + '?' + params.toString();
}

/**
 * AJAX Fetch hívás három szintű hibakezeléssel
 * 
 * Ez a minta függvény bemutatja, hogyan kell helyesen kezelni az AJAX hívásokat,
 * hogy elkerüljük a 404 és más hibákat.
 * 
 * Három szintű hibakezelés:
 * 1. HTTP státusz ellenőrzés (404, 500, stb.)
 * 2. API válasz validálás (success/error flag)
 * 3. Hálózati hibák kezelése (catch blokk)
 * 
 * @param {object} formData - Küldendő adatok
 * @returns {Promise} Fetch promise
 */
function submitGuestForm(formData) {
    // 1. AJAX URL építése abszolút útvonallal
    const ajaxUrl = buildAjaxUrl(
        'com_solidres',          // Komponens
        'reservation.save',       // Feladat
        {
            // További specifikus paraméterek itt
            format: 'json'
        }
    );
    
    console.log('AJAX URL (abszolút):', ajaxUrl);
    
    // 2. Fetch hívás POST metódussal
    return fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'  // AJAX hívás jelzése
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        // ELSŐ SZINT: HTTP státusz ellenőrzés
        console.log('HTTP státusz:', response.status);
        
        if (!response.ok) {
            // 404, 500, stb. hibák kezelése
            throw new Error(`HTTP hiba! Státusz: ${response.status} - ${response.statusText}`);
        }
        
        // JSON válasz feldolgozása
        return response.json();
    })
    .then(data => {
        // MÁSODIK SZINT: API válasz validálás
        console.log('API válasz:', data);
        
        if (data.success === false || data.error) {
            // API szintű hiba (pl. validációs hiba)
            throw new Error(data.message || 'API hiba történt');
        }
        
        // Sikeres válasz esetén
        console.log('Sikeres foglalás!');
        
        // Átirányítás sikeres mentés után (ha szükséges)
        if (data.redirect || data.reservation_id) {
            const redirectUrl = buildRedirectUrl(
                'com_solidres',
                'reservationasset',
                {
                    reservation_id: data.reservation_id
                }
            );
            
            console.log('Átirányítás:', redirectUrl);
            // window.location.href = redirectUrl;  // Kommentezve, csak példa
        }
        
        return data;
    })
    .catch(error => {
        // HARMADIK SZINT: Hálózati és egyéb hibák
        console.error('Fetch hiba:', error);
        
        // Felhasználóbarát hibaüzenet
        alert('Hiba történt a foglalás során: ' + error.message);
        
        throw error;  // Hiba továbbdobása
    });
}

/**
 * Példa használat: Form submit esemény kezelés
 * 
 * Ez a kód demonstrálja, hogyan kell használni a fenti függvényeket
 * egy valódi form feldolgozáshoz.
 */
document.addEventListener('DOMContentLoaded', function() {
    const guestForm = document.getElementById('guest-form');
    
    if (guestForm) {
        guestForm.addEventListener('submit', function(e) {
            e.preventDefault();  // Alapértelmezett form submit megakadályozása
            
            // Form adatok gyűjtése
            const formData = {
                guest_firstname: document.getElementById('guest_firstname')?.value || '',
                guest_lastname: document.getElementById('guest_lastname')?.value || '',
                guest_email: document.getElementById('guest_email')?.value || '',
                guest_phone: document.getElementById('guest_phone')?.value || '',
                // További mezők...
            };
            
            console.log('Form küldése...', formData);
            
            // AJAX hívás indítása
            submitGuestForm(formData)
                .then(result => {
                    console.log('Form sikeresen elküldve:', result);
                    // Sikeres feldolgozás után további műveletek
                })
                .catch(error => {
                    console.error('Form küldési hiba:', error);
                    // Hibaüzenet megjelenítése a felhasználónak
                });
        });
    }
});

/**
 * HASZNÁLATI ÚTMUTATÓ ÉS PÉLDÁK
 * ================================
 * 
 * 1. ALAPVETŐ AJAX HÍVÁS PÉLDA:
 * 
 *    const url = buildAjaxUrl('com_solidres', 'reservation.checkAvailability');
 *    fetch(url, { method: 'GET' })
 *        .then(response => response.json())
 *        .then(data => console.log(data));
 * 
 * 
 * 2. POST KÉRÉS FORM ADATOKKAL:
 * 
 *    const formData = { name: 'Test', email: 'test@example.com' };
 *    submitGuestForm(formData);
 * 
 * 
 * 3. REDIRECT URL ÉPÍTÉSE:
 * 
 *    const redirectUrl = buildRedirectUrl('com_solidres', 'confirmation', {
 *        reservation_id: 123
 *    });
 *    window.location.href = redirectUrl;
 * 
 * 
 * 4. SPECIÁLIS PARAMÉTEREKKEL:
 * 
 *    const url = buildAjaxUrl('com_solidres', 'payment.process', {
 *        payment_method: 'qvik',
 *        amount: 15000
 *    });
 * 
 * 
 * GARANCIA A 404 HIBÁK ELKERÜLÉSÉRE:
 * ===================================
 * 
 * - window.location.origin: Mindig a teljes domain-t adja (http://example.com)
 * - /index.php: Joomla központi belépési pont, mindig elérhető
 * - Itemid megőrzése: Biztosítja a helyes menü kontextust
 * - hub_id, property_id, site_id: Többszállás-helyes és hub kontextus
 * - URLSearchParams: Biztonságos query string építés, automatikus encoding
 * 
 * TESZTELÉS HUB ÉS ALMENÜ KONTEXTUSBAN:
 * ======================================
 * 
 * 1. Főmenü: http://example.com/foglalas
 *    -> buildAjaxUrl eredmény: http://example.com/index.php?option=...
 * 
 * 2. Hub context: http://example.com/hub/budapest
 *    -> buildAjaxUrl eredmény: http://example.com/index.php?option=...&hub_id=1
 * 
 * 3. Almenü: http://example.com/szallasok/foglalasok/vendeg-adatok
 *    -> buildAjaxUrl eredmény: http://example.com/index.php?option=...&Itemid=105
 * 
 * MINDEN esetben az index.php-t használjuk abszolút útvonalként!
 */
</script>

<?php
/**
 * PHP oldali kiegészítések (opcionális)
 * 
 * Ha szeretnél PHP oldalról is előkészíteni valamit az AJAX hívásokhoz:
 */

// Joomla objektumok elérése
$app = JFactory::getApplication();
$doc = JFactory::getDocument();
$input = $app->input;

// Jelenlegi menüpont Itemid-jének lekérése
$itemId = $input->getInt('Itemid', 0);

// Solidres specifikus paraméterek
$propertyId = $input->getInt('property_id', 0);
$hubId = $input->getInt('hub_id', 0);
$siteId = $input->getInt('site_id', 0);

// Adatok átadása JavaScript-nek (ha szükséges)
$doc->addScriptDeclaration("
    // PHP-ből átadott változók JavaScript számára
    var SolidresConfig = {
        itemId: " . $itemId . ",
        propertyId: " . $propertyId . ",
        hubId: " . $hubId . ",
        siteId: " . $siteId . ",
        baseUrl: '" . JURI::root() . "',
        // További config...
    };
");
?>

<!-- HTML Form példa -->
<form id="guest-form" method="post" class="solidres-guest-form">
    <div class="form-group">
        <label for="guest_firstname">Keresztnév *</label>
        <input type="text" id="guest_firstname" name="guest_firstname" required class="form-control">
    </div>
    
    <div class="form-group">
        <label for="guest_lastname">Vezetéknév *</label>
        <input type="text" id="guest_lastname" name="guest_lastname" required class="form-control">
    </div>
    
    <div class="form-group">
        <label for="guest_email">E-mail cím *</label>
        <input type="email" id="guest_email" name="guest_email" required class="form-control">
    </div>
    
    <div class="form-group">
        <label for="guest_phone">Telefonszám *</label>
        <input type="tel" id="guest_phone" name="guest_phone" required class="form-control">
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn btn-primary">
            Foglalás küldése
        </button>
    </div>
</form>

<style>
/* Alapvető stílusok a form-hoz */
.solidres-guest-form {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.btn-primary:hover {
    background-color: #0056b3;
}
</style>
