<?php
/**
 * @package     Solidres
 * @subpackage  Solidrespayment.qvik
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\Adapter\PluginAdapter;
use Joomla\CMS\Language\Text;

/**
 * Installation script for Qvik payment plugin
 *
 * @since  1.0.0
 */
class PlgSolidrespaymentQvikInstallerScript
{
    /**
     * Plugin element name
     *
     * @var string
     */
    protected $element = 'qvik';

    /**
     * Called after install/update/uninstall
     *
     * @param   string          $type    Type of operation (install, update, uninstall)
     * @param   PluginAdapter   $parent  Parent object
     *
     * @return  boolean  True on success
     */
    public function postflight($type, $parent)
    {
        $app = Factory::getApplication();

        if ($type === 'install')
        {
            $this->enablePlugin($app);
        }

        $this->removeDuplicateIndex($app);
        $this->installConfirmationForm($app);
        
        // NEW STEPS: Install reservation.php and email templates
        $this->installReservationPhp($app);
        $this->installEmailTemplates($app);
        
        $this->appendLanguageStrings($app);

        return true;
    }

    /**
     * Enable the plugin after installation
     *
     * @param   object  $app  Application object
     *
     * @return  void
     */
    private function enablePlugin($app)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('solidrespayment'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->element));

        $db->setQuery($query);
        $db->execute();

        $app->enqueueMessage(Text::sprintf('PLG_SOLIDRESPAYMENT_QVIK_PLUGIN_ENABLED', $this->element), 'message');
    }

    /**
     * Remove duplicate index from payment_method_txn_id
     *
     * @param   object  $app  Application object
     *
     * @return  void
     */
    private function removeDuplicateIndex($app)
    {
        $db = Factory::getDbo();
        
        try
        {
            $query = "SHOW INDEX FROM #__solidres_reservation_payment WHERE Key_name = 'payment_method_txn_id'";
            $db->setQuery($query);
            $result = $db->loadResult();

            if ($result)
            {
                $query = "ALTER TABLE #__solidres_reservation_payment DROP INDEX payment_method_txn_id";
                $db->setQuery($query);
                $db->execute();
                $app->enqueueMessage('✅ payment_method_txn_id UNIQUE index eltávolítva', 'message');
            }
        }
        catch (Exception $e)
        {
            $app->enqueueMessage('⚠️ Nem sikerült a payment_method_txn_id index eltávolítása: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Install confirmation form file
     *
     * @param   object  $app  Application object
     *
     * @return  void
     */
    private function installConfirmationForm($app)
    {
        $src = JPATH_PLUGINS . '/solidrespayment/' . $this->element . '/asset/confirmationform.php';
        $dest = JPATH_ROOT . '/components/com_solidres/confirmationform.php';

        if (File::exists($src))
        {
            $this->copyFileWithBackup($src, $dest, $app);
        }
        else
        {
            $app->enqueueMessage('⚠️ Forrás fájl nem található: confirmationform.php', 'warning');
        }
    }

    /**
     * Install reservation.php file
     *
     * @param   object  $app  Application object
     *
     * @return  void
     */
    private function installReservationPhp($app)
    {
        $src = JPATH_PLUGINS . '/solidrespayment/' . $this->element . '/asset/reservation.php';
        $dest = JPATH_LIBRARIES . '/solidres/reservation/reservation.php';
        $destDir = dirname($dest);

        // Create destination directory if it doesn't exist
        if (!Folder::exists($destDir))
        {
            if (!Folder::create($destDir))
            {
                $app->enqueueMessage('⚠️ Nem sikerült létrehozni a könyvtárat: ' . $destDir, 'warning');
                return;
            }
        }

        if (File::exists($src))
        {
            $this->copyFileWithBackup($src, $dest, $app, 'reservation.php');
        }
        else
        {
            $app->enqueueMessage('⚠️ Forrás fájl nem található: reservation.php', 'warning');
        }
    }

    /**
     * Get the default template name from Joomla
     *
     * @return  string  The default template name, 'greenery' as fallback
     */
    private function getDefaultTemplate()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        $query->select($db->quoteName('template'))
              ->from($db->quoteName('#__template_styles'))
              ->where($db->quoteName('client_id') . ' = 0')  // 0 = frontend (site)
              ->where($db->quoteName('home') . ' = 1');      // default template
        
        $db->setQuery($query);
        
        try {
            $template = $db->loadResult();
            return $template ?: 'greenery'; // Fallback ha nem található
        } catch (\Exception $e) {
            return 'greenery'; // Fallback hiba esetén
        }
    }

    /**
     * Install email template files
     *
     * @param   object  $app  Application object
     *
     * @return  void
     */
    private function installEmailTemplates($app)
    {
        $templateName = $this->getDefaultTemplate();
        $srcDir = JPATH_PLUGINS . '/solidrespayment/' . $this->element . '/asset/emails';
        $destDir = JPATH_ROOT . '/templates/' . $templateName . '/html/layouts/com_solidres/emails';

        // Create destination directory if it doesn't exist
        if (!Folder::exists($destDir))
        {
            if (!Folder::create($destDir))
            {
                $app->enqueueMessage('⚠️ Nem sikerült létrehozni a könyvtárat: ' . $destDir, 'warning');
                return;
            }
        }

        // List of email template files to install
        $emailFiles = [
            'reservation_complete_customer_html.php',
            'reservation_complete_customer_html_inliner.php',
            'reservation_complete_owner_html.php',
            'reservation_complete_owner_html_inliner.php',
            'reservation_complete_customer_pdf.php',
        ];

        foreach ($emailFiles as $filename)
        {
            $src = $srcDir . '/' . $filename;
            $dest = $destDir . '/' . $filename;

            if (File::exists($src))
            {
                $this->copyFileWithBackup($src, $dest, $app, $filename);
            }
            else
            {
                $app->enqueueMessage('⚠️ Forrás fájl nem található: ' . $filename, 'warning');
            }
        }
    }

    /**
     * Copy file with backup
     *
     * @param   string  $src       Source file path
     * @param   string  $dest      Destination file path
     * @param   object  $app       Application object
     * @param   string  $filename  Display filename (optional)
     *
     * @return  void
     */
    private function copyFileWithBackup($src, $dest, $app, $filename = null)
    {
        if ($filename === null)
        {
            $filename = basename($dest);
        }

        try
        {
            // Create backup if destination file exists
            if (File::exists($dest))
            {
                $backupFile = $dest . '.backup.' . date('YmdHis');
                if (!File::copy($dest, $backupFile))
                {
                    $app->enqueueMessage('⚠️ Nem sikerült a backup készítése: ' . $filename, 'warning');
                    return;
                }
            }

            // Copy the new file
            if (File::copy($src, $dest))
            {
                $app->enqueueMessage('✅ ' . $filename . ' sikeresen telepítve', 'message');
            }
            else
            {
                $app->enqueueMessage('⚠️ Nem sikerült a ' . $filename . ' másolása!', 'warning');
            }
        }
        catch (Exception $e)
        {
            $app->enqueueMessage('⚠️ Hiba történt a ' . $filename . ' telepítése során: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Append language strings to Solidres language files
     *
     * @param   object  $app  Application object
     *
     * @return  void
     */
    private function appendLanguageStrings($app)
    {
        $languageStrings = [
            'PLG_SOLIDRESPAYMENT_QVIK_TITLE="Qvik"',
            'PLG_SOLIDRESPAYMENT_QVIK_DESC="Qvik payment gateway integration for Solidres"',
        ];

        $langFiles = [
            JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_solidres.ini',
            JPATH_ADMINISTRATOR . '/language/hu-HU/hu-HU.com_solidres.ini',
        ];

        foreach ($langFiles as $langFile)
        {
            if (File::exists($langFile))
            {
                $content = file_get_contents($langFile);
                $modified = false;

                foreach ($languageStrings as $string)
                {
                    if (strpos($content, $string) === false)
                    {
                        $content .= "\n" . $string;
                        $modified = true;
                    }
                }

                if ($modified)
                {
                    file_put_contents($langFile, $content);
                }
            }
        }

        $app->enqueueMessage('✅ Nyelvi konstansok frissítve', 'message');
    }

    /**
     * Called on uninstall
     *
     * @param   PluginAdapter  $parent  Parent object
     *
     * @return  boolean  True on success
     */
    public function uninstall($parent)
    {
        $app = Factory::getApplication();
        $app->enqueueMessage('Qvik plugin eltávolítva. Az email template-eket manuálisan kell törölni, ha szükséges.', 'message');
        
        return true;
    }
}
