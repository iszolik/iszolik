# Solidres Payment Plugin Installers

This repository contains the installation scripts for Qvik and Revolut payment plugins for Solidres.

## Overview

Both plugins (`plg_solidrespayment_qvik` and `plg_solidrespayment_revolut`) include enhanced installation scripts that automatically:

1. Enable the plugin on installation
2. Remove duplicate `payment_method_txn_id` UNIQUE index from the database
3. Install `confirmationform.php` file
4. **NEW:** Install `reservation.php` template file
5. **NEW:** Install email template files
6. Append language constants to Solidres language files

## File Structure

```
plugins/solidrespayment/
├── qvik/
│   ├── script.php
│   └── asset/
│       ├── reservation.php
│       └── emails/
│           ├── reservation_complete_customer_html.php
│           ├── reservation_complete_customer_html_inliner.php
│           ├── reservation_complete_owner_html.php
│           ├── reservation_complete_owner_html_inliner.php
│           └── reservation_complete_customer_pdf.php
└── revolut/
    ├── script.php
    └── asset/
        ├── reservation.php
        └── emails/
            ├── reservation_complete_customer_html.php
            ├── reservation_complete_customer_html_inliner.php
            ├── reservation_complete_owner_html.php
            ├── reservation_complete_owner_html_inliner.php
            └── reservation_complete_customer_pdf.php
```

## Installation Process

### Step 1: reservation.php Installation

**Source:** `plugins/solidrespayment/[qvik|revolut]/asset/reservation.php`  
**Destination:** `libraries/solidres/reservation/reservation.php`

- Creates destination directory if it doesn't exist
- Creates backup of existing file with `.backup.YmdHis` format
- Copies new file to destination
- Displays success/failure message

### Step 2: Email Template Installation

**Source:** `plugins/solidrespayment/[qvik|revolut]/asset/emails/`  
**Destination:** `templates/greenery/html/layouts/com_solidres/emails/`

The following email template files are installed:
- `reservation_complete_customer_html.php` - HTML email for customer
- `reservation_complete_customer_html_inliner.php` - HTML inliner email for customer
- `reservation_complete_owner_html.php` - HTML email for owner
- `reservation_complete_owner_html_inliner.php` - HTML inliner email for owner
- `reservation_complete_customer_pdf.php` - PDF template for customer

For each file:
- Creates destination directory if it doesn't exist
- Creates backup if file exists with `.backup.YmdHis` format
- Copies new file to destination
- Displays success/failure message
- Continues installation even if one file fails (shows warning)

## Technical Details

### Backup Format

Backup files use the format: `original_file.php.backup.YmdHis`

Example: `reservation.php.backup.20260209120000`

### Messages

**Success:** `✅ [Filename] sikeresen telepítve`  
**Failure:** `⚠️ Nem sikerült a [filename] másolása!`

### Key Methods

#### `installReservationPhp($app)`
Installs the reservation.php template file with automatic backup.

#### `installEmailTemplates($app)`
Installs all email template files with automatic backups.

#### `copyFileWithBackup($src, $dest, $app, $filename)`
Helper method that:
- Creates backup if destination exists
- Copies file from source to destination
- Handles errors gracefully
- Provides user feedback

### Compatibility

- Joomla 3.x/4.x compatible
- Uses `Joomla\CMS\Filesystem\File` and `Folder` classes
- Follows Joomla coding standards

## Uninstallation

The `uninstall()` method **does not** automatically remove email templates or the reservation.php file. These must be manually deleted if needed, to prevent accidental data loss.

## Testing

Run the test script to validate the installation structure:

```bash
php test_installation.php
```

This will verify:
- All required files exist
- Backup filename format is correct
- All required methods are implemented
- Email templates are properly referenced

## Notes

- Installation continues even if individual file copies fail
- All file operations include error handling
- User receives clear feedback for each operation
- Target directories are created automatically if they don't exist
