/**
 * Payment Method AJAX Endpoint Fix for Submenu Context
 * 
 * This file provides a complete solution for the Issue #6 bug where
 * payment method fetch endpoints return 404 errors in submenu contexts.
 * 
 * Problem: AJAX calls to update payment methods work in root context but
 * fail with 404 errors in submenu contexts (e.g., /hotels/, /budapest/).
 * 
 * Solution: Context-aware URL building that preserves full path structure
 * and all critical context parameters.
 * 
 * Usage: Include this file in payment plugin templates (Qvik, Revolut, etc.)
 * and call performPaymentMethodUpdate() when user selects a payment method.
 */

/**
 * Detects if the current page is in a submenu context
 * @returns {boolean} True if in submenu context
 */
function isSubmenuContext() {
    const pathname = window.location.pathname;
    const indexPos = pathname.indexOf('index.php');
    
    // If index.php is not at position 1 (after leading /), we're in a submenu
    return indexPos > 1;
}

/**
 * Gets the base path for the current context
 * @returns {string} Base path including any submenu segments
 * 
 * Examples:
 * - Root context: "/"
 * - Submenu: "/hotels/"
 * - Hub: "/budapest/"
 * - Multi-level: "/europe/budapest/"
 */
function getContextBasePath() {
    const pathname = window.location.pathname;
    const indexPos = pathname.indexOf('index.php');
    
    if (indexPos > 1) {
        // Extract path up to and including the last slash before index.php
        return pathname.substring(0, indexPos);
    }
    
    // Root context
    return '/';
}

/**
 * Extracts URL parameter value
 * @param {string} name - Parameter name
 * @returns {string|null} Parameter value or null if not found
 */
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Gets all critical context parameters from the current URL
 * @returns {Object} Object containing all context parameters
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
 * Builds a context-aware AJAX URL that works in all contexts
 * @param {string} task - The Joomla task to execute
 * @param {Object} additionalParams - Additional parameters to include
 * @returns {string} Complete AJAX URL with all parameters
 */
function buildContextAwareAjaxUrl(task, additionalParams = {}) {
    // Get the base URL with full path preservation
    const origin = window.location.origin;
    const basePath = getContextBasePath();
    const baseUrl = origin + basePath + 'index.php';
    
    // Start building query parameters
    const params = new URLSearchParams();
    
    // Add component and task
    params.append('option', 'com_solidres');
    params.append('task', task);
    params.append('format', 'json');
    
    // Get and preserve context parameters
    const context = getCurrentContextParameters();
    
    if (context.itemid) {
        params.append('Itemid', context.itemid);
    }
    
    if (context.propertyId) {
        params.append('property_id', context.propertyId);
    }
    
    if (context.hubId) {
        params.append('hub_id', context.hubId);
    }
    
    if (context.siteId) {
        params.append('site_id', context.siteId);
    }
    
    if (context.reservationId) {
        params.append('reservation_id', context.reservationId);
    }
    
    // Add any additional parameters
    for (const [key, value] of Object.entries(additionalParams)) {
        if (value !== null && value !== undefined) {
            params.append(key, value);
        }
    }
    
    return baseUrl + '?' + params.toString();
}

/**
 * Performs a context-aware AJAX call with proper error handling
 * @param {string} url - The AJAX URL to call
 * @param {Object} options - Fetch options (method, body, etc.)
 * @returns {Promise} Promise that resolves with the response data
 */
async function performContextAwareAjaxCall(url, options = {}) {
    try {
        // Level 1: Perform the fetch
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
        
        // Level 2: Check HTTP status
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Level 3: Parse and validate JSON response
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'API request failed');
        }
        
        return data;
        
    } catch (error) {
        // Level 4: Handle network and other errors
        console.error('AJAX call failed:', error);
        throw error;
    }
}

/**
 * Updates the payment method selection
 * @param {string} paymentMethodId - The selected payment method ID
 * @returns {Promise} Promise that resolves when update is complete
 */
async function performPaymentMethodUpdate(paymentMethodId) {
    // Build the context-aware URL
    const url = buildContextAwareAjaxUrl('updatePaymentMethod', {
        payment_method_id: paymentMethodId
    });
    
    // Create form data
    const formData = new URLSearchParams();
    formData.append('payment_method_id', paymentMethodId);
    
    // Perform the AJAX call
    try {
        const result = await performContextAwareAjaxCall(url, {
            method: 'POST',
            body: formData
        });
        
        console.log('Payment method updated successfully:', result);
        return result;
        
    } catch (error) {
        console.error('Failed to update payment method:', error);
        
        // Show user-friendly error message
        alert('Failed to update payment method. Please try again or contact support.');
        
        throw error;
    }
}

/**
 * Diagnostic function to check context detection
 * Call this from browser console to debug context issues
 */
function debugContextInfo() {
    console.group('Context Debug Info');
    console.log('Is Submenu Context:', isSubmenuContext());
    console.log('Base Path:', getContextBasePath());
    console.log('Full Origin:', window.location.origin);
    console.log('Full Pathname:', window.location.pathname);
    console.log('Context Parameters:', getCurrentContextParameters());
    
    const testUrl = buildContextAwareAjaxUrl('updatePaymentMethod', {
        payment_method_id: 'test_method'
    });
    console.log('Example AJAX URL:', testUrl);
    console.groupEnd();
}

/**
 * Example integration for payment plugin template
 * 
 * In your payment plugin PHP template file, add this JavaScript:
 */
/*
<script>
// Include the functions from PAYMENT_AJAX_FIX.js here

// Then use them in your payment method selection handler
document.addEventListener('DOMContentLoaded', function() {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    
    paymentRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                performPaymentMethodUpdate(this.value)
                    .then(function(result) {
                        console.log('Payment method updated:', result);
                        // Update UI as needed
                    })
                    .catch(function(error) {
                        console.error('Update failed:', error);
                        // Handle error in UI
                    });
            }
        });
    });
});
</script>
*/

/**
 * Alternative: jQuery-based implementation (if jQuery is available)
 */
/*
(function($) {
    $(document).ready(function() {
        $('input[name="payment_method"]').on('change', function() {
            if ($(this).is(':checked')) {
                performPaymentMethodUpdate($(this).val())
                    .then(function(result) {
                        console.log('Payment method updated:', result);
                    })
                    .catch(function(error) {
                        console.error('Update failed:', error);
                    });
            }
        });
    });
})(jQuery);
*/

// Make functions available globally if needed
if (typeof window !== 'undefined') {
    window.SolidresPaymentAjax = {
        isSubmenuContext: isSubmenuContext,
        getContextBasePath: getContextBasePath,
        getUrlParameter: getUrlParameter,
        getCurrentContextParameters: getCurrentContextParameters,
        buildContextAwareAjaxUrl: buildContextAwareAjaxUrl,
        performContextAwareAjaxCall: performContextAwareAjaxCall,
        performPaymentMethodUpdate: performPaymentMethodUpdate,
        debugContextInfo: debugContextInfo
    };
}
