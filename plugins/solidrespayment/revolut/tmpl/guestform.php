<?php
/**
 * @package     Solidres
 * @subpackage  Revolut Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Revolut fizetési plugin - Vendég űrlap sablon
 * 
 * Ez a fájl tartalmazza a vendég adatok űrlapját, amely a foglalás során
 * a fizetési folyamat első lépése. A JavaScript kód biztosítja, hogy minden
 * URL paraméter megmaradjon (hub_id, property_id, site_id, Itemid), így
 * elkerülhetők a 404 hibák és az útvonalvesztés.
 */

// Fontos URL paraméterek kinyerése a kérésből
$hubId       = $this->app->input->getInt('hub_id', 0);
$propertyId  = $this->app->input->getInt('property_id', 0);
$siteId      = $this->app->input->getInt('site_id', 0);
$itemId      = $this->app->input->getInt('Itemid', 0);

?>
<div class="revolut-guest-form-container">
    <h2><?php echo JText::_('COM_SOLIDRES_GUEST_INFORMATION'); ?></h2>
    
    <form id="revolutGuestForm" method="post" class="form-validate">
        <!-- Vendég adatok űrlap mezői -->
        <div class="control-group">
            <label for="guest_firstname" class="control-label required">
                <?php echo JText::_('COM_SOLIDRES_GUEST_FIRSTNAME'); ?> <span class="star">*</span>
            </label>
            <div class="controls">
                <input type="text" name="guest_firstname" id="guest_firstname" 
                       class="required" required="required" />
            </div>
        </div>
        
        <div class="control-group">
            <label for="guest_lastname" class="control-label required">
                <?php echo JText::_('COM_SOLIDRES_GUEST_LASTNAME'); ?> <span class="star">*</span>
            </label>
            <div class="controls">
                <input type="text" name="guest_lastname" id="guest_lastname" 
                       class="required" required="required" />
            </div>
        </div>
        
        <div class="control-group">
            <label for="guest_email" class="control-label required">
                <?php echo JText::_('COM_SOLIDRES_GUEST_EMAIL'); ?> <span class="star">*</span>
            </label>
            <div class="controls">
                <input type="email" name="guest_email" id="guest_email" 
                       class="required" required="required" />
            </div>
        </div>
        
        <div class="control-group">
            <label for="guest_phone" class="control-label required">
                <?php echo JText::_('COM_SOLIDRES_GUEST_PHONE'); ?> <span class="star">*</span>
            </label>
            <div class="controls">
                <input type="tel" name="guest_phone" id="guest_phone" 
                       class="required" required="required" />
            </div>
        </div>
        
        <!-- Rejtett mezők a paraméterek megőrzéséhez -->
        <input type="hidden" name="hub_id" value="<?php echo $hubId; ?>" />
        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>" />
        <input type="hidden" name="site_id" value="<?php echo $siteId; ?>" />
        <input type="hidden" name="Itemid" value="<?php echo $itemId; ?>" />
        <input type="hidden" name="option" value="com_solidres" />
        <input type="hidden" name="task" value="reservationasset.processGuest" />
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
(function() {
    'use strict';
    
    /**
     * URL építő függvény - Teljes útvonal kontextus megőrzése
     * 
     * Ez a függvény biztosítja, hogy minden URL tartalmazza:
     * - A teljes origin-t (protocol + domain)
     * - A teljes pathname-et (beleértve az almenü szegmenseket is)
     * - Az összes szükséges paramétert (hub_id, property_id, site_id, Itemid)
     * 
     * Így elkerülhetők a 404 hibák és az útvonalvesztés multisite/hub/almenü környezetekben.
     */
    function buildSubmitUrl() {
        // Teljes útvonal megőrzése (origin + pathname)
        var baseUrl = window.location.origin + window.location.pathname;
        
        // URL paraméterek kinyerése az űrlapból
        var form = document.getElementById('revolutGuestForm');
        var hubId = form.querySelector('input[name="hub_id"]').value;
        var propertyId = form.querySelector('input[name="property_id"]').value;
        var siteId = form.querySelector('input[name="site_id"]').value;
        var itemId = form.querySelector('input[name="Itemid"]').value;
        
        // URL paraméterek összeállítása
        var params = new URLSearchParams();
        params.append('option', 'com_solidres');
        params.append('task', 'reservationasset.processGuest');
        
        // Csak akkor adjuk hozzá a paramétereket, ha nem 0 értékűek
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
        
        // Teljes URL visszaadása
        return baseUrl + '?' + params.toString();
    }
    
    /**
     * Űrlap beküldés kezelése AJAX-szal
     * 
     * A Fetch API-t használjuk promise-alapú hibakezeléssel, nem XMLHttpRequest-et.
     * Ez modern, tiszta kódot eredményez és jobban kezeli az aszinkron műveleteket.
     */
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('revolutGuestForm');
        
        if (!form) {
            console.error('Revolut guest form not found');
            return;
        }
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Űrlap validáció
            if (!form.checkValidity()) {
                // HTML5 validációs hibák megjelenítése
                form.reportValidity();
                return;
            }
            
            // Submit gomb letiltása dupla beküldés elkerülésére
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_PROCESSING'); ?>';
            }
            
            // FormData objektum létrehozása az űrlap adataiból
            var formData = new FormData(form);
            
            // AJAX kérés a Fetch API-val
            fetch(buildSubmitUrl(), {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // Cookie-k küldése
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // AJAX jelzés Joomla-nak
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Sikeres feldolgozás - átirányítás a fizetési oldalra
                    // A redirect URL már tartalmazza az összes szükséges paramétert
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        console.error('No redirect URL provided');
                        alert('<?php echo JText::_('COM_SOLIDRES_ERROR_NO_REDIRECT'); ?>');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>';
                        }
                    }
                } else {
                    // Hiba történt a szerveroldalon
                    var errorMsg = data.message || '<?php echo JText::_('COM_SOLIDRES_ERROR_PROCESSING_GUEST'); ?>';
                    alert(errorMsg);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>';
                    }
                }
            })
            .catch(function(error) {
                // Hálózati vagy parse hiba
                console.error('Error processing guest form:', error);
                alert('<?php echo JText::_('COM_SOLIDRES_ERROR_NETWORK'); ?>');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>';
                }
            });
        });
    });
})();
</script>
