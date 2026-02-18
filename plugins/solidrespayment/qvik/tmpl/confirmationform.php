<?php
/**
 * Qvik Payment Plugin - Confirmation Form Template
 * 
 * This template is displayed when Qvik is selected as the payment method.
 * Enhanced with context-aware URL building for submenu contexts.
 * 
 * @package     Solidres
 * @subpackage  Payment.Qvik
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

// Load session context helper
JLoader::register('SolidresSessionContextHelper', JPATH_SITE . '/components/com_solidres/helpers/sessioncontext.php');

// Get context parameters
$context = SolidresSessionContextHelper::getContext();
$propertyId = $context->property_id ?? 0;
$hubId = $context->hub_id ?? 0;
$siteId = $context->site_id ?? 0;
$itemId = JFactory::getApplication()->input->getInt('Itemid', 0);
$reservationId = $context->reservation_id ?? 0;

// Add payment enhancement JavaScript
JFactory::getDocument()->addScript(JUri::root() . 'components/com_solidres/views/reservationasset/tmpl/payment-enhancement.js');

// Qvik payment parameters
$qvikParams = $this->params;
$merchantId = $qvikParams->get('merchant_id', '');
$returnUrl = SolidresSessionContextHelper::buildContextAwareUrl('reservationasset.paymentReturn', [
    'payment_method' => 'qvik',
    'reservation_id' => $reservationId
]);
$cancelUrl = SolidresSessionContextHelper::buildContextAwareUrl('reservationasset.paymentCancel', [
    'payment_method' => 'qvik',
    'reservation_id' => $reservationId
]);
?>

<div class="qvik-payment-form">
    
    <h3><?php echo JText::_('SR_PAYMENT_METHOD_QVIK'); ?></h3>
    
    <p><?php echo JText::_('SR_QVIK_PAYMENT_INSTRUCTIONS'); ?></p>
    
    <form id="qvik-payment-form" method="post" action="<?php echo $qvikParams->get('gateway_url', ''); ?>">
        
        <!-- Qvik payment parameters -->
        <input type="hidden" name="merchant_id" value="<?php echo htmlspecialchars($merchantId); ?>">
        <input type="hidden" name="amount" value="<?php echo $this->reservationTotal; ?>">
        <input type="hidden" name="currency" value="<?php echo $this->currency; ?>">
        <input type="hidden" name="order_id" value="<?php echo $reservationId; ?>">
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
        <input type="hidden" name="cancel_url" value="<?php echo htmlspecialchars($cancelUrl); ?>">
        
        <!-- Context preservation -->
        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
        <input type="hidden" name="hub_id" value="<?php echo $hubId; ?>">
        <input type="hidden" name="site_id" value="<?php echo $siteId; ?>">
        <input type="hidden" name="Itemid" value="<?php echo $itemId; ?>">
        
        <div class="payment-actions">
            <button type="submit" class="btn btn-primary btn-qvik-pay">
                <?php echo JText::_('SR_PROCEED_TO_QVIK_PAYMENT'); ?>
            </button>
        </div>
        
    </form>
    
</div>

<script>
(function() {
    'use strict';
    
    // Wait for payment enhancement library
    if (typeof SolidresPayment === 'undefined') {
        console.error('SolidresPayment library not loaded');
        return;
    }
    
    // Log context for debugging
    console.log('Qvik Payment Context:', {
        property_id: <?php echo $propertyId; ?>,
        hub_id: <?php echo $hubId; ?>,
        site_id: <?php echo $siteId; ?>,
        Itemid: <?php echo $itemId; ?>,
        reservation_id: <?php echo $reservationId; ?>,
        return_url: '<?php echo $returnUrl; ?>',
        cancel_url: '<?php echo $cancelUrl; ?>'
    });
    
    // Form submission handler
    document.getElementById('qvik-payment-form').addEventListener('submit', function(e) {
        // Can add validation or pre-submission logic here
        console.log('Submitting Qvik payment form');
        
        // Form will submit normally to Qvik gateway
        return true;
    });
    
})();
</script>

<style>
.qvik-payment-form {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.qvik-payment-form h3 {
    margin-top: 0;
    color: #333;
}

.payment-actions {
    margin-top: 20px;
}

.btn-qvik-pay {
    padding: 12px 30px;
    font-size: 16px;
}
</style>
