<!-- 
=====================================================================
PÉLDA SABLON - CONFIRMATIONFORM.PHP INTEGRÁCIÓ
=====================================================================

Ez a fájl egy példa sablon, amely bemutatja, hogyan kell integrálni
a payment-ajax-patch.js fájlt a confirmationform.php-ba.

FONTOS: Ez csak egy példa! Az Ön valódi confirmationform.php fájlja
valószínűleg más felépítésű. A lényeg, hogy a fájl VÉGÉRE (a záró ?>
után vagy ha nincs záró ?>, akkor a legvégére) illessze be a jelölt
kódrészletet.

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
$transactionId = $this->state->get('transaction.id', '');
$amount = $this->state->get('payment.amount', 0);
$currency = $this->state->get('payment.currency', 'HUF');
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fizetés Megerősítése</title>
    
    <!-- Bootstrap vagy saját CSS -->
    <link rel="stylesheet" href="<?php echo JUri::base(); ?>media/com_solidres/css/solidres.css">
    
    <style>
        /* Alapvető stílusok */
        .confirmation-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            border: 2px solid #28a745;
            border-radius: 8px;
            background: #f8fff9;
        }
        
        .success-icon {
            font-size: 64px;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            text-align: center;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .confirmation-message {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: #333;
        }
        
        .detail-value {
            color: #666;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .print-button {
            background: #17a2b8;
            color: white;
        }
        
        .print-button:hover {
            background: #117a8b;
        }
    </style>
</head>
<body>

<div class="confirmation-container">
    
    <!-- Siker Ikon -->
    <div class="success-icon">
        ✅
    </div>
    
    <!-- Főcím -->
    <h1 class="confirmation-title">Fizetés Sikeresen Megerősítve!</h1>
    
    <!-- Megerősítő Üzenet -->
    <div class="confirmation-message">
        <p style="text-align: center; font-size: 18px; margin-bottom: 20px;">
            Köszönjük a fizetését! A tranzakció sikeresen lezajlott.
        </p>
        
        <p style="text-align: center; color: #666;">
            Egy megerősítő e-mailt küldtünk az Ön e-mail címére a foglalás részleteivel.
        </p>
    </div>
    
    <!-- Tranzakció Részletei -->
    <div class="confirmation-message">
        <h3 style="margin-top: 0;">Tranzakció Részletei</h3>
        
        <div class="detail-row">
            <span class="detail-label">Foglalási azonosító:</span>
            <span class="detail-value"><strong><?php echo htmlspecialchars($reservationId); ?></strong></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Tranzakció azonosító:</span>
            <span class="detail-value"><?php echo htmlspecialchars($transactionId); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Összeg:</span>
            <span class="detail-value">
                <strong><?php echo number_format($amount, 0, ',', ' '); ?> <?php echo htmlspecialchars($currency); ?></strong>
            </span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Dátum és idő:</span>
            <span class="detail-value"><?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Fizetési mód:</span>
            <span class="detail-value">Qvik/Revolut Online Fizetés</span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Státusz:</span>
            <span class="detail-value" style="color: #28a745; font-weight: bold;">✓ FIZETETT</span>
        </div>
    </div>
    
    <!-- Következő Lépések -->
    <div class="confirmation-message">
        <h3 style="margin-top: 0;">Következő Lépések</h3>
        <ul style="color: #666; line-height: 1.8;">
            <li>Ellenőrizze az e-mail fiókját a megerősítő levélért</li>
            <li>Ha kérdése van, lépjen kapcsolatba ügyfélszolgálatunkkal</li>
            <li>A foglalás részleteit a "Foglalásaim" menüpontban tekintheti meg</li>
        </ul>
    </div>
    
    <!-- Akció Gombok -->
    <div class="action-buttons">
        <a href="<?php echo JRoute::_('index.php?option=com_solidres&view=reservations&Itemid=' . JFactory::getApplication()->input->getInt('Itemid', 0)); ?>" 
           class="btn btn-primary">
            📋 Foglalásaim Megtekintése
        </a>
        
        <a href="<?php echo JRoute::_('index.php?option=com_solidres&view=reservationasset&id=' . $propertyId . '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid', 0)); ?>" 
           class="btn btn-secondary">
            🏠 Vissza a Szálláshoz
        </a>
        
        <button onclick="window.print();" class="btn print-button">
            🖨️ Nyomtatás
        </button>
    </div>
    
    <!-- Támogatás Info -->
    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <p style="color: #666; font-size: 14px;">
            Kérdése van? Lépjen kapcsolatba velünk:<br>
            📞 +36-1-234-5678 | 📧 info@example.com
        </p>
    </div>
    
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
 * a HTML-be. A confirmationform oldalon ez általában nem kritikus
 * (mivel már megtörtént a fizetés), de érdemes beilleszteni az
 * egységesség kedvéért, és esetleges jövőbeli funkciók miatt.
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
        // Ha egyik sem található, nem kritikus hiba a confirmation oldalon
        // (mivel már megtörtént a fizetés)
        if (class_exists('JLog')) {
            JLog::add(
                'Payment AJAX patch not found at: ' . $patchPath . ' (confirmation page)', 
                JLog::INFO, 
                'payment'
            );
        }
        // Nem csinálunk semmit, a confirmation oldal így is működik
        echo '// Payment AJAX patch not found - not critical on confirmation page';
    }
}
?>
</script>
<!-- 
=====================================================================
⬆⬆⬆ IDE ILLESZTE BE A PAYMENT-AJAX-PATCH.JS-T ⬆⬆⬆
=====================================================================

ALTERNATÍVA - OPCIÓ B: KÜLSŐ FÁJL BETÖLTÉSE
=====================================================================
Ha inkább külső fájlként szeretné betölteni:

<script src="<?php echo JUri::base(); ?>plugins/solidrespayment/qvik/tmpl/payment-ajax-patch.js"></script>

VAGY relatív útvonallal:

<script src="payment-ajax-patch.js"></script>

=====================================================================
MEGJEGYZÉS - CONFIRMATIONFORM OLDALON
=====================================================================

A confirmationform.php oldalon a JavaScript patch elsősorban az
alábbi esetekben hasznos:

1. Egységesség: Mindkét oldalon ugyanaz a patch fut
2. Jövőbeli funkciók: Ha később további AJAX funkciókat ad hozzá
3. Debug: Könnyebb debuggolni, ha mindenhol betöltött

AZONBAN: A kritikus működés a guestform.php oldalon van, ahol
ténylegesen megtörténik az AJAX kérés a fizetéshez. A confirmation
oldalon már megtörtént a fizetés, így a patch opcionális.

AJÁNLÁS: Illessze be a patch-et mindkét oldalra a konzisztencia
érdekében, de ha valamilyen okból nem lehetséges, a guestform.php
az elsődleges prioritás.

=====================================================================
ELLENŐRZŐLISTA - INSTALLÁCIÓ UTÁN
=====================================================================

✓ 1. A payment-ajax-patch.js fájl a megfelelő helyen van
✓ 2. A confirmationform.php fájl végére beillesztette a fenti kódot
✓ 3. A confirmation oldal szépen megjelenik
✓ 4. Az oldalon láthatók a tranzakció részletei
✓ 5. A gombok működnek (Foglalásaim, Vissza, Nyomtatás)
✓ 6. Tesztelje a teljes fizetési folyamatot: guestform → confirmation

Ha minden működik:
  - A guestform oldalon megtörténik az AJAX fizetés
  - Átirányítás a confirmationform oldalra
  - A confirmation oldalon láthatók a részletek

=====================================================================
TESZTELÉSI FOLYAMAT
=====================================================================

1. ELŐKÉSZÍTÉS:
   - Nyissa meg a böngésző fejlesztői eszközeit (F12)
   - Menjen a "Console" fülre
   - Törölje a korábbi logokat

2. GUESTFORM OLDAL:
   - Töltse ki a formot
   - Kattintson a "Fizetés" gombra
   - Ellenőrizze a konzol logokat:
     [Payment AJAX] URL épül: https://domain.hu/index.php
     [Payment AJAX] Kérés küldése: https://domain.hu/index.php
     [Payment AJAX] Sikeres válasz: {...}
     [Payment Redirect] Átirányítás 1000ms múlva: .../confirmationform

3. CONFIRMATIONFORM OLDAL:
   - Az oldal automatikusan betöltődik
   - Ellenőrizze, hogy megjelennek-e a tranzakció részletei
   - Tesztelje a gombokat (Foglalásaim, Vissza, Nyomtatás)

4. URL ELLENŐRZÉS:
   - Eredeti: .../guestform?reservation_id=123
   - Átirányított: .../confirmationform?reservation_id=123
   - ✓ A paraméterek megmaradtak!

=====================================================================
-->
