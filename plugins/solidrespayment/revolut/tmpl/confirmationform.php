<?php
/**
 * Revolut Payment Plugin - Confirmation Form
 * Handles payment method display with proper session context
 * 
 * @package     Solidres.Plugin
 * @subpackage  Solidrespayment.Revolut
 * @copyright   Copyright (C) 2024
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

// Session Context Helper betöltése
require_once JPATH_SITE . '/components/com_solidres/helpers/sessioncontext.php';

// Session és context inicializálás
$session = Factory::getSession();
$app = Factory::getApplication();

// Reservation details lekérése a helper segítségével
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();

// Context paraméterek lekérése
$contextParams = SolidresSessionContextHelper::getContextParams();

// Context type detektálás
$contextType = SolidresSessionContextHelper::detectContextType();

// Ha nincs reservation adat, hibaüzenet és visszairányítás
if (empty($reservationDetails)) {
    $app->enqueueMessage(Text::_('SR_ERROR_NO_RESERVATION_DATA'), 'error');
    $app->redirect(Uri::base());
    return;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Text::_('SR_REVOLUT_CONFIRMATION_TITLE'); ?></title>
    <style>
        .sr-confirmation-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .sr-payment-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .sr-info-row {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .sr-info-row:last-child {
            border-bottom: none;
        }
        .sr-info-label {
            font-weight: bold;
            display: inline-block;
            min-width: 200px;
        }
        .sr-debug-info {
            margin-top: 30px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
    <script>
        /**
         * Context-aware URL builder function
         * Biztosítja, hogy minden AJAX és redirect URL megőrzi a context paramétereket
         */
        function buildContextAwareUrl(baseUrl, additionalParams) {
            const urlParams = new URLSearchParams(window.location.search);
            const contextParams = {
                Itemid: urlParams.get('Itemid') || '<?php echo $contextParams['Itemid']; ?>',
                property_id: urlParams.get('property_id') || '<?php echo $contextParams['property_id']; ?>',
                hub_id: urlParams.get('hub_id') || '<?php echo $contextParams['hub_id']; ?>',
                site_id: urlParams.get('site_id') || '<?php echo $contextParams['site_id']; ?>',
                reservation_id: urlParams.get('reservation_id') || '<?php echo $contextParams['reservation_id']; ?>'
            };
            
            let fullBaseUrl = baseUrl;
            if (!baseUrl.startsWith('http')) {
                const pathname = window.location.pathname;
                const origin = window.location.origin;
                
                // Submenu context detection
                if (pathname.indexOf('index.php') > 0) {
                    fullBaseUrl = origin + pathname.substring(0, pathname.lastIndexOf('/') + 1) + baseUrl;
                } else {
                    fullBaseUrl = origin + '/' + baseUrl;
                }
            }
            
            const params = new URLSearchParams();
            for (const [key, value] of Object.entries(contextParams)) {
                if (value && value !== '0') {
                    params.append(key, value);
                }
            }
            
            if (additionalParams) {
                for (const [key, value] of Object.entries(additionalParams)) {
                    params.append(key, value);
                }
            }
            
            return fullBaseUrl + (params.toString() ? '?' + params.toString() : '');
        }
        
        /**
         * AJAX URL builder for Revolut payment operations
         */
        function buildRevolutAjaxUrl(task, additionalParams) {
            const baseParams = {
                option: 'com_solidres',
                task: task,
                format: 'json',
                payment_plugin: 'revolut',
                '<?php echo Session::getFormToken(); ?>': '1'
            };
            
            return buildContextAwareUrl('index.php', Object.assign({}, baseParams, additionalParams));
        }
        
        // Debug: Log context information
        console.log('Revolut Payment - Context Info:', {
            isSubmenuContext: <?php echo $contextType['is_submenu'] ? 'true' : 'false'; ?>,
            isHubContext: <?php echo $contextType['is_hub'] ? 'true' : 'false'; ?>,
            contextParams: <?php echo json_encode($contextParams); ?>,
            currentPath: window.location.pathname
        });
    </script>
</head>
<body>
    <div class="sr-confirmation-container">
        <h1><?php echo Text::_('SR_REVOLUT_CONFIRMATION_TITLE'); ?></h1>
        
        <div class="sr-payment-info">
            <h2><?php echo Text::_('SR_CONFIRMATION_PAYMENT_DETAILS'); ?></h2>
            
            <div class="sr-info-row">
                <span class="sr-info-label">
                    <?php echo Text::_('SR_CONFIRMATION_PAYMENT_METHOD'); ?>:
                </span>
                <span class="sr-info-value">
                    <?php
                        $paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
                        $paymentMethodName = SolidresSessionContextHelper::getPaymentMethodName($paymentMethodId);
                        echo htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
                    ?>
                    (Revolut)
                </span>
            </div>
            
            <div class="sr-info-row">
                <span class="sr-info-label">
                    <?php echo Text::_('SR_CONFIRMATION_RESERVATION_ID'); ?>:
                </span>
                <span class="sr-info-value">
                    <?php echo htmlspecialchars($reservationDetails->id ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            
            <?php if (!empty($reservationDetails->guest['firstname'])): ?>
            <div class="sr-info-row">
                <span class="sr-info-label">
                    <?php echo Text::_('SR_CONFIRMATION_GUEST_NAME'); ?>:
                </span>
                <span class="sr-info-value">
                    <?php 
                        echo htmlspecialchars(
                            $reservationDetails->guest['firstname'] . ' ' . $reservationDetails->guest['lastname'], 
                            ENT_QUOTES, 
                            'UTF-8'
                        ); 
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($reservationDetails->guest['email'])): ?>
            <div class="sr-info-row">
                <span class="sr-info-label">
                    <?php echo Text::_('SR_CONFIRMATION_GUEST_EMAIL'); ?>:
                </span>
                <span class="sr-info-value">
                    <?php echo htmlspecialchars($reservationDetails->guest['email'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($reservationDetails->total_price)): ?>
            <div class="sr-info-row">
                <span class="sr-info-label">
                    <?php echo Text::_('SR_CONFIRMATION_TOTAL_PRICE'); ?>:
                </span>
                <span class="sr-info-value">
                    <?php echo htmlspecialchars(number_format($reservationDetails->total_price, 0, ',', ' ') . ' Ft', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($app->get('debug') || $app->input->get('debug', 0)): ?>
        <div class="sr-debug-info">
            <h3>Debug Information</h3>
            <ul>
                <li><strong>Context Type:</strong> 
                    <?php 
                        if ($contextType['is_hub']) {
                            echo 'Hub Context';
                        } elseif ($contextType['is_submenu']) {
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
                <li><strong>Current Path:</strong> <?php echo htmlspecialchars($contextType['current_path'], ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Session Valid:</strong> <?php echo SolidresSessionContextHelper::validateContext() ? 'Yes' : 'No'; ?></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
