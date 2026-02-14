# confirmationform.php Gyors Referencia

## 🎯 Mi ez a fájl?

A `confirmationform.php` egy Joomla/Solidres fizetési plugin sablon, amely kezeli a fizetés megerősítését AJAX kérésekkel és átirányítással, miközben minden URL kontextust (menüpont, almenü, hub, deep-link) megőriz.

## 📍 Fájl Elérési Útvonalak

```
plugins/solidrespayment/qvik/tmpl/confirmationform.php      ← Qvik plugin
plugins/solidrespayment/revolut/tmpl/confirmationform.php   ← Revolut plugin
```

## 🔑 Kulcs Funkciók (JavaScript)

### 1. getUrlParams()
```javascript
// URL paraméterek kinyerése
const params = getUrlParams();
// → { hub_id, property_id, site_id, Itemid, reservation_id }
```

### 2. buildAjaxUrl(task, format)
```javascript
// AJAX URL építés pathname megőrzéssel
const url = buildAjaxUrl('payment.processQvikPayment', 'json');
// → https://example.com/hu/booking/property?option=com_solidres&task=...&hub_id=5&...
```

### 3. buildRedirectUrl(view, layout)
```javascript
// Átirányítási URL teljes kontextussal
const url = buildRedirectUrl('reservation', 'complete');
// → https://example.com/hu/booking/property?option=com_solidres&view=reservation&layout=complete&...
```

### 4. confirmPayment()
```javascript
// Fizetés feldolgozása Fetch API-val
confirmPayment();
// → AJAX POST → JSON válasz → sikeres/hiba üzenet → átirányítás
```

## 🏗️ HTML Struktúra

```html
<div class="qvik-confirmation-form">
    <h3>Fizetés megerősítése</h3>
    
    <!-- Foglalás részletek -->
    <div class="confirmation-details">
        <div class="reservation-summary">
            <p>Foglalás ID: <span id="reservation-id">123</span></p>
            <p>Végösszeg: <span id="total-amount">50000 HUF</span></p>
        </div>
    </div>
    
    <!-- Gombok -->
    <div class="confirmation-actions">
        <button id="qvik-confirm-btn">Fizetés megerősítése</button>
        <button id="qvik-back-btn">Vissza</button>
    </div>
    
    <!-- Státusz -->
    <div id="payment-status"></div>
    
    <!-- Betöltő -->
    <div id="payment-loader"></div>
</div>
```

## 🔄 Folyamat (Flow)

```
1. Felhasználó kattint "Megerősítés" gombra
   ↓
2. toggleLoader(true) - Betöltő megjelenik
   ↓
3. buildAjaxUrl() - URL építés
   ↓
4. fetch() - AJAX POST kérés
   ↓
5. Backend feldolgozás
   ↓
6. JSON válasz érkezik
   ↓
7. if (success):
   → showSuccess() - Sikeres üzenet
   → setTimeout() - 2 mp várakozás
   → buildRedirectUrl() - Átirányítási URL
   → window.location.href - Átirányítás
   
   else:
   → showError() - Hiba üzenet
   → toggleLoader(false) - Betöltő elrejt
```

## 📊 URL Paraméterek (Mindig megmaradnak!)

| Paraméter | Leírás | Példa |
|-----------|--------|-------|
| `hub_id` | Hub azonosító | `5` |
| `property_id` | Szálláshely ID | `123` |
| `site_id` | Oldal ID | `1` |
| `Itemid` | Menüpont ID | `456` |
| `reservation_id` | Foglalás ID | `789` |

## 🎨 Qvik vs Revolut Különbségek

| Elem | Qvik | Revolut |
|------|------|---------|
| CSS osztály | `qvik-confirmation-form` | `revolut-confirmation-form` |
| Gomb ID | `qvik-confirm-btn` | `revolut-confirm-btn` |
| Vissza gomb ID | `qvik-back-btn` | `revolut-back-btn` |
| Task neve | `payment.processQvikPayment` | `payment.processRevolutPayment` |
| Payment method | `'qvik'` | `'revolut'` |
| Nyelvi prefix | `PLG_SOLIDRESPAYMENT_QVIK_` | `PLG_SOLIDRESPAYMENT_REVOLUT_` |

## 📝 Szükséges Nyelvi Konstansok

```ini
# Qvik példa
PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_CONFIRMATION="Fizetés megerősítése"
PLG_SOLIDRESPAYMENT_QVIK_CONFIRM_MESSAGE="Kérem erősítse meg a fizetést."
PLG_SOLIDRESPAYMENT_QVIK_CONFIRM_PAYMENT="Fizetés megerősítése"
PLG_SOLIDRESPAYMENT_QVIK_PROCESSING="Feldolgozás..."
PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_SUCCESS="Sikeres fizetés!"
PLG_SOLIDRESPAYMENT_QVIK_PAYMENT_ERROR="Hiba történt."

# Közös Solidres
COM_SOLIDRES_RESERVATION_ID="Foglalás azonosító"
COM_SOLIDRES_TOTAL_AMOUNT="Végösszeg"
COM_SOLIDRES_BACK="Vissza"
```

## 🔧 Backend API Követelmények

### Végpont
```
POST /index.php?option=com_solidres&task=payment.processQvikPayment&format=json&...
```

### Kérés Body
```json
{
    "reservation_id": "789",
    "payment_method": "qvik"
}
```

### Válasz Formátum
```json
{
    "success": true,
    "message": "Sikeres fizetés!"
}
```

vagy

```json
{
    "success": false,
    "message": "Hiba oka..."
}
```

## 🛡️ Biztonság

### XSS Védelem
```php
<?php echo htmlspecialchars($this->reservation->id); ?>
```

### CSRF Védelem
```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
```

## ⚡ Kritikus Rész: pathname Megőrzés

```javascript
// ❌ ROSSZ - pathname elvész
const url = window.location.origin + '?option=com_solidres&...';

// ✅ JÓ - pathname megmarad
const origin = window.location.origin;
const pathname = window.location.pathname;
const url = origin + pathname + '?option=com_solidres&...';
```

**Miért fontos?**
- `/hu/booking/hotels/property` → almenü kontextus
- `/en/reservations/special` → deep-link
- Pathname nélkül: menürendszer összeomlása!

## 🧪 Gyors Teszt

### Console-ban:
```javascript
// 1. URL paraméterek tesztelése
console.log(getUrlParams());

// 2. AJAX URL tesztelése
console.log(buildAjaxUrl('payment.test', 'json'));

// 3. Redirect URL tesztelése
console.log(buildRedirectUrl('reservation', 'complete'));
```

### Vizuális teszt:
```javascript
// Sikeres üzenet
showSuccess('Teszt üzenet');

// Hiba üzenet
showError('Teszt hiba');

// Betöltő
toggleLoader(true);
setTimeout(() => toggleLoader(false), 2000);
```

## 📦 Telepítés 3 Lépésben

```bash
# 1. Fájl másolása
cp confirmationform.php /path/to/joomla/plugins/solidrespayment/qvik/tmpl/

# 2. Jogosultságok
chmod 644 /path/to/joomla/plugins/solidrespayment/qvik/tmpl/confirmationform.php

# 3. Cache törlése (Joomla admin-ban)
Rendszer → Cache törlése
```

## 🔍 Hibakeresés

### 1. AJAX nem indul el
```javascript
// Console-ban ellenőrizd:
console.log(buildAjaxUrl('payment.processQvikPayment', 'json'));
// Helyes formátum látható?
```

### 2. Paraméterek elvesznek
```javascript
// Console-ban ellenőrizd:
console.log(getUrlParams());
// Minden paraméter benne van?
```

### 3. Átirányítás nem működik
```javascript
// Console-ban ellenőrizd:
console.log(buildRedirectUrl('reservation', 'complete'));
// Helyes URL?
```

### 4. Backend hiba
```javascript
// Nézd meg a hálózati forgalmat (F12 → Network)
// Státusz kód: 200? 
// Válasz formátum: JSON?
```

## 📚 További Dokumentáció

- **Teljes leírás**: `CONFIRMATIONFORM_TELJES.md`
- **Implementációs jegyzetek**: `IMPLEMENTATION_NOTES.md`
- **Összefoglaló**: `SUMMARY.md`
- **Vizuális áttekintő**: `VISUAL_OVERVIEW.md`
- **Projekt README**: `README.md`

## ✅ Checklist

Használat előtt ellenőrizd:

- [ ] Fájl a helyes helyen van
- [ ] Jogosultságok helyesek (644)
- [ ] Nyelvi konstansok definiálva
- [ ] Backend API végpont létezik
- [ ] `$this->reservation` objektum elérhető
- [ ] Joomla cache törölve

Működés közben teszteld:

- [ ] Gombok kattinthatók
- [ ] AJAX kérés elindul
- [ ] Betöltő megjelenik
- [ ] Üzenetek megjelennek
- [ ] Átirányítás működik
- [ ] URL paraméterek megmaradnak

## 🎓 Tanulságok

1. **Pathname kritikus**: `window.location.pathname` nélkül almenük nem működnek
2. **Minden paraméter fontos**: `hub_id`, `property_id`, `site_id`, `Itemid`, `reservation_id`
3. **Fetch > XMLHttpRequest**: Modern, tisztább hibakezelés
4. **Promise-ok**: `.then().catch()` struktúra a legmegbízhatóbb
5. **Felhasználói visszajelzés**: Betöltő + üzenetek + késleltetett átirányítás

## 💡 Pro Tippek

- Mindig használj `const` és `let`, ne `var`
- IIFE-ben tartsd a kódot: `(function() { ... })()`
- Console.log hibakereséshez
- Network tab (F12) az AJAX követéshez
- `'use strict'` a biztonságosabb kódhoz

---

**Verzió**: 1.0  
**Utolsó frissítés**: 2026-02-14  
**Részletes dokumentáció**: CONFIRMATIONFORM_TELJES.md
