<?php
/**
 * Solidres ReservationAsset Controller Enhancement
 * 
 * This patch enhances the ReservationAsset controller to handle payment method
 * updates with session-based validation instead of strict menu context validation.
 * This fixes 404 errors in submenu contexts.
 * 
 * Installation:
 * Merge these methods into: components/com_solidres/controllers/reservationasset.php
 * 
 * @package     Solidres
 * @subpackage  Controllers
 */

defined('_JEXEC') or die;

/**
 * Enhanced updatePaymentMethod method for ReservationAsset controller
 * 
 * Add this method to your ReservationAssetController class, or replace the existing one.
 */

/**
 * Update payment method via AJAX
 * 
 * This method handles payment method updates for Qvik, Revolut, and other payment plugins.
 * It uses session-based validation as primary authentication, with menu context as secondary.
 * 
 * @return  void
 */
public function updatePaymentMethod()
{
    // Set JSON response header
    JResponse::setHeader('Content-Type', 'application/json', true);
    
    $app = JFactory::getApplication();
    $input = $app->input;
    $session = JFactory::getSession();
    
    // Get request parameters
    $paymentMethodId = $input->getInt('payment_method_id', 0);
    $propertyId = $input->getInt('property_id', 0);
    $hubId = $input->getInt('hub_id', 0);
    $siteId = $input->getInt('site_id', 0);
    $itemId = $input->getInt('Itemid', 0);
    $reservationId = $input->getInt('reservation_id', 0);
    
    // Log request (if logging is configured)
    if (class_exists('JLog')) {
        JLog::add(
            sprintf(
                'Payment Method Update Request - Method ID: %s, Property: %s, Hub: %s, Itemid: %s, Session: %s',
                $paymentMethodId,
                $propertyId,
                $hubId,
                $itemId,
                $session->getId()
            ),
            JLog::INFO,
            'com_solidres.payment'
        );
    }
    
    try {
        // CRITICAL FIX: Session-based validation (primary)
        // This allows AJAX to work regardless of menu context
        $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
        
        if (!$reservationDetails) {
            throw new Exception('Invalid or expired reservation session. Please start the reservation process again.');
        }
        
        // Validate that session matches request context
        if (isset($reservationDetails->property_id) && $propertyId > 0) {
            if ($reservationDetails->property_id != $propertyId) {
                if (class_exists('JLog')) {
                    JLog::add(
                        sprintf(
                            'Property ID mismatch - Session: %s, Request: %s',
                            $reservationDetails->property_id,
                            $propertyId
                        ),
                        JLog::WARNING,
                        'com_solidres.payment'
                    );
                }
                throw new Exception('Property context mismatch. Please refresh and try again.');
            }
        }
        
        // Update payment method in session
        if (!isset($reservationDetails->context)) {
            $reservationDetails->context = new stdClass();
        }
        
        $reservationDetails->payment_method_id = $paymentMethodId;
        $reservationDetails->context->payment_method_id = $paymentMethodId;
        $reservationDetails->context->property_id = $propertyId;
        $reservationDetails->context->hub_id = $hubId;
        $reservationDetails->context->site_id = $siteId;
        $reservationDetails->context->Itemid = $itemId;
        $reservationDetails->context->reservation_id = $reservationId;
        $reservationDetails->context->last_updated = JFactory::getDate()->toSql();
        
        // Save updated session
        $session->set('reservationDetails', $reservationDetails, 'com_solidres');
        
        // Load payment method details
        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_solidres/models');
        $model = JModelLegacy::getInstance('ReservationAsset', 'SolidresModel');
        
        if (!$model) {
            throw new Exception('Unable to load reservation model');
        }
        
        $paymentMethod = $this->getPaymentMethodDetails($paymentMethodId);
        
        // Log success
        if (class_exists('JLog')) {
            JLog::add(
                sprintf(
                    'Payment Method Updated Successfully - Method: %s (%s)',
                    $paymentMethodId,
                    $paymentMethod ? $paymentMethod->name : 'unknown'
                ),
                JLog::INFO,
                'com_solidres.payment'
            );
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => JText::_('SR_PAYMENT_METHOD_UPDATED_SUCCESSFULLY'),
            'data' => [
                'payment_method_id' => $paymentMethodId,
                'payment_method_name' => $paymentMethod ? $paymentMethod->name : '',
                'session_id' => $session->getId(),
                'context_preserved' => true
            ]
        ]);
        
    } catch (Exception $e) {
        // Log error
        if (class_exists('JLog')) {
            JLog::add(
                sprintf('Payment Method Update Error - %s', $e->getMessage()),
                JLog::ERROR,
                'com_solidres.payment'
            );
        }
        
        // Return error response
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error' => true
        ]);
    }
    
    $app->close();
}

/**
 * Get payment method details by ID
 * 
 * @param   int  $methodId  The payment method ID
 * 
 * @return  object|null  Payment method object or null
 */
protected function getPaymentMethodDetails($methodId)
{
    if ($methodId <= 0) {
        return null;
    }
    
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    
    $query->select('*')
        ->from($db->quoteName('#__sr_payment_methods'))
        ->where($db->quoteName('id') . ' = ' . (int) $methodId)
        ->where($db->quoteName('published') . ' = 1');
    
    $db->setQuery($query);
    
    try {
        return $db->loadObject();
    } catch (Exception $e) {
        if (class_exists('JLog')) {
            JLog::add(
                sprintf('Error loading payment method %s: %s', $methodId, $e->getMessage()),
                JLog::ERROR,
                'com_solidres.payment'
            );
        }
        return null;
    }
}

/**
 * Validate session context (helper method)
 * 
 * This method can be called from other controller methods to validate
 * that the session has proper reservation context.
 * 
 * @param   bool  $throwException  Whether to throw exception on failure
 * 
 * @return  object|null  Reservation details or null
 * 
 * @throws  Exception  If validation fails and $throwException is true
 */
protected function validateSessionContext($throwException = true)
{
    $session = JFactory::getSession();
    $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
    
    if (!$reservationDetails && $throwException) {
        throw new Exception('Invalid or expired reservation session');
    }
    
    // Log validation
    if (class_exists('JLog')) {
        JLog::add(
            sprintf(
                'Session Context Validation - Valid: %s, Session ID: %s',
                $reservationDetails ? 'yes' : 'no',
                $session->getId()
            ),
            JLog::DEBUG,
            'com_solidres.session'
        );
    }
    
    return $reservationDetails;
}

/**
 * Get context-aware parameters from request and session
 * 
 * This helper method merges parameters from the current request with
 * stored session context, ensuring all required parameters are available.
 * 
 * @return  object  Context parameters
 */
protected function getContextParameters()
{
    $app = JFactory::getApplication();
    $input = $app->input;
    $session = JFactory::getSession();
    
    // Get from request
    $params = new stdClass();
    $params->property_id = $input->getInt('property_id', 0);
    $params->hub_id = $input->getInt('hub_id', 0);
    $params->site_id = $input->getInt('site_id', 0);
    $params->Itemid = $input->getInt('Itemid', 0);
    $params->reservation_id = $input->getInt('reservation_id', 0);
    $params->payment_method_id = $input->getInt('payment_method_id', 0);
    
    // Get from session
    $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
    if ($reservationDetails && isset($reservationDetails->context)) {
        $context = $reservationDetails->context;
        
        // Use session values as fallback
        if (!$params->property_id && isset($context->property_id)) {
            $params->property_id = $context->property_id;
        }
        if (!$params->hub_id && isset($context->hub_id)) {
            $params->hub_id = $context->hub_id;
        }
        if (!$params->site_id && isset($context->site_id)) {
            $params->site_id = $context->site_id;
        }
        if (!$params->Itemid && isset($context->Itemid)) {
            $params->Itemid = $context->Itemid;
        }
        if (!$params->reservation_id && isset($context->reservation_id)) {
            $params->reservation_id = $context->reservation_id;
        }
    }
    
    return $params;
}

/**
 * Send JSON error response (helper method)
 * 
 * @param   string  $message      Error message
 * @param   int     $httpCode     HTTP status code (default 400)
 * @param   array   $extraData    Additional data to include
 * 
 * @return  void
 */
protected function sendJsonError($message, $httpCode = 400, $extraData = [])
{
    http_response_code($httpCode);
    
    $response = [
        'success' => false,
        'error' => true,
        'message' => $message
    ];
    
    if (!empty($extraData)) {
        $response['data'] = $extraData;
    }
    
    echo json_encode($response);
    
    JFactory::getApplication()->close();
}

/**
 * Send JSON success response (helper method)
 * 
 * @param   string  $message   Success message
 * @param   array   $data      Data to include
 * 
 * @return  void
 */
protected function sendJsonSuccess($message, $data = [])
{
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    
    JFactory::getApplication()->close();
}
