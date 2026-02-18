<?php
/**
 * Revolut Payment Plugin - Confirmation Form Template
 * 
 * This template is displayed when Revolut is selected as the payment method.
 * Enhanced with context-aware URL building for submenu contexts.
 * 
 * @package     Solidres
 * @subpackage  Payment.Revolut
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

// Revolut payment parameters
$revolutParams = $this->params;
$apiKey = $revolutParams->get('api_key', '');
$returnUrl = SolidresSessionContextHelper::buildContextAwareUrl('reservationasset.paymentReturn', [
    'payment_method' => 'revolut',
    'reservation_id' => $reservationId
]);
$cancelUrl = SolidresSessionContextHelper::buildContextAwareUrl('reservationasset.paymentCancel', [
    'payment_method' => 'revolut',
    'reservation_id' => $reservationId
]);
?>

<div class="revolut-payment-form">
    
    <h3><?php echo JText::_('SR_PAYMENT_METHOD_REVOLUT'); ?></h3>
    
    <p><?php echo JText::_('SR_REVOLUT_PAYMENT_INSTRUCTIONS'); ?></p>
    
    <div id="revolut-payment-widget">
        <!-- Revolut payment widget will be injected here -->
    </div>
    
    <form id="revolut-payment-form" method="post" style="display:none;">
        
        <!-- Context preservation -->
        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
        <input type="hidden" name="hub_id" value="<?php echo $hubId; ?>">
        <input type="hidden" name="site_id" value="<?php echo $siteId; ?>">
        <input type="hidden" name="Itemid" value="<?php echo $itemId; ?>">
        <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
        
    </form>
    
    <div class="payment-actions">
        <button type="button" class="btn btn-primary btn-revolut-pay" id="revolut-pay-btn">
            <?php echo JText::_('SR_PROCEED_TO_REVOLUT_PAYMENT'); ?>
        </button>
    </div>
    
</div>

<script src="https://sandbox-merchant.revolut.com/embed.js"></script>

<script>
(function() {
    'use strict';
    
    // Wait for payment enhancement library
    if (typeof SolidresPayment === 'undefined') {
        console.error('SolidresPayment library not loaded');
        return;
    }
    
    // Context parameters
    const contextParams = {
        property_id: <?php echo $propertyId; ?>,
        hub_id: <?php echo $hubId; ?>,
        site_id: <?php echo $siteId; ?>,
        Itemid: <?php echo $itemId; ?>,
        reservation_id: <?php echo $reservationId; ?>
    };
    
    console.log('Revolut Payment Context:', contextParams);
    
    // Initialize Revolut payment
    let revolutInstance = null;
    
    function initRevolutPayment() {
        // Create payment order via AJAX
        const createOrderUrl = SolidresPayment.buildAjaxUrl('reservationasset.createRevolutOrder', {
            property_id: contextParams.property_id,
            hub_id: contextParams.hub_id,
            site_id: contextParams.site_id,
            Itemid: contextParams.Itemid,
            reservation_id: contextParams.reservation_id
        });
        
        SolidresPayment.enhancedFetch(createOrderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(data) {
            if (!data.order_id) {
                throw new Error('Failed to create Revolut order');
            }
            
            console.log('Revolut order created:', data.order_id);
            
            // Initialize Revolut widget
            revolutInstance = RevolutCheckout(data.order_id, 'sandbox');
            
            return revolutInstance;
        })
        .catch(function(error) {
            console.error('Failed to initialize Revolut payment:', error);
            alert('Error: ' + error.message);
        });
    }
    
    // Handle payment button click
    document.getElementById('revolut-pay-btn').addEventListener('click', function() {
        if (!revolutInstance) {
            alert('<?php echo JText::_('SR_PAYMENT_NOT_INITIALIZED'); ?>');
            return;
        }
        
        // Open Revolut payment modal
        revolutInstance.payWithPopup({
            onSuccess: function() {
                console.log('Revolut payment successful');
                
                // Redirect to return URL
                window.location.href = '<?php echo $returnUrl; ?>';
            },
            onError: function(error) {
                console.error('Revolut payment failed:', error);
                alert('Payment failed: ' + error.message);
            },
            onCancel: function() {
                console.log('Revolut payment cancelled');
                
                // Redirect to cancel URL
                window.location.href = '<?php echo $cancelUrl; ?>';
            }
        });
    });
    
    // Initialize on page load
    if (typeof RevolutCheckout !== 'undefined') {
        initRevolutPayment();
    } else {
        console.error('Revolut SDK not loaded');
    }
    
})();
</script>

<style>
.revolut-payment-form {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}

.revolut-payment-form h3 {
    margin-top: 0;
    color: #333;
}

#revolut-payment-widget {
    min-height: 200px;
    margin: 20px 0;
}

.payment-actions {
    margin-top: 20px;
}

.btn-revolut-pay {
    padding: 12px 30px;
    font-size: 16px;
}
</style>
