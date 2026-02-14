<?php
/**
 * Qvik Fizetési Plugin - Confirmation Form Template
 * 
 * Ez a fájl tartalmazza a véglegesített JS fetch+redirect logikát,
 * amely biztosítja a helyes működést minden menüpont, almenü és 
 * hub/multiszálláshely esetében, 404 hibák elkerülésével.
 */

defined('_JEXEC') or die;
?>

<div id="sr-qvik-payment-form">
    <!-- Qvik fizetési form tartalma -->
    <div class="alert alert-info">
        <p><?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PROCESSING_PAYMENT'); ?></p>
    </div>
    
    <button id="qvik-pay-button" class="btn btn-primary">
        <?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PAY_NOW'); ?>
    </button>
</div>

<script type="text/javascript">
(function() {
    'use strict';
    
    /**
     * Teljes URL felépítése az aktuális útvonal megőrzésével
     * 
     * Ez a függvény biztosítja, hogy minden esetben megmaradjon:
     * - A teljes elérési útvonal (pathname), beleértve az almenüket is
     * - Az eredeti origin (protocol + domain + port)
     * 
     * @returns {string} Teljes alap URL
     */
    function buildBaseUrl() {
        // Az origin tartalmazza: protocol + domain + port
        // pl. "https://example.com" vagy "http://localhost:8080"
        var origin = window.location.origin;
        
        // A pathname tartalmazza a teljes elérési utat, beleértve:
        // - Almenüket: /hu/szallasok/apartmanok
        // - Hub struktúrát: /hub/property-name
        // - Egyéb útvonal szegmenseket
        var pathname = window.location.pathname;
        
        // Visszaadjuk a teljes URL-t minden szegmenssel
        return origin + pathname;
    }
    
    /**
     * AJAX URL felépítése a Solidres API hívásokhoz
     * 
     * Ez a függvény épít fel egy teljesen kvalifikált URL-t az AJAX kérésekhez,
     * megőrizve minden fontos paramétert és útvonal komponenst.
     * 
     * @param {string} task - A végrehajtandó Solidres task
     * @param {object} additionalParams - További URL paraméterek (opcionális)
     * @returns {string} Teljes AJAX URL
     */
    function buildAjaxUrl(task, additionalParams) {
        // Kiindulási URL - teljes elérési úttal
        var baseUrl = buildBaseUrl();
        
        // Új URL objektum létrehozása a paraméterek kezeléséhez
        var url = new URL(baseUrl);
        var params = url.searchParams;
        
        // Alapvető Joomla/Solidres paraméterek
        params.set('option', 'com_solidres');
        params.set('task', task);
        params.set('format', 'json');
        
        // Fontos paraméterek megőrzése az aktuális URL-ből
        // Ezek kritikusak a helyes működéshez:
        
        // Itemid - Joomla menüpont azonosító (szükséges az útvonal megőrzéséhez)
        var currentParams = new URLSearchParams(window.location.search);
        if (currentParams.has('Itemid')) {
            params.set('Itemid', currentParams.get('Itemid'));
        }
        
        // property_id - Szálláshely azonosító
        if (currentParams.has('property_id')) {
            params.set('property_id', currentParams.get('property_id'));
        }
        
        // hub_id - Hub/multiszálláshely azonosító
        if (currentParams.has('hub_id')) {
            params.set('hub_id', currentParams.get('hub_id'));
        }
        
        // site_id - Oldal azonosító (több nyelv/site esetén)
        if (currentParams.has('site_id')) {
            params.set('site_id', currentParams.get('site_id'));
        }
        
        // reservation_id - Foglalás azonosító
        if (currentParams.has('reservation_id')) {
            params.set('reservation_id', currentParams.get('reservation_id'));
        }
        
        // További paraméterek hozzáadása, ha vannak
        if (additionalParams && typeof additionalParams === 'object') {
            for (var key in additionalParams) {
                if (additionalParams.hasOwnProperty(key)) {
                    params.set(key, additionalParams[key]);
                }
            }
        }
        
        return url.toString();
    }
    
    /**
     * Redirect URL felépítése sikeres/sikertelen fizetés után
     * 
     * Ez a függvény biztosítja, hogy a visszairányítás után is
     * megmaradjon a teljes kontextus (menüpont, almenü, hub stb.)
     * 
     * @param {string} view - A Solidres view neve (pl. 'reservationdetails', 'reservationcancelled')
     * @param {object} additionalParams - További URL paraméterek (opcionális)
     * @returns {string} Teljes redirect URL
     */
    function buildRedirectUrl(view, additionalParams) {
        // Kiindulási URL - teljes elérési úttal
        var baseUrl = buildBaseUrl();
        
        // Új URL objektum létrehozása
        var url = new URL(baseUrl);
        var params = url.searchParams;
        
        // Alapvető Joomla/Solidres paraméterek
        params.set('option', 'com_solidres');
        params.set('view', view);
        
        // Fontos paraméterek megőrzése
        var currentParams = new URLSearchParams(window.location.search);
        
        // Itemid megőrzése - KRITIKUS a menüpont kontextushoz
        if (currentParams.has('Itemid')) {
            params.set('Itemid', currentParams.get('Itemid'));
        }
        
        // property_id megőrzése
        if (currentParams.has('property_id')) {
            params.set('property_id', currentParams.get('property_id'));
        }
        
        // hub_id megőrzése - fontos multi-property esetén
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
        
        // További paraméterek hozzáadása
        if (additionalParams && typeof additionalParams === 'object') {
            for (var key in additionalParams) {
                if (additionalParams.hasOwnProperty(key)) {
                    params.set(key, additionalParams[key]);
                }
            }
        }
        
        return url.toString();
    }
    
    /**
     * Qvik fizetés inicializálása Fetch API-val
     * 
     * Ez a függvény kezeli a Qvik fizetési folyamat elindítását,
     * promise-alapú hibakezeléssel.
     */
    function initiateQvikPayment() {
        // Gomb letiltása dupla kattintás ellen
        var payButton = document.getElementById('qvik-pay-button');
        if (payButton) {
            payButton.disabled = true;
            payButton.textContent = 'Feldolgozás...';
        }
        
        // AJAX URL felépítése a Qvik fizetés inicializálásához
        var ajaxUrl = buildAjaxUrl('payment.initiate', {
            payment_method: 'qvik'
        });
        
        // Fetch API használata promise-alapú hibakezeléssel
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            // CSRF token küldése, ha szükséges
            credentials: 'same-origin'
        })
        .then(function(response) {
            // HTTP státusz ellenőrzése
            if (!response.ok) {
                throw new Error('HTTP hiba: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            // Sikeres válasz kezelése
            if (data.success && data.payment_url) {
                // Átirányítás a Qvik fizetési oldalra
                window.location.href = data.payment_url;
            } else {
                // Sikertelen fizetés inicializálás
                var errorMsg = data.message || 'Ismeretlen hiba történt';
                alert('Fizetési hiba: ' + errorMsg);
                
                // Átirányítás a megszakított foglalás oldalra
                var cancelUrl = buildRedirectUrl('reservationcancelled', {
                    error: 'payment_failed'
                });
                window.location.href = cancelUrl;
            }
        })
        .catch(function(error) {
            // Hálózati vagy egyéb hiba kezelése
            console.error('Qvik fizetési hiba:', error);
            alert('Hiba történt a fizetés során. Kérjük, próbálja újra később.');
            
            // Gomb újra engedélyezése
            if (payButton) {
                payButton.disabled = false;
                payButton.textContent = '<?php echo JText::_('PLG_SOLIDRESPAYMENT_QVIK_PAY_NOW'); ?>';
            }
        });
    }
    
    /**
     * Qvik callback kezelése sikeres fizetés után
     * 
     * Ez a függvény fut le, amikor a felhasználó visszatér
     * a Qvik fizetési oldalról.
     */
    function handleQvikCallback() {
        // URL paraméterek ellenőrzése
        var currentParams = new URLSearchParams(window.location.search);
        
        // Ellenőrizzük, hogy Qvik callback-ről van-e szó
        if (currentParams.has('qvik_status') || currentParams.has('payment_status')) {
            var status = currentParams.get('qvik_status') || currentParams.get('payment_status');
            var transactionId = currentParams.get('transaction_id');
            
            // AJAX hívás a fizetés státuszának megerősítésére
            var verifyUrl = buildAjaxUrl('payment.verify', {
                payment_method: 'qvik',
                transaction_id: transactionId,
                status: status
            });
            
            fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.verified) {
                    // Sikeres fizetés - átirányítás a foglalás részleteihez
                    var successUrl = buildRedirectUrl('reservationdetails', {
                        reservation_id: data.reservation_id,
                        payment_success: '1'
                    });
                    window.location.href = successUrl;
                } else {
                    // Sikertelen ellenőrzés
                    var failUrl = buildRedirectUrl('reservationcancelled', {
                        error: 'verification_failed'
                    });
                    window.location.href = failUrl;
                }
            })
            .catch(function(error) {
                console.error('Qvik callback hiba:', error);
                // Átirányítás hibaoldalra
                var errorUrl = buildRedirectUrl('reservationcancelled', {
                    error: 'callback_error'
                });
                window.location.href = errorUrl;
            });
        }
    }
    
    // Event listener hozzáadása a fizetési gombhoz
    document.addEventListener('DOMContentLoaded', function() {
        var payButton = document.getElementById('qvik-pay-button');
        if (payButton) {
            payButton.addEventListener('click', function(e) {
                e.preventDefault();
                initiateQvikPayment();
            });
        }
        
        // Callback kezelése oldal betöltéskor
        handleQvikCallback();
    });
    
})();
</script>
