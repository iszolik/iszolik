<?php
/**
 * Solidres Session Context Helper
 * 
 * This helper class manages reservation session data and context parameters
 * across different menu contexts (root, submenu, hub).
 * 
 * @package     Solidres
 * @subpackage  Helpers
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Session Context Helper Class
 */
class SolidresSessionContextHelper
{
    /**
     * Get reservation details from session
     * 
     * @return  object|null  Reservation details or null
     */
    public static function getReservationDetails()
    {
        $session = JFactory::getSession();
        return $session->get('reservationDetails', null, 'com_solidres');
    }

    /**
     * Set reservation details in session
     * 
     * @param   object  $reservationDetails  Reservation details object
     * 
     * @return  void
     */
    public static function setReservationDetails($reservationDetails)
    {
        $session = JFactory::getSession();
        $session->set('reservationDetails', $reservationDetails, 'com_solidres');
    }

    /**
     * Get context parameters from session
     * 
     * @return  object|null  Context parameters or null
     */
    public static function getContext()
    {
        $reservationDetails = self::getReservationDetails();
        
        if ($reservationDetails && isset($reservationDetails->context)) {
            return $reservationDetails->context;
        }
        
        return null;
    }

    /**
     * Update context parameters in session
     * 
     * @param   array  $params  Context parameters to update
     * 
     * @return  bool  Success status
     */
    public static function updateContext($params)
    {
        $reservationDetails = self::getReservationDetails();
        
        if (!$reservationDetails) {
            return false;
        }
        
        if (!isset($reservationDetails->context)) {
            $reservationDetails->context = new stdClass();
        }
        
        foreach ($params as $key => $value) {
            $reservationDetails->context->$key = $value;
        }
        
        $reservationDetails->context->last_updated = JFactory::getDate()->toSql();
        
        self::setReservationDetails($reservationDetails);
        
        return true;
    }

    /**
     * Validate and sync context parameters
     * 
     * This method ensures session context matches URL parameters
     * 
     * @param   array  $urlParams  URL parameters to validate against
     * 
     * @return  bool  Whether context is valid
     */
    public static function validateAndSync($urlParams = [])
    {
        $app = JFactory::getApplication();
        $input = $app->input;
        
        // Get URL parameters if not provided
        if (empty($urlParams)) {
            $urlParams = [
                'property_id' => $input->getInt('property_id', 0),
                'hub_id' => $input->getInt('hub_id', 0),
                'site_id' => $input->getInt('site_id', 0),
                'Itemid' => $input->getInt('Itemid', 0),
                'reservation_id' => $input->getInt('reservation_id', 0)
            ];
        }
        
        $context = self::getContext();
        
        // If no context exists, create it from URL params
        if (!$context) {
            self::updateContext($urlParams);
            return true;
        }
        
        // Validate critical parameters
        $criticalParams = ['property_id', 'hub_id'];
        foreach ($criticalParams as $param) {
            if (isset($urlParams[$param]) && $urlParams[$param] > 0) {
                if (isset($context->$param) && $context->$param != $urlParams[$param]) {
                    // Mismatch - log warning
                    if (class_exists('JLog')) {
                        JLog::add(
                            sprintf(
                                'Context parameter mismatch - %s: session=%s, url=%s',
                                $param,
                                $context->$param,
                                $urlParams[$param]
                            ),
                            JLog::WARNING,
                            'com_solidres.session'
                        );
                    }
                    return false;
                }
            }
        }
        
        // Sync non-critical parameters
        self::updateContext($urlParams);
        
        return true;
    }

    /**
     * Clear reservation session
     * 
     * @return  void
     */
    public static function clearSession()
    {
        $session = JFactory::getSession();
        $session->clear('reservationDetails', 'com_solidres');
    }

    /**
     * Get payment method name with language support
     * 
     * @param   string  $methodId  Payment method ID
     * 
     * @return  string  Formatted payment method name
     */
    public static function getPaymentMethodName($methodId)
    {
        // Try language constant first
        $langKey = 'SR_PAYMENT_METHOD_' . strtoupper($methodId);
        $translated = JText::_($langKey);
        
        // If not translated, format the ID
        if ($translated === $langKey) {
            return ucwords(str_replace('_', ' ', $methodId));
        }
        
        return $translated;
    }

    /**
     * Build context-aware URL
     * 
     * This method builds URLs that preserve the current menu context
     * 
     * @param   string  $task    Task to execute
     * @param   array   $params  Additional parameters
     * 
     * @return  string  Complete URL
     */
    public static function buildContextAwareUrl($task, $params = [])
    {
        $app = JFactory::getApplication();
        $input = $app->input;
        
        // Get context parameters
        $context = self::getContext();
        
        // Build base URL
        $url = 'index.php?option=com_solidres';
        
        // Add task
        if (!empty($task)) {
            $url .= '&task=' . $task;
        }
        
        // Add context parameters
        if ($context) {
            if (isset($context->property_id) && $context->property_id > 0) {
                $url .= '&property_id=' . $context->property_id;
            }
            if (isset($context->hub_id) && $context->hub_id > 0) {
                $url .= '&hub_id=' . $context->hub_id;
            }
            if (isset($context->site_id) && $context->site_id > 0) {
                $url .= '&site_id=' . $context->site_id;
            }
            if (isset($context->Itemid) && $context->Itemid > 0) {
                $url .= '&Itemid=' . $context->Itemid;
            }
        }
        
        // Add additional parameters
        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $url .= '&' . $key . '=' . urlencode($value);
            }
        }
        
        return JRoute::_($url);
    }

    /**
     * Log context state (for debugging)
     * 
     * @return  void
     */
    public static function logContextState()
    {
        if (!class_exists('JLog')) {
            return;
        }
        
        $session = JFactory::getSession();
        $reservationDetails = self::getReservationDetails();
        
        JLog::add(
            sprintf(
                'Session Context State - Session ID: %s, Has Reservation: %s, Context: %s',
                $session->getId(),
                $reservationDetails ? 'yes' : 'no',
                $reservationDetails ? json_encode($reservationDetails->context ?? 'no context') : 'n/a'
            ),
            JLog::DEBUG,
            'com_solidres.session'
        );
    }
}

/**
 * Session Context Validator Class
 */
class SolidresSessionContextValidator
{
    /**
     * Validate and sync session context with URL parameters
     * 
     * Call this before rendering payment selection or confirmation forms
     * 
     * @param   array  $urlParams  URL parameters (optional)
     * 
     * @return  bool  Validation result
     */
    public static function validateAndSync($urlParams = [])
    {
        return SolidresSessionContextHelper::validateAndSync($urlParams);
    }

    /**
     * Ensure session has valid reservation context
     * 
     * @param   bool  $throwException  Whether to throw exception on failure
     * 
     * @return  bool  Whether session is valid
     * 
     * @throws  Exception  If session invalid and $throwException is true
     */
    public static function ensureValidSession($throwException = false)
    {
        $reservationDetails = SolidresSessionContextHelper::getReservationDetails();
        
        if (!$reservationDetails) {
            if ($throwException) {
                throw new Exception('Invalid or expired reservation session');
            }
            return false;
        }
        
        return true;
    }
}
