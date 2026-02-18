<?php
/**
 * Solidres ReservationAsset Confirmation Form
 * 
 * This template displays the reservation confirmation form with payment method selection.
 * Enhanced with context-aware URL building and session management.
 * 
 * @package     Solidres
 * @subpackage  Views
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

// Load session context helper
JLoader::register('SolidresSessionContextHelper', JPATH_SITE . '/components/com_solidres/helpers/sessioncontext.php');

// Validate and sync session context
SolidresSessionContextValidator::validateAndSync();

// Get reservation details
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();

// Get context parameters
$context = SolidresSessionContextHelper::getContext();
$propertyId = $context->property_id ?? 0;
$hubId = $context->hub_id ?? 0;
$siteId = $context->site_id ?? 0;
$itemId = JFactory::getApplication()->input->getInt('Itemid', 0);

// Add payment enhancement JavaScript
JFactory::getDocument()->addScript(JUri::root() . 'components/com_solidres/views/reservationasset/tmpl/payment-enhancement.js');
?>

<div id="solidres-confirmation-form" class="solidres-reservationasset-confirmationform">
    
    <h2><?php echo JText::_('SR_RESERVATION_CONFIRMATION'); ?></h2>
    
    <!-- Reservation Details -->
    <div class="reservation-details">
        <!-- Display reservation information -->
    </div>
    
    <!-- Payment Method Selection -->
    <div class="payment-methods">
        <h3><?php echo JText::_('SR_SELECT_PAYMENT_METHOD'); ?></h3>
        
        <div id="payment-method-list">
            <?php
            // Load available payment methods
            $paymentMethods = []; // Load from database/model
            
            foreach ($paymentMethods as $method):
                $methodName = SolidresSessionContextHelper::getPaymentMethodName($method->id);
            ?>
                <div class="payment-method-option">
                    <input type="radio" 
                           name="payment_method" 
                           id="payment-method-<?php echo $method->id; ?>" 
                           value="<?php echo $method->id; ?>"
                           data-method-id="<?php echo $method->id; ?>"
                           class="payment-method-radio">
                    <label for="payment-method-<?php echo $method->id; ?>">
                        <?php echo htmlspecialchars($methodName); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Confirmation Buttons -->
    <div class="confirmation-actions">
        <button type="button" class="btn btn-primary" id="confirm-reservation-btn">
            <?php echo JText::_('SR_CONFIRM_RESERVATION'); ?>
        </button>
    </div>
    
</div>

<script>
(function() {
    'use strict';
    
    // Wait for payment enhancement library to load
    if (typeof SolidresPayment === 'undefined') {
        console.error('SolidresPayment library not loaded');
        return;
    }
    
    // Get context parameters from page
    const contextParams = {
        property_id: <?php echo $propertyId; ?>,
        hub_id: <?php echo $hubId; ?>,
        site_id: <?php echo $siteId; ?>,
        Itemid: <?php echo $itemId; ?>
    };
    
    // Handle payment method selection
    document.querySelectorAll('.payment-method-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const methodId = this.getAttribute('data-method-id');
            
            console.log('Payment method selected:', methodId);
            
            // Build AJAX URL with context awareness
            const url = SolidresPayment.buildAjaxUrl('reservationasset.updatePaymentMethod', {
                payment_method_id: methodId,
                property_id: contextParams.property_id,
                hub_id: contextParams.hub_id,
                site_id: contextParams.site_id,
                Itemid: contextParams.Itemid
            });
            
            console.log('Updating payment method via:', url);
            
            // Update payment method via AJAX
            SolidresPayment.enhancedFetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(data) {
                console.log('Payment method updated successfully:', data);
                
                // Show success message
                if (data.message) {
                    // Display success notification
                    alert(data.message);
                }
            })
            .catch(function(error) {
                console.error('Failed to update payment method:', error);
                
                // Show error message
                alert('Error: ' + error.message);
                
                // Reset radio selection
                radio.checked = false;
            });
        });
    });
    
    // Handle confirmation button
    document.getElementById('confirm-reservation-btn').addEventListener('click', function() {
        const selectedMethod = document.querySelector('.payment-method-radio:checked');
        
        if (!selectedMethod) {
            alert('<?php echo JText::_('SR_PLEASE_SELECT_PAYMENT_METHOD'); ?>');
            return;
        }
        
        // Build confirmation URL
        const confirmUrl = SolidresPayment.buildRedirectUrl('reservationasset', {
            task: 'reservationasset.confirm',
            property_id: contextParams.property_id,
            hub_id: contextParams.hub_id,
            site_id: contextParams.site_id,
            Itemid: contextParams.Itemid
        });
        
        // Redirect to confirmation
        window.location.href = confirmUrl;
    });
    
    // Log context state for debugging (if debug mode enabled)
    if (window.location.search.indexOf('debug=1') > -1) {
        console.log('Context parameters:', contextParams);
        console.log('Current location:', {
            href: window.location.href,
            pathname: window.location.pathname,
            origin: window.location.origin
        });
    }
    
})();
</script>
