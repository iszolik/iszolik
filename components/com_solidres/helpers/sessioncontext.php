<?php
/**
 * Session Context Helper
 * Handles session context for reservation and payment processing
 * 
 * @package     Solidres
 * @copyright   Copyright (C) 2024
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Session Context Helper Class
 * 
 * Biztosítja a helyes session context kezelést menu/hub/root környezetekben
 */
class SolidresSessionContextHelper
{
    /**
     * Session namespace
     */
    const SESSION_NAMESPACE = 'com_solidres';
    
    /**
     * Context paraméterek lekérése
     * 
     * @return array Context paraméterek (Itemid, property_id, hub_id, site_id, reservation_id)
     */
    public static function getContextParams()
    {
        $app = Factory::getApplication();
        $session = Factory::getSession();
        
        // Először a session-ból próbáljuk
        $cachedParams = $session->get('context_params', null, self::SESSION_NAMESPACE);
        
        // Ha van session cache és friss, használjuk
        if ($cachedParams && is_array($cachedParams)) {
            return $cachedParams;
        }
        
        // Különben az aktuális requestből építjük fel
        $params = [
            'Itemid' => $app->input->getInt('Itemid', 0),
            'property_id' => $app->input->getInt('property_id', 0),
            'hub_id' => $app->input->getInt('hub_id', 0),
            'site_id' => $app->input->getInt('site_id', 0),
            'reservation_id' => $app->input->getInt('reservation_id', 0),
        ];
        
        // Session-ba mentjük
        $session->set('context_params', $params, self::SESSION_NAMESPACE);
        
        return $params;
    }
    
    /**
     * Reservation details lekérése session-ból
     * 
     * @param int $reservationId Opcionális reservation ID
     * @return object|null Reservation details vagy null
     */
    public static function getReservationDetails($reservationId = null)
    {
        $session = Factory::getSession();
        $app = Factory::getApplication();
        
        // Session-ból próbáljuk
        $reservationDetails = $session->get('reservation_details', null, self::SESSION_NAMESPACE);
        
        // Ha nincs session adat, adatbázisból lekérjük
        if (empty($reservationDetails)) {
            if (empty($reservationId)) {
                $reservationId = $app->input->getInt('reservation_id', 0);
            }
            
            if ($reservationId > 0) {
                $reservationDetails = self::loadReservationFromDatabase($reservationId);
                
                if ($reservationDetails) {
                    // Session-ba mentjük
                    $session->set('reservation_details', $reservationDetails, self::SESSION_NAMESPACE);
                }
            }
        }
        
        return $reservationDetails;
    }
    
    /**
     * Reservation betöltése adatbázisból
     * 
     * @param int $reservationId Reservation ID
     * @return object|null Reservation object vagy null
     */
    protected static function loadReservationFromDatabase($reservationId)
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__sr_reservations'))
                ->where($db->quoteName('id') . ' = ' . (int) $reservationId);
            
            $db->setQuery($query);
            $reservationData = $db->loadObject();
            
            if ($reservationData) {
                // Objektum strukturálása
                return (object) [
                    'id' => $reservationData->id,
                    'guest' => [
                        'payment_method_id' => $reservationData->payment_method_id ?? '',
                        'firstname' => $reservationData->guest_firstname ?? '',
                        'lastname' => $reservationData->guest_lastname ?? '',
                        'email' => $reservationData->guest_email ?? '',
                    ],
                    'property_id' => $reservationData->property_id ?? 0,
                    'total_price' => $reservationData->total_price ?? 0,
                    'checkin' => $reservationData->checkin ?? '',
                    'checkout' => $reservationData->checkout ?? '',
                ];
            }
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error loading reservation: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Context type detektálás
     * 
     * @return array Context információk (is_submenu, is_hub, is_root)
     */
    public static function detectContextType()
    {
        $uri = Uri::getInstance();
        $currentPath = $uri->getPath();
        $contextParams = self::getContextParams();
        
        $isSubmenu = (strpos($currentPath, '/index.php/') !== false && strpos($currentPath, '/index.php/') > 0);
        $isHub = !empty($contextParams['hub_id']);
        $isRoot = !$isSubmenu && !$isHub;
        
        return [
            'is_submenu' => $isSubmenu,
            'is_hub' => $isHub,
            'is_root' => $isRoot,
            'current_path' => $currentPath,
        ];
    }
    
    /**
     * URL építés context-aware módon
     * 
     * @param string $task Task name
     * @param array $additionalParams További paraméterek
     * @return string Teljes URL
     */
    public static function buildContextUrl($task, $additionalParams = [])
    {
        $uri = Uri::getInstance();
        $contextParams = self::getContextParams();
        $contextType = self::detectContextType();
        
        // Base URL meghatározása
        $baseUrl = $uri->getScheme() . '://' . $uri->getHost();
        
        if ($contextType['is_submenu']) {
            // Submenu context: teljes path megőrzése
            $path = $uri->getPath();
            $baseUrl .= substr($path, 0, strrpos($path, '/') + 1) . 'index.php';
        } else {
            // Root context
            $baseUrl .= '/index.php';
        }
        
        // Paraméterek összeállítása
        $params = array_merge([
            'option' => 'com_solidres',
            'task' => $task,
        ], $contextParams, $additionalParams);
        
        // Query string építése
        $queryString = http_build_query(array_filter($params));
        
        return $baseUrl . '?' . $queryString;
    }
    
    /**
     * Session context validáció
     * 
     * @return bool True ha valid, false ha nem
     */
    public static function validateContext()
    {
        $session = Factory::getSession();
        $contextParams = self::getContextParams();
        $reservationDetails = self::getReservationDetails();
        
        // Ellenőrizzük, hogy van-e reservation_id és van-e hozzá adat
        if (!empty($contextParams['reservation_id'])) {
            if (empty($reservationDetails)) {
                return false;
            }
            
            // Ellenőrizzük, hogy a reservation_id egyezik-e
            if ($reservationDetails->id != $contextParams['reservation_id']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Payment method név lekérése
     * 
     * @param string $paymentMethodId Payment method ID
     * @return string Payment method megjelenítendő neve
     */
    public static function getPaymentMethodName($paymentMethodId)
    {
        if (empty($paymentMethodId)) {
            return '';
        }
        
        // Language constant kulcs
        $languageKey = 'SR_PAYMENT_METHOD_' . strtoupper($paymentMethodId);
        $translatedName = JText::_($languageKey);
        
        // Ha nincs fordítás, formázzuk az ID-t
        if ($translatedName === $languageKey) {
            $translatedName = ucfirst(str_replace('_', ' ', $paymentMethodId));
        }
        
        return $translatedName;
    }
    
    /**
     * Session tisztítása
     */
    public static function clearSession()
    {
        $session = Factory::getSession();
        $session->clear('reservation_details', self::SESSION_NAMESPACE);
        $session->clear('context_params', self::SESSION_NAMESPACE);
        $session->clear('is_submenu_context', self::SESSION_NAMESPACE);
        $session->clear('is_hub_context', self::SESSION_NAMESPACE);
    }
}
