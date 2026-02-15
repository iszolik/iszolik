<!-- 
=====================================================================
PÉLDA SABLON - GUESTFORM.PHP INTEGRÁCIÓ
=====================================================================

Ez a fájl egy példa sablon, amely bemutatja, hogyan kell integrálni
a payment-ajax-patch.js fájlt a guestform.php-ba.

FONTOS: Ez csak egy példa! Az Ön valódi guestform.php fájlja valószínűleg
más felépítésű. A lényeg, hogy a fájl VÉGÉRE (a záró ?> után vagy ha
nincs záró ?>, akkor a legvégére) illessze be a jelölt kódrészletet.

=====================================================================
-->

<?php
/**
 * @package     Solidres
 * @subpackage  Qvik/Revolut Payment Plugin
 * @copyright   Copyright (C) 2023, All rights reserved.
 * @license     GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

// Példa változók (az Ön implementációja valószínűleg más)
$reservationId = $this->state->get('reservation.id', 0);
$propertyId = $this->state->get('property.id', 0);
$hubId = $this->state->get('hub.id', 0);
$siteId = $this->state->get('site.id', 0);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendég Információk - Fizetés</title>
    
    <!-- Bootstrap vagy saját CSS -->
    <link rel="stylesheet" href="<?php echo JUri::base(); ?>media/com_solidres/css/solidres.css">
    
    <style>
        /* Alapvető stílusok */
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        .payment-submit-btn {
            background: #28a745;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .payment-submit-btn:hover {
            background: #218838;
        }
        
        .payment-submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        #payment-error-message {
            padding: 10px;
            margin: 15px 0;
            border-radius: 3px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        #payment-loading {
            padding: 15px;
            text-align: center;
            font-weight: bold;
            color: #004085;
            background: #cce5ff;
            border: 1px solid #b8daff;
            border-radius: 3px;
            margin: 15px 0;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <h1>Vendég Információk</h1>
    <p>Kérjük, töltse ki az adatokat a fizetés folytatásához.</p>
    
    <!-- 
    ===================================================================
    FIZETÉSI FORM - ID: payment-form (FONTOS!)
    ===================================================================
    -->
    <form id="payment-form" method="post" action="">
        
        <!-- Vendég Adatok -->
        <div class="form-group">
            <label for="guest_name">Teljes Név *</label>
            <input type="text" 
                   id="guest_name" 
                   name="guest_name" 
                   required 
                   placeholder="Kovács János">
        </div>
        
        <div class="form-group">
            <label for="guest_email">Email Cím *</label>
            <input type="email" 
                   id="guest_email" 
                   name="guest_email" 
                   required 
                   placeholder="kovacs.janos@example.com">
        </div>
        
        <div class="form-group">
            <label for="guest_phone">Telefonszám *</label>
            <input type="tel" 
                   id="guest_phone" 
                   name="guest_phone" 
                   required 
                   placeholder="+36 30 123 4567">
        </div>
        
        <div class="form-group">
            <label for="guest_address">Cím</label>
            <input type="text" 
                   id="guest_address" 
                   name="guest_address" 
                   placeholder="Budapest, Fő utca 1.">
        </div>
        
        <!-- Rejtett Mezők - FONTOS PARAMÉTEREK -->
        <input type="hidden" name="option" value="com_solidres">
        <input type="hidden" name="task" value="payment.process">
        <input type="hidden" name="view" value="reservation">
        <input type="hidden" name="layout" value="payment">
        <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
        <input type="hidden" name="hub_id" value="<?php echo $hubId; ?>">
        <input type="hidden" name="site_id" value="<?php echo $siteId; ?>">
        <input type="hidden" name="Itemid" value="<?php echo JFactory::getApplication()->input->getInt('Itemid', 0); ?>">
        <input type="hidden" name="<?php echo JSession::getFormToken(); ?>" value="1">
        
        <!-- 
        ===================================================================
        HIBAÜZENET KONTÉNER - ID: payment-error-message (FONTOS!)
        ===================================================================
        -->
        <div id="payment-error-message" style="display:none;">
            <!-- Ide kerülnek a hibaüzenetek dinamikusan -->
        </div>
        
        <!-- 
        ===================================================================
        BETÖLTÉS JELZŐ - ID: payment-loading (FONTOS!)
        ===================================================================
        -->
        <div id="payment-loading" style="display:none;">
            <i>⏳</i> Fizetés feldolgozása folyamatban, kérjük várjon...
        </div>
        
        <!-- 
        ===================================================================
        FIZETÉS GOMB - CLASS: payment-submit-btn (FONTOS!)
        ===================================================================
        -->
        <button type="submit" class="payment-submit-btn">
            💳 Fizetés
        </button>
        
    </form>
    
    <p style="margin-top: 20px; font-size: 12px; color: #666;">
        * Kötelező mezők
    </p>
</div>

</body>
</html>

<?php
// ======================================================================
// FONTOS: Ha van záró ?> a PHP fájlban, akkor ez utána jön!
// Ha nincs záró ?>, akkor a fenti </html> után közvetlenül jön ez:
// ======================================================================
?>

<!-- 
=====================================================================
⬇⬇⬇ IDE ILLESSZE BE A PAYMENT-AJAX-PATCH.JS-T ⬇⬇⬇
=====================================================================
OPCIÓ A: BEÁGYAZOTT (AJÁNLOTT)
=====================================================================
-->
<script>
<?php
/**
 * PAYMENT AJAX PATCH BEÁGYAZÁSA
 * 
 * Ez a kód beágyazza a payment-ajax-patch.js tartalmát közvetlenül
 * a HTML-be, így nem kell külön HTTP kérést küldeni a fájlért.
 */

// Patch fájl elérési útja - MÓDOSÍTSA A SAJÁT KÖRNYEZETÉHEZ!
$patchPath = __DIR__ . '/payment-ajax-patch.js';

// Ellenőrizzük, hogy létezik-e a fájl
if (file_exists($patchPath)) {
    // Beágyazzuk a JavaScript tartalmat
    include $patchPath;
} else {
    // Alternatív útvonal próbálkozás
    $fallbackPath = JPATH_ROOT . '/plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js';
    if (file_exists($fallbackPath)) {
        include $fallbackPath;
    } else {
        // Ha egyik sem található, hiba logolás
        if (class_exists('JLog')) {
            JLog::add(
                'Payment AJAX patch not found at: ' . $patchPath . ' or ' . $fallbackPath, 
                JLog::WARNING, 
                'payment'
            );
        }
        // Fallback: ürítjük a scriptet, de nem törik el az oldal
        echo '// Payment AJAX patch not found - please check file path';
    }
}
?>
</script>
<!-- 
=====================================================================
⬆⬆⬆ IDE ILLESSZE BE A PAYMENT-AJAX-PATCH.JS-T ⬆⬆⬆
=====================================================================

ALTERNATÍVA - OPCIÓ B: KÜLSŐ FÁJL BETÖLTÉSE
=====================================================================
Ha inkább külső fájlként szeretné betölteni (ez HTTP kérést igényel):

<script src="<?php echo JUri::base(); ?>plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js"></script>

VAGY relatív útvonallal:

<script src="payment-ajax-patch.js"></script>

=====================================================================
MEGJEGYZÉSEK
=====================================================================

1. BEÁGYAZOTT (Opció A) ELŐNYEI:
   - Nincs külön HTTP kérés (gyorsabb)
   - Garantáltan betöltődik, ha a PHP fájl fut
   - Cache problémák elkerülése
   
2. KÜLSŐ FÁJL (Opció B) ELŐNYEI:
   - Könnyebb debuggolni a böngésző fejlesztői eszközökkel
   - Cache-elhető a böngésző által
   - Kisebb HTML méret
   
AJÁNLÁS: Használja az Opció A-t (beágyazott), hacsak nincs konkrét
oka a külső fájl használatára.

=====================================================================
ELLENŐRZŐLISTA - INSTALLÁCIÓ UTÁN
=====================================================================

✓ 1. A payment-ajax-patch.js fájl a megfelelő helyen van
✓ 2. A guestform.php fájl végére beillesztette a fenti kódot
✓ 3. A HTML-ben van #payment-form ID-val ellátott form
✓ 4. A HTML-ben van .payment-submit-btn class-szal ellátott gomb
✓ 5. A HTML-ben van #payment-error-message ID-val ellátott div
✓ 6. A HTML-ben van #payment-loading ID-val ellátott div
✓ 7. A form tartalmazza az összes szükséges rejtett mezőt
✓ 8. Tesztelje a böngészőben és nézze meg a konzolt (F12)

Ha minden működik, a konzolban (F12 > Console) ezt kell látnia:
  [Payment Handler] Inicializálás...
  [Payment Handler] Form eseménykezelő regisztrálva: #payment-form

=====================================================================
-->
