<?php
/**
 * @package     Solidres
 * @subpackage  Revolut Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Revolut fizetési plugin vendég űrlap sablon
 * 
 * Ez a sablon kezeli a vendég adatok megjelenítését és a fizetési folyamat inicializálását.
 * Biztosítja, hogy minden URL paraméter (hub_id, property_id, site_id, Itemid) megmaradjon.
 */
?>

<div class="revolut-payment-form">
    <h3><?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_GUEST_INFORMATION'); ?></h3>
    
    <div class="guest-details">
        <p><strong><?php echo JText::_('COM_SOLIDRES_GUEST_NAME'); ?>:</strong> 
           <span id="guest-name"><?php echo htmlspecialchars($this->reservation->customer_firstname . ' ' . $this->reservation->customer_lastname); ?></span>
        </p>
        <p><strong><?php echo JText::_('COM_SOLIDRES_EMAIL'); ?>:</strong> 
           <span id="guest-email"><?php echo htmlspecialchars($this->reservation->customer_email); ?></span>
        </p>
        <p><strong><?php echo JText::_('COM_SOLIDRES_PHONE'); ?>:</strong> 
           <span id="guest-phone"><?php echo htmlspecialchars($this->reservation->customer_phonenumber); ?></span>
        </p>
        <p><strong><?php echo JText::_('COM_SOLIDRES_TOTAL_AMOUNT'); ?>:</strong> 
           <span id="total-amount"><?php echo $this->reservation->total_price_tax_incl . ' ' . $this->reservation->currency_code; ?></span>
        </p>
    </div>

    <div class="payment-actions">
        <button type="button" id="revolut-payment-btn" class="btn btn-primary">
            <?php echo JText::_('PLG_SOLIDRESPAYMENT_REVOLUT_PROCEED_TO_PAYMENT'); ?>
        </button>
        <button type="button" id="revolut-cancel-btn" class="btn btn-default">
            <?php echo JText::_('COM_SOLIDRES_CANCEL'); ?>
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    /**
     * URL paraméterek kinyerése és megőrzése
     * Biztosítja, hogy hub_id, property_id, site_id, Itemid paraméterek megmaradjanak
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
     * Teljes útvonal építése a jelenlegi kontextussal
     * Megőrzi az összes menüpont, almenü és hub információt
     * 
     * @param {string} view - A cél view neve
     * @param {string} task - A cél task neve (opcionális)
     * @returns {string} - Teljes URL
     */
    function buildFullUrl(view, task) {
        const urlParams = getUrlParams();
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        
        // Paraméterek összegyűjtése
        let queryParams = [];
        queryParams.push('option=com_solidres');
        queryParams.push('view=' + view);
        if (task) {
            queryParams.push('task=' + task);
        }
        
        // Kötelező paraméterek hozzáadása, ha léteznek
        if (urlParams.hub_id) queryParams.push('hub_id=' + urlParams.hub_id);
        if (urlParams.property_id) queryParams.push('property_id=' + urlParams.property_id);
        if (urlParams.site_id) queryParams.push('site_id=' + urlParams.site_id);
        if (urlParams.Itemid) queryParams.push('Itemid=' + urlParams.Itemid);
        if (urlParams.reservation_id) queryParams.push('reservation_id=' + urlParams.reservation_id);
        
        // Teljes URL összeállítása - pathname megőrzése biztosítja az almenük kontextusát
        return origin + pathname + '?' + queryParams.join('&');
    }
    
    /**
     * Fizetés indítása gomb eseménykezelő
     * Átirányítja a felhasználót a megerősítési oldalra, minden kontextus megőrzésével
     */
    document.getElementById('revolut-payment-btn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Átirányítás a megerősítési oldalra, teljes kontextus megőrzésével
        const confirmUrl = buildFullUrl('reservation', 'confirmPayment');
        window.location.href = confirmUrl;
    });
    
    /**
     * Mégse gomb eseménykezelő
     * Visszairányít a foglalási oldalra, minden kontextus megőrzésével
     */
    document.getElementById('revolut-cancel-btn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Visszairányítás a foglalási oldalra
        const cancelUrl = buildFullUrl('reservation', 'cancel');
        window.location.href = cancelUrl;
    });
})();
</script>
