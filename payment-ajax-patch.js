/**
 * ============================================================================
 * QVIK/REVOLUT FIZETÉSI RENDSZER - AJAX ÉS ÁTIRÁNYÍTÁS PATCH
 * ============================================================================
 * 
 * Ez a JavaScript patch biztosítja, hogy:
 * 1. Az AJAX kérések mindig az abszolút /index.php végpontra POST-olnak
 * 2. Az átirányítás dinamikusan cseréli a guestform-ot confirmationform-ra
 * 3. Testreszabható ID és class nevek használatával
 * 
 * HASZNÁLAT:
 * - Illessze be ezt a kódot a guestform.php és confirmationform.php végére,
 *   közvetlenül a záró ?> után (vagy ha nincs záró ?>, akkor a fájl végére)
 * - Konfigurálja a CONFIG objektumban található beállításokat
 * ============================================================================
 */

(function() {
    'use strict';

    // ========================================================================
    // KONFIGURÁCIÓ - TESTRESZABHATÓ BEÁLLÍTÁSOK
    // ========================================================================
    
    const CONFIG = {
        // AJAX végpont beállítások
        ajax: {
            // Abszolút végpont a domain gyökérből (ne változtassa meg!)
            endpoint: '/index.php',
            // HTTP metódus
            method: 'POST',
            // Időtúllépés milliszekundumban
            timeout: 30000
        },
        
        // Átirányítás beállítások
        redirect: {
            // Lecserélendő útvonal végződés
            fromPath: 'guestform',
            // Új útvonal végződés
            toPath: 'confirmationform',
            // Átirányítás késleltetése (ms) - sikeres fizetés után
            delay: 1000
        },
        
        // Testreszabható selectorok - módosítsa ezeket a saját HTML struktúrájához
        selectors: {
            // Fizetési form azonosító
            paymentForm: '#payment-form',
            // Fizetés gomb azonosító vagy class
            paymentButton: '.payment-submit-btn',
            // Hibaüzenet megjelenítő elem
            errorContainer: '#payment-error-message',
            // Betöltés jelző elem
            loadingIndicator: '#payment-loading'
        },
        
        // Megőrzendő URL paraméterek
        preserveParams: [
            'Itemid',
            'property_id', 
            'hub_id',
            'site_id',
            'reservation_id',
            'option',
            'view',
            'layout'
        ],
        
        // Hibaüzenetek magyarul
        messages: {
            networkError: 'Hálózati hiba történt. Kérjük, próbálja újra később.',
            timeoutError: 'A kérés időtúllépés miatt megszakadt. Kérjük, próbálja újra.',
            serverError: 'Szerver hiba történt. Kérjük, lépjen kapcsolatba az ügyfélszolgálattal.',
            validationError: 'Kérjük, ellenőrizze a megadott adatokat.',
            generalError: 'Ismeretlen hiba történt. Kérjük, próbálja újra.'
        }
    };

    // ========================================================================
    // URL KEZELŐ FÜGGVÉNYEK
    // ========================================================================

    /**
     * Abszolút AJAX URL építése
     * Mindig az /index.php végpontra mutat a domain gyökérből
     * 
     * @returns {string} Abszolút AJAX URL
     */
    function buildAjaxUrl() {
        const origin = window.location.origin;
        const endpoint = CONFIG.ajax.endpoint;
        
        // Összeállítjuk az abszolút URL-t
        const ajaxUrl = origin + endpoint;
        
        // Debug logolás (development módban)
        if (window.console && window.console.log) {
            console.log('[Payment AJAX] URL épül:', ajaxUrl);
        }
        
        return ajaxUrl;
    }

    /**
     * URL paraméterek kinyerése az aktuális URL-ből
     * 
     * @returns {Object} Paraméterek objektum
     */
    function getUrlParameters() {
        const params = {};
        const searchParams = new URLSearchParams(window.location.search);
        
        CONFIG.preserveParams.forEach(function(paramName) {
            const value = searchParams.get(paramName);
            if (value !== null && value !== '') {
                params[paramName] = value;
            }
        });
        
        return params;
    }

    /**
     * Átirányítási URL építése
     * Az aktuális útvonalból a guestform végződést confirmationform-ra cseréli
     * 
     * @returns {string} Átirányítási URL
     */
    function buildRedirectUrl() {
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        const search = window.location.search;
        
        // Ellenőrizzük, hogy az útvonal tartalmazza-e a guestform szót
        if (pathname.indexOf(CONFIG.redirect.fromPath) === -1) {
            console.warn('[Payment Redirect] Az aktuális útvonal nem tartalmazza a "' + 
                         CONFIG.redirect.fromPath + '" szót:', pathname);
            // Ha nem tartalmazza, akkor csak hozzáadjuk a végére
            return origin + pathname + '/' + CONFIG.redirect.toPath + search;
        }
        
        // Cseréljük le a guestform-ot confirmationform-ra
        const newPathname = pathname.replace(
            new RegExp(CONFIG.redirect.fromPath, 'g'),
            CONFIG.redirect.toPath
        );
        
        // Teljes URL összeállítása paraméterekkel
        const redirectUrl = origin + newPathname + search;
        
        // Debug logolás
        if (window.console && window.console.log) {
            console.log('[Payment Redirect] Eredeti útvonal:', pathname);
            console.log('[Payment Redirect] Új útvonal:', newPathname);
            console.log('[Payment Redirect] Teljes átirányítási URL:', redirectUrl);
        }
        
        return redirectUrl;
    }

    /**
     * Paraméterek hozzáfűzése URL-hez
     * 
     * @param {string} url - Alap URL
     * @param {Object} params - Paraméterek objektum
     * @returns {string} URL paraméterekkel
     */
    function appendParamsToUrl(url, params) {
        if (!params || Object.keys(params).length === 0) {
            return url;
        }
        
        const separator = url.indexOf('?') === -1 ? '?' : '&';
        const paramString = Object.keys(params)
            .map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            })
            .join('&');
        
        return url + separator + paramString;
    }

    // ========================================================================
    // AJAX KOMMUNIKÁCIÓ
    // ========================================================================

    /**
     * AJAX kérés küldése a fizetési adatokkal
     * Mindig POST metódussal az abszolút /index.php végpontra
     * 
     * @param {Object} paymentData - Fizetési adatok
     * @param {Function} onSuccess - Sikeres válasz callback
     * @param {Function} onError - Hiba callback
     */
    function sendPaymentRequest(paymentData, onSuccess, onError) {
        const ajaxUrl = buildAjaxUrl();
        const urlParams = getUrlParameters();
        
        // Kombináljuk a fizetési adatokat és az URL paramétereket
        const requestData = Object.assign({}, urlParams, paymentData);
        
        // Debug logolás
        if (window.console && window.console.log) {
            console.log('[Payment AJAX] Kérés küldése:', ajaxUrl);
            console.log('[Payment AJAX] Adatok:', requestData);
        }
        
        // FormData objektum készítése
        const formData = new FormData();
        Object.keys(requestData).forEach(function(key) {
            formData.append(key, requestData[key]);
        });
        
        // Betöltés jelző megjelenítése
        showLoading(true);
        
        // Időtúllépés kezelése
        const timeoutId = setTimeout(function() {
            if (window.console && window.console.warn) {
                console.warn('[Payment AJAX] Időtúllépés');
            }
            showLoading(false);
            if (onError) {
                onError({
                    error: 'timeout',
                    message: CONFIG.messages.timeoutError
                });
            }
        }, CONFIG.ajax.timeout);
        
        // Fetch API használata
        fetch(ajaxUrl, {
            method: CONFIG.ajax.method,
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            clearTimeout(timeoutId);
            
            // HTTP státusz ellenőrzése
            if (!response.ok) {
                throw new Error('HTTP hiba! Státusz: ' + response.status);
            }
            
            // JSON válasz feldolgozása
            return response.json();
        })
        .then(function(data) {
            showLoading(false);
            
            // API válasz validálása
            if (data.success === false || data.error) {
                if (window.console && window.console.error) {
                    console.error('[Payment AJAX] API hiba:', data);
                }
                if (onError) {
                    onError({
                        error: 'api_error',
                        message: data.message || CONFIG.messages.serverError,
                        data: data
                    });
                }
                return;
            }
            
            // Sikeres válasz
            if (window.console && window.console.log) {
                console.log('[Payment AJAX] Sikeres válasz:', data);
            }
            if (onSuccess) {
                onSuccess(data);
            }
        })
        .catch(function(error) {
            clearTimeout(timeoutId);
            showLoading(false);
            
            // Hálózati vagy feldolgozási hiba
            if (window.console && window.console.error) {
                console.error('[Payment AJAX] Hiba történt:', error);
            }
            if (onError) {
                onError({
                    error: 'network_error',
                    message: CONFIG.messages.networkError,
                    details: error.message
                });
            }
        });
    }

    // ========================================================================
    // UI KEZELÉS
    // ========================================================================

    /**
     * Betöltés jelző megjelenítése/elrejtése
     * 
     * @param {boolean} show - Megjelenítés (true) vagy elrejtés (false)
     */
    function showLoading(show) {
        const loadingElement = document.querySelector(CONFIG.selectors.loadingIndicator);
        if (loadingElement) {
            loadingElement.style.display = show ? 'block' : 'none';
        }
        
        // Fizetés gomb letiltása/engedélyezése
        const paymentButton = document.querySelector(CONFIG.selectors.paymentButton);
        if (paymentButton) {
            paymentButton.disabled = show;
        }
    }

    /**
     * Hibaüzenet megjelenítése
     * 
     * @param {string} message - Hibaüzenet szöveg
     */
    function showError(message) {
        const errorContainer = document.querySelector(CONFIG.selectors.errorContainer);
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
            
            // Automatikus elrejtés 10 másodperc után
            setTimeout(function() {
                errorContainer.style.display = 'none';
            }, 10000);
        } else if (window.alert) {
            // Fallback: alert használata, ha nincs error container
            window.alert(message);
        }
    }

    /**
     * Hibaüzenet elrejtése
     */
    function hideError() {
        const errorContainer = document.querySelector(CONFIG.selectors.errorContainer);
        if (errorContainer) {
            errorContainer.style.display = 'none';
            errorContainer.textContent = '';
        }
    }

    /**
     * Átirányítás a megerősítő oldalra
     * 
     * @param {number} delay - Késleltetés milliszekundumban (opcionális)
     */
    function redirectToConfirmation(delay) {
        const redirectUrl = buildRedirectUrl();
        const actualDelay = delay || CONFIG.redirect.delay;
        
        if (window.console && window.console.log) {
            console.log('[Payment Redirect] Átirányítás ' + actualDelay + 'ms múlva:', redirectUrl);
        }
        
        setTimeout(function() {
            window.location.href = redirectUrl;
        }, actualDelay);
    }

    // ========================================================================
    // ESEMÉNYKEZELŐK
    // ========================================================================

    /**
     * Fizetési form submit eseménykezelő
     * 
     * @param {Event} event - Submit esemény
     */
    function handlePaymentSubmit(event) {
        event.preventDefault();
        hideError();
        
        const form = event.target;
        const formData = new FormData(form);
        const paymentData = {};
        
        // FormData konvertálása objektummá
        formData.forEach(function(value, key) {
            paymentData[key] = value;
        });
        
        // AJAX kérés küldése
        sendPaymentRequest(
            paymentData,
            // Sikeres fizetés
            function(response) {
                if (window.console && window.console.log) {
                    console.log('[Payment] Sikeres fizetés, átirányítás...');
                }
                redirectToConfirmation();
            },
            // Hiba történt
            function(error) {
                showError(error.message);
            }
        );
    }

    /**
     * DOM betöltés után inicializálás
     */
    function initializePaymentHandler() {
        if (window.console && window.console.log) {
            console.log('[Payment Handler] Inicializálás...');
        }
        
        // Fizetési form keresése
        const paymentForm = document.querySelector(CONFIG.selectors.paymentForm);
        
        if (paymentForm) {
            // Submit eseménykezelő hozzáadása
            paymentForm.addEventListener('submit', handlePaymentSubmit);
            
            if (window.console && window.console.log) {
                console.log('[Payment Handler] Form eseménykezelő regisztrálva:', 
                           CONFIG.selectors.paymentForm);
            }
        } else {
            if (window.console && window.console.warn) {
                console.warn('[Payment Handler] Fizetési form nem található:', 
                            CONFIG.selectors.paymentForm);
            }
        }
        
        // Hibaüzenet kezdeti elrejtése
        hideError();
    }

    // ========================================================================
    // INICIALIZÁLÁS
    // ========================================================================

    // DOM betöltésének várakozása
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePaymentHandler);
    } else {
        // Ha a DOM már betöltött, azonnal inicializáljuk
        initializePaymentHandler();
    }

    // ========================================================================
    // GLOBÁLIS API (opcionális - külső használatra)
    // ========================================================================

    // Exportáljuk a fő függvényeket globális névtérbe (ha szükséges)
    window.PaymentAjaxHandler = {
        config: CONFIG,
        buildAjaxUrl: buildAjaxUrl,
        buildRedirectUrl: buildRedirectUrl,
        sendPaymentRequest: sendPaymentRequest,
        redirectToConfirmation: redirectToConfirmation,
        showError: showError,
        hideError: hideError
    };

})();

/**
 * ============================================================================
 * HASZNÁLATI ÚTMUTATÓ
 * ============================================================================
 * 
 * BEILLESZTÉS GUESTFORM.PHP-BA:
 * ------------------------------
 * A fájl végén, közvetlenül a záró ?> után (vagy ha nincs záró ?>, akkor
 * a fájl legvégére) illessze be az alábbi kódot:
 * 
 * <script>
 * <?php include __DIR__ . '/payment-ajax-patch.js'; ?>
 * </script>
 * 
 * VAGY külső fájlként:
 * 
 * <script src="<?php echo JURI::base(); ?>path/to/payment-ajax-patch.js"></script>
 * 
 * ============================================================================
 * 
 * BEILLESZTÉS CONFIRMATIONFORM.PHP-BA:
 * -------------------------------------
 * Ugyanúgy, mint a guestform.php esetében, a fájl végére:
 * 
 * <script>
 * <?php include __DIR__ . '/payment-ajax-patch.js'; ?>
 * </script>
 * 
 * ============================================================================
 * 
 * TESTRESZABÁS:
 * -------------
 * 1. Módosítsa a CONFIG objektumban található selectorokat a saját HTML
 *    struktúrájához:
 *    - paymentForm: A fizetési form ID-ja vagy class-a
 *    - paymentButton: A fizetés gomb ID-ja vagy class-a
 *    - errorContainer: A hibaüzenet megjelenítő elem ID-ja
 *    - loadingIndicator: A betöltés jelző elem ID-ja
 * 
 * 2. Szükség esetén módosíthatja a hibaüzeneteket a CONFIG.messages-ban
 * 
 * 3. Az átirányítás késleltetését a CONFIG.redirect.delay-ben állíthatja
 * 
 * ============================================================================
 * 
 * KÖVETELMÉNYEK:
 * --------------
 * - Modern böngésző (támogatja a Fetch API-t és ES5+ JavaScript-et)
 * - Joomla/Solidres környezet ajánlott
 * - A HTML-ben léteznie kell a CONFIG-ban megadott selectoroknak
 * 
 * ============================================================================
 */
