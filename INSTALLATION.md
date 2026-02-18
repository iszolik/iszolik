# Telepítési Útmutató - Solidres 404 Hiba Javítás

## Áttekintés

Ez az útmutató végigvezet a Solidres komponens fájljainak telepítésén, amelyek megoldják a Qvik és Revolut fizetési módok 404 hibáját almenü contextben.

## Fájlstruktúra

A repository a következő konkrét implementációs fájlokat tartalmazza:

```
components/
└── com_solidres/
    ├── router.php                                    # Továbbfejlesztett router
    ├── controllers/
    │   └── reservationasset.php                     # Controller session validációval
    ├── helpers/
    │   └── sessioncontext.php                       # Session context helper
    └── views/
        └── reservationasset/
            └── tmpl/
                ├── confirmationform.php              # Főoldal template
                └── payment-enhancement.js            # JavaScript library

plugins/
└── solidrespayment/
    ├── qvik/
    │   └── tmpl/
    │       └── confirmationform.php                 # Qvik fizetési template
    └── revolut/
        └── tmpl/
            └── confirmationform.php                 # Revolut fizetési template
```

## Telepítési Lépések

### 1. Előkészületek

**Készíts biztonsági mentést!**

```bash
# Teljes Joomla biztonsági mentés
cd /path/to/joomla
tar -czf backup-$(date +%Y%m%d).tar.gz .

# Vagy csak a Solidres komponens
tar -czf solidres-backup-$(date +%Y%m%d).tar.gz components/com_solidres/ plugins/solidrespayment/
```

### 2. Router.php Telepítése

A router.php a legkritikusabb fájl - ez oldja meg a routing problémát.

```bash
# Backup
cp components/com_solidres/router.php components/com_solidres/router.php.backup

# Telepítés
cp /path/to/repository/components/com_solidres/router.php components/com_solidres/router.php

# Ellenőrzés
ls -lh components/com_solidres/router.php
# Méret: ~8.6KB, ~278 sor
```

**Ellenőrizd hogy a fájl tartalmazza:**
```bash
grep -c "findItemIdForContext" components/com_solidres/router.php
# Eredmény: legalább 2 (definíció + hívás)

grep -c "extractPathContext" components/com_solidres/router.php
# Eredmény: legalább 2
```

### 3. Controller Telepítése

```bash
# Backup (ha létezik)
cp components/com_solidres/controllers/reservationasset.php \
   components/com_solidres/controllers/reservationasset.php.backup

# Telepítés
cp /path/to/repository/components/com_solidres/controllers/reservationasset.php \
   components/com_solidres/controllers/reservationasset.php

# Jogosultságok beállítása
chmod 644 components/com_solidres/controllers/reservationasset.php
```

**Ha már létezik controller fájl:**

Két lehetőséged van:
1. **Teljes csere** (ajánlott ha nincs egyedi módosítás)
2. **Metódus merge** (ha van egyedi kód)

Merge esetén:
- Másold az `updatePaymentMethod()` metódust
- Másold a helper metódusokat (`getPaymentMethodDetails`, `validateSessionContext`, stb.)
- Ellenőrizd hogy nincs-e duplikáció

### 4. Session Context Helper Telepítése

```bash
# Könyvtár létrehozása (ha nem létezik)
mkdir -p components/com_solidres/helpers

# Telepítés
cp /path/to/repository/components/com_solidres/helpers/sessioncontext.php \
   components/com_solidres/helpers/sessioncontext.php

# Jogosultságok
chmod 644 components/com_solidres/helpers/sessioncontext.php
```

### 5. View Templates Telepítése

```bash
# Könyvtár létrehozása (ha nem létezik)
mkdir -p components/com_solidres/views/reservationasset/tmpl

# JavaScript library
cp /path/to/repository/components/com_solidres/views/reservationasset/tmpl/payment-enhancement.js \
   components/com_solidres/views/reservationasset/tmpl/payment-enhancement.js

# Template fájl (opcionalisan - módosítsd a meglévőt vagy használd példaként)
cp /path/to/repository/components/com_solidres/views/reservationasset/tmpl/confirmationform.php \
   components/com_solidres/views/reservationasset/tmpl/confirmationform.php.example
```

**Fontos:** Ha már van `confirmationform.php`, NE írd felül! Ehelyett:
1. Nyisd meg a példa fájlt
2. Másold ki a JavaScript részt
3. Illesszd be a meglévő template-be
4. Ellenőrizd hogy a `payment-enhancement.js` be van töltve

### 6. Payment Plugin Templates

#### Qvik Plugin

```bash
# Könyvtár létrehozása
mkdir -p plugins/solidrespayment/qvik/tmpl

# Backup (ha létezik)
if [ -f plugins/solidrespayment/qvik/tmpl/confirmationform.php ]; then
    cp plugins/solidrespayment/qvik/tmpl/confirmationform.php \
       plugins/solidrespayment/qvik/tmpl/confirmationform.php.backup
fi

# Telepítés
cp /path/to/repository/plugins/solidrespayment/qvik/tmpl/confirmationform.php \
   plugins/solidrespayment/qvik/tmpl/confirmationform.php
```

#### Revolut Plugin

```bash
# Könyvtár létrehozása
mkdir -p plugins/solidrespayment/revolut/tmpl

# Backup (ha létezik)
if [ -f plugins/solidrespayment/revolut/tmpl/confirmationform.php ]; then
    cp plugins/solidrespayment/revolut/tmpl/confirmationform.php \
       plugins/solidrespayment/revolut/tmpl/confirmationform.php.backup
fi

# Telepítés
cp /path/to/repository/plugins/solidrespayment/revolut/tmpl/confirmationform.php \
   plugins/solidrespayment/revolut/tmpl/confirmationform.php
```

### 7. Cache Törlése (KRITIKUS!)

```bash
# Joomla cache
rm -rf cache/*
rm -rf administrator/cache/*

# Ha van Memcache/Redis
# Joomla Admin: System → Clear Cache → Clear All

# PHP OPcache restart
systemctl restart php-fpm
# vagy
systemctl restart apache2
```

### 8. Jogosultságok Ellenőrzése

```bash
# Minden fájl 644
find components/com_solidres -type f -exec chmod 644 {} \;
find plugins/solidrespayment -type f -exec chmod 644 {} \;

# Könyvtárak 755
find components/com_solidres -type d -exec chmod 755 {} \;
find plugins/solidrespayment -type d -exec chmod 755 {} \;
```

### 9. Logging Beállítása (Ajánlott)

Hozd létre a logging konfigurációt:

```bash
mkdir -p administrator/components/com_solidres/config
```

Hozz létre `administrator/components/com_solidres/config/logging.php` fájlt:

```php
<?php
defined('_JEXEC') or die;

JLog::addLogger(
    array(
        'text_file' => 'com_solidres.routing.php',
        'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
    ),
    JLog::ALL,
    array('com_solidres.routing')
);

JLog::addLogger(
    array(
        'text_file' => 'com_solidres.payment.php',
        'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
    ),
    JLog::ALL,
    array('com_solidres.payment')
);

JLog::addLogger(
    array(
        'text_file' => 'com_solidres.session.php',
        'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
    ),
    JLog::ALL,
    array('com_solidres.session')
);
```

Ellenőrizd hogy a `logs/` könyvtár írható:
```bash
chmod 755 logs
```

### 10. Debug Mode Engedélyezése (Teszteléshez)

```php
// configuration.php
public $debug = '1';
public $log_path = '/path/to/joomla/logs';
```

## Ellenőrzés

### Automatikus Ellenőrzés

Futtasd a diagnosztikai scriptet:

```bash
cd /path/to/joomla
bash /path/to/repository/diagnostic.sh
```

### Web-alapú Ellenőrzés

```bash
# Másold a verify-router.php-t a Joomla gyökérbe
cp /path/to/repository/verify-router.php .

# Nyisd meg böngészőben
# http://yoursite.com/verify-router.php

# Töröld használat után!
rm verify-router.php
```

### Manuális Ellenőrzés

```bash
# 1. Router fájl
ls -lh components/com_solidres/router.php
wc -l components/com_solidres/router.php  # ~278 sor

# 2. Kritikus metódusok
grep "function findItemIdForContext" components/com_solidres/router.php
grep "function extractPathContext" components/com_solidres/router.php

# 3. Controller
ls -lh components/com_solidres/controllers/reservationasset.php
grep "function updatePaymentMethod" components/com_solidres/controllers/reservationasset.php

# 4. Helper
ls -lh components/com_solidres/helpers/sessioncontext.php

# 5. JavaScript
ls -lh components/com_solidres/views/reservationasset/tmpl/payment-enhancement.js
```

## Tesztelés

### 1. Root Context Teszt

```
Navigálj: http://yoursite.com/index.php?option=com_solidres&view=reservationasset&...
Válassz fizetési módot: Qvik vagy Revolut
Várt eredmény: ✓ Sikeres (200 OK)
```

### 2. Submenu Context Teszt (Ez volt a hibás!)

```
Navigálj: http://yoursite.com/property1/index.php?option=com_solidres&view=reservationasset&...
Válassz fizetési módot: Qvik vagy Revolut
Várt eredmény: ✓ Sikeres (200 OK) - NEM 404!
```

### 3. Hub Context Teszt

```
Navigálj: http://yoursite.com/hub/property2/index.php?option=com_solidres&view=reservationasset&...
Válassz fizetési módot: Qvik vagy Revolut
Várt eredmény: ✓ Sikeres (200 OK)
```

### 4. Logok Ellenőrzése

```bash
# Routing log
tail -f logs/com_solidres.routing.php

# Sikeres routing kimenet:
# Solidres Router Parse - Path: /property1/index.php, Task: reservationasset.updatePaymentMethod
# Solidres Router - Corrected Menu Context: Original Itemid=123, Corrected Itemid=456
# Solidres Router - Task-based route parsed: {...}

# Payment log
tail -f logs/com_solidres.payment.php

# Sikeres payment kimenet:
# Payment Method Update Request - Method ID: 5, Property: 10, ...
# Payment Method Updated Successfully - Method: 5 (Qvik)
```

## Hibaelhárítás

Ha még mindig 404-et kapsz:

### 1. Futtasd a diagnosztikát
```bash
bash /path/to/repository/diagnostic.sh
```

### 2. Nézd meg a troubleshooting útmutatót
```bash
cat /path/to/repository/TROUBLESHOOTING_404.md
```

### 3. Ellenőrizd a menu items-okat
```sql
SELECT id, title, link, published 
FROM #__menu 
WHERE link LIKE '%com_solidres%' 
  AND published = 1
  AND client_id = 0;
```

Ha nincs eredmény, hozz létre legalább EGY menu item-et!

### 4. Ellenőrizd a cache-t
```bash
# Töröld újra
rm -rf cache/* administrator/cache/*

# Restart PHP
systemctl restart php-fpm
```

## Gyakori Hibák

### "Class 'SolidresRouter' not found"

**Ok:** Router fájl nincs jó helyen vagy nincs betöltve.

**Megoldás:**
```bash
# Ellenőrizd a helyet
ls -la components/com_solidres/router.php

# Ha nincs ott, másold oda
cp /path/to/repository/components/com_solidres/router.php components/com_solidres/

# Töröld a cache-t
rm -rf cache/*
```

### "Call to undefined method findItemIdForContext"

**Ok:** Hiányzik a metódus a router.php-ból.

**Megoldás:**
```bash
# Másold a TELJES router.php fájlt
cp /path/to/repository/components/com_solidres/router.php components/com_solidres/router.php

# NE csak részeket!
```

### Továbbra is 404

**Megoldás:**
1. Nézd meg a TROUBLESHOOTING_404.md fájlt
2. Futtasd a diagnostic.sh-t
3. Használd a verify-router.php-t

## Maintenance

### Frissítés Esetén

Ha a Solidres frissítésre kerül:

```bash
# 1. Mentsd el a módosított fájlokat
cp components/com_solidres/router.php ~/solidres-custom/router.php
cp components/com_solidres/controllers/reservationasset.php ~/solidres-custom/reservationasset.php

# 2. Frissítsd a Solidres-t

# 3. Másold vissza a módosított fájlokat
cp ~/solidres-custom/router.php components/com_solidres/
cp ~/solidres-custom/reservationasset.php components/com_solidres/controllers/

# 4. Töröld a cache-t
rm -rf cache/* administrator/cache/*
systemctl restart php-fpm
```

## Összefoglalás

**Minimum telepítés (5 perc):**
1. Router.php → components/com_solidres/
2. Cache törlése
3. PHP restart
4. Teszt

**Ajánlott telepítés (15 perc):**
1. Router.php
2. Controller
3. Helper
4. JavaScript library
5. Cache törlése
6. PHP restart
7. Logging konfiguráció
8. Teszt

**Teljes telepítés (30 perc):**
- Minden fenti + payment plugin templates
- Részletes tesztelés minden contextben
- Log monitoring beállítása

## Támogatás

**Diagnosztikai információk gyűjtése:**

```bash
# Futtasd ezt és küldd el az outputot
echo "=== Diagnostic Report ===" > diagnostic-report.txt
bash /path/to/repository/diagnostic.sh >> diagnostic-report.txt
echo "" >> diagnostic-report.txt
echo "=== Router Content ===" >> diagnostic-report.txt
head -50 components/com_solidres/router.php >> diagnostic-report.txt
echo "" >> diagnostic-report.txt
echo "=== Routing Log ===" >> diagnostic-report.txt
tail -50 logs/com_solidres.routing.php >> diagnostic-report.txt
```

**További segítség:**
- TROUBLESHOOTING_404.md - Részletes hibaelhárítás
- DEBUGGING_GUIDE.md - Debug útmutató
- README.md - Teljes dokumentáció

---

**Sikeres telepítés után a 404 hibák megszűnnek minden contextben!** ✅
