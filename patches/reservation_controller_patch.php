<?php
/**
 * @package     Solidres
 * @subpackage  Controller
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * 
 * PATCH FILE - ReservationController kiegészítés
 * Fájl: components/com_solidres/controllers/reservation.php
 * 
 * FONTOS: Ez a metódus hozzáadandó a ReservationController osztályhoz!
 */

/**
 * Frissíti a kiválasztott fizetési módot a session-ben
 * Context-független működést biztosít minden menü/hub/submenu környezetben
 * 
 * @return void JSON válasz és alkalmazás leállítása
 */
public function updatePaymentMethod()
{
    // Session lekérése
    $session = JFactory::getSession();
    $app = JFactory::getApplication();
    
    // Input validáció - kritikus paraméterek
    $paymentMethodId = $app->input->getInt('payment_method_id', 0);
    $reservationId = $app->input->getInt('reservation_id', 0);
    
    // Context paraméterek megőrzése (URL preservation pattern)
    $hubId = $app->input->getInt('hub_id', 0);
    $propertyId = $app->input->getInt('property_id', 0);
    $siteId = $app->input->getInt('site_id', 0);
    $itemId = $app->input->getInt('Itemid', 0);
    
    // Validáció
    if ($paymentMethodId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => JText::_('SR_INVALID_PAYMENT_METHOD')
        ]);
        $app->close();
        return;
    }
    
    // Reservation details lekérése a session-ből
    $reservationDetails = $session->get('reservationdetails', null, 'sr');
    
    // Ha nincs session, inicializálás
    if (!$reservationDetails) {
        $reservationDetails = new stdClass();
        $reservationDetails->guest = [];
        $reservationDetails->context = new stdClass();
    }
    
    // Guest array biztosítása
    if (!isset($reservationDetails->guest) || !is_array($reservationDetails->guest)) {
        $reservationDetails->guest = [];
    }
    
    // Fizetési mód frissítése
    $reservationDetails->guest['payment_method_id'] = $paymentMethodId;
    
    // Context létrehozása, ha nem létezik
    if (!isset($reservationDetails->context)) {
        $reservationDetails->context = new stdClass();
    }
    
    // Context paraméterek tárolása - biztosítja a konzisztens működést
    if ($hubId > 0) {
        $reservationDetails->context->hub_id = $hubId;
    }
    if ($propertyId > 0) {
        $reservationDetails->context->property_id = $propertyId;
    }
    if ($siteId > 0) {
        $reservationDetails->context->site_id = $siteId;
    }
    if ($itemId > 0) {
        $reservationDetails->context->Itemid = $itemId;
    }
    if ($reservationId > 0) {
        $reservationDetails->context->reservation_id = $reservationId;
    }
    
    // Timestamp a változás követésére
    $reservationDetails->context->payment_method_updated_at = time();
    
    // Session-be írás
    $session->set('reservationdetails', $reservationDetails, 'sr');
    
    // Debug információ (opcionális, production-ben eltávolítható)
    $debug = JDEBUG ? [
        'session_id' => $session->getId(),
        'timestamp' => date('Y-m-d H:i:s'),
        'context' => $reservationDetails->context
    ] : null;
    
    // Sikeres válasz
    $response = [
        'success' => true,
        'message' => JText::_('SR_PAYMENT_METHOD_UPDATED_SUCCESSFULLY'),
        'payment_method_id' => $paymentMethodId,
        'context' => [
            'hub_id' => $hubId,
            'property_id' => $propertyId,
            'site_id' => $siteId,
            'Itemid' => $itemId,
            'reservation_id' => $reservationId
        ]
    ];
    
    if ($debug) {
        $response['debug'] = $debug;
    }
    
    echo json_encode($response);
    $app->close();
}
