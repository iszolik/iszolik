/**
 * Revolut Payment Plugin - Context-Aware AJAX Implementation Example
 * 
 * Ez a fájl demonstrálja a helyes context-aware AJAX hívásokat,
 * amelyek működnek mind root, mind submenu contextben.
 */

// ============================================================================
// 1. CONTEXT-AWARE URL BUILDER (ugyanaz mint Qvik-nél)
// ============================================================================

/**
 * Épít egy context-aware AJAX URL-t
 * 
 * @param {Object} params - Új paraméterek hozzáadásához
 * @returns {string} Teljes, context-aware URL
 */
function buildAjaxUrl(params) {
    const basePath = window.location.origin + window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        'property_id': urlParams.get('property_id'),
        'site_id': urlParams.get('site_id')
    };
    
    const allParams = { ...criticalParams, ...params };
    
    const newParams = new URLSearchParams();
    for (const [key, value] of Object.entries(allParams)) {
        if (value !== null && value !== undefined && value !== '') {
            newParams.append(key, value);
        }
    }
    
    return basePath + '?' + newParams.toString();
}

// ============================================================================
// 2. REVOLUT PAYMENT INITIALIZATION
// ============================================================================

/**
 * Revolut fizetés inicializálása
 * 
 * @param {string} reservationId - Foglalás azonosító
 */
function initializeRevolutPayment(reservationId) {
    console.log('Initializing Revolut payment for reservation:', reservationId);
    
    // Context-aware URL építés
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeRevolutPayment',
        'plugin': 'revolut',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    console.log('Revolut payment URL:', url);
    
    // Fetch API hívás háromszintű error handling-gel
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
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
        handleRevolutPaymentSuccess(data);
    })
    .catch(error => {
        // 3. szint: Hálózati/parse hibák kezelése
        console.error('Payment initialization error:', error);
        handleRevolutPaymentError(error.message);
    });
}

/**
 * Sikeres Revolut payment callback
 */
function handleRevolutPaymentSuccess(data) {
    console.log('Payment initialized successfully:', data);
    
    // Revolut widget megjelenítése
    if (data.public_id) {
        // Revolut SDK inicializálása
        if (window.RevolutCheckout) {
            RevolutCheckout(data.public_id).then(function(instance) {
                instance.payWithPopup({
                    onSuccess() {
                        console.log('Payment successful');
                        checkRevolutPaymentStatus(data.reservation_id, data.order_id);
                    },
                    onError(error) {
                        console.error('Payment error:', error);
                        handleRevolutPaymentError(error.message);
                    },
                    onCancel() {
                        console.log('Payment cancelled');
                        handleRevolutPaymentError('Payment was cancelled');
                    }
                });
            });
        } else {
            console.error('Revolut SDK not loaded');
            handleRevolutPaymentError('Revolut payment system not available');
        }
    } else {
        console.error('No public_id in response');
        handleRevolutPaymentError('Invalid payment response');
    }
}

/**
 * Revolut payment hiba callback
 */
function handleRevolutPaymentError(message) {
    console.error('Payment error:', message);
    
    const errorDiv = document.getElementById('payment-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    } else {
        alert('Payment error: ' + message);
    }
}

// ============================================================================
// 3. REVOLUT PAYMENT STATUS CHECK
// ============================================================================

/**
 * Revolut fizetés státusz ellenőrzés
 * 
 * @param {string} reservationId - Foglalás azonosító
 * @param {string} orderId - Revolut order ID
 */
function checkRevolutPaymentStatus(reservationId, orderId) {
    console.log('Checking Revolut payment status:', reservationId, orderId);
    
    // Context-aware URL építés
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.checkRevolutPaymentStatus',
        'plugin': 'revolut',
        'reservation_id': reservationId,
        'order_id': orderId,
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
        
        handleRevolutStatusUpdate(data);
    })
    .catch(error => {
        console.error('Status check error:', error);
    });
}

/**
 * Revolut státusz frissítés callback
 */
function handleRevolutStatusUpdate(data) {
    console.log('Payment status:', data.status);
    
    switch(data.status) {
        case 'completed':
        case 'COMPLETED':
            // Átirányítás a sikeres oldalra
            window.location.href = buildRedirectUrl({
                'view': 'reservation',
                'layout': 'success',
                'reservation_id': data.reservation_id
            });
            break;
            
        case 'pending':
        case 'PENDING':
            // Folytatjuk a polling-ot
            setTimeout(() => {
                checkRevolutPaymentStatus(data.reservation_id, data.order_id);
            }, 3000);
            break;
            
        case 'failed':
        case 'FAILED':
            handleRevolutPaymentError(data.message || 'Payment failed');
            break;
    }
}

// ============================================================================
// 4. REDIRECT URL BUILDER (context-aware)
// ============================================================================

/**
 * Épít egy context-aware redirect URL-t
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
// 5. REVOLUT SDK LOADING
// ============================================================================

/**
 * Revolut SDK betöltése dinamikusan
 * Ez biztosítja, hogy az SDK mindig elérhető legyen
 */
function loadRevolutSDK() {
    return new Promise((resolve, reject) => {
        // Ellenőrizzük, hogy már be van-e töltve
        if (window.RevolutCheckout) {
            resolve(window.RevolutCheckout);
            return;
        }
        
        // SDK script betöltése
        const script = document.createElement('script');
        script.src = 'https://merchant.revolut.com/embed.js';
        script.async = true;
        
        script.onload = () => {
            console.log('Revolut SDK loaded successfully');
            resolve(window.RevolutCheckout);
        };
        
        script.onerror = () => {
            console.error('Failed to load Revolut SDK');
            reject(new Error('Failed to load Revolut SDK'));
        };
        
        document.head.appendChild(script);
    });
}

// ============================================================================
// 6. REVOLUT PAYMENT WITH SDK PRELOAD
// ============================================================================

/**
 * Revolut fizetés inicializálása SDK előtöltéssel
 * 
 * @param {string} reservationId - Foglalás azonosító
 */
async function initializeRevolutPaymentWithSDK(reservationId) {
    try {
        // Először betöltjük az SDK-t
        await loadRevolutSDK();
        
        // Aztán inicializáljuk a fizetést
        initializeRevolutPayment(reservationId);
    } catch (error) {
        console.error('SDK loading error:', error);
        handleRevolutPaymentError('Failed to load payment system');
    }
}

// ============================================================================
// 7. PÉLDA URL-EK KÜLÖNBÖZŐ CONTEXTEKBEN
// ============================================================================

/*
ROOT CONTEXT:
--------------
Oldal URL: https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=123

buildAjaxUrl() eredmény:
https://example.com/index.php?Itemid=123&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json

SUBMENU CONTEXT:
----------------
Oldal URL: https://example.com/hu/reservations/index.php?option=com_solidres&view=reservationasset&Itemid=123

buildAjaxUrl() eredmény:
https://example.com/hu/reservations/index.php?Itemid=123&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json

HUB CONTEXT:
------------
Oldal URL: https://example.com/hotel1/index.php?option=com_solidres&view=reservationasset&Itemid=123&hub_id=5

buildAjaxUrl() eredmény:
https://example.com/hotel1/index.php?Itemid=123&hub_id=5&option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=789&format=json
*/

// ============================================================================
// 8. EVENT LISTENER PÉLDA
// ============================================================================

/**
 * Inicializálás DOM ready után
 */
document.addEventListener('DOMContentLoaded', function() {
    // Revolut payment gomb eseménykezelő
    const revolutButton = document.querySelector('[data-payment-method="revolut"]');
    if (revolutButton) {
        revolutButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const reservationId = this.getAttribute('data-reservation-id');
            if (reservationId) {
                // SDK-val együtt inicializálás
                initializeRevolutPaymentWithSDK(reservationId);
            } else {
                console.error('No reservation ID found');
            }
        });
    }
});

// ============================================================================
// 9. DEBUGGING SEGÍTSÉG
// ============================================================================

/**
 * Debug információ kiírása a console-ra
 */
function debugContextInfo() {
    console.group('Revolut Context Debug Info');
    console.log('window.location.origin:', window.location.origin);
    console.log('window.location.pathname:', window.location.pathname);
    console.log('window.location.search:', window.location.search);
    
    const urlParams = new URLSearchParams(window.location.search);
    console.log('Itemid:', urlParams.get('Itemid'));
    console.log('hub_id:', urlParams.get('hub_id'));
    console.log('property_id:', urlParams.get('property_id'));
    console.log('site_id:', urlParams.get('site_id'));
    
    const testUrl = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'test.task',
        'plugin': 'revolut',
        'format': 'json'
    });
    console.log('Test AJAX URL:', testUrl);
    
    console.log('Revolut SDK loaded:', !!window.RevolutCheckout);
    
    console.groupEnd();
}

// Debug info futtatása development módban
if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
    debugContextInfo();
}

// ============================================================================
// 10. ÖSSZEHASONLÍTÁS - HELYES VS HIBÁS
// ============================================================================

/*
❌ HIBÁS MEGKÖZELÍTÉS (404 hiba submenu contextben):
----------------------------------------------------

function badInitializeRevolutPayment(reservationId) {
    // HIBA: Hardcoded /index.php - nem őrzi meg a submenu path-t!
    const url = window.location.origin + '/index.php?option=com_solidres&task=reservationasset.initializeRevolutPayment&plugin=revolut&reservation_id=' + reservationId;
    
    fetch(url, {
        method: 'POST',
        body: JSON.stringify({ reservation_id: reservationId })
    })
    .then(response => response.json())
    .then(data => {
        // Nincs proper error handling!
        handleRevolutPaymentSuccess(data);
    });
}

PROBLÉMA:
- Root contextben: /index.php ✓ működik
- Submenu contextben: /index.php ✗ 404 hiba! (kellene: /hu/reservations/index.php)
- Hiányzik Itemid, hub_id, stb.
- Nincs háromszintű error handling


✓ HELYES MEGKÖZELÍTÉS (minden contextben működik):
--------------------------------------------------

function initializeRevolutPayment(reservationId) {
    // HELYES: window.location.pathname megőrzi a teljes path-t
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeRevolutPayment',
        'plugin': 'revolut',
        'reservation_id': reservationId,
        'format': 'json'
    });
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ reservation_id: reservationId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Payment initialization failed');
        }
        handleRevolutPaymentSuccess(data);
    })
    .catch(error => {
        console.error('Payment initialization error:', error);
        handleRevolutPaymentError(error.message);
    });
}

ELŐNYÖK:
- Root contextben: /index.php ✓ működik
- Submenu contextben: /hu/reservations/index.php ✓ működik!
- Hub contextben: /hotel1/index.php ✓ működik!
- Minden kritikus paraméter átadódik (Itemid, hub_id, stb.)
- Háromszintű error handling
*/
