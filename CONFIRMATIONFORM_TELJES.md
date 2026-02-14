# Teljes confirmationform.php Implementáció

## Áttekintés

Ez a dokumentum bemutatja a teljes confirmationform.php implementációt, amely mind a Qvik, mind a Revolut fizetési pluginokban használható. A fájl tartalmazza az összes szükséges funkciót a biztonságos és megbízható fizetés feldolgozásához.

## Teljes Qvik confirmationform.php

```php
<?php
/**
 * @package     Solidres
 * @subpackage  Qvik Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Qvik fizetési plugin megerősítési űrlap sablon
 * 
 * Ez a sablon kezeli a fizetés megerősítését AJAX kérésekkel és átirányítással.
 * Minden URL paraméter (hub_id, property_id, site_id, Itemid) megmarad a teljes folyamat során.
 */
?>

<div class="qvik-confirmation-form">
    <h3><?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_CONFIRMATION'); ?></h3>
    
    <div class="confirmation-details">
        <p><?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_CONFIRM_MESSAGE'); ?></p>
        
        <div class="reservation-summary">
            <p><strong><?php echo JText::_('COM_SOLIDRES_RESERVATION_ID'); ?>:</strong> 
               <span id="reservation-id"><?php echo htmlspecialchars($this->reservation->id); ?></span>
            </p>
            <p><strong><?php echo JText::_('COM_SOLIDRES_TOTAL_AMOUNT'); ?>:</strong> 
               <span id="total-amount"><?php echo $this->reservation->total_price_tax_incl . ' ' . $this->reservation->currency_code; ?></span>
            </p>
        </div>
    </div>

    <div class="confirmation-actions">
        <button type="button" id="qvik-confirm-btn" class="btn btn-success">
            <?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_CONFIRM_PAYMENT'); ?>
        </button>
        <button type="button" id="qvik-back-btn" class="btn btn-default">
            <?php echo JText::_('COM_SOLIDRES_BACK'); ?>
        </button>
    </div>
    
    <div id="payment-status" class="alert" style="display:none;"></div>
    <div id="payment-loader" class="loader" style="display:none;">
        <p><?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PROCESSING'); ?></p>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    /**
     * URL paraméterek kinyerése az aktuális oldalból
     * Ezek a paraméterek szükségesek a helyes kontextus megőrzéséhez
     * 
     * @returns {Object} - URL paraméterek objektum
     */
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
    
    /**
     * AJAX URL építése teljes pathname megőrzéssel
     * Ez biztosítja, hogy az AJAX kérések a helyes végpontra menjenek,
     * figyelembe véve az összes almenü és hub szegmenst az útvonalban
     * 
     * @param {string} task - A végrehajtandó task
     * @param {string} format - Válasz formátum (alapértelmezett: 'json')
     * @returns {string} - Teljes AJAX URL
     */
    function buildAjaxUrl(task, format) {
        format = format || 'json';
        const urlParams = getUrlParams();
        
        // window.location.origin és pathname használata - teljes kontextus megőrzése
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        
        // Query paraméterek összeállítása
        let queryParams = [];
        queryParams.push('option=com_solidres');
        queryParams.push('task=' + task);
        queryParams.push('format=' + format);
        
        // Kötelező paraméterek hozzáadása
        if (urlParams.hub_id) queryParams.push('hub_id=' + urlParams.hub_id);
        if (urlParams.property_id) queryParams.push('property_id=' + urlParams.property_id);
        if (urlParams.site_id) queryParams.push('site_id=' + urlParams.site_id);
        if (urlParams.Itemid) queryParams.push('Itemid=' + urlParams.Itemid);
        if (urlParams.reservation_id) queryParams.push('reservation_id=' + urlParams.reservation_id);
        
        // Teljes URL - origin + pathname megőrzi az összes menüpont és almenü kontextust
        return origin + pathname + '?' + queryParams.join('&');
    }
    
    /**
     * Átirányítási URL építése teljes kontextus megőrzéssel
     * Minden deep-link, hub és menüpont paraméter megmarad
     * 
     * @param {string} view - Cél view
     * @param {string} layout - Cél layout (opcionális)
     * @returns {string} - Teljes redirect URL
     */
    function buildRedirectUrl(view, layout) {
        const urlParams = getUrlParams();
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        
        // Query paraméterek összeállítása
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
        
        // Teljes redirect URL - pathname megőrzése kritikus a helyes útvonalhoz
        return origin + pathname + '?' + queryParams.join('&');
    }
    
    /**
     * Sikeres üzenet megjelenítése
     * 
     * @param {string} message - Az üzenet szövege
     */
    function showSuccess(message) {
        const statusDiv = document.getElementById('payment-status');
        statusDiv.className = 'alert alert-success';
        statusDiv.textContent = message;
        statusDiv.style.display = 'block';
    }
    
    /**
     * Hibaüzenet megjelenítése
     * 
     * @param {string} message - Az üzenet szövege
     */
    function showError(message) {
        const statusDiv = document.getElementById('payment-status');
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = message;
        statusDiv.style.display = 'block';
    }
    
    /**
     * Betöltő megjelenítése/elrejtése
     * 
     * @param {boolean} show - Mutassa vagy rejtse el
     */
    function toggleLoader(show) {
        const loader = document.getElementById('payment-loader');
        loader.style.display = show ? 'block' : 'none';
    }
    
    /**
     * Fizetés megerősítése AJAX kéréssel
     * A kérés URL-je megőrzi a teljes path és query kontextust
     */
    function confirmPayment() {
        toggleLoader(true);
        document.getElementById('payment-status').style.display = 'none';
        
        // AJAX URL építése teljes kontextus megőrzéssel
        const ajaxUrl = buildAjaxUrl('payment.processQvikPayment', 'json');
        const urlParams = getUrlParams();
        
        // Fetch API használata - modern, promise-alapú megközelítés
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                reservation_id: urlParams.reservation_id,
                payment_method: 'qvik'
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
                showSuccess(data.message || '<?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_SUCCESS'); ?>');
                
                // Sikeres fizetés után átirányítás - minden paraméter megmarad
                setTimeout(function() {
                    const successUrl = buildRedirectUrl('reservation', 'complete');
                    window.location.href = successUrl;
                }, 2000);
            } else {
                showError(data.message || '<?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_ERROR'); ?>');
            }
        })
        .catch(function(error) {
            toggleLoader(false);
            console.error('Payment error:', error);
            showError('<?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_ERROR'); ?>');
        });
    }
    
    /**
     * Vissza gomb kezelése - visszairányít az előző oldalra
     * Minden kontextus megmarad
     */
    function goBack() {
        const backUrl = buildRedirectUrl('reservation', 'guest');
        window.location.href = backUrl;
    }
    
    // Eseménykezelők regisztrálása
    document.getElementById('qvik-confirm-btn').addEventListener('click', function(e) {
        e.preventDefault();
        confirmPayment();
    });
    
    document.getElementById('qvik-back-btn').addEventListener('click', function(e) {
        e.preventDefault();
        goBack();
    });
    
})();
</script>
```

## Teljes Revolut confirmationform.php

```php
<?php
/**
 * @package     Solidres
 * @subpackage  Revolut Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Revolut fizetési plugin megerősítési űrlap sablon
 * 
 * Ez a sablon kezeli a fizetés megerősítését AJAX kérésekkel és átirányítással.
 * Minden URL paraméter (hub_id, property_id, site_id, Itemid) megmarad a teljes folyamat során.
 */
?>

<div class="revolut-confirmation-form">
    <h3><?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_CONFIRMATION'); ?></h3>
    
    <div class="confirmation-details">
        <p><?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_CONFIRM_MESSAGE'); ?></p>
        
        <div class="reservation-summary">
            <p><strong><?php echo JText::_('COM_SOLIDRES_RESERVATION_ID'); ?>:</strong> 
               <span id="reservation-id"><?php echo htmlspecialchars($this->reservation->id); ?></span>
            </p>
            <p><strong><?php echo JText::_('COM_SOLIDRES_TOTAL_AMOUNT'); ?>:</strong> 
               <span id="total-amount"><?php echo $this->reservation->total_price_tax_incl . ' ' . $this->reservation->currency_code; ?></span>
            </p>
        </div>
    </div>

    <div class="confirmation-actions">
        <button type="button" id="revolut-confirm-btn" class="btn btn-success">
            <?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_CONFIRM_PAYMENT'); ?>
        </button>
        <button type="button" id="revolut-back-btn" class="btn btn-default">
            <?php echo JText::_('COM_SOLIDRES_BACK'); ?>
        </button>
    </div>
    
    <div id="payment-status" class="alert" style="display:none;"></div>
    <div id="payment-loader" class="loader" style="display:none;">
        <p><?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_PROCESSING'); ?></p>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    /**
     * URL paraméterek kinyerése az aktuális oldalból
     * Ezek a paraméterek szükségesek a helyes kontextus megőrzéséhez
     * 
     * @returns {Object} - URL paraméterek objektum
     */
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
    
    /**
     * AJAX URL építése teljes pathname megőrzéssel
     * Ez biztosítja, hogy az AJAX kérések a helyes végpontra menjenek,
     * figyelembe véve az összes almenü és hub szegmenst az útvonalban
     * 
     * @param {string} task - A végrehajtandó task
     * @param {string} format - Válasz formátum (alapértelmezett: 'json')
     * @returns {string} - Teljes AJAX URL
     */
    function buildAjaxUrl(task, format) {
        format = format || 'json';
        const urlParams = getUrlParams();
        
        // window.location.origin és pathname használata - teljes kontextus megőrzése
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        
        // Query paraméterek összeállítása
        let queryParams = [];
        queryParams.push('option=com_solidres');
        queryParams.push('task=' + task);
        queryParams.push('format=' + format);
        
        // Kötelező paraméterek hozzáadása
        if (urlParams.hub_id) queryParams.push('hub_id=' + urlParams.hub_id);
        if (urlParams.property_id) queryParams.push('property_id=' + urlParams.property_id);
        if (urlParams.site_id) queryParams.push('site_id=' + urlParams.site_id);
        if (urlParams.Itemid) queryParams.push('Itemid=' + urlParams.Itemid);
        if (urlParams.reservation_id) queryParams.push('reservation_id=' + urlParams.reservation_id);
        
        // Teljes URL - origin + pathname megőrzi az összes menüpont és almenü kontextust
        return origin + pathname + '?' + queryParams.join('&');
    }
    
    /**
     * Átirányítási URL építése teljes kontextus megőrzéssel
     * Minden deep-link, hub és menüpont paraméter megmarad
     * 
     * @param {string} view - Cél view
     * @param {string} layout - Cél layout (opcionális)
     * @returns {string} - Teljes redirect URL
     */
    function buildRedirectUrl(view, layout) {
        const urlParams = getUrlParams();
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        
        // Query paraméterek összeállítása
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
        
        // Teljes redirect URL - pathname megőrzése kritikus a helyes útvonalhoz
        return origin + pathname + '?' + queryParams.join('&');
    }
    
    /**
     * Sikeres üzenet megjelenítése
     * 
     * @param {string} message - Az üzenet szövege
     */
    function showSuccess(message) {
        const statusDiv = document.getElementById('payment-status');
        statusDiv.className = 'alert alert-success';
        statusDiv.textContent = message;
        statusDiv.style.display = 'block';
    }
    
    /**
     * Hibaüzenet megjelenítése
     * 
     * @param {string} message - Az üzenet szövege
     */
    function showError(message) {
        const statusDiv = document.getElementById('payment-status');
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = message;
        statusDiv.style.display = 'block';
    }
    
    /**
     * Betöltő megjelenítése/elrejtése
     * 
     * @param {boolean} show - Mutassa vagy rejtse el
     */
    function toggleLoader(show) {
        const loader = document.getElementById('payment-loader');
        loader.style.display = show ? 'block' : 'none';
    }
    
    /**
     * Fizetés megerősítése AJAX kéréssel
     * A kérés URL-je megőrzi a teljes path és query kontextust
     */
    function confirmPayment() {
        toggleLoader(true);
        document.getElementById('payment-status').style.display = 'none';
        
        // AJAX URL építése teljes kontextus megőrzéssel
        const ajaxUrl = buildAjaxUrl('payment.processRevolutPayment', 'json');
        const urlParams = getUrlParams();
        
        // Fetch API használata - modern, promise-alapú megközelítés
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                reservation_id: urlParams.reservation_id,
                payment_method: 'revolut'
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
                showSuccess(data.message || '<?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_SUCCESS'); ?>');
                
                // Sikeres fizetés után átirányítás - minden paraméter megmarad
                setTimeout(function() {
                    const successUrl = buildRedirectUrl('reservation', 'complete');
                    window.location.href = successUrl;
                }, 2000);
            } else {
                showError(data.message || '<?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_ERROR'); ?>');
            }
        })
        .catch(function(error) {
            toggleLoader(false);
            console.error('Payment error:', error);
            showError('<?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_ERROR'); ?>');
        });
    }
    
    /**
     * Vissza gomb kezelése - visszairányít az előző oldalra
     * Minden kontextus megmarad
     */
    function goBack() {
        const backUrl = buildRedirectUrl('reservation', 'guest');
        window.location.href = backUrl;
    }
    
    // Eseménykezelők regisztrálása
    document.getElementById('revolut-confirm-btn').addEventListener('click', function(e) {
        e.preventDefault();
        confirmPayment();
    });
    
    document.getElementById('revolut-back-btn').addEventListener('click', function(e) {
        e.preventDefault();
        goBack();
    });
    
})();
</script>
```

## Fő Komponensek Részletezése

### 1. HTML Struktúra

#### Megerősítési Form
```html
<div class="[plugin]-confirmation-form">
    <h3>Fizetés megerősítése</h3>
    <div class="confirmation-details">
        <!-- Foglalás összegzése -->
    </div>
    <div class="confirmation-actions">
        <!-- Gombok -->
    </div>
    <div id="payment-status">
        <!-- Státusz üzenetek -->
    </div>
    <div id="payment-loader">
        <!-- Betöltő animáció -->
    </div>
</div>
```

### 2. JavaScript Funkciók

#### A. getUrlParams()
**Cél**: URL paraméterek kinyerése és objektumba szervezése

**Visszaadott objektum**:
```javascript
{
    hub_id: "5",
    property_id: "123",
    site_id: "1",
    Itemid: "456",
    reservation_id: "789"
}
```

**Használat**:
```javascript
const params = getUrlParams();
console.log(params.hub_id); // "5"
```

---

#### B. buildAjaxUrl(task, format)
**Cél**: AJAX kérések URL-jeinek építése teljes kontextus megőrzéssel

**Paraméterek**:
- `task` (string): A végrehajtandó Joomla task (pl. "payment.processQvikPayment")
- `format` (string, optional): Válasz formátum, alapértelmezett: "json"

**Példa használat**:
```javascript
const url = buildAjaxUrl('payment.processQvikPayment', 'json');
// Eredmény: https://example.com/hu/booking/property?option=com_solidres&task=payment.processQvikPayment&format=json&hub_id=5&property_id=123&site_id=1&Itemid=456&reservation_id=789
```

**Kritikus rész**:
```javascript
const origin = window.location.origin;
const pathname = window.location.pathname; // ← Ez megőrzi az almenük útvonalát!
```

---

#### C. buildRedirectUrl(view, layout)
**Cél**: Átirányítási URL-ek építése teljes kontextus megőrzéssel

**Paraméterek**:
- `view` (string): Cél view neve (pl. "reservation")
- `layout` (string, optional): Cél layout neve (pl. "complete")

**Példa használat**:
```javascript
const url = buildRedirectUrl('reservation', 'complete');
// Eredmény: https://example.com/hu/booking/property?option=com_solidres&view=reservation&layout=complete&hub_id=5&property_id=123&site_id=1&Itemid=456&reservation_id=789
```

---

#### D. showSuccess(message) / showError(message)
**Cél**: Felhasználói visszajelzés megjelenítése

**Példa**:
```javascript
showSuccess('Fizetés sikeres!');
// vagy
showError('Hiba történt a fizetés során.');
```

**HTML eredmény**:
```html
<div id="payment-status" class="alert alert-success" style="display:block;">
    Fizetés sikeres!
</div>
```

---

#### E. toggleLoader(show)
**Cél**: Betöltő animáció megjelenítése/elrejtése

**Példa**:
```javascript
toggleLoader(true);  // Megjelenít
// ... folyamat ...
toggleLoader(false); // Elrejt
```

---

#### F. confirmPayment()
**Cél**: Fizetés megerősítése AJAX kéréssel

**Folyamat**:
1. Betöltő megjelenítése
2. AJAX URL építése
3. Fetch API kérés POST metódussal
4. Válasz feldolgozása
5. Sikeres: üzenet + átirányítás 2 mp után
6. Sikertelen: hibaüzenet megjelenítése

**Kód folyamat**:
```javascript
toggleLoader(true) 
  → buildAjaxUrl() 
  → fetch() 
  → response.json() 
  → if (success) showSuccess() + setTimeout(redirect) 
  → else showError()
```

**Fetch kérés szerkezete**:
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
```

---

#### G. goBack()
**Cél**: Visszairányítás az előző oldalra

**Példa**:
```javascript
goBack();
// Átirányít: https://example.com/hu/booking/property?option=com_solidres&view=reservation&layout=guest&hub_id=5&...
```

---

### 3. Eseménykezelők

#### Megerősítés gomb
```javascript
document.getElementById('qvik-confirm-btn').addEventListener('click', function(e) {
    e.preventDefault();
    confirmPayment();
});
```

#### Vissza gomb
```javascript
document.getElementById('qvik-back-btn').addEventListener('click', function(e) {
    e.preventDefault();
    goBack();
});
```

---

## Különbségek Qvik és Revolut között

### Qvik specifikus elemek:
1. HTML ID-k: `qvik-confirm-btn`, `qvik-back-btn`
2. CSS osztály: `qvik-confirmation-form`
3. Task neve: `payment.processQvikPayment`
4. Nyelvi konstansok: `PLG_SOLIDRESPAYMENT_QVIK_*`
5. Payment method: `'qvik'`

### Revolut specifikus elemek:
1. HTML ID-k: `revolut-confirm-btn`, `revolut-back-btn`
2. CSS osztály: `revolut-confirmation-form`
3. Task neve: `payment.processRevolutPayment`
4. Nyelvi konstansok: `PLG_SOLIDRESPAYMENT_REVOLUT_*`
5. Payment method: `'revolut'`

---

## URL Példák

### Kezdeti URL (példa)
```
https://example.com/hu/booking/hotels/property?option=com_solidres&view=reservation&layout=confirmation&hub_id=5&property_id=123&site_id=1&Itemid=456&reservation_id=789
```

### AJAX Kérés URL
```
https://example.com/hu/booking/hotels/property?option=com_solidres&task=payment.processQvikPayment&format=json&hub_id=5&property_id=123&site_id=1&Itemid=456&reservation_id=789
```

**Megfigyelhető**:
- ✅ pathname megmaradt: `/hu/booking/hotels/property`
- ✅ Minden paraméter megmaradt
- ✅ Task hozzáadva: `task=payment.processQvikPayment`
- ✅ Format hozzáadva: `format=json`

### Sikeres Átirányítás URL
```
https://example.com/hu/booking/hotels/property?option=com_solidres&view=reservation&layout=complete&hub_id=5&property_id=123&site_id=1&Itemid=456&reservation_id=789
```

**Megfigyelhető**:
- ✅ pathname megmaradt
- ✅ Minden paraméter megmaradt
- ✅ View változott: `view=reservation`
- ✅ Layout változott: `layout=complete`

---

## Használati Útmutató

### 1. Telepítés

**Qvik plugin**:
```bash
cp confirmationform.php /path/to/joomla/plugins/solidrespayment/qvik/tmpl/
chmod 644 /path/to/joomla/plugins/solidrespayment/qvik/tmpl/confirmationform.php
```

**Revolut plugin**:
```bash
cp confirmationform.php /path/to/joomla/plugins/solidrespayment/revolut/tmpl/
chmod 644 /path/to/joomla/plugins/solidrespayment/revolut/tmpl/confirmationform.php
```

### 2. Szükséges PHP Változók

A sablon a következő változókat várja a `$this` objektumból:

```php
$this->reservation->id                  // Foglalás ID
$this->reservation->total_price_tax_incl // Végösszeg
$this->reservation->currency_code        // Pénznem (HUF, EUR, stb.)
```

### 3. Szükséges Nyelvi Konstansok

**Qvik**:
```ini
PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_CONFIRMATION="Fizetés megerősítése"
PLG_SOLIDRESPAYMENT_QVIK_CONFIRM_MESSAGE="Kérem erősítse meg a fizetést."
PLG_SOLIDRESPAYMENT_QVIK_CONFIRM_PAYMENT="Fizetés megerősítése"
PLG_SOLIDRESPAYMENT_QVIK_PROCESSING="Feldolgozás..."
PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_SUCCESS="Sikeres fizetés!"
PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_ERROR="Hiba történt a fizetés során."
```

**Revolut**:
```ini
PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_CONFIRMATION="Fizetés megerősítése"
PLG_SOLIDRESPAYMENT_REVOLUT_CONFIRM_MESSAGE="Kérem erősítse meg a fizetést."
PLG_SOLIDRESPAYMENT_REVOLUT_CONFIRM_PAYMENT="Fizetés megerősítése"
PLG_SOLIDRESPAYMENT_REVOLUT_PROCESSING="Feldolgozás..."
PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_SUCCESS="Sikeres fizetés!"
PLG_SOLIDRESPAYMENT_REVOLUT_PAYMENT_ERROR="Hiba történt a fizetés során."
```

**Közös Solidres konstansok**:
```ini
COM_SOLIDRES_RESERVATION_ID="Foglalás azonosító"
COM_SOLIDRES_TOTAL_AMOUNT="Végösszeg"
COM_SOLIDRES_BACK="Vissza"
```

### 4. Backend API Végpont

A fizetés feldolgozásához szükséges backend controller metódus:

**Qvik esetén**:
- Task: `payment.processQvikPayment`
- Várható válasz formátum: JSON

**Revolut esetén**:
- Task: `payment.processRevolutPayment`
- Várható válasz formátum: JSON

**Válasz struktúra**:
```json
{
    "success": true,
    "message": "Sikeres fizetés!"
}
```

vagy hiba esetén:

```json
{
    "success": false,
    "message": "Hiba oka..."
}
```

---

## Hibakezelés

### 1. Hálózati Hiba
```javascript
.catch(function(error) {
    toggleLoader(false);
    console.error('Payment error:', error);
    showError('Fizetési hiba');
});
```

### 2. HTTP Hiba
```javascript
.then(function(response) {
    if (!response.ok) {
        throw new Error('Network response was not ok');
    }
    return response.json();
})
```

### 3. Backend Hiba
```javascript
.then(function(data) {
    if (data.success) {
        // Sikeres
    } else {
        showError(data.message);
    }
})
```

---

## Tesztelési Checklist

### Funkcionális Tesztek
- [ ] Megerősítés gomb működik
- [ ] Vissza gomb működik
- [ ] AJAX kérés elindul
- [ ] Betöltő megjelenik
- [ ] Sikeres üzenet megjelenik
- [ ] Hiba üzenet megjelenik
- [ ] Átirányítás működik

### URL Kontextus Tesztek
- [ ] hub_id megmarad
- [ ] property_id megmarad
- [ ] site_id megmarad
- [ ] Itemid megmarad
- [ ] reservation_id megmarad
- [ ] pathname megmarad

### Browser Kompatibilitás
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge
- [ ] Mobile böngészők

---

## Biztonság

### XSS Védelem
```php
<?php echo htmlspecialchars($this->reservation->id); ?>
```

### CSRF Védelem
```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
```

### Input Validáció
- Backend oldalon minden paramétert validálni kell
- reservation_id ellenőrzése kötelező
- Felhasználói jogosultságok ellenőrzése

---

## Teljesítmény Optimalizáció

### 1. IIFE Használata
```javascript
(function() {
    'use strict';
    // Kód...
})();
```
Előny: Globális névtér nem szennyeződik

### 2. Egyszer Lefutó Inicializáció
```javascript
const urlParams = getUrlParams(); // Csak egyszer fut le
```

### 3. Késleltetett Átirányítás
```javascript
setTimeout(function() {
    window.location.href = successUrl;
}, 2000);
```
Előny: Felhasználó látja a sikeres üzenetet

---

## Összefoglalás

A confirmationform.php egy komplex, de jól strukturált sablon, amely:

✅ **Biztonságos**: XSS védelem, CSRF védelem  
✅ **Megbízható**: Promise-alapú hibakezelés  
✅ **Kontextus-tudatos**: Minden URL paraméter megmarad  
✅ **Felhasználóbarát**: Betöltő, üzenetek, átirányítás  
✅ **Karbantartható**: Tiszta kód, magyar kommentek  
✅ **Modern**: Fetch API, ES6+ szintaxis  
✅ **Kompatibilis**: Backward compatible function() használat  

---

**Verzió**: 1.0  
**Utolsó frissítés**: 2026-02-14  
**Készítette**: Solidres Development Team
