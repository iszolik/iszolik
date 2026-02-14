<?php
/**
 * @package     Solidres
 * @subpackage  Qvik Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Qvik fizetési plugin - Megerősítő űrlap sablon
 * 
 * Ez a fájl tartalmazza a fizetés véglegesítő űrlapját, amely a Qvik fizetési
 * gateway-hez küldi el az adatokat. A JavaScript kód biztosítja a helyes
 * fetch/redirect logikát minden menüpont alatt (almenü/hub/multisite).
 * 
 * Kulcs funkcionalitások:
 * - Teljes útvonal kontextus megőrzése (origin + pathname)
 * - Fetch API promise-alapú hibakezeléssel
 * - Minden paraméter megőrzése (hub_id, property_id, site_id, Itemid, reservation_id)
 * - 404 hibák és útvonalvesztés elkerülése
 */

// Fontos URL paraméterek kinyerése
$hubId          = $this->app->input->getInt('hub_id', 0);
$propertyId     = $this->app->input->getInt('property_id', 0);
$siteId         = $this->app->input->getInt('site_id', 0);
$itemId         = $this->app->input->getInt('Itemid', 0);
$reservationId  = $this->app->input->getInt('reservation_id', 0);

// Foglalási adatok betöltése
$reservation = $this->reservation;
$customer = $this->customer;
$totalAmount = $this->totalAmount;

?>
<div class="qvik-payment-confirmation">
    <h2><?php echo JText::_('COM_SOLIDRES_PAYMENT_CONFIRMATION'); ?></h2>
    
    <!-- Foglalás összefoglaló -->
    <div class="reservation-summary">
        <h3><?php echo JText::_('COM_SOLIDRES_RESERVATION_SUMMARY'); ?></h3>
        <table class="table table-striped">
            <tr>
                <td><?php echo JText::_('COM_SOLIDRES_RESERVATION_ID'); ?>:</td>
                <td><strong>#<?php echo $reservationId; ?></strong></td>
            </tr>
            <tr>
                <td><?php echo JText::_('COM_SOLIDRES_CUSTOMER_NAME'); ?>:</td>
                <td><?php echo htmlspecialchars($customer->firstname . ' ' . $customer->lastname); ?></td>
            </tr>
            <tr>
                <td><?php echo JText::_('COM_SOLIDRES_CUSTOMER_EMAIL'); ?>:</td>
                <td><?php echo htmlspecialchars($customer->email); ?></td>
            </tr>
            <tr>
                <td><?php echo JText::_('COM_SOLIDRES_TOTAL_AMOUNT'); ?>:</td>
                <td><strong><?php echo number_format($totalAmount, 2) . ' ' . $this->currency; ?></strong></td>
            </tr>
        </table>
    </div>
    
    <!-- Fizetési űrlap -->
    <form id="qvikPaymentForm" method="post" action="">
        <!-- Rejtett mezők a Qvik gateway számára -->
        <input type="hidden" name="merchant_id" value="<?php echo $this->params->get('merchant_id'); ?>" />
        <input type="hidden" name="amount" value="<?php echo $totalAmount; ?>" />
        <input type="hidden" name="currency" value="<?php echo $this->currency; ?>" />
        <input type="hidden" name="order_id" value="<?php echo $reservationId; ?>" />
        <input type="hidden" name="customer_email" value="<?php echo $customer->email; ?>" />
        
        <!-- Paraméterek megőrzése -->
        <input type="hidden" name="hub_id" id="hubId" value="<?php echo $hubId; ?>" />
        <input type="hidden" name="property_id" id="propertyId" value="<?php echo $propertyId; ?>" />
        <input type="hidden" name="site_id" id="siteId" value="<?php echo $siteId; ?>" />
        <input type="hidden" name="Itemid" id="itemId" value="<?php echo $itemId; ?>" />
        <input type="hidden" name="reservation_id" id="reservationId" value="<?php echo $reservationId; ?>" />
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg" id="paymentBtn">
                <?php echo JText::_('COM_SOLIDRES_PAY_NOW'); ?>
            </button>
            <button type="button" class="btn btn-default" onclick="history.back()">
                <?php echo JText::_('COM_SOLIDRES_BACK'); ?>
            </button>
        </div>
    </form>
    
    <div id="paymentStatus" class="alert" style="display: none;"></div>
</div>

<script type="text/javascript">
(function() {
    'use strict';
    
    /**
     * AJAX URL építő függvény
     * 
     * Ez a függvény felelős azért, hogy minden AJAX kérés URL-je tartalmazza:
     * - A teljes origin-t (window.location.origin)
     * - A teljes pathname-et (window.location.pathname) - ez magában foglalja
     *   az összes almenü szegmenst is (pl. /submenu1/submenu2/index.php)
     * - Az összes szükséges URL paramétert
     * 
     * Ez biztosítja, hogy multisite, hub és almenü környezetekben is helyesen
     * működjön a fizetési folyamat, és ne legyen 404 vagy útvonalvesztés.
     */
    function buildAjaxUrl() {
        // Teljes útvonal kontextus megőrzése
        // Az origin tartalmazza: protocol + domain + port
        // A pathname tartalmazza: minden útvonal szegmenst (beleértve almenüket)
        var baseUrl = window.location.origin + window.location.pathname;
        
        // Paraméterek kinyerése az űrlapból
        var hubId = document.getElementById('hubId').value;
        var propertyId = document.getElementById('propertyId').value;
        var siteId = document.getElementById('siteId').value;
        var itemId = document.getElementById('itemId').value;
        var reservationId = document.getElementById('reservationId').value;
        
        // URLSearchParams használata a paraméterek helyes kezeléséhez
        var params = new URLSearchParams();
        params.append('option', 'com_solidres');
        params.append('task', 'reservationasset.processPayment');
        params.append('format', 'json'); // JSON válasz kérése
        
        // Paraméterek hozzáadása, ha értékük nem 0
        // Ez fontos, mert multisite környezetben ezek határozzák meg a kontextust
        if (hubId && hubId !== '0') {
            params.append('hub_id', hubId);
        }
        if (propertyId && propertyId !== '0') {
            params.append('property_id', propertyId);
        }
        if (siteId && siteId !== '0') {
            params.append('site_id', siteId);
        }
        if (itemId && itemId !== '0') {
            params.append('Itemid', itemId);
        }
        if (reservationId && reservationId !== '0') {
            params.append('reservation_id', reservationId);
        }
        
        return baseUrl + '?' + params.toString();
    }
    
    /**
     * Átirányítási URL építő függvény
     * 
     * Hasonló logika, mint az AJAX URL-nél, de ez a sikeres fizetés utáni
     * átirányításhoz használatos. Biztosítja, hogy a sikeres fizetés oldal
     * URL-je is tartalmazza az összes szükséges paramétert.
     */
    function buildRedirectUrl(action) {
        var baseUrl = window.location.origin + window.location.pathname;
        
        var hubId = document.getElementById('hubId').value;
        var propertyId = document.getElementById('propertyId').value;
        var siteId = document.getElementById('siteId').value;
        var itemId = document.getElementById('itemId').value;
        var reservationId = document.getElementById('reservationId').value;
        
        var params = new URLSearchParams();
        params.append('option', 'com_solidres');
        params.append('view', 'reservationasset');
        params.append('layout', action); // 'success' vagy 'cancel'
        
        if (hubId && hubId !== '0') {
            params.append('hub_id', hubId);
        }
        if (propertyId && propertyId !== '0') {
            params.append('property_id', propertyId);
        }
        if (siteId && siteId !== '0') {
            params.append('site_id', siteId);
        }
        if (itemId && itemId !== '0') {
            params.append('Itemid', itemId);
        }
        if (reservationId && reservationId !== '0') {
            params.append('reservation_id', reservationId);
        }
        
        return baseUrl + '?' + params.toString();
    }
    
    /**
     * Státusz üzenet megjelenítése
     */
    function showStatus(message, type) {
        var statusDiv = document.getElementById('paymentStatus');
        if (statusDiv) {
            statusDiv.className = 'alert alert-' + type;
            statusDiv.textContent = message;
            statusDiv.style.display = 'block';
        }
    }
    
    /**
     * Fizetési űrlap beküldés kezelése
     * 
     * A Fetch API-t használjuk XMLHttpRequest helyett, mert:
     * - Modern, tiszta promise-alapú API
     * - Jobb hibakezelés
     * - Könnyebb async/await használat
     * - Natív JSON támogatás
     */
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('qvikPaymentForm');
        var paymentBtn = document.getElementById('paymentBtn');
        
        if (!form) {
            console.error('Qvik payment form not found');
            return;
        }
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Gomb letiltása dupla beküldés ellen
            if (paymentBtn) {
                paymentBtn.disabled = true;
                paymentBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_PROCESSING_PAYMENT'); ?>';
            }
            
            showStatus('<?php echo JText::_('COM_SOLIDRES_PROCESSING_PAYMENT'); ?>', 'info');
            
            // FormData létrehozása az űrlapból
            var formData = new FormData(form);
            
            // Fetch API használata promise-alapú hibakezeléssel
            fetch(buildAjaxUrl(), {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // Cookie-k küldése (fontos a session számára)
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Jelzés Joomla-nak, hogy AJAX kérés
                }
            })
            .then(function(response) {
                // HTTP státusz kód ellenőrzése
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                // JSON parse
                return response.json();
            })
            .then(function(data) {
                // Sikeres válasz feldolgozása
                if (data.success) {
                    showStatus('<?php echo JText::_('COM_SOLIDRES_PAYMENT_SUCCESS'); ?>', 'success');
                    
                    // Ha a gateway átirányítást igényel (pl. 3D Secure)
                    if (data.redirectUrl) {
                        // Külső átirányítás (gateway oldalára)
                        window.location.href = data.redirectUrl;
                    } else {
                        // Belső átirányítás (sikeres fizetés oldalra)
                        setTimeout(function() {
                            window.location.href = buildRedirectUrl('success');
                        }, 1500);
                    }
                } else {
                    // Szerver oldali hiba
                    var errorMsg = data.message || '<?php echo JText::_('COM_SOLIDRES_PAYMENT_ERROR'); ?>';
                    showStatus(errorMsg, 'danger');
                    
                    // Gomb újra engedélyezése
                    if (paymentBtn) {
                        paymentBtn.disabled = false;
                        paymentBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_PAY_NOW'); ?>';
                    }
                }
            })
            .catch(function(error) {
                // Hálózati vagy parse hiba kezelése
                console.error('Payment processing error:', error);
                showStatus('<?php echo JText::_('COM_SOLIDRES_PAYMENT_NETWORK_ERROR'); ?>', 'danger');
                
                // Gomb újra engedélyezése
                if (paymentBtn) {
                    paymentBtn.disabled = false;
                    paymentBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_PAY_NOW'); ?>';
                }
            });
        });
        
        /**
         * Callback kezelés külső gateway-től való visszatéréskor
         * 
         * Ha a Qvik gateway átirányít vissza az oldalra (pl. 3D Secure után),
         * akkor URL paramétereket kell ellenőriznünk
         */
        var urlParams = new URLSearchParams(window.location.search);
        var paymentStatus = urlParams.get('payment_status');
        
        if (paymentStatus === 'success') {
            showStatus('<?php echo JText::_('COM_SOLIDRES_PAYMENT_SUCCESS'); ?>', 'success');
            setTimeout(function() {
                window.location.href = buildRedirectUrl('success');
            }, 2000);
        } else if (paymentStatus === 'failed') {
            showStatus('<?php echo JText::_('COM_SOLIDRES_PAYMENT_FAILED'); ?>', 'danger');
        } else if (paymentStatus === 'cancelled') {
            showStatus('<?php echo JText::_('COM_SOLIDRES_PAYMENT_CANCELLED'); ?>', 'warning');
        }
    });
})();
</script>

<style>
.qvik-payment-confirmation {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.reservation-summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.reservation-summary h3 {
    margin-top: 0;
    color: #333;
}

.form-actions {
    margin-top: 20px;
    text-align: center;
}

.form-actions .btn {
    margin: 0 10px;
    padding: 12px 30px;
    font-size: 16px;
}

#paymentStatus {
    margin-top: 20px;
}
</style>
