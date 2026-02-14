<?php
/**
 * Qvik Fizetési Plugin - Guest Form Template
 * 
 * Ez a fájl tartalmazza a vendég adatlap űrlap JS logikáját,
 * amely biztosítja a helyes adatküldést és átirányítást
 * minden menüpont, almenü és hub/multiszálláshely esetében.
 */

defined('_JEXEC') or die;
?>

<div id="sr-guest-form">
    <form id="qvik-guest-form" method="post" class="form-horizontal">
        
        <div class="form-group">
            <label for="guest_firstname" class="control-label">
                <?php echo JText::_('COM_SOLIDRES_GUEST_FIRSTNAME'); ?>*
            </label>
            <input type="text" 
                   id="guest_firstname" 
                   name="guest_firstname" 
                   class="form-control" 
                   required />
        </div>
        
        <div class="form-group">
            <label for="guest_lastname" class="control-label">
                <?php echo JText::_('COM_SOLIDRES_GUEST_LASTNAME'); ?>*
            </label>
            <input type="text" 
                   id="guest_lastname" 
                   name="guest_lastname" 
                   class="form-control" 
                   required />
        </div>
        
        <div class="form-group">
            <label for="guest_email" class="control-label">
                <?php echo JText::_('COM_SOLIDRES_GUEST_EMAIL'); ?>*
            </label>
            <input type="email" 
                   id="guest_email" 
                   name="guest_email" 
                   class="form-control" 
                   required />
        </div>
        
        <div class="form-group">
            <label for="guest_phone" class="control-label">
                <?php echo JText::_('COM_SOLIDRES_GUEST_PHONE'); ?>*
            </label>
            <input type="tel" 
                   id="guest_phone" 
                   name="guest_phone" 
                   class="form-control" 
                   required />
        </div>
        
        <div class="form-group">
            <button type="submit" 
                    id="submit-guest-form" 
                    class="btn btn-primary">
                <?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>
            </button>
        </div>
        
    </form>
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
        var origin = window.location.origin;
        
        // A pathname tartalmazza a teljes elérési utat
        var pathname = window.location.pathname;
        
        return origin + pathname;
    }
    
    /**
     * AJAX URL felépítése vendég adatok mentéséhez
     * 
     * @param {string} task - A végrehajtandó task
     * @param {object} additionalParams - További paraméterek (opcionális)
     * @returns {string} Teljes AJAX URL
     */
    function buildAjaxUrl(task, additionalParams) {
        var baseUrl = buildBaseUrl();
        var url = new URL(baseUrl);
        var params = url.searchParams;
        
        // Alapvető paraméterek
        params.set('option', 'com_solidres');
        params.set('task', task);
        params.set('format', 'json');
        
        // Aktuális paraméterek megőrzése
        var currentParams = new URLSearchParams(window.location.search);
        
        // Itemid - menüpont azonosító
        if (currentParams.has('Itemid')) {
            params.set('Itemid', currentParams.get('Itemid'));
        }
        
        // property_id - szálláshely azonosító
        if (currentParams.has('property_id')) {
            params.set('property_id', currentParams.get('property_id'));
        }
        
        // hub_id - hub azonosító
        if (currentParams.has('hub_id')) {
            params.set('hub_id', currentParams.get('hub_id'));
        }
        
        // site_id - oldal azonosító
        if (currentParams.has('site_id')) {
            params.set('site_id', currentParams.get('site_id'));
        }
        
        // reservation_id - foglalás azonosító
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
     * Redirect URL felépítése a következő lépéshez
     * 
     * @param {string} view - A következő view neve
     * @param {object} additionalParams - További paraméterek (opcionális)
     * @returns {string} Teljes redirect URL
     */
    function buildRedirectUrl(view, additionalParams) {
        var baseUrl = buildBaseUrl();
        var url = new URL(baseUrl);
        var params = url.searchParams;
        
        // Alapvető paraméterek
        params.set('option', 'com_solidres');
        params.set('view', view);
        
        // Aktuális paraméterek megőrzése
        var currentParams = new URLSearchParams(window.location.search);
        
        if (currentParams.has('Itemid')) {
            params.set('Itemid', currentParams.get('Itemid'));
        }
        
        if (currentParams.has('property_id')) {
            params.set('property_id', currentParams.get('property_id'));
        }
        
        if (currentParams.has('hub_id')) {
            params.set('hub_id', currentParams.get('hub_id'));
        }
        
        if (currentParams.has('site_id')) {
            params.set('site_id', currentParams.get('site_id'));
        }
        
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
     * Form validálása
     * 
     * @param {object} formData - Az űrlap adatok
     * @returns {boolean} Érvényes-e az űrlap
     */
    function validateForm(formData) {
        // Kötelező mezők ellenőrzése
        if (!formData.guest_firstname || formData.guest_firstname.trim() === '') {
            alert('Kérjük, adja meg a keresztnevet!');
            return false;
        }
        
        if (!formData.guest_lastname || formData.guest_lastname.trim() === '') {
            alert('Kérjük, adja meg a vezetéknevet!');
            return false;
        }
        
        if (!formData.guest_email || formData.guest_email.trim() === '') {
            alert('Kérjük, adja meg az email címet!');
            return false;
        }
        
        // Email formátum ellenőrzése
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(formData.guest_email)) {
            alert('Kérjük, adjon meg egy érvényes email címet!');
            return false;
        }
        
        if (!formData.guest_phone || formData.guest_phone.trim() === '') {
            alert('Kérjük, adja meg a telefonszámot!');
            return false;
        }
        
        return true;
    }
    
    /**
     * Vendég adatok mentése és továbblépés fizetéshez
     * 
     * Ez a függvény kezeli az űrlap beküldését Fetch API-val,
     * megőrizve minden szükséges kontextust.
     * 
     * @param {Event} event - A form submit event
     */
    function handleGuestFormSubmit(event) {
        event.preventDefault();
        
        // Form elemek összegyűjtése
        var form = document.getElementById('qvik-guest-form');
        var submitButton = document.getElementById('submit-guest-form');
        
        // Gomb letiltása dupla küldés ellen
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Adatok mentése...';
        }
        
        // Form adatok összegyűjtése
        var formData = {
            guest_firstname: document.getElementById('guest_firstname').value,
            guest_lastname: document.getElementById('guest_lastname').value,
            guest_email: document.getElementById('guest_email').value,
            guest_phone: document.getElementById('guest_phone').value
        };
        
        // Validálás
        if (!validateForm(formData)) {
            // Gomb újra engedélyezése hiba esetén
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = '<?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>';
            }
            return;
        }
        
        // AJAX URL felépítése
        var ajaxUrl = buildAjaxUrl('reservation.saveguestdata');
        
        // Fetch API használata az adatok mentéséhez
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(formData)
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP hiba: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Sikeres mentés - átirányítás a fizetési oldalra
                var paymentUrl = buildRedirectUrl('reservationpayment', {
                    reservation_id: data.reservation_id,
                    payment_method: 'qvik'
                });
                window.location.href = paymentUrl;
            } else {
                // Sikertelen mentés
                var errorMsg = data.message || 'Hiba történt az adatok mentése során';
                alert(errorMsg);
                
                // Gomb újra engedélyezése
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = '<?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>';
                }
            }
        })
        .catch(function(error) {
            // Hálózati vagy egyéb hiba
            console.error('Adatmentési hiba:', error);
            alert('Hiba történt az adatok mentése során. Kérjük, próbálja újra később.');
            
            // Gomb újra engedélyezése
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = '<?php echo JText::_('COM_SOLIDRES_CONTINUE_TO_PAYMENT'); ?>';
            }
        });
    }
    
    /**
     * Űrlap inicializálása
     */
    function initializeGuestForm() {
        var form = document.getElementById('qvik-guest-form');
        if (form) {
            form.addEventListener('submit', handleGuestFormSubmit);
        }
        
        // Automatikus űrlap kitöltés mentett adatokból (ha vannak)
        loadSavedGuestData();
    }
    
    /**
     * Mentett vendég adatok betöltése (ha vannak)
     * 
     * Ez a függvény lekéri a korábban mentett vendég adatokat
     * és kitölti velük az űrlapot.
     */
    function loadSavedGuestData() {
        var ajaxUrl = buildAjaxUrl('reservation.getguestdata');
        
        fetch(ajaxUrl, {
            method: 'GET',
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
            if (data.success && data.guest_data) {
                // Adatok kitöltése az űrlapba
                var guestData = data.guest_data;
                
                if (guestData.firstname) {
                    document.getElementById('guest_firstname').value = guestData.firstname;
                }
                if (guestData.lastname) {
                    document.getElementById('guest_lastname').value = guestData.lastname;
                }
                if (guestData.email) {
                    document.getElementById('guest_email').value = guestData.email;
                }
                if (guestData.phone) {
                    document.getElementById('guest_phone').value = guestData.phone;
                }
            }
        })
        .catch(function(error) {
            // Nem kritikus hiba - egyszerűen nem töltjük ki az űrlapot
            console.log('Mentett adatok betöltése sikertelen:', error);
        });
    }
    
    // Inicializálás DOM betöltés után
    document.addEventListener('DOMContentLoaded', initializeGuestForm);
    
})();
</script>
