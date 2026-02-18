<?php
/**
 * Example Qvik Payment Plugin Template with Context-Aware AJAX Fix
 * 
 * This is an example implementation showing how to integrate the AJAX fix
 * into a Joomla/Solidres payment plugin template.
 * 
 * Location: plugins/solidrespayment/qvik/tmpl/confirmationform.php
 */

defined('_JEXEC') or die;

// Get current context parameters for passing to JavaScript
$app = JFactory::getApplication();
$input = $app->input;

$itemid = $input->getInt('Itemid', 0);
$propertyId = $input->getInt('property_id', 0);
$hubId = $input->getInt('hub_id', 0);
$siteId = $input->getInt('site_id', 0);
$reservationId = $input->getInt('reservation_id', 0);
?>

<div class="qvik-payment-container">
    <h3><?php echo JText::_('SR_PAYMENT_METHOD_QVIK'); ?></h3>
    
    <div class="qvik-payment-options">
        <label>
            <input type="radio" 
                   name="payment_method" 
                   value="qvik" 
                   class="qvik-payment-radio"
                   data-payment-method="qvik">
            <?php echo JText::_('SR_PAY_WITH_QVIK'); ?>
        </label>
    </div>
    
    <div id="qvik-payment-details" style="display:none;">
        <p><?php echo JText::_('SR_QVIK_PAYMENT_DESCRIPTION'); ?></p>
    </div>
</div>

<script>
/**
 * Context-Aware AJAX Implementation for Qvik Payment
 * 
 * This script includes all the functions from PAYMENT_AJAX_FIX.js
 * and implements them specifically for Qvik payment method.
 */

(function() {
    'use strict';
    
    /**
     * Detects if the current page is in a submenu context
     */
    function isSubmenuContext() {
        const pathname = window.location.pathname;
        const indexPos = pathname.indexOf('index.php');
        return indexPos > 1;
    }
    
    /**
     * Gets the base path for the current context
     */
    function getContextBasePath() {
        const pathname = window.location.pathname;
        const indexPos = pathname.indexOf('index.php');
        
        if (indexPos > 1) {
            return pathname.substring(0, indexPos);
        }
        
        return '/';
    }
    
    /**
     * Extracts URL parameter value
     */
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
    
    /**
     * Gets all critical context parameters
     */
    function getCurrentContextParameters() {
        return {
            itemid: getUrlParameter('Itemid'),
            propertyId: getUrlParameter('property_id'),
            hubId: getUrlParameter('hub_id'),
            siteId: getUrlParameter('site_id'),
            reservationId: getUrlParameter('reservation_id')
        };
    }
    
    /**
     * Builds a context-aware AJAX URL
     */
    function buildContextAwareAjaxUrl(task, additionalParams = {}) {
        const origin = window.location.origin;
        const basePath = getContextBasePath();
        const baseUrl = origin + basePath + 'index.php';
        
        const params = new URLSearchParams();
        params.append('option', 'com_solidres');
        params.append('task', task);
        params.append('format', 'json');
        
        const context = getCurrentContextParameters();
        
        if (context.itemid) params.append('Itemid', context.itemid);
        if (context.propertyId) params.append('property_id', context.propertyId);
        if (context.hubId) params.append('hub_id', context.hubId);
        if (context.siteId) params.append('site_id', context.siteId);
        if (context.reservationId) params.append('reservation_id', context.reservationId);
        
        for (const [key, value] of Object.entries(additionalParams)) {
            if (value !== null && value !== undefined) {
                params.append(key, value);
            }
        }
        
        return baseUrl + '?' + params.toString();
    }
    
    /**
     * Performs a context-aware AJAX call
     */
    async function performContextAwareAjaxCall(url, options = {}) {
        try {
            const response = await fetch(url, {
                method: options.method || 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                body: options.body,
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
            
        } catch (error) {
            console.error('AJAX call failed:', error);
            throw error;
        }
    }
    
    /**
     * Updates the payment method selection
     */
    async function updatePaymentMethod(paymentMethodId) {
        const url = buildContextAwareAjaxUrl('updatePaymentMethod', {
            payment_method_id: paymentMethodId
        });
        
        const formData = new URLSearchParams();
        formData.append('payment_method_id', paymentMethodId);
        
        try {
            const result = await performContextAwareAjaxCall(url, {
                method: 'POST',
                body: formData
            });
            
            console.log('Payment method updated successfully:', result);
            return result;
            
        } catch (error) {
            console.error('Failed to update payment method:', error);
            alert('<?php echo JText::_('SR_PAYMENT_UPDATE_FAILED'); ?>');
            throw error;
        }
    }
    
    /**
     * Initialize payment method handler
     */
    function initQvikPayment() {
        const paymentRadio = document.querySelector('.qvik-payment-radio');
        const paymentDetails = document.getElementById('qvik-payment-details');
        
        if (!paymentRadio) {
            console.warn('Qvik payment radio not found');
            return;
        }
        
        paymentRadio.addEventListener('change', function() {
            if (this.checked) {
                // Show payment details
                if (paymentDetails) {
                    paymentDetails.style.display = 'block';
                }
                
                // Update payment method via AJAX
                updatePaymentMethod('qvik')
                    .then(function(result) {
                        console.log('Qvik payment selected:', result);
                    })
                    .catch(function(error) {
                        console.error('Failed to select Qvik payment:', error);
                        // Uncheck the radio on error
                        paymentRadio.checked = false;
                        if (paymentDetails) {
                            paymentDetails.style.display = 'none';
                        }
                    });
            } else {
                if (paymentDetails) {
                    paymentDetails.style.display = 'none';
                }
            }
        });
        
        // Debug info (remove in production)
        console.log('Qvik Payment Initialized');
        console.log('Context:', {
            isSubmenu: isSubmenuContext(),
            basePath: getContextBasePath(),
            parameters: getCurrentContextParameters()
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQvikPayment);
    } else {
        initQvikPayment();
    }
    
})();
</script>

<style>
.qvik-payment-container {
    margin: 20px 0;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.qvik-payment-options {
    margin: 10px 0;
}

.qvik-payment-options label {
    display: block;
    padding: 10px;
    cursor: pointer;
}

.qvik-payment-radio {
    margin-right: 10px;
}

#qvik-payment-details {
    margin-top: 15px;
    padding: 10px;
    background-color: #f9f9f9;
    border-left: 3px solid #0066cc;
}
</style>
