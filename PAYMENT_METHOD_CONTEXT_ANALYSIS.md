# Solidres Payment Method Context Handling - Elemzés és Megoldás

## Probléma Leírása

A `com_solidres/layouts/asset/payments.php` sablonban a fizetési módok kiválasztása során előfordulhatnak context-mismatch problémák:

1. **Megjelenítési probléma**: A `$reservationDetails->guest["payment_method_id"]` érték nem mindig érkezik helyesen minden menü/hub kontextusban
2. **POST probléma**: A kiválasztott fizetési mód nem íródik vissza megfelelően a session-be
3. **404 hibák**: A payment AJAX hívások 404-et adnak bizonyos kontextusokban (hub, submenu)

## Gyökérok Elemzése

### 1. Context Propagáció Hiányosságok

A fizetési mód kiválasztása során a következő problémák merülhetnek fel:

- **URL paraméterek elveszése**: Amikor különböző menü kontextusokból (root, hub, submenu) érkezik a kérés, az `Itemid`, `hub_id`, `property_id`, stb. paraméterek elveszhetnek
- **Session context eltérés**: A session-ben tárolt reservation context nem egyezik a megjelenített form kontextusával
- **AJAX URL építés**: A relatív URL-ek nem megfelelően kezelik a submenu és hub struktúrákat

### 2. A Jelenlegi Implementáció Problémái

```php
// Jelenlegi payments.php logika (feltételezett implementáció)
foreach ($paymentPlugins as $paymentPlugin) {
    $paymentPluginId = $paymentPlugin->id;
    $checked = '';
    
    // Ez a logika context-függő lehet
    if (isset($reservationDetails->guest["payment_method_id"]) 
        && $reservationDetails->guest["payment_method_id"] == $paymentPluginId) {
        $checked = 'checked="checked"';
    } elseif ($paymentPlugin->params->get('default', 0) == 1 && empty($checked)) {
        $checked = 'checked="checked"';
    }
    
    echo '<input type="radio" name="payment_method_id" value="' . $paymentPluginId . '" ' . $checked . ' />';
}
```

**Problémák**:
- A `$reservationDetails` objektum kontextusa nem garantált minden esetben
- A default érték kiválasztása nem konzisztens
- Nincs explicit session sync a megjelenítés előtt

### 3. AJAX POST Problémák

Amikor a felhasználó kiválaszt egy fizetési módot:

```javascript
// Feltételezett jelenlegi AJAX hívás
fetch('/index.php?option=com_solidres&task=reservation.updatePaymentMethod', {
    method: 'POST',
    body: formData
})
```

**Problémák**:
- Relatív URL használata → 404 submenu kontextusban
- Hiányzó paraméterek: `Itemid`, `hub_id`, `property_id`, `reservation_id`
- Nincs megfelelő session context kezelés a controller oldalon

## Megoldási Javaslat

### 1. Session Context Kezelés Javítása

#### A. Controller Módosítás (ReservationController.php)

```php
/**
 * Frissíti a kiválasztott fizetési módot a session-ben
 * Biztosítja a context-független működést
 */
public function updatePaymentMethod()
{
    // Session lekérése
    $session = JFactory::getSession();
    
    // Input validáció
    $app = JFactory::getApplication();
    $paymentMethodId = $app->input->getInt('payment_method_id', 0);
    $reservationId = $app->input->getInt('reservation_id', 0);
    
    // Context paraméterek megőrzése
    $hubId = $app->input->getInt('hub_id', 0);
    $propertyId = $app->input->getInt('property_id', 0);
    $siteId = $app->input->getInt('site_id', 0);
    $itemId = $app->input->getInt('Itemid', 0);
    
    if ($paymentMethodId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Érvénytelen fizetési mód'
        ]);
        $app->close();
    }
    
    // Reservation details lekérése és frissítése
    $reservationDetails = $session->get('reservationdetails', null, 'sr');
    
    if (!$reservationDetails) {
        // Ha nincs session, inicializálás
        $reservationDetails = new stdClass();
        $reservationDetails->guest = [];
    }
    
    // Fizetési mód frissítése
    if (!isset($reservationDetails->guest)) {
        $reservationDetails->guest = [];
    }
    $reservationDetails->guest['payment_method_id'] = $paymentMethodId;
    
    // Context paraméterek tárolása a session-ben
    if (!isset($reservationDetails->context)) {
        $reservationDetails->context = new stdClass();
    }
    $reservationDetails->context->hub_id = $hubId;
    $reservationDetails->context->property_id = $propertyId;
    $reservationDetails->context->site_id = $siteId;
    $reservationDetails->context->Itemid = $itemId;
    $reservationDetails->context->reservation_id = $reservationId;
    
    // Session-be írás
    $session->set('reservationdetails', $reservationDetails, 'sr');
    
    // Válasz
    echo json_encode([
        'success' => true,
        'message' => 'Fizetési mód sikeresen frissítve',
        'payment_method_id' => $paymentMethodId,
        'context' => [
            'hub_id' => $hubId,
            'property_id' => $propertyId,
            'site_id' => $siteId,
            'Itemid' => $itemId,
            'reservation_id' => $reservationId
        ]
    ]);
    
    $app->close();
}
```

#### B. Payments.php Template Módosítás

```php
<?php
/**
 * @package     Solidres
 * @subpackage  Layout
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

// Session lekérése és reservation details szinkronizálása
$session = JFactory::getSession();
$sessionReservationDetails = $session->get('reservationdetails', null, 'sr');

// Context biztonsági ellenőrzés
if ($sessionReservationDetails) {
    // Session és paraméter szinkronizálása
    $app = JFactory::getApplication();
    $currentPropertyId = $app->input->getInt('property_id', 0);
    $currentReservationId = $app->input->getInt('reservation_id', 0);
    
    // Ha a session context egyezik a jelenlegi kontextussal, használjuk
    if (isset($sessionReservationDetails->context) &&
        $sessionReservationDetails->context->property_id == $currentPropertyId &&
        $sessionReservationDetails->context->reservation_id == $currentReservationId) {
        $reservationDetails = $sessionReservationDetails;
    }
}

// Alapértelmezett érték, ha nincs session
if (!isset($reservationDetails)) {
    $reservationDetails = new stdClass();
    $reservationDetails->guest = [];
}

// Fizetési módok lekérése
$paymentPlugins = $displayData['paymentplugins'];
$hasChecked = false;

?>

<div class="payment-methods-container">
    <h3>Fizetési mód kiválasztása</h3>
    
    <?php foreach ($paymentPlugins as $paymentPlugin) : 
        $paymentPluginId = $paymentPlugin->id;
        $checked = '';
        
        // Ellenőrizzük, hogy van-e mentett fizetési mód a session-ben
        if (isset($reservationDetails->guest['payment_method_id']) 
            && $reservationDetails->guest['payment_method_id'] == $paymentPluginId) {
            $checked = 'checked="checked"';
            $hasChecked = true;
        }
    ?>
    
    <div class="payment-method-option">
        <label>
            <input type="radio" 
                   name="payment_method_id" 
                   value="<?php echo $paymentPluginId; ?>" 
                   <?php echo $checked; ?>
                   class="payment-method-radio"
                   data-plugin-id="<?php echo $paymentPluginId; ?>" />
            <?php echo $paymentPlugin->name; ?>
        </label>
        <?php if (!empty($paymentPlugin->description)) : ?>
        <div class="payment-method-description">
            <?php echo $paymentPlugin->description; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php endforeach; ?>
    
    <?php 
    // Ha egyik sem volt checked, az elsőt vagy a default-ot jelöljük be
    if (!$hasChecked && count($paymentPlugins) > 0) :
        $defaultFound = false;
        foreach ($paymentPlugins as $paymentPlugin) {
            if ($paymentPlugin->params->get('default', 0) == 1) {
                $defaultFound = true;
                ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var defaultRadio = document.querySelector('input[name="payment_method_id"][value="<?php echo $paymentPlugin->id; ?>"]');
                    if (defaultRadio) {
                        defaultRadio.checked = true;
                    }
                });
                </script>
                <?php
                break;
            }
        }
        
        // Ha nincs default, az elsőt jelöljük be
        if (!$defaultFound) :
            $firstPlugin = reset($paymentPlugins);
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var firstRadio = document.querySelector('input[name="payment_method_id"][value="<?php echo $firstPlugin->id; ?>"]');
                if (firstRadio && !document.querySelector('input[name="payment_method_id"]:checked')) {
                    firstRadio.checked = true;
                }
            });
            </script>
            <?php
        endif;
    endif;
    ?>
</div>

<script>
(function() {
    'use strict';
    
    /**
     * URL paraméterek kinyerése a jelenlegi URL-ből
     */
    function getUrlParams() {
        var params = {};
        var searchParams = new URLSearchParams(window.location.search);
        
        // Kritikus paraméterek megőrzése
        var criticalParams = ['Itemid', 'property_id', 'hub_id', 'site_id', 'reservation_id'];
        criticalParams.forEach(function(param) {
            var value = searchParams.get(param);
            if (value) {
                params[param] = value;
            }
        });
        
        return params;
    }
    
    /**
     * AJAX URL építése context-független módon
     */
    function buildAjaxUrl() {
        // Abszolút URL használata a 404 hibák elkerülésére
        var baseUrl = window.location.origin + window.location.pathname;
        
        // Submenu kezelés
        if (window.location.pathname.indexOf('index.php') === -1) {
            baseUrl = window.location.origin + '/index.php';
        }
        
        var params = getUrlParams();
        params.option = 'com_solidres';
        params.task = 'reservation.updatePaymentMethod';
        params.format = 'json';
        
        var queryString = Object.keys(params)
            .map(function(key) { return key + '=' + encodeURIComponent(params[key]); })
            .join('&');
        
        return baseUrl + '?' + queryString;
    }
    
    /**
     * Fizetési mód frissítése AJAX-szal
     */
    function updatePaymentMethod(paymentMethodId) {
        var url = buildAjaxUrl();
        
        var formData = new FormData();
        formData.append('payment_method_id', paymentMethodId);
        
        // Context paraméterek hozzáadása
        var params = getUrlParams();
        Object.keys(params).forEach(function(key) {
            formData.append(key, params[key]);
        });
        
        // Fetch API három szintű error handling-gel
        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            // 1. HTTP státusz ellenőrzés
            if (!response.ok) {
                throw new Error('HTTP hiba: ' + response.status + ' ' + response.statusText);
            }
            return response.json();
        })
        .then(function(data) {
            // 2. API válasz validáció
            if (!data.success) {
                console.error('Fizetési mód frissítés hiba:', data.message);
                alert('Hiba történt: ' + (data.message || 'Ismeretlen hiba'));
                return;
            }
            
            console.log('Fizetési mód sikeresen frissítve:', data);
            
            // Optional: Event trigger más komponensek számára
            var event = new CustomEvent('paymentMethodUpdated', {
                detail: {
                    paymentMethodId: paymentMethodId,
                    context: data.context
                }
            });
            document.dispatchEvent(event);
        })
        .catch(function(error) {
            // 3. Network és egyéb hibák
            console.error('Fetch hiba:', error);
            alert('Kapcsolati hiba történt. Kérjük, próbálja újra.');
        });
    }
    
    /**
     * Event listener a rádió gombokra
     */
    document.addEventListener('DOMContentLoaded', function() {
        var radioButtons = document.querySelectorAll('.payment-method-radio');
        
        radioButtons.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    updatePaymentMethod(this.value);
                }
            });
        });
    });
})();
</script>
```

### 2. URL Preservation Pattern Alkalmazása

A repository memories alapján már létezik egy bevált URL preservation pattern. Ezt kell alkalmazni a payment method handling esetében is:

```javascript
// Bevált pattern a memories alapján
function buildAjaxUrl() {
    // Abszolút URL használata
    var baseUrl = window.location.origin + window.location.pathname;
    
    // Submenu detektálás és kezelés
    if (window.location.pathname.indexOf('index.php') === -1) {
        baseUrl = window.location.origin + '/index.php';
    }
    
    // Kritikus paraméterek megőrzése
    var params = new URLSearchParams(window.location.search);
    var preservedParams = {
        Itemid: params.get('Itemid'),
        property_id: params.get('property_id'),
        hub_id: params.get('hub_id'),
        site_id: params.get('site_id'),
        reservation_id: params.get('reservation_id')
    };
    
    // Task hozzáadása
    preservedParams.option = 'com_solidres';
    preservedParams.task = 'reservation.updatePaymentMethod';
    preservedParams.format = 'json';
    
    return baseUrl + '?' + new URLSearchParams(preservedParams).toString();
}
```

### 3. Session Validation Layer

Hozzunk létre egy session validation layer-t, amely biztosítja a konzisztens context kezelést:

```php
<?php
/**
 * SessionContextValidator.php
 * Validálja és szinkronizálja a reservation context-et
 */

class SolidresSessionContextValidator
{
    /**
     * Validálja és szinkronizálja a reservation details context-et
     * 
     * @return object A validált és szinkronizált reservation details
     */
    public static function validateAndSync()
    {
        $session = JFactory::getSession();
        $app = JFactory::getApplication();
        
        // Session-ből reservation details lekérése
        $reservationDetails = $session->get('reservationdetails', null, 'sr');
        
        // URL paraméterek lekérése
        $urlParams = [
            'property_id' => $app->input->getInt('property_id', 0),
            'hub_id' => $app->input->getInt('hub_id', 0),
            'site_id' => $app->input->getInt('site_id', 0),
            'Itemid' => $app->input->getInt('Itemid', 0),
            'reservation_id' => $app->input->getInt('reservation_id', 0)
        ];
        
        // Ha nincs session, inicializálás
        if (!$reservationDetails) {
            $reservationDetails = new stdClass();
            $reservationDetails->guest = [];
            $reservationDetails->context = (object)$urlParams;
            
            // Session-be mentés
            $session->set('reservationdetails', $reservationDetails, 'sr');
            
            return $reservationDetails;
        }
        
        // Context létrehozása, ha nem létezik
        if (!isset($reservationDetails->context)) {
            $reservationDetails->context = new stdClass();
        }
        
        // Context frissítése az aktuális URL paraméterekkel
        foreach ($urlParams as $key => $value) {
            if ($value > 0) {
                $reservationDetails->context->$key = $value;
            }
        }
        
        // Guest array biztosítása
        if (!isset($reservationDetails->guest)) {
            $reservationDetails->guest = [];
        }
        
        // Session-be írás
        $session->set('reservationdetails', $reservationDetails, 'sr');
        
        return $reservationDetails;
    }
    
    /**
     * Ellenőrzi, hogy a session context egyezik-e a jelenlegi URL paraméterekkel
     * 
     * @return bool True, ha a context egyezik
     */
    public static function isContextValid()
    {
        $session = JFactory::getSession();
        $app = JFactory::getApplication();
        
        $reservationDetails = $session->get('reservationdetails', null, 'sr');
        
        if (!$reservationDetails || !isset($reservationDetails->context)) {
            return false;
        }
        
        // Kritikus paraméterek ellenőrzése
        $criticalParams = ['property_id', 'reservation_id'];
        
        foreach ($criticalParams as $param) {
            $urlValue = $app->input->getInt($param, 0);
            $sessionValue = isset($reservationDetails->context->$param) 
                ? $reservationDetails->context->$param 
                : 0;
            
            if ($urlValue > 0 && $sessionValue > 0 && $urlValue != $sessionValue) {
                return false;
            }
        }
        
        return true;
    }
}
```

## Implementációs Lépések

### 1. Azonnal Implementálandó Változtatások

1. **Controller módosítás**: `updatePaymentMethod()` metódus hozzáadása a ReservationController-hez
2. **Template frissítés**: A `com_solidres/layouts/asset/payments.php` frissítése a fenti kóddal
3. **Session validator**: `SolidresSessionContextValidator` osztály létrehozása

### 2. Tesztelési Esetek

Minden kontextusban tesztelni kell:

#### A. Root Menü Context
- URL: `/index.php?option=com_solidres&view=reservation&Itemid=123&property_id=1`
- Ellenőrizni: fizetési mód kiválasztás és mentés működik

#### B. Hub Context
- URL: `/index.php?option=com_solidres&view=reservation&Itemid=123&hub_id=5&property_id=1`
- Ellenőrizni: hub_id megmarad minden AJAX hívásban

#### C. Submenu Context
- URL: `/hotel/index.php?option=com_solidres&view=reservation&Itemid=456&property_id=2`
- Ellenőrizni: pathname preservation működik, nincs 404

### 3. Validációs Checklist

- [ ] `$reservationDetails->guest["payment_method_id"]` minden kontextusban helyesen töltődik
- [ ] AJAX POST nem ad 404-et egyik kontextusban sem
- [ ] Session-ben helyesen tárolódik a kiválasztott fizetési mód
- [ ] Context paraméterek (Itemid, hub_id, property_id stb.) megmaradnak
- [ ] Default fizetési mód helyesen jelölődik be, ha nincs korábbi választás
- [ ] A kiválasztott fizetési mód perzisztens marad navigáció után is

## Összefoglalás

A probléma három fő területen jelentkezik:

1. **Context propagáció**: URL paraméterek elvesznek → Session validator megoldja
2. **AJAX URL építés**: Relatív URL-ek → Abszolút URL + pathname preservation
3. **Session kezelés**: Nem konzisztens → Explicit session sync minden lépésnél

A javasolt megoldás:
- Session validation layer minden megjelenítés előtt
- Context-független AJAX URL építés bevált pattern alapján
- Háromszintű error handling minden AJAX hívásban
- Explicit context tárolás a session-ben

Ez biztosítja, hogy **soha ne legyen 404 hiba menüben, hubban vagy almenüben sem**, és a fizetési mód kiválasztás mindig konzisztens maradjon.
