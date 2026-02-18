<?php
/**
 * @package     Solidres
 * @subpackage  Helper
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * 
 * PATCH FILE - Session Context Validator
 * Fájl: components/com_solidres/helpers/sessioncontext.php
 * 
 * Ez az osztály biztosítja a reservation details context szinkronizálását
 * és validálását minden menü/hub/submenu környezetben.
 */

defined('_JEXEC') or die;

/**
 * Session Context Validator osztály
 * 
 * Feladata:
 * - Session context validálása
 * - Context szinkronizálása URL paraméterekkel
 * - Context-független működés biztosítása
 */
class SolidresSessionContextValidator
{
    /**
     * Validálja és szinkronizálja a reservation details context-et
     * 
     * Ez a metódus minden megjelenítés előtt meghívandó, hogy biztosítsa
     * a session és az URL paraméterek konzisztenciáját.
     * 
     * @return stdClass A validált és szinkronizált reservation details
     */
    public static function validateAndSync()
    {
        $session = JFactory::getSession();
        $app = JFactory::getApplication();
        
        // Session-ből reservation details lekérése
        $reservationDetails = $session->get('reservationdetails', null, 'sr');
        
        // URL paraméterek lekérése
        $urlParams = [
            'property_id' => $app->input->getInt('property_id', 0),
            'hub_id' => $app->input->getInt('hub_id', 0),
            'site_id' => $app->input->getInt('site_id', 0),
            'Itemid' => $app->input->getInt('Itemid', 0),
            'reservation_id' => $app->input->getInt('reservation_id', 0)
        ];
        
        // Ha nincs session, inicializálás
        if (!$reservationDetails) {
            $reservationDetails = new stdClass();
            $reservationDetails->guest = [];
            $reservationDetails->context = (object)$urlParams;
            
            // Session-be mentés
            $session->set('reservationdetails', $reservationDetails, 'sr');
            
            return $reservationDetails;
        }
        
        // Context létrehozása, ha nem létezik
        if (!isset($reservationDetails->context)) {
            $reservationDetails->context = new stdClass();
        }
        
        // Context frissítése az aktuális URL paraméterekkel
        // Csak a nem-nulla értékeket írjuk felül
        foreach ($urlParams as $key => $value) {
            if ($value > 0) {
                $reservationDetails->context->$key = $value;
            }
        }
        
        // Guest array biztosítása
        if (!isset($reservationDetails->guest)) {
            $reservationDetails->guest = [];
        }
        
        // Utolsó validálás időpontja
        $reservationDetails->context->last_validated = time();
        
        // Session-be írás
        $session->set('reservationdetails', $reservationDetails, 'sr');
        
        return $reservationDetails;
    }
    
    /**
     * Ellenőrzi, hogy a session context egyezik-e a jelenlegi URL paraméterekkel
     * 
     * Kritikus paraméterek ellenőrzése:
     * - property_id
     * - reservation_id
     * 
     * @return bool True, ha a context egyezik vagy elfogadható
     */
    public static function isContextValid()
    {
        $session = JFactory::getSession();
        $app = JFactory::getApplication();
        
        $reservationDetails = $session->get('reservationdetails', null, 'sr');
        
        // Ha nincs session, az is valid (inicializálandó)
        if (!$reservationDetails || !isset($reservationDetails->context)) {
            return true;
        }
        
        // Kritikus paraméterek ellenőrzése
        $criticalParams = ['property_id', 'reservation_id'];
        
        foreach ($criticalParams as $param) {
            $urlValue = $app->input->getInt($param, 0);
            $sessionValue = isset($reservationDetails->context->$param) 
                ? $reservationDetails->context->$param 
                : 0;
            
            // Ha mindkét érték létezik és nem egyezik, context invalid
            if ($urlValue > 0 && $sessionValue > 0 && $urlValue != $sessionValue) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Lekéri a payment method ID-t a session-ből
     * Context validálással együtt
     * 
     * @return int A payment method ID, vagy 0 ha nincs beállítva
     */
    public static function getPaymentMethodId()
    {
        // Először validáljuk a context-et
        $reservationDetails = self::validateAndSync();
        
        if (isset($reservationDetails->guest['payment_method_id'])) {
            return (int)$reservationDetails->guest['payment_method_id'];
        }
        
        return 0;
    }
    
    /**
     * Beállítja a payment method ID-t a session-ben
     * Context biztonsággal
     * 
     * @param int $paymentMethodId A beállítandó payment method ID
     * @return bool True sikeres mentés esetén
     */
    public static function setPaymentMethodId($paymentMethodId)
    {
        $session = JFactory::getSession();
        
        // Validálás
        if ($paymentMethodId <= 0) {
            return false;
        }
        
        // Reservation details lekérése és validálása
        $reservationDetails = self::validateAndSync();
        
        // Fizetési mód beállítása
        $reservationDetails->guest['payment_method_id'] = (int)$paymentMethodId;
        
        // Session-be mentés
        $session->set('reservationdetails', $reservationDetails, 'sr');
        
        return true;
    }
    
    /**
     * Context információk lekérése debug célokra
     * 
     * @return array Context információk
     */
    public static function getContextInfo()
    {
        $session = JFactory::getSession();
        $app = JFactory::getApplication();
        
        $reservationDetails = $session->get('reservationdetails', null, 'sr');
        
        $sessionContext = isset($reservationDetails->context) 
            ? (array)$reservationDetails->context 
            : [];
        
        $urlContext = [
            'property_id' => $app->input->getInt('property_id', 0),
            'hub_id' => $app->input->getInt('hub_id', 0),
            'site_id' => $app->input->getInt('site_id', 0),
            'Itemid' => $app->input->getInt('Itemid', 0),
            'reservation_id' => $app->input->getInt('reservation_id', 0)
        ];
        
        return [
            'session_context' => $sessionContext,
            'url_context' => $urlContext,
            'is_valid' => self::isContextValid(),
            'payment_method_id' => self::getPaymentMethodId()
        ];
    }
    
    /**
     * Session reset - csak debug/fejlesztés céljára
     * Production környezetben ne használjuk!
     * 
     * @return void
     */
    public static function resetSession()
    {
        $session = JFactory::getSession();
        $session->clear('reservationdetails', 'sr');
    }
}
