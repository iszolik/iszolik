<?php
/**
 * @package     Solidres
 * @subpackage  Qvik Payment Plugin
 * @copyright   Copyright (C) 2024 Solidres. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;

/**
 * Qvik Payment Plugin Installer Script
 */
class plgSolidrespaymentQvikInstallerScript
{
    /**
     * Plugin-specifikus marker
     */
    const PLUGIN_MARKER = '; === QVIK PAYMENT PLUGIN - START ===';

    /**
     * Post-install hook
     *
     * @param   string            $type     Install type
     * @param   InstallerAdapter  $parent   Parent object
     *
     * @return  boolean
     */
    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update')
        {
            try
            {
                $this->installConfirmationForm();
                $this->installReservationPhp();
                $this->installEmailTemplates();

                echo '<div class="alert alert-success">Qvik plugin sikeresen telepítve!</div>';
            }
            catch (\Exception $e)
            {
                echo '<div class="alert alert-danger">Hiba történt: ' . $e->getMessage() . '</div>';
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall hook
     *
     * @param   InstallerAdapter  $parent   Parent object
     *
     * @return  boolean
     */
    public function uninstall($parent)
    {
        echo '<div class="alert alert-info">Qvik plugin eltávolítva. A módosított fájlok megmaradtak.</div>';
        return true;
    }

    /**
     * Confirmation form telepítése filesize optimalizációval
     *
     * @return  void
     * @throws  \RuntimeException
     */
    protected function installConfirmationForm()
    {
        $template = $this->getDefaultTemplate();
        $sourceFile = __DIR__ . '/asset/confirmation.php';
        $targetDir = JPATH_SITE . '/templates/' . $template . '/html/com_solidres/reservationasset';
        $targetFile = $targetDir . '/confirmation.php';

        if (!file_exists($sourceFile))
        {
            throw new \RuntimeException('Forrásfájl nem található: ' . $sourceFile);
        }

        // Filesize ellenőrzés - skip ha azonos méret
        if (file_exists($targetFile))
        {
            $sourceSize = filesize($sourceFile);
            $targetSize = filesize($targetFile);

            if ($sourceSize === $targetSize)
            {
                echo '<div class="alert alert-info">Confirmation form már telepítve (azonos méret), átugorva.</div>';
                return;
            }

            // Backup csak akkor, ha méret különbözik
            $backupFile = $targetFile . '.backup.' . date('YmdHis');
            if (!File::copy($targetFile, $backupFile))
            {
                throw new \RuntimeException('Backup készítése sikertelen: ' . $targetFile);
            }
            echo '<div class="alert alert-info">Backup létrehozva: ' . basename($backupFile) . '</div>';
        }

        // Célkönyvtár létrehozása
        if (!is_dir($targetDir))
        {
            if (!Folder::create($targetDir))
            {
                throw new \RuntimeException('Könyvtár létrehozása sikertelen: ' . $targetDir);
            }
        }

        // Fájl másolása
        if (!File::copy($sourceFile, $targetFile))
        {
            throw new \RuntimeException('Fájl másolása sikertelen: ' . $targetFile);
        }

        echo '<div class="alert alert-success">Confirmation form telepítve: ' . $targetFile . '</div>';
    }

    /**
     * Reservation.php telepítése filesize optimalizációval
     *
     * @return  void
     * @throws  \RuntimeException
     */
    protected function installReservationPhp()
    {
        $template = $this->getDefaultTemplate();
        $sourceFile = __DIR__ . '/asset/reservation.php';
        $targetDir = JPATH_SITE . '/templates/' . $template . '/html/com_solidres/reservationasset';
        $targetFile = $targetDir . '/reservation.php';

        if (!file_exists($sourceFile))
        {
            throw new \RuntimeException('Forrásfájl nem található: ' . $sourceFile);
        }

        // Filesize ellenőrzés - skip ha azonos méret
        if (file_exists($targetFile))
        {
            $sourceSize = filesize($sourceFile);
            $targetSize = filesize($targetFile);

            if ($sourceSize === $targetSize)
            {
                echo '<div class="alert alert-info">Reservation.php már telepítve (azonos méret), átugorva.</div>';
                return;
            }

            // Backup csak akkor, ha méret különbözik
            $backupFile = $targetFile . '.backup.' . date('YmdHis');
            if (!File::copy($targetFile, $backupFile))
            {
                throw new \RuntimeException('Backup készítése sikertelen: ' . $targetFile);
            }
            echo '<div class="alert alert-info">Backup létrehozva: ' . basename($backupFile) . '</div>';
        }

        // Célkönyvtár létrehozása
        if (!is_dir($targetDir))
        {
            if (!Folder::create($targetDir))
            {
                throw new \RuntimeException('Könyvtár létrehozása sikertelen: ' . $targetDir);
            }
        }

        // Fájl másolása
        if (!File::copy($sourceFile, $targetFile))
        {
            throw new \RuntimeException('Fájl másolása sikertelen: ' . $targetFile);
        }

        echo '<div class="alert alert-success">Reservation.php telepítve: ' . $targetFile . '</div>';
    }

    /**
     * Email template-ek telepítése filesize optimalizációval minden fájlra
     *
     * @return  void
     * @throws  \RuntimeException
     */
    protected function installEmailTemplates()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        try
        {
            $query->select('template')
                ->from($db->quoteName('#__template_styles'))
                ->where($db->quoteName('client_id') . ' = 0')
                ->where($db->quoteName('home') . ' = 1');
            
            $db->setQuery($query);
            $template = $db->loadResult();
        }
        catch (\RuntimeException $e)
        {
            throw new \RuntimeException('Template lekérdezés sikertelen: ' . $e->getMessage());
        }

        if (!$template)
        {
            $template = 'greenery';
            echo '<div class="alert alert-warning">Default template nem található, fallback: ' . $template . '</div>';
        }

        $sourceDir = __DIR__ . '/asset/email';
        $targetDir = JPATH_SITE . '/templates/' . $template . '/html/com_solidres/email';

        if (!is_dir($sourceDir))
        {
            throw new \RuntimeException('Email template mappa nem található: ' . $sourceDir);
        }

        // Célkönyvtár létrehozása
        if (!is_dir($targetDir))
        {
            if (!Folder::create($targetDir))
            {
                throw new \RuntimeException('Könyvtár létrehozása sikertelen: ' . $targetDir);
            }
        }

        // Fájlok másolása filesize ellenőrzéssel
        $files = Folder::files($sourceDir, '.php$', false, false);
        $installedCount = 0;
        $skippedCount = 0;

        foreach ($files as $file)
        {
            $sourceFile = $sourceDir . '/' . $file;
            $targetFile = $targetDir . '/' . $file;

            // Filesize ellenőrzés - skip ha azonos méret
            if (file_exists($targetFile))
            {
                $sourceSize = filesize($sourceFile);
                $targetSize = filesize($targetFile);

                if ($sourceSize === $targetSize)
                {
                    $skippedCount++;
                    continue;
                }

                // Backup csak akkor, ha méret különbözik
                $backupFile = $targetFile . '.backup.' . date('YmdHis');
                if (!File::copy($targetFile, $backupFile))
                {
                    throw new \RuntimeException('Backup készítése sikertelen: ' . $targetFile);
                }
            }

            // Fájl másolása
            if (!File::copy($sourceFile, $targetFile))
            {
                throw new \RuntimeException('Fájl másolása sikertelen: ' . $targetFile);
            }

            $installedCount++;
        }

        if ($installedCount > 0)
        {
            echo '<div class="alert alert-success">Email template-ek telepítve: ' . $installedCount . ' fájl</div>';
        }

        if ($skippedCount > 0)
        {
            echo '<div class="alert alert-info">Email template-ek átugorva (azonos méret): ' . $skippedCount . ' fájl</div>';
        }

        // Nyelvi konstansok hozzáadása
        $this->installLanguageConstants();
    }

    /**
     * Nyelvi konstansok hozzáadása marker alapú intelligens skipppel
     *
     * @return  void
     * @throws  \RuntimeException
     */
    protected function installLanguageConstants()
    {
        $sourceFile = __DIR__ . '/asset/en-GB.com_solidres.ini';
        $targetFile = JPATH_SITE . '/language/en-GB/en-GB.com_solidres.ini';

        if (!file_exists($sourceFile))
        {
            throw new \RuntimeException('Nyelvi fájl nem található: ' . $sourceFile);
        }

        // Marker alapú ellenőrzés
        if (file_exists($targetFile))
        {
            $targetContent = file_get_contents($targetFile);
            
            if (strpos($targetContent, self::PLUGIN_MARKER) !== false)
            {
                echo '<div class="alert alert-info">Nyelvi konstansok már telepítve (marker megtalálva), átugorva.</div>';
                return;
            }
        }

        // Konstansok hozzáfűzése
        $this->appendToFile($sourceFile, $targetFile);
        echo '<div class="alert alert-success">Nyelvi konstansok hozzáadva.</div>';
    }

    /**
     * Fájl tartalmának hozzáfűzése marker alapú duplikáció-ellenőrzéssel
     *
     * @param   string  $sourceFile  Forrásfájl
     * @param   string  $targetFile  Célfájl
     *
     * @return  void
     * @throws  \RuntimeException
     */
    protected function appendToFile($sourceFile, $targetFile)
    {
        $sourceContent = file_get_contents($sourceFile);
        
        if ($sourceContent === false)
        {
            throw new \RuntimeException('Forrásfájl olvasása sikertelen: ' . $sourceFile);
        }

        // Ha a célfájl nem létezik, létrehozzuk
        if (!file_exists($targetFile))
        {
            if (file_put_contents($targetFile, $sourceContent) === false)
            {
                throw new \RuntimeException('Célfájl létrehozása sikertelen: ' . $targetFile);
            }
            return;
        }

        $targetContent = file_get_contents($targetFile);
        
        if ($targetContent === false)
        {
            throw new \RuntimeException('Célfájl olvasása sikertelen: ' . $targetFile);
        }

        // Plugin marker ellenőrzése
        if (strpos($targetContent, self::PLUGIN_MARKER) !== false)
        {
            return; // Már telepítve van
        }

        // Tartalom hozzáfűzése új sorral
        $newContent = $targetContent . "\n" . $sourceContent;

        if (file_put_contents($targetFile, $newContent) === false)
        {
            throw new \RuntimeException('Célfájl frissítése sikertelen: ' . $targetFile);
        }
    }

    /**
     * Default site template lekérdezése dinamikusan
     *
     * @return  string  Template név
     */
    protected function getDefaultTemplate()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        try
        {
            $query->select('template')
                ->from($db->quoteName('#__template_styles'))
                ->where($db->quoteName('client_id') . ' = 0')
                ->where($db->quoteName('home') . ' = 1');
            
            $db->setQuery($query);
            $template = $db->loadResult();
        }
        catch (\RuntimeException $e)
        {
            // Fallback greenery-re ha hiba történik
            return 'greenery';
        }

        // Fallback ha nem található template
        return $template ?: 'greenery';
    }
}
