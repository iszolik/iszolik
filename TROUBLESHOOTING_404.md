# Router.php 404 Hibaelhárítás - Amikor a patch után is 404-et kapsz

## A Probléma

A router.php-t módosítottad a patch alapján, de továbbra is 404 hibát kapsz almenü contextben.

## Gyors Diagnosztika Checklist

Végezd el sorrendben ezeket az ellenőrzéseket:

### ✓ 1. Router.php Helye

**Ellenőrizd:**
```bash
# A fájlnak itt KELL lennie:
ls -la components/com_solidres/router.php

# NEM itt:
ls -la administrator/components/com_solidres/router.php
ls -la plugins/solidrespayment/*/router.php
```

**Gyakori hiba:** Rossz helyre másolod a fájlt.

**Helyes hely:** `components/com_solidres/router.php` (frontend komponens, nem admin!)

---

### ✓ 2. Teljes Router.php Struktúra

**Ellenőrizd hogy a fájl tartalmazza:**

```bash
# Ellenőrizd a kritikus részeket:
grep -n "class SolidresRouter" components/com_solidres/router.php
grep -n "function findItemIdForContext" components/com_solidres/router.php
grep -n "function extractPathContext" components/com_solidres/router.php
grep -n "function SolidresBuildRoute" components/com_solidres/router.php
grep -n "function SolidresParseRoute" components/com_solidres/router.php
```

**Mindegyiknek meg KELL jelennie!**

Ha valamelyik hiányzik:
```bash
# Használd a teljes patch fájlt:
cp /path/to/patches/router.php components/com_solidres/router.php
```

---

### ✓ 3. Parse Metódus Teljes Implementációja

**A parse() metódusnak tartalmaznia KELL:**

```php
public function parse(&$segments)
{
    $vars = array();
    $app = JFactory::getApplication();
    $input = $app->input;
    $menu = $app->getMenu();
    $active = $menu->getActive();
    
    // Kérés részletei
    $task = $input->get('task', '');
    $format = $input->get('format', '');
    $itemid = $input->getInt('Itemid', 0);
    $uri = JUri::getInstance();
    $path = $uri->getPath();
    
    // KRITIKUS: Task-based AJAX felismerése
    if (!empty($task) && strpos($task, '.') !== false) {
        list($controller, $method) = explode('.', $task, 2);
        
        if ($format === 'json' || $format === 'raw') {
            $vars['view'] = $controller;
            $vars['task'] = $task;
            
            // KRITIKUS FIX: Menu context korrekció
            if (!$active || ($active && $active->id != $itemid)) {
                $correctItemId = $this->findItemIdForContext($path, $itemid);
                
                if ($correctItemId) {
                    $menu->setActive($correctItemId);
                    $input->set('Itemid', $correctItemId);
                }
            }
            
            return $vars;  // FONTOS: Return itt!
        }
    }
    
    // Standard routing
    if (!empty($segments)) {
        $vars['view'] = array_shift($segments);
        if (!empty($segments)) {
            $vars['id'] = array_shift($segments);
        }
    }
    
    return $vars;
}
```

**Gyakori hibák:**
- ❌ Hiányzik a `if ($format === 'json' || $format === 'raw')` ellenőrzés
- ❌ Hiányzik a `$this->findItemIdForContext()` hívás
- ❌ Nem adod vissza a `$vars`-t megfelelő helyen
- ❌ Elírás a változónevekben

---

### ✓ 4. Helper Metódusok Megléte

**Ellenőrizd hogy megvannak:**

```bash
# findItemIdForContext metódus
grep -A 50 "function findItemIdForContext" components/com_solidres/router.php

# extractPathContext metódus
grep -A 20 "function extractPathContext" components/com_solidres/router.php
```

**Ha hiányoznak, másold be őket a patch fájlból!**

```php
protected function findItemIdForContext($currentPath, $requestedId)
{
    $app = JFactory::getApplication();
    $menu = $app->getMenu();
    
    // Get all menu items for this component
    $items = $menu->getItems('component', 'com_solidres');
    
    if (empty($items)) {
        return null;
    }
    
    // Extract context from path
    $pathContext = $this->extractPathContext($currentPath);
    
    // Try to find a menu item that matches the path context
    foreach ($items as $item) {
        if (!$item->published) {
            continue;
        }
        
        $itemRoute = $menu->getRoute($item->id);
        $itemPath = JRoute::_($itemRoute, false);
        $itemContext = $this->extractPathContext($itemPath);
        
        if ($pathContext === $itemContext) {
            return $item->id;
        }
    }
    
    // Fallback to requested ID if valid
    if ($requestedId > 0) {
        $requestedItem = $menu->getItem($requestedId);
        if ($requestedItem && $requestedItem->component === 'com_solidres' && $requestedItem->published) {
            return $requestedId;
        }
    }
    
    // Last resort: first published item
    foreach ($items as $item) {
        if ($item->published) {
            return $item->id;
        }
    }
    
    return null;
}

protected function extractPathContext($path)
{
    // Remove index.php and trailing slashes
    $path = str_replace('index.php', '', $path);
    $path = trim($path, '/');
    
    if (empty($path)) {
        return '';
    }
    
    return '/' . $path;
}
```

---

### ✓ 5. Joomla Cache Törlése

**KRITIKUS lépés!** A Joomla cache-eli a router fájlt!

```bash
# Admin felületen:
# System → Clear Cache → Clear All

# Vagy parancssorból:
rm -rf cache/*
rm -rf administrator/cache/*

# Ha van APC/OPcache:
# Indítsd újra a webszervert vagy PHP-FPM-et
systemctl restart php-fpm
# vagy
systemctl restart apache2
```

**Gyakori hiba:** Módosítod a router.php-t, de a cache-elt verziót használja a rendszer!

---

### ✓ 6. Menüelemek Ellenőrzése

**Ellenőrizd hogy van-e menüelem a komponenshez:**

```sql
-- Futtasd le MySQL-ben:
SELECT 
    id,
    title,
    alias,
    path,
    link,
    published
FROM 
    #__menu
WHERE 
    link LIKE '%com_solidres%'
    AND published = 1
    AND client_id = 0  -- Frontend menü
ORDER BY 
    id;
```

**Ha üres az eredmény:**
- Létre kell hoznod legalább EGY menüelemet a Solidres komponenshez
- Joomla Admin → Menus → Main Menu → Add New Menu Item
- Menu Item Type: Solidres → Reservation (vagy bármely Solidres view)
- Jegyezd fel az Itemid-t!

**Ha van menüelem de mégis 404:**
- Ellenőrizd hogy `published = 1`
- Ellenőrizd hogy `client_id = 0` (frontend)
- Ellenőrizd hogy a link valóban tartalmazza az `option=com_solidres` paramétert

---

### ✓ 7. Debug Logging Engedélyezése

**Engedélyezd a debug logot hogy lásd mi történik:**

```php
// configuration.php-ben:
public $debug = '1';
public $log_path = '/path/to/joomla/logs';
```

**Hozz létre logging konfigurációt:**

```php
// administrator/components/com_solidres/config/logging.php
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
```

**Nézd meg a logokat:**

```bash
# Valós időben:
tail -f logs/com_solidres.routing.php

# Utolsó 50 sor:
tail -50 logs/com_solidres.routing.php | grep "Parse"
```

**Mit kell látnod a logban:**
```
2026-02-18 16:00:00 DEBUG Solidres Router Parse - Path: /property1/index.php, Task: reservationasset.updatePaymentMethod
2026-02-18 16:00:00 INFO Solidres Router - Corrected Menu Context: Original Itemid=123, Corrected Itemid=456
2026-02-18 16:00:00 DEBUG Solidres Router - Task-based route parsed: {"view":"reservationasset","task":"reservationasset.updatePaymentMethod"}
```

**Ha nem látod ezeket:**
- A router.php nem fut le (rossz helyen van vagy cache-elve van)
- A logging nincs jól konfigurálva
- Nincs írási jog a logs mappához

---

### ✓ 8. Controller Ellenőrzése

**Ellenőrizd hogy létezik a controller:**

```bash
ls -la components/com_solidres/controllers/reservationasset.php
```

**Ellenőrizd hogy van updatePaymentMethod metódus:**

```bash
grep -n "function updatePaymentMethod" components/com_solidres/controllers/reservationasset.php
```

**Ha hiányzik:**
- A controller fájl létezik de nincs benne a metódus
- Másold be a patches/controller_reservationasset.php-ból
- Vagy hozd létre a metódust

**Minimum controller kód:**

```php
<?php
// components/com_solidres/controllers/reservationasset.php

class SolidresControllerReservationAsset extends JControllerLegacy
{
    public function updatePaymentMethod()
    {
        // Set JSON header
        JResponse::setHeader('Content-Type', 'application/json', true);
        
        $app = JFactory::getApplication();
        $input = $app->input;
        $session = JFactory::getSession();
        
        try {
            // Get parameters
            $paymentMethodId = $input->getInt('payment_method_id', 0);
            
            // Validate session (PRIMARY)
            $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
            
            if (!$reservationDetails) {
                throw new Exception('Invalid reservation session');
            }
            
            // Update session
            $reservationDetails->payment_method_id = $paymentMethodId;
            $session->set('reservationDetails', $reservationDetails, 'com_solidres');
            
            // Success
            echo json_encode([
                'success' => true,
                'message' => 'Payment method updated'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        $app->close();
    }
}
```

---

### ✓ 9. PHP Hibák Ellenőrzése

**Nézd meg a PHP error logot:**

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx + PHP-FPM
tail -f /var/log/php-fpm/www-error.log

# Vagy Joomla error.php
tail -f logs/error.php
```

**Gyakori PHP hibák:**
- `Class 'SolidresRouter' not found` - router.php nincs betöltve
- `Call to undefined method` - hiányzó metódus
- `Parse error` - szintaktikai hiba a kódban
- `Fatal error: Cannot redeclare` - duplikált függvény definíció

---

### ✓ 10. Közvetlen URL Teszt

**Teszteld közvetlenül böngészőből:**

```
# Root context (ennek működnie KELL):
http://yoursite.com/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=5&Itemid=123

# Submenu context (ez a probléma):
http://yoursite.com/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=5&Itemid=456
```

**Mit láss:**
- Root: `{"success": true, ...}` vagy valami JSON válasz
- Submenu: Ugyanaz (NEM 404!)

**Ha 404-et kapsz:**
- Nézd meg a Joomla debug output-ot az oldal alján
- Ellenőrizd hogy melyik komponens/view-t próbál betölteni
- Nézd meg hogy "Active Menu" mit mutat

---

## Lépésről Lépésre Hibakeresés

### 1. lépés: Alapvető Ellenőrzések

```bash
# 1. Router.php helye
ls -la components/com_solidres/router.php
# Kell: -rw-r--r-- ... router.php

# 2. Router.php mérete
wc -l components/com_solidres/router.php
# Kell: ~278 sor

# 3. Kritikus metódusok
grep -c "findItemIdForContext" components/com_solidres/router.php
# Kell: legalább 2 (definíció + hívás)

# 4. Cache törlése
rm -rf cache/* administrator/cache/*

# 5. PHP restart (OPcache miatt)
systemctl restart php-fpm
```

### 2. lépés: Debug Engedélyezése

```php
// configuration.php
public $debug = '1';
```

### 3. lépés: Teszt URL Hívás

```bash
# Curl-el tesztelés
curl -v "http://yoursite.com/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json"
```

**Mit kell látnod:**
```
< HTTP/1.1 200 OK
< Content-Type: application/json
...
{"success": true, ...}
```

**Ha 404-et látsz:**
```
< HTTP/1.1 404 Not Found
...
```

Akkor folytasd a 4. lépéssel.

### 4. lépés: Log Elemzés

```bash
# Nézd meg az utolsó routing kísérletet
tail -20 logs/com_solidres.routing.php
```

**Mit keress:**
1. `Solidres Router Parse` - Ha nincs: router nem fut le
2. `Corrected Menu Context` - Ha nincs: findItemIdForContext nem hívódik meg
3. `Task-based route parsed` - Ha nincs: parse nem tér vissza megfelelően

### 5. lépés: Menu Item Ellenőrzés

```sql
SELECT id, title, link FROM #__menu WHERE link LIKE '%com_solidres%' AND published=1;
```

**Ha üres:** Hozz létre menüelemet!

**Ha van:** Jegyezd fel az ID-t és használd az URL-ben `&Itemid=X` paraméterként.

### 6. lépés: Controller Teszt

```bash
# Ellenőrizd a controller létezését
ls -la components/com_solidres/controllers/reservationasset.php

# Ellenőrizd az updatePaymentMethod metódust
grep -A 10 "function updatePaymentMethod" components/com_solidres/controllers/reservationasset.php
```

---

## Specifikus Hibaüzenetek és Megoldások

### "Component not found" vagy "404 Not Found"

**Ok:** Router nem találja meg a megfelelő controller-t.

**Megoldás:**
1. Ellenőrizd hogy a router.php teljes (lásd fent)
2. Töröld a cache-t
3. Indítsd újra a PHP-t
4. Ellenőrizd a menüelemeket

### "Class 'SolidresRouter' not found"

**Ok:** A router.php fájl nincs betöltve.

**Megoldás:**
1. Ellenőrizd a fájl helyét: `components/com_solidres/router.php`
2. Ne legyen PHP szintaktikai hiba a fájlban
3. Töröld a cache-t

### "Call to undefined method SolidresRouter::findItemIdForContext"

**Ok:** Hiányzik a findItemIdForContext metódus.

**Megoldás:**
1. Másold be a teljes patches/router.php fájlt
2. Ellenőrizd hogy a metódus `protected function findItemIdForContext` néven létezik

### "Invalid reservation session"

**Ok:** A controller fut, de nincs session.

**Megoldás:**
1. Először végezz normál foglalást hogy legyen session
2. Utána próbáld a fizetési mód frissítést
3. Ellenőrizd a session cookie-t a böngészőben

### Továbbra is 404

**Ha minden fenti lépést végrehajtottál és még mindig 404:**

1. **Ellenőrizd a teljes router.php fájlt:**
   ```bash
   diff -u patches/router.php components/com_solidres/router.php
   ```
   Kell: Nincs különbség vagy csak apró módosítások

2. **Generálj részletes debug log-ot:**
   ```php
   // A parse() metódus elejére:
   error_log("SOLIDRES ROUTER PARSE START - Path: " . JUri::getInstance()->getPath());
   error_log("SOLIDRES ROUTER PARSE - Task: " . JFactory::getApplication()->input->get('task'));
   ```

3. **Próbálj egy minimál példát:**
   ```php
   // Ideiglenes teszt a parse() metódusban:
   public function parse(&$segments)
   {
       error_log("PARSE CALLED!");
       $vars = array();
       // ... rest of code
   }
   ```
   
   Ha nem látod a log-ban a "PARSE CALLED!" üzenetet, akkor a router egyáltalán nem hívódik meg!

---

## Gyors Fix Checklist

Próbáld ezt a sorrendet:

1. ☐ Másold be a TELJES patches/router.php fájlt
2. ☐ Töröld a cache-t (System → Clear Cache)
3. ☐ Indítsd újra a PHP-t (`systemctl restart php-fpm`)
4. ☐ Engedélyezd a debug mode-ot (`public $debug = '1';`)
5. ☐ Ellenőrizd hogy van menüelem (`SELECT * FROM #__menu WHERE link LIKE '%solidres%'`)
6. ☐ Tesztelj közvetlen URL-lel böngészőből
7. ☐ Nézd meg a logs/com_solidres.routing.php fájlt
8. ☐ Ha még mindig nem megy, nézd meg a PHP error log-ot

---

## További Segítség

Ha még mindig nem működik:

1. **Készíts egy teljes diagnosztikát:**
   ```bash
   echo "=== Router file check ===" > diagnostic.txt
   ls -la components/com_solidres/router.php >> diagnostic.txt
   wc -l components/com_solidres/router.php >> diagnostic.txt
   grep -n "function findItemIdForContext" components/com_solidres/router.php >> diagnostic.txt
   
   echo "=== Menu items ===" >> diagnostic.txt
   mysql -u root -p joomla -e "SELECT id, title, link FROM joomla_menu WHERE link LIKE '%solidres%';" >> diagnostic.txt
   
   echo "=== Routing logs ===" >> diagnostic.txt
   tail -50 logs/com_solidres.routing.php >> diagnostic.txt
   
   echo "=== PHP errors ===" >> diagnostic.txt
   tail -50 logs/error.php >> diagnostic.txt
   ```

2. **Küldd el a diagnostic.txt fájlt** további elemzéshez

3. **Nézd meg a teljes dokumentációt:**
   - DEBUGGING_GUIDE.md
   - IMPLEMENTATION_GUIDE.md
   - BACKEND_ROUTING_ANALYSIS.md

---

## Összefoglalás

A leggyakoribb okok miért nem működik a router patch után:

1. **Router.php nincs a megfelelő helyen** (components/com_solidres/)
2. **Hiányoznak a helper metódusok** (findItemIdForContext, extractPathContext)
3. **Cache nincs törölve** (Joomla cache-eli!)
4. **PHP OPcache nincs újraindítva**
5. **Nincsenek menüelemek** a komponenshez
6. **Controller nincs telepítve** vagy hiányzik a metódus

**Megoldás:** Kövesd az ellenőrzési listát fentről lefelé!
