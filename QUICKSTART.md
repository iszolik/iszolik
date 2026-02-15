# 🚀 GYORS TELEPÍTÉSI ÚTMUTATÓ

## Mi található ebben a repository-ban?

Ez a repository tartalmazza a **teljes Qvik/Revolut fizetési AJAX patch**-et, amely biztosítja:

✅ **Abszolút `/index.php` végpont** - Minden AJAX kérés a domain gyökérből POST-ol  
✅ **Dinamikus átirányítás** - `guestform` → `confirmationform` automatikusan  
✅ **Magyar kommentek** - Minden sor kódban érthető magyarázat  
✅ **Testreszabható** - ID-k, class-ok, hibaüzenetek módosíthatók  

---

## 📦 Fájlok

1. **payment-ajax-patch.js** → A fő JavaScript kód (ezt kell beilleszteni!)
2. **README.md** → Teljes dokumentáció magyarul
3. **INTEGRATION_GUIDE.md** → Lépésről lépésre telepítési útmutató
4. **example-guestform-integration.php** → Példa sablon guestform.php-hoz
5. **example-confirmationform-integration.php** → Példa sablon confirmationform.php-hoz

---

## ⚡ 3 Lépéses Telepítés

### 1️⃣ Fájl Másolása

Másolja a `payment-ajax-patch.js` fájlt a megfelelő helyre:

```bash
# Qvik esetén:
cp payment-ajax-patch.js /path/to/joomla/plugins/solidrespayment/qvik/tmpl/

# Revolut esetén:
cp payment-ajax-patch.js /path/to/joomla/plugins/solidrespayment/revolut/tmpl/
```

### 2️⃣ Guestform.php Módosítása

Nyissa meg a `guestform.php` fájlt és a **fájl végére** (a `?>` után) illessze be:

```php
?>
<script>
<?php include __DIR__ . '/payment-ajax-patch.js'; ?>
</script>
```

### 3️⃣ Confirmationform.php Módosítása

Ugyanígy a `confirmationform.php` fájl végére:

```php
?>
<script>
<?php include __DIR__ . '/payment-ajax-patch.js'; ?>
</script>
```

---

## ✅ Ellenőrzés

### HTML Követelmények

Győződjön meg róla, hogy a HTML tartalmazza:

```html
<!-- 1. Fizetési Form -->
<form id="payment-form" method="post">
    <!-- form mezők -->
</form>

<!-- 2. Fizetés Gomb -->
<button type="submit" class="payment-submit-btn">
    Fizetés
</button>

<!-- 3. Hibaüzenet Konténer -->
<div id="payment-error-message" style="display:none;"></div>

<!-- 4. Betöltés Jelző -->
<div id="payment-loading" style="display:none;">
    Feldolgozás...
</div>
```

### Browser Teszt

1. Nyissa meg a guestform oldalt
2. Nyomja meg **F12** (konzol)
3. Ellenőrizze a logot:
   ```
   [Payment Handler] Inicializálás...
   [Payment Handler] Form eseménykezelő regisztrálva: #payment-form
   ```

---

## 🎨 Testreszabás

Ha eltérő ID-k/class-ok vannak a HTML-ben, módosítsa a `payment-ajax-patch.js` fájl elején:

```javascript
const CONFIG = {
    selectors: {
        paymentForm: '#payment-form',              // ← Cserélje ki
        paymentButton: '.payment-submit-btn',      // ← Cserélje ki
        errorContainer: '#payment-error-message',  // ← Cserélje ki
        loadingIndicator: '#payment-loading'       // ← Cserélje ki
    }
};
```

---

## 📚 Részletes Dokumentáció

Ha többet szeretne tudni:

- **README.md** - Teljes dokumentáció (GYIK, hibaelhárítás, példák)
- **INTEGRATION_GUIDE.md** - Lépésről lépésre útmutató (testreszabás, példák)
- **example-guestform-integration.php** - Teljes példa sablon
- **example-confirmationform-integration.php** - Teljes példa sablon

---

## 🔧 Hibaelhárítás Gyors Útmutató

### Probléma: Nincs log a konzolban

**Megoldás:**
```php
<!-- Ellenőrizze a fájl elérési utat -->
<?php
$patchPath = __DIR__ . '/payment-ajax-patch.js';
var_dump(file_exists($patchPath));  // Kell legyen: bool(true)
?>
```

### Probléma: Form normálisan submit-ol (nem AJAX)

**Megoldás:**
- Ellenőrizze a form ID-t: `<form id="payment-form">`
- Nézze meg a konzol hibaüzeneteit (F12 → Console)

### Probléma: Rossz átirányítás

**Megoldás:**
```javascript
// Konzolban futtassa:
window.PaymentAjaxHandler.buildRedirectUrl()
// Ellenőrizze az eredményt
```

---

## ✨ Főbb Funkciók

### AJAX Végpont

```
Eredeti oldal: https://domain.hu/foglalas/menu/guestform?id=123
AJAX kérés:    https://domain.hu/index.php ← Mindig abszolút!
```

### Átirányítás

```
Előtte: https://domain.hu/foglalas/menu/guestform?id=123
Utána:  https://domain.hu/foglalas/menu/confirmationform?id=123
                                     ^^^^^^^^^^^^^^^^
```

### Paraméter Megőrzés

Az alábbi URL paraméterek automatikusan megmaradnak:
- `Itemid`
- `property_id`
- `hub_id`
- `site_id`
- `reservation_id`
- `option`
- `view`
- `layout`

---

## 🎯 Követelmények

- **Joomla 3.x vagy 4.x** (ajánlott, de nem kötelező)
- **Modern böngésző** (Chrome, Firefox, Safari, Edge)
- **PHP 7.0+**
- **Fetch API támogatás** (IE11-hez polyfill kell)

---

## 📞 Támogatás

1. **Olvassa el**: README.md és INTEGRATION_GUIDE.md
2. **Nézze meg**: example-*.php fájlokat
3. **Ellenőrizze**: Böngésző konzolt (F12)
4. **GitHub**: Nyisson issue-t, ha problémája van

---

## 📝 Changelog

### v1.0.0 (2026-02-15)
- ✅ Kezdeti kiadás
- ✅ Abszolút `/index.php` AJAX végpont
- ✅ Dinamikus `guestform` → `confirmationform` átirányítás
- ✅ Teljes magyar kommentálás
- ✅ Testreszabható konfigurációk
- ✅ Háromszintű hibakezelés
- ✅ Fetch API alapú kommunikáció
- ✅ Globális API exportálás

---

## ⭐ Gyors Ellenőrzőlista

Telepítés után ellenőrizze:

- [ ] `payment-ajax-patch.js` a megfelelő helyen van
- [ ] Beillesztve a `guestform.php` végére
- [ ] Beillesztve a `confirmationform.php` végére
- [ ] HTML-ben van `#payment-form` ID
- [ ] HTML-ben van `.payment-submit-btn` class
- [ ] HTML-ben van `#payment-error-message` ID
- [ ] HTML-ben van `#payment-loading` ID
- [ ] Konzolban látszik: `[Payment Handler] Inicializálás...`
- [ ] Form submit → AJAX (nem töltődik újra az oldal)
- [ ] Sikeres fizetés → átirányítás confirmationform-ra

Ha minden ✅, akkor **KÉSZ**! 🎉

---

## 🔐 Biztonság

- ✅ POST metódus használata (nem GET)
- ✅ CSRF token támogatás
- ✅ Same-origin policy
- ✅ XSS védelem (hibaüzenetek escape)
- ⚠️ **HTTPS használat ajánlott** éles környezetben!

---

## 📄 Licensz

**MIT License** - Szabadon használható, módosítható, terjeszthető.

---

**Verzió:** 1.0.0  
**Nyelv:** Magyar 🇭🇺  
**Platform:** Joomla/Solidres (de adaptálható más rendszerekhez is)

---

## 🎓 Mit Tanulhat Ebből a Kódból?

- ✅ Modern JavaScript (ES6+) best practices
- ✅ Fetch API használata
- ✅ Promise-based aszinkron programozás
- ✅ DOM manipuláció és eseménykezelés
- ✅ Hibakezelés (error handling) három szinten
- ✅ IIFE pattern (Immediately Invoked Function Expression)
- ✅ Tiszta, jól dokumentált kód írása
- ✅ Konfigurálható, újrafelhasználható komponensek

---

**Kellemes munkát! Ha bármi kérdése van, nézze meg a részletes dokumentációt!** 📖
