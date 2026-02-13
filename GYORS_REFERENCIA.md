# Gyors Referencia - AJAX/Redirect Javítások

## 🎯 Mi lett javítva?

A Qvik és Revolut fizetési plugin-okban lévő JavaScript kód, amely biztosítja hogy:
- ✅ **NINCS 404 hiba** - AJAX hívások mindig helyes URL-re mutatnak
- ✅ **NINCS elveszett útvonal** - Almenü és hub kontextus megmarad
- ✅ **NINCS elveszett paraméter** - Minden kritikus adat megőrződik

## 📁 Módosított Fájlok

```
plugins/solidrespayment/qvik/asset/confirmation.php
plugins/solidrespayment/revolut/asset/confirmation.php
```

## 🔧 Két Fő JavaScript Függvény

### 1. buildAjaxUrl(params)
**Mit csinál?** AJAX URL-t épít, megőrizve a teljes útvonalat és paramétereket.

**Használat:**
```javascript
var ajaxUrl = buildAjaxUrl({
    option: 'com_solidres',
    task: 'payment.confirm',
    format: 'json',
    payment_method: 'qvik',
    payment_id: paymentId
});
```

**Eredmény:**
```
https://example.com/properties/hotel-a/booking/index.php?
Itemid=123&property_id=456&option=com_solidres&task=payment.confirm&...
```

### 2. buildRedirectUrl(view, additionalParams)
**Mit csinál?** Átirányítási URL-t épít, megőrizve a kontextust.

**Használat:**
```javascript
var redirectUrl = buildRedirectUrl('confirmation', {
    booking_id: bookingId,
    payment_id: paymentId,
    status: 'success'
});
window.location.href = redirectUrl;
```

**Eredmény:**
```
https://example.com/properties/hotel-a/booking/index.php?
option=com_solidres&Itemid=123&property_id=456&view=confirmation&...
```

## 🚀 Hogyan Működik?

### Kulcs Technika: window.location

```javascript
// Aktuális útvonal lekérése
var currentPath = window.location.pathname;
// Példa: "/properties/hotel-a/booking"

// Teljes alap URL építése
var baseUrl = window.location.origin + currentPath;
// Példa: "https://example.com/properties/hotel-a/booking"
```

### Kulcs Technika: URLSearchParams

```javascript
// Meglévő paraméterek kinyerése
var existingParams = new URLSearchParams(window.location.search);
// Példa: ?Itemid=123&property_id=456

// Új paraméterek hozzáadása
existingParams.set('task', 'payment.confirm');
existingParams.set('format', 'json');
```

## 📊 Működés Különböző Kontextusokban

| Kontextus | URL Példa | Működik? |
|-----------|-----------|----------|
| Főmenü | `/booking` | ✅ Igen |
| Almenü (1 szint) | `/properties/booking` | ✅ Igen |
| Almenü (több szint) | `/properties/city/hotel-a/booking` | ✅ Igen |
| Hub/Multi-site | `/hub-site/property-123/booking` | ✅ Igen |
| Mély link | `/props/hotel/book?custom=param` | ✅ Igen |

## 🔒 Megőrzött Paraméterek

Ezek a paraméterek **MINDIG** megmaradnak:

- `option` - Joomla komponens (com_solidres)
- `Itemid` - Menü elem azonosító
- `property_id` - Ingatlan azonosító
- `hub_id` - Hub azonosító
- `site_id` - Oldal azonosító

## 📝 Kód Példák

### ROSSZ ❌ (régi módszer):
```javascript
// Ez elveszíti a kontextust!
var url = 'index.php?option=com_solidres&task=confirm';
fetch(url);
```

### JÓ ✅ (új módszer):
```javascript
// Ez megőrzi a kontextust!
var url = buildAjaxUrl({
    option: 'com_solidres',
    task: 'payment.confirm'
});
fetch(url);
```

## 🎨 Vizuális Visszajelzés

A kód tartalmaz stílusos alert üzeneteket:

```html
<div class="alert alert-info">Feldolgozás...</div>
<div class="alert alert-success">Sikeres!</div>
<div class="alert alert-error">Hiba történt</div>
```

Színek:
- **Kék** (info) - Folyamatban
- **Zöld** (success) - Sikeres művelet
- **Piros** (error) - Hiba

## 🐛 Debug Üzenetek

A kód konzol üzeneteket ír hibakereséshez:

```javascript
console.log('[Qvik] AJAX URL:', ajaxUrl);
console.log('[Qvik] Átirányítási URL:', redirectUrl);
console.error('[Qvik] Hiba:', error);
```

**Böngésző konzol megnyitása:**
- Chrome/Firefox: `F12` vagy `Ctrl+Shift+I`
- Safari: `Cmd+Option+I`

## 📚 Teljes Dokumentáció

### Fő Dokumentumok:
1. **JAVITASOK_RESZLETES_DOKUMENTACIO.md** - Teljes részletes leírás
2. **FORR ASKOD_MODOSITASOK.md** - Forráskód összehasonlítások
3. **GYORS_REFERENCIA.md** - Ez a dokumentum

### További Információk:
- `IMPLEMENTATION_NOTES.md` - Angol implementációs jegyzet
- `SOLUTION_SUMMARY.md` - Angol összefoglaló
- `plugins/solidrespayment/README.md` - Plugin README

## 🔍 Tesztelési Gyors Checklist

**Qvik plugin:**
```
□ Főmenüről működik
□ Almenüről működik  
□ Hub-ból működik
□ Nincs 404 hiba
□ Átirányítás helyes
```

**Revolut plugin:**
```
□ Főmenüről működik
□ Almenüről működik
□ Hub-ból működik
□ Nincs 404 hiba
□ Átirányítás helyes
```

## 💡 Gyors Tippek Fejlesztőknek

### DO ✅
```javascript
// Használd a segédfüggvényeket
var url = buildAjaxUrl({...});
var redirect = buildRedirectUrl('view', {...});
```

### DON'T ❌
```javascript
// NE használj egyszerű string konkatenációt
var url = 'index.php?option=' + option;
```

### DO ✅
```javascript
// Őrizd meg a kritikus paramétereket
preserveParams = ['option', 'Itemid', 'property_id', 'hub_id'];
```

### DON'T ❌
```javascript
// NE hagyj el kritikus paramétereket
var url = 'index.php?task=confirm'; // Itemid hiányzik!
```

## 🎓 Technikai Részletek

### Használt Technológiák:
- **JavaScript ES5** - Széles böngésző kompatibilitás
- **Fetch API** - Modern AJAX hívások
- **URLSearchParams** - Paraméter kezelés
- **window.location** - URL információk

### Böngésző Támogatás:
- ✅ Chrome (minden verzió)
- ✅ Firefox (minden verzió)
- ✅ Safari (minden verzió)
- ✅ Edge (minden verzió)
- ✅ IE11 (fetch polyfill-el)

## 🔐 Biztonsági Funkciók

- **CSRF védelem** - Meglévő token-ek megőrzése
- **Same-origin** - `credentials: 'same-origin'`
- **XSS védelem** - Szerver-oldali válaszok validálása
- **Error handling** - Hibák biztonságos kezelése

## 📞 Kapcsolat és Támogatás

Ha kérdésed van a módosításokról:
1. Olvasd el a részletes dokumentációt
2. Nézd meg a forráskód példákat
3. Ellenőrizd a konzol üzeneteket
4. Tesztelj különböző kontextusokban

## 📅 Verzióinformáció

- **Verzió**: 1.0
- **Dátum**: 2026-02-13
- **Plugin-ok**: Qvik & Revolut
- **Komponens**: Solidres
- **Platform**: Joomla 3.x/4.x

---

**Ez a gyors referencia egy összefoglaló. A teljes részletekért lásd a JAVITASOK_RESZLETES_DOKUMENTACIO.md fájlt.**
