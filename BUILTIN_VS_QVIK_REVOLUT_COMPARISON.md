# Beépített vs Qvik/Revolut Fizetési Módok - Fetch Különbségek

## Összefoglaló

Ez a dokumentum részletezi a különbségeket a beépített (pl. banki átutalás) és a Qvik/Revolut fizetési módok között, különös tekintettel az AJAX/fetch hívásokra és context paraméterekre.

## 1. Beépített Fizetési Módok (pl. Banki Átutalás)

### Működési Mód

A beépített fizetési módok általában **egyszerű form submit**-ot használnak, nem AJAX-ot:

```php
<!-- PHP template - beépített fizetési mód -->
<form method="post" action="<?php echo JRoute::_('index.php'); ?>" id="payment-form">
    <input type="hidden" name="option" value="com_solidres" />
    <input type="hidden" name="task" value="reservationasset.completePayment" />
    <input type="hidden" name="payment_method" value="bank_transfer" />
    <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>" />
    <?php echo JHtml::_('form.token'); ?>
    
    <button type="submit">Fizetés befejezése</button>
</form>
```

### Előnyök

- **Joomla routing automatikusan kezeli a context-et**
  - `JRoute::_()` függvény automatikusan hozzáadja az Itemid-t
  - A form action-ben nem kell explicit path management
  
- **Token védelem**
  - CSRF token automatikusan hozzáadódik
  
- **Egyszerűbb implementáció**
  - Nincs szükség JavaScript-re
  - Nincs szükség fetch API-ra
  - Nincs szükség URL builder függvényekre

### Hátrányok

- **Korlátozott UX**
  - Teljes oldal újratöltés
  - Nincs progress indicator
  - Nincs real-time feedback
  
- **Nem támogat aszinkron műveleteket**
  - Nem lehet polling-ot végezni
  - Nem lehet status update-et mutatni

## 2. Qvik és Revolut Fizetési Módok

### Működési Mód

A Qvik és Revolut **Fetch API-t használ JavaScript-ben** aszinkron AJAX hívásokhoz:

```javascript
// JavaScript - Qvik/Revolut fizetési mód
function initializePayment(reservationId) {
    const url = buildAjaxUrl({
        'option': 'com_solidres',
        'task': 'reservationasset.initializeQvikPayment',
        'plugin': 'qvik',
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
    .then(response => response.json())
    .then(data => handleSuccess(data))
    .catch(error => handleError(error));
}
```

### Előnyök

- **Jobb UX**
  - Nincs teljes oldal újratöltés
  - Real-time feedback
  - Progress indicator mutatható
  
- **Aszinkron műveletek**
  - Payment gateway integráció
  - Status polling
  - Időzített frissítések
  
- **Flexibilis**
  - Komplex payment flow-k
  - Widget integráció (pl. Revolut SDK)
  - Custom error handling

### Hátrányok (és a 404 probléma oka!)

- **Manuális URL management szükséges**
  - Nincs automatikus `JRoute::_()` JavaScript-ben
  - Kézzel kell építeni a context-aware URL-eket
  - **EZ OKOZZA A 404 HIBÁT SUBMENU CONTEXTBEN!**
  
- **Manuális context paraméter kezelés**
  - Itemid-t kézzel kell átadni
  - hub_id, property_id, site_id kézzel kell kezelni
  
- **Komplexebb implementáció**
  - JavaScript kód szükséges
  - Error handling implementáció
  - URL builder függvények

## 3. URL Építés Összehasonlítása

### Beépített Módszer (PHP)

```php
<?php
// Joomla JRoute automatikusan kezeli mindent
$url = JRoute::_('index.php?option=com_solidres&view=reservation&layout=success');

// EREDMÉNY root contextben:
// /index.php?option=com_solidres&view=reservation&layout=success&Itemid=123

// EREDMÉNY submenu contextben:
// /hu/reservations/index.php?option=com_solidres&view=reservation&layout=success&Itemid=123
// ✓ Automatikusan működik!
?>
```

### Qvik/Revolut (JavaScript) - HIBÁS

```javascript
// ❌ HIBÁS - Hardcoded path
const url = window.location.origin + '/index.php?option=com_solidres&task=payment.process';

// EREDMÉNY root contextben:
// https://example.com/index.php?option=com_solidres&task=payment.process
// ✓ Működik

// EREDMÉNY submenu contextben:
// https://example.com/index.php?option=com_solidres&task=payment.process
// ✗ 404 HIBA! (kellene: /hu/reservations/index.php)
```

### Qvik/Revolut (JavaScript) - HELYES

```javascript
// ✓ HELYES - Context-aware URL builder
function buildAjaxUrl(params) {
    // Megőrzi a teljes pathname-t (beleértve submenu-t is)
    const basePath = window.location.origin + window.location.pathname;
    
    // Kinyeri a kritikus paramétereket
    const urlParams = new URLSearchParams(window.location.search);
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),
        'hub_id': urlParams.get('hub_id'),
        // ... stb
    };
    
    // Összevonja és visszaadja
    const allParams = { ...criticalParams, ...params };
    return basePath + '?' + new URLSearchParams(allParams).toString();
}

const url = buildAjaxUrl({
    'option': 'com_solidres',
    'task': 'payment.process'
});

// EREDMÉNY root contextben:
// https://example.com/index.php?Itemid=123&option=com_solidres&task=payment.process
// ✓ Működik

// EREDMÉNY submenu contextben:
// https://example.com/hu/reservations/index.php?Itemid=123&option=com_solidres&task=payment.process
// ✓ Működik! Nincs 404!
```

## 4. Paraméter Átadás Összehasonlítása

### Beépített Módszer

```php
<!-- Joomla automatikusan kezeli -->
<form action="<?php echo JRoute::_('index.php'); ?>">
    <input type="hidden" name="option" value="com_solidres" />
    <input type="hidden" name="task" value="payment.complete" />
    <!-- Itemid automatikusan hozzáadódik a JRoute::_() által -->
</form>
```

**Automatikusan hozzáadódik:**
- Itemid
- SEF routing információk
- Language tag (ha multi-language)

### Qvik/Revolut - HIBÁS

```javascript
// ❌ HIBÁS - Paraméterek hiányoznak
const params = new URLSearchParams({
    'option': 'com_solidres',
    'task': 'payment.process',
    'reservation_id': reservationId
    // Itemid HIÁNYZIK!
    // hub_id HIÁNYZIK!
    // property_id HIÁNYZIK!
});
```

**Probléma:**
- Joomla nem tudja azonosítani a menu item-et → routing hiba
- Hub context elvész → multi-hub környezetben hiba
- Property context elvész → nem tudja, melyik ingatlanhoz tartozik

### Qvik/Revolut - HELYES

```javascript
// ✓ HELYES - Minden kritikus paraméter átadva
function buildAjaxUrl(params) {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Kritikus paraméterek kinyerése a jelenlegi URL-ből
    const criticalParams = {
        'Itemid': urlParams.get('Itemid'),           // ← KÖTELEZŐ!
        'hub_id': urlParams.get('hub_id'),           // ← KÖTELEZŐ multi-hub-ban!
        'property_id': urlParams.get('property_id'), // ← KÖTELEZŐ!
        'site_id': urlParams.get('site_id')          // ← KÖTELEZŐ multi-site-ban!
    };
    
    // Összevonás az új paraméterekkel
    const allParams = { ...criticalParams, ...params };
    
    return buildUrl(allParams);
}
```

## 5. Error Handling Összehasonlítása

### Beépített Módszer

```php
// PHP-ban server-side error handling
try {
    $result = $paymentModel->processPayment($data);
    
    if (!$result) {
        $app->enqueueMessage('Payment failed', 'error');
        $app->redirect(JRoute::_('index.php?option=com_solidres&view=reservation&layout=error'));
    }
    
    $app->redirect(JRoute::_('index.php?option=com_solidres&view=reservation&layout=success'));
} catch (Exception $e) {
    $app->enqueueMessage($e->getMessage(), 'error');
    $app->redirect(JRoute::_('index.php?option=com_solidres&view=reservation&layout=error'));
}
```

**Előny:** Egyszerű server-side error handling

**Hátrány:** Nincs client-side feedback (full page reload)

### Qvik/Revolut

```javascript
// Háromszintű error handling JavaScript-ben
fetch(url, options)
    .then(response => {
        // 1. szint: HTTP status ellenőrzés
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // 2. szint: API response validálás
        if (!data.success) {
            throw new Error(data.message || 'Operation failed');
        }
        // Sikeres feldolgozás
        handleSuccess(data);
    })
    .catch(error => {
        // 3. szint: Hálózati/parse hibák
        console.error('Error:', error);
        showErrorMessage(error.message);
    });
```

**Előny:** 
- Részletes client-side error handling
- Real-time feedback
- Nincs page reload

**Hátrány:** 
- Komplexebb implementáció
- JavaScript required

## 6. Routing Különbségek

### Beépített Módszer (Server-side Routing)

```
Böngésző → Form Submit → Joomla Router → Controller → Model → View → Response
                            ↓
                    Automatikus routing:
                    - Itemid feloldás
                    - SEF URL kezelés
                    - Menu context
```

**Előny:** Minden automatikus, nincs manual URL építés

### Qvik/Revolut (Client-side → AJAX → Server-side)

```
Böngésző → JavaScript fetch → Server AJAX endpoint → Controller → Model → JSON Response
    ↓
Manual URL építés:
- window.location.pathname
- Paraméterek kinyerése
- URL összefűzés
- Context megőrzés
```

**Probléma:** Ha nem megfelelő az URL építés → 404 vagy routing hiba

## 7. Miért 404 Submenu Contextben?

### A Probléma Anatómiája

#### Submenu URL struktúra:
```
https://example.com/hu/reservations/index.php?option=com_solidres&...
                     ^^^^^^^^^^^^^^^^^^
                     Ez a submenu path!
```

#### Hibás JavaScript (hardcoded /index.php):
```javascript
const url = window.location.origin + '/index.php?...';
// Eredmény: https://example.com/index.php?...
//                                ^^^^^^^^^^
//                                Hiányzik: /hu/reservations/
```

#### Szerver oldali hatás:
1. Kérés érkezik: `GET /index.php?option=com_solidres&...`
2. Joomla router keresi a menüpontot az Itemid alapján
3. Itemid-hez tartozó menüpont path: `/hu/reservations/index.php`
4. Request path: `/index.php`
5. **ELTÉRÉS** → 404 Not Found vagy Invalid Menu Item

#### Helyes JavaScript (context-aware):
```javascript
const url = window.location.origin + window.location.pathname + '?...';
// Eredmény: https://example.com/hu/reservations/index.php?...
//                                ^^^^^^^^^^^^^^^^^^^^^^^^^^
//                                Megmarad a teljes path!
```

#### Szerver oldali hatás:
1. Kérés érkezik: `GET /hu/reservations/index.php?option=com_solidres&...`
2. Joomla router keresi a menüpontot az Itemid alapján
3. Itemid-hez tartozó menüpont path: `/hu/reservations/index.php`
4. Request path: `/hu/reservations/index.php`
5. **EGYEZÉS** → Request processed ✓

## 8. Context Paraméterek Fontossága

| Paraméter | Beépített Módszer | Qvik/Revolut (Hibás) | Qvik/Revolut (Helyes) |
|-----------|-------------------|----------------------|-----------------------|
| **Itemid** | Automatikus (JRoute) | ❌ Hiányzik | ✓ Manuálisan átadva |
| **hub_id** | Automatikus (session) | ❌ Hiányzik | ✓ URL-ből kinyerve |
| **property_id** | Automatikus (session) | ❌ Hiányzik | ✓ URL-ből kinyerve |
| **site_id** | Automatikus (config) | ❌ Hiányzik | ✓ URL-ből kinyerve |
| **Path** | Automatikus (JRoute) | ❌ Hardcoded | ✓ window.location.pathname |

## 9. Megoldás Összefoglalás

### A Qvik/Revolut 404 probléma megoldása:

1. **Path megőrzés:**
   ```javascript
   // Használd: window.location.pathname
   // Ne használd: hardcoded '/index.php'
   ```

2. **Paraméter átadás:**
   ```javascript
   // Nyerd ki a kritikus paramétereket a jelenlegi URL-ből
   const urlParams = new URLSearchParams(window.location.search);
   ```

3. **Context-aware URL builder:**
   ```javascript
   // Implementálj egy buildAjaxUrl() függvényt
   function buildAjaxUrl(params) { /* ... */ }
   ```

4. **Minden AJAX hívásban használd:**
   ```javascript
   const url = buildAjaxUrl({ option: '...', task: '...' });
   fetch(url, { /* ... */ });
   ```

## 10. Ellenőrző Lista

**Qvik/Revolut payment implementációhoz:**

- [ ] `buildAjaxUrl()` függvény implementálva
- [ ] `window.location.pathname` használva (nem hardcoded path)
- [ ] `window.location.origin` használva
- [ ] Itemid paraméter átadva
- [ ] hub_id paraméter átadva (ha van)
- [ ] property_id paraméter átadva
- [ ] site_id paraméter átadva (ha van)
- [ ] reservation_id paraméter átadva
- [ ] Háromszintű error handling implementálva
- [ ] `X-Requested-With: XMLHttpRequest` header hozzáadva
- [ ] Tesztelve root contextben
- [ ] Tesztelve submenu contextben
- [ ] Tesztelve hub contextben (ha alkalmazható)

**Ha minden ✓ → Nincs többé 404 hiba!**
