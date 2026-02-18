<?php
/**
 * @package     Solidres
 * @subpackage  Layout
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * 
 * PATCH FILE - Fizetési módok megjelenítése context-független módon
 * Fájl: com_solidres/layouts/asset/payments.php
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
        isset($sessionReservationDetails->context->property_id) &&
        isset($sessionReservationDetails->context->reservation_id) &&
        $sessionReservationDetails->context->property_id == $currentPropertyId &&
        $sessionReservationDetails->context->reservation_id == $currentReservationId) {
        $reservationDetails = $sessionReservationDetails;
    }
}

// Alapértelmezett érték, ha nincs megfelelő session
if (!isset($reservationDetails)) {
    $reservationDetails = new stdClass();
    $reservationDetails->guest = [];
}

// Fizetési módok lekérése
$paymentPlugins = isset($displayData['paymentplugins']) ? $displayData['paymentplugins'] : [];
$hasChecked = false;

?>

<div class="payment-methods-container" id="solidres-payment-methods">
    <h3><?php echo JText::_('SR_PAYMENT_METHOD_SELECTION'); ?></h3>
    
    <?php if (empty($paymentPlugins)) : ?>
        <p class="alert alert-warning"><?php echo JText::_('SR_NO_PAYMENT_METHODS_AVAILABLE'); ?></p>
    <?php else : ?>
    
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
    
    <div class="payment-method-option form-check">
        <label class="form-check-label">
            <input type="radio" 
                   name="payment_method_id" 
                   value="<?php echo (int)$paymentPluginId; ?>" 
                   <?php echo $checked; ?>
                   class="payment-method-radio form-check-input"
                   data-plugin-id="<?php echo (int)$paymentPluginId; ?>" />
            <span class="payment-method-name"><?php echo htmlspecialchars($paymentPlugin->name); ?></span>
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
        $defaultPluginId = null;
        
        // Keresés default plugin után
        foreach ($paymentPlugins as $paymentPlugin) {
            if ($paymentPlugin->params->get('default', 0) == 1) {
                $defaultFound = true;
                $defaultPluginId = $paymentPlugin->id;
                break;
            }
        }
        
        // Ha nincs default, az elsőt használjuk
        if (!$defaultFound) {
            $firstPlugin = reset($paymentPlugins);
            $defaultPluginId = $firstPlugin->id;
        }
        ?>
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                var defaultRadio = document.querySelector('input[name="payment_method_id"][value="<?php echo (int)$defaultPluginId; ?>"]');
                if (defaultRadio && !document.querySelector('input[name="payment_method_id"]:checked')) {
                    defaultRadio.checked = true;
                    // Trigger change event to save default selection
                    if (typeof Event === 'function') {
                        defaultRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        // IE11 compatibility
                        var evt = document.createEvent('Event');
                        evt.initEvent('change', true, true);
                        defaultRadio.dispatchEvent(evt);
                    }
                }
            });
        })();
        </script>
        <?php
    endif;
    ?>
    
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';
    
    /**
     * URL paraméterek kinyerése a jelenlegi URL-ből
     * Biztosítja a kritikus context paraméterek megőrzését
     */
    function getUrlParams() {
        var params = {};
        var searchParams = new URLSearchParams(window.location.search);
        
        // Kritikus paraméterek megőrzése (repository memories alapján)
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
     * Bevált pattern a repository memories alapján
     */
    function buildAjaxUrl() {
        // Abszolút URL használata a 404 hibák elkerülésére
        // Pattern: window.location.origin + window.location.pathname
        var baseUrl = window.location.origin + window.location.pathname;
        
        // Submenu detektálás és kezelés
        // Ha a pathname nem tartalmazza az index.php-t, akkor a root-hoz kell menni
        if (window.location.pathname.indexOf('index.php') === -1) {
            baseUrl = window.location.origin + '/index.php';
        }
        
        // URL paraméterek összegyűjtése
        var params = getUrlParams();
        params.option = 'com_solidres';
        params.task = 'reservation.updatePaymentMethod';
        params.format = 'json';
        
        // Query string összeállítása
        var queryString = Object.keys(params)
            .map(function(key) { 
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]); 
            })
            .join('&');
        
        return baseUrl + '?' + queryString;
    }
    
    /**
     * Fizetési mód frissítése AJAX-szal
     * Háromszintű error handling pattern alkalmazása
     */
    function updatePaymentMethod(paymentMethodId) {
        var url = buildAjaxUrl();
        
        console.log('Payment method update requested:', {
            paymentMethodId: paymentMethodId,
            url: url
        });
        
        var formData = new FormData();
        formData.append('payment_method_id', paymentMethodId);
        
        // Context paraméterek hozzáadása
        var params = getUrlParams();
        Object.keys(params).forEach(function(key) {
            formData.append(key, params[key]);
        });
        
        // Fetch API három szintű error handling-gel
        // Pattern: repository memories alapján
        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
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
                alert('Hiba történt a fizetési mód mentésekor: ' + (data.message || 'Ismeretlen hiba'));
                return;
            }
            
            console.log('Fizetési mód sikeresen frissítve:', data);
            
            // Optional: Event trigger más komponensek számára
            if (typeof CustomEvent === 'function') {
                var event = new CustomEvent('paymentMethodUpdated', {
                    detail: {
                        paymentMethodId: paymentMethodId,
                        context: data.context || {}
                    }
                });
                document.dispatchEvent(event);
            }
        })
        .catch(function(error) {
            // 3. Network és egyéb hibák
            console.error('Fetch hiba a fizetési mód frissítésekor:', error);
            alert('Kapcsolati hiba történt. Kérjük, próbálja újra később.');
        });
    }
    
    /**
     * Event listener inicializálás
     */
    function initPaymentMethodHandlers() {
        var radioButtons = document.querySelectorAll('.payment-method-radio');
        
        if (radioButtons.length === 0) {
            console.warn('Nem találhatók fizetési mód választók');
            return;
        }
        
        radioButtons.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    var paymentMethodId = this.value;
                    console.log('Fizetési mód kiválasztva:', paymentMethodId);
                    updatePaymentMethod(paymentMethodId);
                }
            });
        });
        
        console.log('Fizetési mód event handlers inicializálva (' + radioButtons.length + ' elem)');
    }
    
    /**
     * Inicializálás DOMContentLoaded után
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPaymentMethodHandlers);
    } else {
        // DOM már betöltött
        initPaymentMethodHandlers();
    }
})();
</script>
