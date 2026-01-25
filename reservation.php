<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

JLoader::register('SolidresHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php');
JLoader::register('SolidresControllerReservationBase', JPATH_COMPONENT_ADMINISTRATOR . '/controllers/reservationbase.php');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class SolidresControllerReservation extends SolidresControllerReservationBase
{
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->view_item = 'reservationform';
        $this->view_list = 'reservations';
    }

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $model                    = $this->getModel();
        $resTable                 = Table::getInstance('Reservation', 'SolidresTable');
        $hubDashboard             = $this->app->getUserState($this->context + '.hub_dashboard');
        $isGuestMakingReservation = $this->app->isClient('site') && !$hubDashboard;

        $savedReservationId = $model->getState($model->getName() + '.id');
        $resTable->load($savedReservationId);

        // Saving bank_account_number to the database
        $bankAccountNumber = $this->app->input->getString('bank_account_number', '');
        if (!empty($bankAccountNumber)) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__temporary_bank_data'))
                ->columns(['reservation_id', 'bank_account'])
                ->values(implode(', ', [$db->quote($savedReservationId), $db->quote($bankAccountNumber)]));
            try {
                $db->setQuery($query);
                $db->execute();
                $this->app->enqueueMessage(Text::_('SR_BANK_ACCOUNT_SAVED_SUCCESSFULLY'), 'success');
            } catch (RuntimeException $e) {
                $this->app->enqueueMessage(Text::_('SR_BANK_ACCOUNT_SAVE_FAILED') + ': ' + $e->getMessage(), 'error');
            }
        }

        if ($hubDashboard == 0) {
            // Run payment plugin here
            PluginHelper::importPlugin('solidrespayment', $resTable->payment_method_id);
            
            if ($resTable->payment_method_id === 'qvik' && empty($bankAccountNumber)) {
                $this->app->enqueueMessage(Text::_('SR_BANK_ACCOUNT_REQUIRED'), 'error');
                $this->setRedirect(Route::_('index.php?option=com_solidres&view=reservation&layout=edit&id=' + $savedReservationId, false));
                return false;
            }
            
            $responses = $this->app->triggerEvent('onSolidresPaymentNew', [$resTable]);
        }
    }

    public function finalize()
    {
        $reservationId = $this->input->getUint('reservation_id', 0);
        $bankAccountNumber = $this->app->input->getString('bank_account_number', '');

        if ($reservationId && !empty($bankAccountNumber)) {
            // Save the final bank account to the reservations table
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__sr_reservations_bank_details'))
                ->columns(['reservation_id', 'bank_account'])
                ->values(implode(', ', [$db->quote($reservationId), $db->quote($bankAccountNumber)]));
            try {
                $db->setQuery($query);
                $db->execute();
            } catch (Exception $e) {
                $this->app->enqueueMessage(Text::_('SR_FINALIZE_BANK_ACCOUNT_SAVE_FAILED') + ': ' + $e->getMessage(), 'error');
            }
        }
    }
}