/**
 * JavaScript Enhancement for Payment Plugins (Qvik & Revolut)
 * 
 * This script provides context-aware URL building and dynamic Itemid resolution
 * to fix 404 errors in submenu contexts.
 * 
 * Usage:
 * Include this in your payment plugin's confirmationform.php template
 * 
 * @package     Solidres
 * @subpackage  PaymentPlugins
 */

(function() {
    'use strict';
    
    /**
     * Get URL parameter by name
     * 
     * @param {string} name - Parameter name
     * @param {string} url - URL to parse (default: current URL)
     * @return {string|null} Parameter value or null
     */
    function getUrlParameter(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, '\\$&');
        const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
        const results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    /**
     * Find the correct Itemid for the current context
     * 
     * This function uses multiple strategies to find the right Itemid:
     * 1. Check current page URL
     * 2. Search page links for matching context
     * 3. Check meta tag (if available)
     * 4. Use fallback value
     * 
     * @return {string} The correct Itemid
     */
    function findCorrectItemId() {
        console.group('Solidres: Finding Correct Itemid');
        
        // Strategy 1: Current URL
        const currentItemId = getUrlParameter('Itemid');
        if (currentItemId) {
            console.log('✓ Found Itemid in current URL:', currentItemId);
            console.groupEnd();
            return currentItemId;
        }
        
        // Strategy 2: Analyze page links
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll('a[href*="com_solidres"]');
        
        console.log('Searching', links.length, 'Solidres links for path:', currentPath);
        
        for (const link of links) {
            try {
                const url = new URL(link.href);
                if (url.pathname === currentPath) {
                    const linkItemId = getUrlParameter('Itemid', link.href);
                    if (linkItemId) {
                        console.log('✓ Found Itemid from matching link:', linkItemId);
                        console.groupEnd();
                        return linkItemId;
                    }
                }
            } catch (e) {
                // Invalid URL, skip
            }
        }
        
        // Strategy 3: Meta tag
        const metaItemId = document.querySelector('meta[name="solidres-itemid"]');
        if (metaItemId) {
            const itemId = metaItemId.getAttribute('content');
            console.log('✓ Found Itemid in meta tag:', itemId);
            console.groupEnd();
            return itemId;
        }
        
        // Strategy 4: Check first Solidres link
        if (links.length > 0) {
            const firstItemId = getUrlParameter('Itemid', links[0].href);
            if (firstItemId) {
                console.warn('⚠ Using Itemid from first Solidres link:', firstItemId);
                console.groupEnd();
                return firstItemId;
            }
        }
        
        // Fallback: Use 0 (session validation will be primary)
        console.warn('⚠ Could not find Itemid, using 0 (will rely on session validation)');
        console.groupEnd();
        return '0';
    }
    
    /**
     * Get context base path
     * 
     * Detects if we're in a submenu context and returns the appropriate base path.
     * 
     * @return {string} Base path (e.g., "/property1/index.php" or "/index.php")
     */
    function getContextBasePath() {
        const pathname = window.location.pathname;
        
        // Check if we're in a submenu context
        if (pathname.indexOf('index.php') > 0) {
            // Submenu context: /property1/index.php
            return pathname;
        } else if (pathname.endsWith('/')) {
            // Root with trailing slash
            return pathname + 'index.php';
        } else if (pathname === '') {
            // Empty pathname
            return '/index.php';
        } else {
            // Standard: /index.php
            return pathname;
        }
    }
    
    /**
     * Build context-aware AJAX URL
     * 
     * Constructs a proper AJAX URL that works in all contexts (root, submenu, hub).
     * Automatically detects context and includes all required parameters.
     * 
     * @param {string} task - The task to execute (e.g., "reservationasset.updatePaymentMethod")
     * @param {object} params - Additional parameters
     * @return {string} Complete AJAX URL
     */
    function buildAjaxUrl(task, params) {
        console.group('Solidres: Building AJAX URL');
        console.log('Task:', task);
        console.log('Input params:', params);
        
        // Get base URL with context
        const origin = window.location.origin;
        const basePath = getContextBasePath();
        const baseUrl = origin + basePath;
        
        console.log('Base URL:', baseUrl);
        
        // Build query parameters
        const queryParams = new URLSearchParams();
        
        // Required Joomla parameters
        queryParams.set('option', 'com_solidres');
        queryParams.set('task', task);
        queryParams.set('format', 'json');
        
        // Get correct Itemid
        const itemId = params.Itemid || findCorrectItemId();
        if (itemId && itemId !== '0') {
            queryParams.set('Itemid', itemId);
        }
        
        // Add all other parameters
        for (const key in params) {
            if (params.hasOwnProperty(key) && key !== 'Itemid') {
                const value = params[key];
                if (value !== null && value !== undefined && value !== '') {
                    queryParams.set(key, value);
                }
            }
        }
        
        // Build final URL
        const finalUrl = baseUrl + '?' + queryParams.toString();
        
        console.log('Final URL:', finalUrl);
        console.log('Query params:', Object.fromEntries(queryParams));
        console.groupEnd();
        
        return finalUrl;
    }
    
    /**
     * Build redirect URL (for after payment)
     * 
     * Similar to buildAjaxUrl but for page redirects instead of AJAX calls.
     * 
     * @param {string} view - The view to redirect to
     * @param {object} params - Additional parameters
     * @return {string} Complete redirect URL
     */
    function buildRedirectUrl(view, params) {
        const origin = window.location.origin;
        const basePath = getContextBasePath();
        const baseUrl = origin + basePath;
        
        const queryParams = new URLSearchParams();
        queryParams.set('option', 'com_solidres');
        queryParams.set('view', view);
        
        // Get correct Itemid
        const itemId = params.Itemid || findCorrectItemId();
        if (itemId && itemId !== '0') {
            queryParams.set('Itemid', itemId);
        }
        
        // Add all other parameters
        for (const key in params) {
            if (params.hasOwnProperty(key) && key !== 'Itemid') {
                const value = params[key];
                if (value !== null && value !== undefined && value !== '') {
                    queryParams.set(key, value);
                }
            }
        }
        
        return baseUrl + '?' + queryParams.toString();
    }
    
    /**
     * Enhanced fetch with three-level error handling
     * 
     * @param {string} url - The URL to fetch
     * @param {object} options - Fetch options
     * @return {Promise} Fetch promise with enhanced error handling
     */
    function enhancedFetch(url, options) {
        console.log('Solidres: Enhanced Fetch -', url);
        
        const startTime = performance.now();
        
        return fetch(url, options)
            .then(response => {
                const endTime = performance.now();
                console.log(`Fetch completed in ${(endTime - startTime).toFixed(2)}ms`);
                console.log('Response:', {
                    status: response.status,
                    statusText: response.statusText,
                    ok: response.ok,
                    url: response.url
                });
                
                // Level 1: HTTP status check
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                // Level 2: API response validation
                if (data.success === false) {
                    throw new Error(data.message || 'Request failed');
                }
                
                return data;
            })
            .catch(error => {
                // Level 3: Error handling
                console.error('Solidres: Fetch Error -', error);
                
                // Provide user-friendly error messages
                let userMessage = 'An error occurred. Please try again.';
                
                if (error.message.includes('404')) {
                    userMessage = 'Service not found. Please refresh the page and try again.';
                } else if (error.message.includes('500')) {
                    userMessage = 'Server error. Please contact support.';
                } else if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                    userMessage = 'Network error. Please check your connection.';
                } else if (error.message) {
                    userMessage = error.message;
                }
                
                // Re-throw with user-friendly message
                throw new Error(userMessage);
            });
    }
    
    /**
     * Get all context parameters from current page
     * 
     * @return {object} Context parameters
     */
    function getContextParameters() {
        return {
            property_id: getUrlParameter('property_id') || '',
            hub_id: getUrlParameter('hub_id') || '',
            site_id: getUrlParameter('site_id') || '',
            reservation_id: getUrlParameter('reservation_id') || '',
            Itemid: findCorrectItemId()
        };
    }
    
    /**
     * Update payment method (example usage)
     * 
     * @param {number} paymentMethodId - The payment method ID
     * @return {Promise} Promise that resolves with response data
     */
    function updatePaymentMethod(paymentMethodId) {
        const context = getContextParameters();
        
        const url = buildAjaxUrl('reservationasset.updatePaymentMethod', {
            payment_method_id: paymentMethodId,
            property_id: context.property_id,
            hub_id: context.hub_id,
            site_id: context.site_id,
            reservation_id: context.reservation_id,
            Itemid: context.Itemid
        });
        
        return enhancedFetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    }
    
    // Export functions to global scope
    window.SolidresPayment = {
        getUrlParameter: getUrlParameter,
        findCorrectItemId: findCorrectItemId,
        getContextBasePath: getContextBasePath,
        buildAjaxUrl: buildAjaxUrl,
        buildRedirectUrl: buildRedirectUrl,
        enhancedFetch: enhancedFetch,
        getContextParameters: getContextParameters,
        updatePaymentMethod: updatePaymentMethod
    };
    
    console.log('✓ Solidres Payment Enhancement loaded');
    console.log('Available methods:', Object.keys(window.SolidresPayment));
    
})();
