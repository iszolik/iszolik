<?php
/**
 * @package     Solidres
 * @subpackage  Revolut Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * 
 * Revolut Payment Confirmation Page
 * This template handles payment confirmation with AJAX and redirects
 * 
 * CRITICAL: This file contains JavaScript that must correctly handle URLs in:
 * - Root menu items
 * - Submenu items (deeply nested paths)
 * - Hub/multisite contexts (multiple properties/sites)
 * - Deep links (directly accessing booking URLs)
 */

defined('_JEXEC') or die;
?>

<div id="revolut-confirmation-container">
    <h2>Revolut Payment Confirmation</h2>
    <div id="revolut-status-message">Processing payment...</div>
</div>

<script>
(function() {
    'use strict';
    
    /**
     * Build AJAX URL with full path preservation
     * 
     * WHY THIS IS NECESSARY FOR SOLIDRES:
     * In Joomla/Solidres environments, the booking system can be accessed through:
     * 1. Root menu items: /booking
     * 2. Submenu items: /properties/hotel-a/booking
     * 3. Hub/multisite: /hub-site/properties/hotel-a/booking
     * 
     * Simple relative URLs like 'index.php?option=com_solidres&task=confirm'
     * will lose the path context, causing 404 errors or wrong routing.
     * 
     * This function uses window.location to preserve ALL path segments,
     * ensuring AJAX calls work regardless of menu structure or hub context.
     */
    function buildAjaxUrl(params) {
        // Get the current page's full path, including all submenu/hub segments
        var currentPath = window.location.pathname;
        var baseUrl = window.location.origin + currentPath;
        
        // If we're on a specific page (e.g., confirmation.php), remove the filename
        // to get back to the directory level where we can make proper AJAX calls
        if (currentPath.indexOf('.php') !== -1 || currentPath.match(/\/[^\/]+\.\w+$/)) {
            baseUrl = window.location.origin + currentPath.substring(0, currentPath.lastIndexOf('/'));
        }
        
        // Preserve any existing query parameters from current URL
        var existingParams = new URLSearchParams(window.location.search);
        var newParams = new URLSearchParams(params);
        
        // Merge params, with new params taking precedence
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                existingParams.set(key, params[key]);
            }
        }
        
        // Build the complete URL with all context preserved
        var ajaxUrl = baseUrl + '/index.php?' + existingParams.toString();
        
        console.log('[Revolut] Built AJAX URL preserving path:', ajaxUrl);
        return ajaxUrl;
    }
    
    /**
     * Build redirect URL with full path and parameter preservation
     * 
     * WHY THIS IS NECESSARY FOR SOLIDRES:
     * After successful payment confirmation, we need to redirect to the
     * confirmation/thank-you page. This redirect must preserve:
     * - All URL path segments (submenu, hub context)
     * - All query parameters (booking ID, property ID, etc.)
     * - Menu item context (so navigation remains consistent)
     * 
     * Without this, users would be redirected to root URL, losing their
     * submenu/hub context, breaking the user experience on multi-property sites.
     */
    function buildRedirectUrl(view, additionalParams) {
        // Start with current path to preserve submenu/hub context
        var currentPath = window.location.pathname;
        var baseUrl = window.location.origin + currentPath;
        
        // Clean up current path if it includes a specific filename
        if (currentPath.indexOf('.php') !== -1 || currentPath.match(/\/[^\/]+\.\w+$/)) {
            baseUrl = window.location.origin + currentPath.substring(0, currentPath.lastIndexOf('/'));
        }
        
        // Preserve critical query parameters from current URL
        var currentParams = new URLSearchParams(window.location.search);
        var redirectParams = new URLSearchParams();
        
        // Always preserve these critical Joomla/Solidres parameters
        var preserveParams = ['option', 'Itemid', 'property_id', 'hub_id', 'site_id'];
        preserveParams.forEach(function(param) {
            if (currentParams.has(param)) {
                redirectParams.set(param, currentParams.get(param));
            }
        });
        
        // Set the new view
        redirectParams.set('view', view);
        
        // Add any additional parameters
        if (additionalParams) {
            for (var key in additionalParams) {
                if (additionalParams.hasOwnProperty(key)) {
                    redirectParams.set(key, additionalParams[key]);
                }
            }
        }
        
        // Build complete redirect URL
        var redirectUrl = baseUrl + '/index.php?' + redirectParams.toString();
        
        console.log('[Revolut] Built redirect URL preserving context:', redirectUrl);
        return redirectUrl;
    }
    
    /**
     * Process payment confirmation via AJAX
     */
    function processPaymentConfirmation() {
        // Extract payment details from URL or form
        var urlParams = new URLSearchParams(window.location.search);
        var paymentId = urlParams.get('payment_id') || urlParams.get('id');
        var bookingId = urlParams.get('booking_id') || urlParams.get('reservation_id');
        
        if (!paymentId) {
            document.getElementById('revolut-status-message').innerHTML = 
                '<div class="alert alert-error">Missing payment ID</div>';
            return;
        }
        
        // Build AJAX URL with full path preservation (critical for submenus/hubs)
        var ajaxUrl = buildAjaxUrl({
            option: 'com_solidres',
            task: 'payment.confirm',
            format: 'json',
            payment_method: 'revolut',
            payment_id: paymentId,
            booking_id: bookingId
        });
        
        // Perform AJAX call
        document.getElementById('revolut-status-message').innerHTML = 
            '<div class="alert alert-info">Confirming payment with Revolut...</div>';
        
        fetch(ajaxUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Success: redirect to confirmation page with full context preserved
                document.getElementById('revolut-status-message').innerHTML = 
                    '<div class="alert alert-success">' + (data.message || 'Payment confirmed successfully!') + '</div>';
                
                setTimeout(function() {
                    var redirectUrl = buildRedirectUrl('confirmation', {
                        booking_id: data.booking_id || bookingId,
                        payment_id: paymentId,
                        status: 'success'
                    });
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                // Error from server
                document.getElementById('revolut-status-message').innerHTML = 
                    '<div class="alert alert-error">' + (data.message || 'Payment confirmation failed') + '</div>';
            }
        })
        .catch(function(error) {
            console.error('[Revolut] Payment confirmation error:', error);
            document.getElementById('revolut-status-message').innerHTML = 
                '<div class="alert alert-error">Payment confirmation failed: ' + error.message + '</div>';
        });
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', processPaymentConfirmation);
    } else {
        processPaymentConfirmation();
    }
    
})();
</script>

<style>
#revolut-confirmation-container {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
}

#revolut-status-message {
    margin-top: 20px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-info {
    color: #31708f;
    background-color: #d9edf7;
    border-color: #bce8f1;
}

.alert-success {
    color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}

.alert-error {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}
</style>
