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
