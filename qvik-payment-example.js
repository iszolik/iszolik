/**
 * Qvik Payment Plugin - Context-Aware AJAX Implementation Example
 * 
 * Ez a fájl demonstrálja a helyes context-aware AJAX hívásokat,
 * amelyek működnek mind root, mind submenu contextben.
 */

// ============================================================================
// 1. CONTEXT-AWARE URL BUILDER
// ============================================================================

/**
 * Épít egy context-aware AJAX URL-t
 * 
 * Ez a függvény biztosítja, hogy:
 * - Megőrzi a teljes pathname-t (beleértve submenu path-okat is)
 * - Átadja a kritikus Joomla/Solidres paramétereket
 * - Minden contextben működik (root, submenu, hub)
 * 
 * @param {Object} params - Új paraméterek hozzáadásához
 * @returns {string} Teljes, context-aware URL
 */
function buildAjaxUrl(params) {
    // Teljes path megőrzése (beleértve submenu-t is)
    // Példák:
    // - Root context: /index.php
    // - Submenu: /hu/reservations/index.php
    // - Hub: /site1/index.php
    const basePath = window.location.origin + window.location.pathname;
    
    // Jelenlegi URL paraméterek kinyerése
    const urlParams = new URLSearchParams(window.location.search);
    
    // Kritikus paraméterek megőrzése a jelenlegi URL-ből
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),           // Joomla menu item
        'hub_id': urlParams.get('hub_id'),           // Solidres hub context
        'property_id': urlParams.get('property_id'), // Property context
        'site_id': urlParams.get('site_id')          // Multi-site context
    };
    
    // Új paraméterek összevonása a kritikusakkal
    const allParams = { ...criticalParams, ...params };
    
    // URL paraméterek építése (csak nem-null értékekkel)
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    // Teljes URL visszaadása
    return basePath + '?' + newParams.toString();
}

// ============================================================================
// 2. QVIK PAYMENT INITIALIZATION
// ============================================================================

/**
 * Qvik fizetés inicializálása
 * 
 * Ez a függvény:
 * - Context-aware URL-t használ
 * - Háromszintű error handling-et alkalmaz
 * - Minden kritikus paramétert átad
 * 
 * @param {string} reservationId - Foglalás azonosító
 */
function initializeQvikPayment(reservationId) {
    console.log('Initializing Qvik payment for reservation:', reservationId);
    
    // Context-aware URL építés
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeQvikPayment',
        'plugin': 'qvik',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    console.log('Qvik payment URL:', url);
    
    // Fetch API hívás háromszintű error handling-gel
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'  // AJAX header
        },
        body: JSON.stringify({
            reservation_id: reservationId
        })
    })
    .then(response => {
        // 1. szint: HTTP status ellenőrzés
        console.log('HTTP status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // 2. szint: API válasz validálás
        console.log('API response:', data);
        if (!data.success) {
            throw new Error(data.message || 'Payment initialization failed');
        }
        
        // Sikeres inicializálás
        handleQvikPaymentSuccess(data);
    })
    .catch(error => {
        // 3. szint: Hálózati/parse hibák kezelése
        console.error('Payment initialization error:', error);
        handleQvikPaymentError(error.message);
    });
}

/**
 * Sikeres Qvik payment callback
 */
function handleQvikPaymentSuccess(data) {
    console.log('Payment initialized successfully:', data);
    
    // Átirányítás a Qvik payment gateway-re
    if (data.payment_url) {
        window.location.href = data.payment_url;
    } else {
        console.error('No payment URL in response');
        handleQvikPaymentError('No payment URL received');
    }
}

/**
 * Qvik payment hiba callback
 */
function handleQvikPaymentError(message) {
    console.error('Payment error:', message);
    
    // Hibaüzenet megjelenítése
    const errorDiv = document.getElementById('payment-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    } else {
        alert('Payment error: ' + message);
    }
}

// ============================================================================
// 3. QVIK PAYMENT STATUS CHECK
// ============================================================================

/**
 * Qvik fizetés státusz ellenőrzés
 * 
 * Ez a függvény polling-ot végez a fizetés állapotának ellenőrzésére
 * 
 * @param {string} reservationId - Foglalás azonosító
 * @param {string} transactionId - Tranzakció azonosító
 */
function checkQvikPaymentStatus(reservationId, transactionId) {
    console.log('Checking Qvik payment status:', reservationId, transactionId);
    
    // Context-aware URL építés
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.checkQvikPaymentStatus',
        'plugin': 'qvik',
        'reservation_id': reservationId,
        'transaction_id': transactionId,
        'format': 'json'
    });
    
    console.log('Status check URL:', url);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Status check failed');
        }
        
        handleQvikStatusUpdate(data);
    })
    .catch(error => {
        console.error('Status check error:', error);
    });
}

/**
 * Qvik státusz frissítés callback
 */
function handleQvikStatusUpdate(data) {
    console.log('Payment status:', data.status);
    
    switch(data.status) {
        case 'completed':
            // Átirányítás a sikeres oldarra
            window.location.href = buildRedirectUrl({
                'view': 'reservation',
                'layout': 'success',
                'reservation_id': data.reservation_id
            });
            break;
            
        case 'pending':
            // Folytatjuk a polling-ot
            setTimeout(() => {
                checkQvikPaymentStatus(data.reservation_id, data.transaction_id);
            }, 3000);
            break;
            
        case 'failed':
            handleQvikPaymentError(data.message || 'Payment failed');
            break;
    }
}

// ============================================================================
// 4. REDIRECT URL BUILDER (context-aware)
// ============================================================================

/**
 * Épít egy context-aware redirect URL-t
 * Hasonló a buildAjaxUrl-hez, de redirect-re optimalizált
 */
function buildRedirectUrl(params) {
    const basePath = window.location.origin + window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        'property_id': urlParams.get('property_id'),
        'site_id': urlParams.get('site_id')
    };
    
    const allParams = { ...criticalParams, 'option': 'com_solidres', ...params };
    
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    return basePath + '?' + newParams.toString();
}

// ============================================================================
// 5. PÉLDA URL-EK KÜLÖNBÖZŐ CONTEXTEKBEN
// ============================================================================

/*
ROOT CONTEXT:
--------------
Oldal URL: https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=123

buildAjaxUrl() eredmény:
https://example.com/index.php?Itemid=123&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json

SUBMENU CONTEXT:
----------------
Oldal URL: https://example.com/hu/reservations/index.php?option=com_solidres&view=reservationasset&Itemid=123

buildAjaxUrl() eredmény:
https://example.com/hu/reservations/index.php?Itemid=123&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json

HUB CONTEXT:
------------
Oldal URL: https://example.com/hotel1/index.php?option=com_solidres&view=reservationasset&Itemid=123&hub_id=5

buildAjaxUrl() eredmény:
https://example.com/hotel1/index.php?Itemid=123&hub_id=5&option=com_solidres&task=reservationasset.initializeQvikPayment&plugin=qvik&reservation_id=789&format=json

MINDHÁROM ESETBEN:
- Megmarad a teljes path (/, /hu/reservations/, /hotel1/)
- Megmaradnak a kritikus paraméterek (Itemid, hub_id, stb.)
- Nem lesz 404 hiba!
*/

// ============================================================================
// 6. EVENT LISTENER PÉLDA
// ============================================================================

/**
 * Inicializálás DOM ready után
 */
document.addEventListener('DOMContentLoaded', function() {
    // Qvik payment gomb eseménykezelő
    const qvikButton = document.querySelector('[data-payment-method="qvik"]');
    if (qvikButton) {
        qvikButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const reservationId = this.getAttribute('data-reservation-id');
            if (reservationId) {
                initializeQvikPayment(reservationId);
            } else {
                console.error('No reservation ID found');
            }
        });
    }
});

// ============================================================================
// 7. DEBUGGING SEGÍTSÉG
// ============================================================================

/**
 * Debug információ kiírása a console-ra
 * Használd ezt a függvényt, ha ellenőrizni akarod a context paramétereket
 */
function debugContextInfo() {
    console.group('Context Debug Info');
    console.log('window.location.origin:', window.location.origin);
    console.log('window.location.pathname:', window.location.pathname);
    console.log('window.location.search:', window.location.search);
    
    const urlParams = new URLSearchParams(window.location.search);
    console.log('Itemid:', urlParams.get('Itemid'));
    console.log('hub_id:', urlParams.get('hub_id'));
    console.log('property_id:', urlParams.get('property_id'));
    console.log('site_id:', urlParams.get('site_id'));
    
    // Tesztelünk egy URL-t
    const testUrl = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'test.task',
        'format': 'json'
    });
    console.log('Test AJAX URL:', testUrl);
    
    console.groupEnd();
}

// Debug info futtatása development módban
if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
    debugContextInfo();
}
