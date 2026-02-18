<?php
/**
 * Confirmation Form Template
 * Handles payment method display with proper session context
 * 
 * @package     Solidres
 * @copyright   Copyright (C) 2024
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

// Biztosítjuk, hogy a session megfelelően inicializálva van
$session = Factory::getSession();
$app = Factory::getApplication();

// Reservation details lekérése session-ból a context megőrzésével
$reservationDetails = $session->get('reservation_details', null, 'com_solidres');

// Ha nincs session adat, próbáljuk meg újra lekérni az adatbázisból
if (empty($reservationDetails) && !empty($_GET['reservation_id'])) {
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__sr_reservations'))
        ->where($db->quoteName('id') . ' = ' . (int) $_GET['reservation_id']);
    
    $db->setQuery($query);
    $reservationData = $db->loadObject();
    
    if ($reservationData) {
        // Session újraépítése az adatbázis adatokból
        $reservationDetails = (object) [
            'id' => $reservationData->id,
            'guest' => [
                'payment_method_id' => $reservationData->payment_method_id,
                'firstname' => $reservationData->guest_firstname,
                'lastname' => $reservationData->guest_lastname,
                'email' => $reservationData->guest_email,
            ],
            'property_id' => $reservationData->property_id,
            'total_price' => $reservationData->total_price,
        ];
        
        // Session mentése a későbbi használatra
        $session->set('reservation_details', $reservationDetails, 'com_solidres');
    }
}

// Context paraméterek megőrzése
$contextParams = [
    'Itemid' => $app->input->getInt('Itemid', 0),
    'property_id' => $app->input->getInt('property_id', 0),
    'hub_id' => $app->input->getInt('hub_id', 0),
    'site_id' => $app->input->getInt('site_id', 0),
    'reservation_id' => !empty($reservationDetails->id) ? $reservationDetails->id : $app->input->getInt('reservation_id', 0),
];

// URL context detection
$uri = Uri::getInstance();
$currentPath = $uri->getPath();
$isSubmenuContext = (strpos($currentPath, '/index.php/') !== false && strpos($currentPath, '/index.php/') > 0);
$isHubContext = !empty($contextParams['hub_id']);

// Session context mentése
$session->set('context_params', $contextParams, 'com_solidres');
$session->set('is_submenu_context', $isSubmenuContext, 'com_solidres');
$session->set('is_hub_context', $isHubContext, 'com_solidres');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Text::_('SR_CONFIRMATION_TITLE'); ?></title>
    <script>
        /**
         * URL builder helper - context-aware URL építés
         * Megőrzi az összes fontos paramétert (Itemid, property_id, hub_id, site_id, reservation_id)
         */
        function buildContextAwareUrl(baseUrl, additionalParams) {
            // Context paraméterek kinyerése az aktuális URL-ből
            const urlParams = new URLSearchParams(window.location.search);
            const contextParams = {
                Itemid: urlParams.get('Itemid') || '<?php echo $contextParams['Itemid']; ?>',
                property_id: urlParams.get('property_id') || '<?php echo $contextParams['property_id']; ?>',
                hub_id: urlParams.get('hub_id') || '<?php echo $contextParams['hub_id']; ?>',
                site_id: urlParams.get('site_id') || '<?php echo $contextParams['site_id']; ?>',
                reservation_id: urlParams.get('reservation_id') || '<?php echo $contextParams['reservation_id']; ?>'
            };
            
            // Base URL meghatározása - pathname megőrzése submenu esetén
            let fullBaseUrl = baseUrl;
            if (!baseUrl.startsWith('http')) {
                const pathname = window.location.pathname;
                const origin = window.location.origin;
                
                // Submenu context detektálás
                if (pathname.indexOf('index.php') > 0) {
                    // Submenu context: /submenu/index.php
                    fullBaseUrl = origin + pathname.substring(0, pathname.lastIndexOf('/') + 1) + baseUrl;
                } else {
                    // Root context: /index.php
                    fullBaseUrl = origin + '/' + baseUrl;
                }
            }
            
            // URL query string építése
            const params = new URLSearchParams();
            
            // Context paraméterek hozzáadása
            for (const [key, value] of Object.entries(contextParams)) {
                if (value && value !== '0') {
                    params.append(key, value);
                }
            }
            
            // További paraméterek hozzáadása
            if (additionalParams) {
                for (const [key, value] of Object.entries(additionalParams)) {
                    params.append(key, value);
                }
            }
            
            return fullBaseUrl + (params.toString() ? '?' + params.toString() : '');
        }
        
        /**
         * AJAX URL builder - fetch kérésekhez
         */
        function buildAjaxUrl(task, additionalParams) {
            const baseParams = {
                option: 'com_solidres',
                task: task,
                format: 'json',
                '<?php echo Session::getFormToken(); ?>': '1'
            };
            
            return buildContextAwareUrl('index.php', Object.assign({}, baseParams, additionalParams));
        }
        
        /**
         * Session context validáció
         */
        function validateSessionContext() {
            const ajaxUrl = buildAjaxUrl('reservationasset.validateContext');
            
            return fetch(ajaxUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    console.error('Session context validation failed:', data.message);
                    return false;
                }
                return true;
            })
            .catch(error => {
                console.error('Session context validation error:', error);
                return false;
            });
        }
        
        // Context validáció page load után
        document.addEventListener('DOMContentLoaded', function() {
            validateSessionContext().then(isValid => {
                if (!isValid) {
                    console.warn('Session context might be invalid, please refresh the page');
                }
            });
            
            // Context debug információk
            console.log('Context Info:', {
                isSubmenuContext: <?php echo $isSubmenuContext ? 'true' : 'false'; ?>,
                isHubContext: <?php echo $isHubContext ? 'true' : 'false'; ?>,
                contextParams: <?php echo json_encode($contextParams); ?>,
                currentPath: window.location.pathname,
                currentHref: window.location.href
            });
        });
    </script>
</head>
<body>
    <div class="sr-confirmation-container">
        <h1><?php echo Text::_('SR_CONFIRMATION_TITLE'); ?></h1>
        
        <?php if (!empty($reservationDetails)): ?>
            <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
                <div class="<?php echo SR_UI_GRID_COL_6 ?>">
                    <strong>
                        <?php
                            // Payment method ID biztonságos kiírása
                            $paymentMethodId = isset($reservationDetails->guest['payment_method_id']) 
                                ? $reservationDetails->guest['payment_method_id'] 
                                : '';
                            
                            // Language constant kulcs generálása
                            $paymentMethodKey = 'SR_PAYMENT_METHOD_' . strtoupper($paymentMethodId);
                            $paymentMethodName = Text::_($paymentMethodKey);
                            
                            // Ha a language constant nem található, használjuk az ID-t
                            if ($paymentMethodName === $paymentMethodKey) {
                                $paymentMethodName = ucfirst(str_replace('_', ' ', $paymentMethodId));
                            }
                            
                            echo Text::_('SR_CONFIRMATION_PAYMENT_METHOD') . ': ' . htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
                        ?>
                    </strong>
                </div>
                
                <div class="<?php echo SR_UI_GRID_COL_6 ?>">
                    <strong><?php echo Text::_('SR_CONFIRMATION_RESERVATION_ID'); ?>:</strong>
                    <?php echo htmlspecialchars($reservationDetails->id ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                
                <?php if (!empty($reservationDetails->guest['firstname'])): ?>
                <div class="<?php echo SR_UI_GRID_COL_6 ?>">
                    <strong><?php echo Text::_('SR_CONFIRMATION_GUEST_NAME'); ?>:</strong>
                    <?php 
                        echo htmlspecialchars($reservationDetails->guest['firstname'] . ' ' . $reservationDetails->guest['lastname'], ENT_QUOTES, 'UTF-8'); 
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($reservationDetails->total_price)): ?>
                <div class="<?php echo SR_UI_GRID_COL_6 ?>">
                    <strong><?php echo Text::_('SR_CONFIRMATION_TOTAL_PRICE'); ?>:</strong>
                    <?php echo htmlspecialchars(number_format($reservationDetails->total_price, 2), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sr-context-info" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ddd;">
                <h3>Context Information (Debug)</h3>
                <ul>
                    <li><strong>Context Type:</strong> 
                        <?php 
                            if ($isHubContext) {
                                echo 'Hub Context';
                            } elseif ($isSubmenuContext) {
                                echo 'Submenu Context';
                            } else {
                                echo 'Root Context';
                            }
                        ?>
                    </li>
                    <li><strong>Itemid:</strong> <?php echo $contextParams['Itemid']; ?></li>
                    <li><strong>Property ID:</strong> <?php echo $contextParams['property_id']; ?></li>
                    <li><strong>Hub ID:</strong> <?php echo $contextParams['hub_id']; ?></li>
                    <li><strong>Site ID:</strong> <?php echo $contextParams['site_id']; ?></li>
                    <li><strong>Reservation ID:</strong> <?php echo $contextParams['reservation_id']; ?></li>
                    <li><strong>Payment Method ID:</strong> <?php echo htmlspecialchars($paymentMethodId, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Current Path:</strong> <?php echo htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <?php echo Text::_('SR_CONFIRMATION_NO_RESERVATION_DATA'); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
