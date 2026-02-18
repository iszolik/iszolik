# Fizetési Mód Context Kezelés - Implementációs Útmutató

## Áttekintés

Ez a dokumentum részletes útmutatót ad a fizetési mód context kezelés javításához a Solidres rendszerben, amely megszünteti a 404 hibákat és biztosítja a konzisztens működést minden menü/hub/submenu környezetben.

## Fájlok és Módosítások

### 1. Template Fájl: `com_solidres/layouts/asset/payments.php`

**Fájl helye:** `/administrator/components/com_solidres/layouts/asset/payments.php` vagy `/components/com_solidres/layouts/asset/payments.php`

**Módosítás:** Teljes fájl cseréje a `patches/payments.php` fájllal

**Változások:**
- Session context validálás hozzáadása
- Context-független AJAX URL építés
- Háromszintű error handling
- Default payment method automatikus kiválasztása és mentése

**Implementációs lépések:**
```bash
# 1. Backup készítése
cp com_solidres/layouts/asset/payments.php com_solidres/layouts/asset/payments.php.backup.$(date +%Y%m%d_%H%M%S)

# 2. Új fájl másolása
cp patches/payments.php com_solidres/layouts/asset/payments.php
```

### 2. Controller: `components/com_solidres/controllers/reservation.php`

**Fájl helye:** `/components/com_solidres/controllers/reservation.php`

**Módosítás:** `updatePaymentMethod()` metódus hozzáadása a ReservationController osztályhoz

**Implementációs lépések:**

1. Nyisd meg a `reservation.php` fájlt
2. Keresd meg a ReservationController osztályt (általában `class ReservationController extends JControllerLegacy`)
3. Add hozzá a `patches/reservation_controller_patch.php` fájlban található `updatePaymentMethod()` metódust az osztály végéhez

**Példa:**
```php
class ReservationController extends JControllerLegacy
{
    // ... meglévő metódusok ...
    
    /**
     * Frissíti a kiválasztott fizetési módot a session-ben
     * Context-független működést biztosít minden menü/hub/submenu környezetben
     * 
     * @return void JSON válasz és alkalmazás leállítása
     */
    public function updatePaymentMethod()
    {
        // A patches/reservation_controller_patch.php tartalmát ide másolni
    }
}
```

### 3. Helper Osztály: `components/com_solidres/helpers/sessioncontext.php`

**Fájl helye:** `/components/com_solidres/helpers/sessioncontext.php`

**Módosítás:** Új fájl létrehozása

**Implementációs lépések:**
```bash
# 1. Helper könyvtár létrehozása, ha nem létezik
mkdir -p components/com_solidres/helpers

# 2. Új helper fájl másolása
cp patches/sessioncontext.php components/com_solidres/helpers/sessioncontext.php
```

**Használat a kódban:**
```php
// Példa: payment template elején
JLoader::register('SolidresSessionContextValidator', JPATH_COMPONENT . '/helpers/sessioncontext.php');

$reservationDetails = SolidresSessionContextValidator::validateAndSync();
$paymentMethodId = SolidresSessionContextValidator::getPaymentMethodId();
```

## Nyelvi Konstansok

Add hozzá a következő konstansokat a nyelvi fájlokhoz:

### Magyar (`administrator/language/hu-HU/hu-HU.com_solidres.ini`):

```ini
SR_PAYMENT_METHOD_SELECTION="Fizetési mód kiválasztása"
SR_NO_PAYMENT_METHODS_AVAILABLE="Nincs elérhető fizetési mód"
SR_INVALID_PAYMENT_METHOD="Érvénytelen fizetési mód"
SR_PAYMENT_METHOD_UPDATED_SUCCESSFULLY="Fizetési mód sikeresen frissítve"
```

### Angol (`administrator/language/en-GB/en-GB.com_solidres.ini`):

```ini
SR_PAYMENT_METHOD_SELECTION="Payment Method Selection"
SR_NO_PAYMENT_METHODS_AVAILABLE="No payment methods available"
SR_INVALID_PAYMENT_METHOD="Invalid payment method"
SR_PAYMENT_METHOD_UPDATED_SUCCESSFULLY="Payment method updated successfully"
```

## Tesztelési Forgatókönyvek

### 1. Root Menü Context Teszt

**URL példa:**
```
https://example.com/index.php?option=com_solidres&view=reservation&Itemid=123&property_id=1&reservation_id=456
```

**Ellenőrzési pontok:**
- [ ] Fizetési módok helyesen megjelennek
- [ ] Kiválasztott fizetési mód automatikusan mentésre kerül
- [ ] Nincs 404 hiba az AJAX híváskor
- [ ] Console log-ban látható: "Payment method update requested"
- [ ] Console log-ban látható: "Fizetési mód sikeresen frissítve"

**Debug:**
```javascript
// Browser konzolban:
console.log('Current URL:', window.location.href);
// Ellenőrizd, hogy a buildAjaxUrl() helyes URL-t épít-e
```

### 2. Hub Context Teszt

**URL példa:**
```
https://example.com/index.php?option=com_solidres&view=reservation&Itemid=123&hub_id=5&property_id=1&reservation_id=456
```

**Ellenőrzési pontok:**
- [ ] `hub_id` paraméter megmarad az AJAX hívásban
- [ ] Session-ben `hub_id` helyesen tárolódik
- [ ] Nincs 404 hiba
- [ ] Response JSON tartalmazza a `hub_id` értéket

**Debug:**
```javascript
// Browser konzolban, AJAX hívás után:
// Ellenőrizd a fetch URL-t
document.addEventListener('paymentMethodUpdated', function(e) {
    console.log('Context preserved:', e.detail.context);
});
```

### 3. Submenu Context Teszt

**URL példa:**
```
https://example.com/hotel/index.php?option=com_solidres&view=reservation&Itemid=456&property_id=2&reservation_id=789
```

**Ellenőrzési pontok:**
- [ ] Pathname preservation működik (`/hotel/` megmarad)
- [ ] AJAX URL helyes: `https://example.com/hotel/index.php?...` vagy `https://example.com/index.php?...`
- [ ] Nincs 404 hiba
- [ ] Összes paraméter megmarad

**Debug:**
```javascript
// Browser konzolban:
console.log('Pathname:', window.location.pathname);
console.log('Contains index.php:', window.location.pathname.indexOf('index.php') !== -1);
```

### 4. Session Persistence Teszt

**Lépések:**
1. Válassz ki egy fizetési módot
2. Navigálj egy másik oldalra (pl. vissza a property listához)
3. Navigálj vissza a reservation oldalra
4. Ellenőrizd, hogy a korábban kiválasztott fizetési mód checked-e marad

**Ellenőrzési pontok:**
- [ ] Kiválasztott fizetési mód perzisztens
- [ ] Session-ben helyesen tárolódik
- [ ] Context paraméterek megmaradnak

**Debug PHP:**
```php
// Temp debug code a payments.php elején:
$session = JFactory::getSession();
$debugData = $session->get('reservationdetails', null, 'sr');
echo '<pre style="display:none;" id="debug-session">' . print_r($debugData, true) . '</pre>';
```

### 5. Default Payment Method Teszt

**Lépések:**
1. Nincs előzetesen kiválasztott fizetési mód
2. Töltsd be a payments template-et
3. Ellenőrizd, hogy a default vagy az első fizetési mód checked-e
4. Ellenőrizd, hogy automatikusan meghívódik-e az `updatePaymentMethod()`

**Ellenőrzési pontok:**
- [ ] Default vagy első fizetési mód automatikusan checked
- [ ] Automatikus mentés megtörténik
- [ ] Console log: "Fizetési mód kiválasztva"

## Hibakeresés (Troubleshooting)

### Probléma: 404 hiba AJAX híváskor

**Lehetséges okok:**
1. Helytelen URL építés
2. Hiányzó `option` vagy `task` paraméter
3. Submenu path nem őrződik meg

**Megoldás:**
```javascript
// Debug a browser konzolban:
function buildAjaxUrl() {
    var baseUrl = window.location.origin + window.location.pathname;
    if (window.location.pathname.indexOf('index.php') === -1) {
        baseUrl = window.location.origin + '/index.php';
    }
    
    var params = new URLSearchParams(window.location.search);
    var url = baseUrl + '?option=com_solidres&task=reservation.updatePaymentMethod';
    url += '&Itemid=' + (params.get('Itemid') || '');
    url += '&property_id=' + (params.get('property_id') || '');
    
    console.log('Built AJAX URL:', url);
    return url;
}

// Teszteld:
buildAjaxUrl();
```

### Probléma: Session nem frissül

**Lehetséges okok:**
1. Controller metódus nem fut le
2. Session névtér ("sr") eltérő
3. PHP session problémák

**Megoldás:**
```php
// Temp debug a controller updatePaymentMethod() metódusban:
error_log('updatePaymentMethod called with payment_method_id: ' . $paymentMethodId);
error_log('Session ID: ' . $session->getId());
error_log('Session data: ' . print_r($session->get('reservationdetails', null, 'sr'), true));
```

### Probléma: Context paraméterek elvesznek

**Lehetséges okok:**
1. URL paraméterek nem kerülnek át az AJAX hívásba
2. Session context nem frissül

**Megoldás:**
```javascript
// Debug function az AJAX hívásban:
function updatePaymentMethod(paymentMethodId) {
    var params = getUrlParams();
    console.log('URL params:', params);
    
    var formData = new FormData();
    Object.keys(params).forEach(function(key) {
        formData.append(key, params[key]);
        console.log('Added to FormData:', key, '=', params[key]);
    });
    
    // ... fetch ...
}
```

## Validációs Checklist

Implementáció után végezd el a következő ellenőrzéseket:

### Funkcionális Tesztek
- [ ] Fizetési mód kiválasztás root menüben működik
- [ ] Fizetési mód kiválasztás hub context-ben működik
- [ ] Fizetési mód kiválasztás submenu context-ben működik
- [ ] Default fizetési mód automatikusan kiválasztódik
- [ ] Kiválasztott fizetési mód perzisztens navigáció után
- [ ] Nincs 404 hiba egyetlen context-ben sem

### Technikai Tesztek
- [ ] AJAX URL-ek helyesen épülnek minden context-ben
- [ ] Session helyesen frissül minden fizetési mód váltáskor
- [ ] Context paraméterek (Itemid, hub_id, property_id, stb.) megmaradnak
- [ ] Console-ban nincsenek JavaScript hibák
- [ ] PHP error log-ban nincsenek hibák

### Biztonsági Tesztek
- [ ] Input validáció működik (payment_method_id > 0)
- [ ] XSS védelem működik (htmlspecialchars használata)
- [ ] CSRF token ellenőrzés (ha szükséges)
- [ ] Session hijacking védelem

### Teljesítmény Tesztek
- [ ] Egy fizetési mód kiválasztása < 500ms
- [ ] Session műveletek nem okoznak bottleneck-et
- [ ] JavaScript inicializálás < 100ms

## Következő Lépések

1. **Implementáld a változtatásokat** a fenti útmutató szerint
2. **Teszteld minden context-ben** (root, hub, submenu)
3. **Ellenőrizd a console log-okat** hibakeresés céljából
4. **Verifikáld a session tartalmát** debug eszközökkel
5. **Végezz integrációs teszteket** a teljes checkout folyamattal

## További Információk

- **Repository memories:** URL preservation pattern dokumentáció
- **Kapcsolódó fájlok:** plugins/solidrespayment/*/tmpl/*.php (hasonló pattern)
- **Referencia implementáció:** guestform.php (AJAX context preservation)

## Support és Hibajelentés

Ha problémába ütközöl az implementáció során:

1. Ellenőrizd a console log-okat
2. Nézd meg a PHP error log-ot
3. Használd a fenti debug kódokat
4. Dokumentáld a hibát (URL, context, console output)
5. Jelezd a fejlesztői csapatnak

---

**Verzió:** 1.0  
**Utolsó frissítés:** 2026-02-18  
**Kapcsolódó issue:** Payment Method Context Handling
