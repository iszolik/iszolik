# Quick Start Guide

## A Qvik és Revolut Payment Plugin Telepítése

Ez az útmutató segít a Qvik és Revolut fizetési pluginok telepítésében és beállításában.

## Előfeltételek

1. ✅ Joomla 3.x vagy 4.x telepítve
2. ✅ Solidres komponens telepítve
3. ✅ **Bármilyen Joomla template** (a plugin automatikusan detektálja az aktív template-et)

## Telepítés Lépései

### 1. Plugin Telepítése

1. Jelentkezz be a Joomla adminisztrációba
2. Navigálj: **Extensions** → **Manage** → **Install**
3. Töltsd fel a plugin zip fájlt (qvik vagy revolut)
4. Kattints az **Upload & Install** gombra

### 2. Mit Tesz Automatikusan a Telepítő?

A telepítés során a script automatikusan:

✅ **Engedélyezi a plugint**

✅ **Törli a payment_method_txn_id UNIQUE indexet** az adatbázisból

✅ **Telepíti a confirmationform.php fájlt**
   - Cél: `components/com_solidres/confirmationform.php`

✅ **Telepíti a reservation.php fájlt**
   - Forrás: `plugins/solidrespayment/[qvik|revolut]/asset/reservation.php`
   - Cél: `libraries/solidres/reservation/reservation.php`
   - ⚠️ Backup készül, ha létezik: `reservation.php.backup.YmdHis`

✅ **Telepíti az 5 email template fájlt**
   - Forrás: `plugins/solidrespayment/[qvik|revolut]/asset/emails/`
   - Cél: `templates/[AKTUÁLIS-DEFAULT-TEMPLATE]/html/layouts/com_solidres/emails/`
   - Az installer automatikusan detektálja a Joomla default frontend template-jét
   - Fallback: `greenery` ha nem sikerül a detektálás
   - Email fájlok:
     - `reservation_complete_customer_html.php`
     - `reservation_complete_customer_html_inliner.php`
     - `reservation_complete_owner_html.php`
     - `reservation_complete_owner_html_inliner.php`
     - `reservation_complete_customer_pdf.php`
   - ⚠️ Minden fájlról backup készül, ha létezik

✅ **Hozzáfűzi a nyelvi konstansokat** a Solidres nyelvi fájlokhoz

### 3. Telepítés Után

A telepítés után a következő üzenetek jelennek meg:

```
✅ Qvik/Revolut plugin engedélyezve
✅ payment_method_txn_id UNIQUE index eltávolítva
✅ confirmationform.php sikeresen telepítve
✅ reservation.php sikeresen telepítve
✅ reservation_complete_customer_html.php sikeresen telepítve
✅ reservation_complete_customer_html_inliner.php sikeresen telepítve
✅ reservation_complete_owner_html.php sikeresen telepítve
✅ reservation_complete_owner_html_inliner.php sikeresen telepítve
✅ reservation_complete_customer_pdf.php sikeresen telepítve
✅ Nyelvi konstansok frissítve
```

Ha valamelyik fájl telepítése sikertelen, warning üzenet jelenik meg:
```
⚠️ Nem sikerült a [fájlnév] másolása!
```

## Backup Fájlok

A telepítő automatikusan backup-ot készít minden felülírt fájlról:

- **Formátum:** `eredeti_fajl.php.backup.20260209120000`
- **Hely:** Ugyanott, ahol az eredeti fájl
- **Visszaállítás:** Töröld az új fájlt és nevezd át a backup fájlt

Példa backup fájlok:
```
reservation.php.backup.20260209095752
reservation_complete_customer_html.php.backup.20260209095752
```

## Template Kompatibilitás

Az installer automatikusan detektálja a Joomla default frontend template-jét az adatbázisból (`#__template_styles` tábla), így bármilyen template-tel kompatibilis (pl. Cassiopeia, Protostar, Greenery, egyedi template-ek).

Ha a detektálás sikertelen, az installer figyelmeztetést jelenít meg és a `greenery` template-et használja fallback-ként.

## Több Plugin Telepítése (Qvik + Revolut)

⚠️ **Fontos:** Ha mindkét plugint telepíted:

- A `reservation.php` fájl közös helyre kerül
- Az utoljára telepített plugin `reservation.php` fájlja lesz érvényben
- A backup fájlok megőrzik az előző verziót
- Győződj meg róla, hogy mindkét plugin `reservation.php` fájlja kompatibilis

## Plugin Beállítása

A telepítés után:

1. Navigálj: **Extensions** → **Plugins**
2. Keresd meg: **Solidrespayment - Qvik** vagy **Solidrespayment - Revolut**
3. Állítsd be a plugin paramétereit (API kulcsok, stb.)
4. Mentsd el a beállításokat

## Plugin Eltávolítása

Az eltávolítás során:

- ✅ A plugin törlődik
- ❌ A `reservation.php` **NEM** törlődik automatikusan
- ❌ Az email template-ek **NEM** törlődnek automatikusan

Ha szükséges, manuálisan töröld ezeket a fájlokat.

## Gyakori Problémák

### Problem: "⚠️ Nem sikerült létrehozni a könyvtárat"

**Megoldás:**
- Ellenőrizd a fájlrendszer jogosultságokat
- A könyvtáraknak írhatónak kell lenniük

### Problem: Email template-ek nem működnek

**Megoldás:**
1. Ellenőrizd, hogy van-e default frontend template beállítva a Joomla adminban
2. Ha a plugin figyelmeztetést ad, ellenőrizd az adatbázis kapcsolatot
3. Ellenőrizd a template könyvtár írási jogosultságát: `templates/[TEMPLATE-NÉV]/html/layouts/com_solidres/emails/`
4. Ha szükséges, manuálisan másold át a fájlokat a forrásból: `plugins/solidrespayment/[qvik|revolut]/asset/emails/`

### Problem: Mindkét plugin telepítve, de csak az egyik működik

**Megoldás:**
- Ez normális, ha a `reservation.php` fájlok nem kompatibilisek
- Az utoljára telepített plugin verziója van érvényben
- Ellenőrizd a backup fájlokat és állítsd vissza, ha szükséges

## Támogatás

Ha problémába ütközöl:

1. Ellenőrizd a Joomla hibanaplókat
2. Ellenőrizd a fájl jogosultságokat
3. Ellenőrizd a backup fájlokat
4. Lépj kapcsolatba a fejlesztővel

## Összefoglaló Checklist

- [ ] Joomla és Solidres telepítve
- [ ] Greenery template telepítve (vagy előkészítve a manuális másolásra)
- [ ] Plugin zip fájl letöltve
- [ ] Plugin telepítve
- [ ] Telepítési üzenetek ellenőrizve
- [ ] Plugin paraméterek beállítva
- [ ] Email template-ek működnek
- [ ] Fizetési folyamat tesztelve

🎉 **Sikeres telepítés!**
