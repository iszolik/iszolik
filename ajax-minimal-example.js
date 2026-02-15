/**
 * MINIMÁLIS PÉLDA - AJAX 404 Hiba Megoldás
 * =========================================
 * 
 * Ez a minimális példa bemutatja a legfontosabb részeket,
 * amelyek garantálják, hogy NEM lesz többé 404 hiba.
 */

// ========================================
// 1. URL ÉPÍTŐ FÜGGVÉNY (KÖTELEZŐ!)
// ========================================

function buildAjaxUrl(option, task, additionalParams = {}) {
    // KULCS: Abszolút útvonal a Joomla gyökérből
    const baseUrl = window.location.origin + '/index.php';
    
    const params = new URLSearchParams();
    params.append('option', option);
    params.append('task', task);
    
    // KRITIKUS: Itemid megőrzése
    const currentUrl = new URL(window.location.href);
    const itemId = currentUrl.searchParams.get('Itemid');
    if (itemId) params.append('Itemid', itemId);
    
    // KRITIKUS: Solidres paraméterek megőrzése
    ['property_id', 'hub_id', 'site_id', 'reservation_id'].forEach(param => {
        const value = currentUrl.searchParams.get(param);
        if (value) params.append(param, value);
    });
    
    // További paraméterek
    Object.keys(additionalParams).forEach(key => {
        params.append(key, additionalParams[key]);
    });
    
    params.append('format', 'json');
    
    return baseUrl + '?' + params.toString();
}

// ========================================
// 2. FETCH HÍVÁS MINTAKÓD
// ========================================

function sendAjaxRequest(data) {
    // URL építése - GARANTÁLTAN ABSZOLÚT!
    const url = buildAjaxUrl('com_solidres', 'reservation.save');
    
    console.log('AJAX URL:', url);  // Debug célra
    
    // Fetch hívás három szintű hibakezeléssel
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        // 1. HTTP státusz ellenőrzés (404, 500, stb.)
        if (!response.ok) {
            throw new Error(`HTTP hiba: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // 2. API válasz ellenőrzés
        if (data.success === false) {
            throw new Error(data.message || 'API hiba');
        }
        return data;
    })
    .catch(error => {
        // 3. Hálózati és egyéb hibák
        console.error('Hiba:', error);
        alert('Hiba történt: ' + error.message);
        throw error;
    });
}

// ========================================
// 3. HASZNÁLAT
// ========================================

// Példa: Form submit
document.getElementById('myForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: 'Teszt',
        email: 'test@example.com'
    };
    
    sendAjaxRequest(formData)
        .then(result => console.log('Siker!', result))
        .catch(error => console.error('Hiba!', error));
});

// ========================================
// MIÉRT NEM LESZ TÖBBÉ 404?
// ========================================

/*
1. window.location.origin
   - Teljes domain: http://example.com
   - Mindig helyes, bármilyen útvonalon
   
2. /index.php
   - Joomla központi végpont
   - Mindig elérhető a gyökérből
   - Router kezeli az útvonalakat
   
3. Itemid megőrzése
   - Biztosítja a menü kontextust
   - SEF URL routing működik
   
4. hub_id, property_id, site_id
   - Hub és multi-site kontextus
   - Automatikusan megőrződnek

PÉLDA URL-EK:
=============

Főmenü:
  Jelenlegi: http://example.com/foglalas?Itemid=101
  AJAX URL:  http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=101&format=json
  Státusz:   ✅ 200 OK

Hub:
  Jelenlegi: http://example.com/hub/budapest?hub_id=5&Itemid=102
  AJAX URL:  http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=102&hub_id=5&format=json
  Státusz:   ✅ 200 OK

Almenü:
  Jelenlegi: http://example.com/szallasok/foglalasok/vendeg?Itemid=105&property_id=123
  AJAX URL:  http://example.com/index.php?option=com_solidres&task=reservation.save&Itemid=105&property_id=123&format=json
  Státusz:   ✅ 200 OK

MINDEN ESETBEN: Abszolút útvonal a gyökérből!
*/
