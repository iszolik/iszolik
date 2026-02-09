# Installation Flow Diagram

## Plugin Installation Process

```
┌─────────────────────────────────────────────┐
│   Plugin Installation Initiated              │
│   (install/update)                           │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│   postflight($type, $parent)                 │
└──────────────────┬──────────────────────────┘
                   │
                   ├─► if ($type === 'install')
                   │       enablePlugin($app)
                   │
                   ├─► removeDuplicateIndex($app)
                   │       └─► DROP INDEX payment_method_txn_id
                   │
                   ├─► installConfirmationForm($app)
                   │       └─► Copy confirmationform.php
                   │
                   ├─► ★ installReservationPhp($app)
                   │       ├─► Check/Create: libraries/solidres/reservation/
                   │       ├─► Backup existing: reservation.php.backup.YmdHis
                   │       └─► Copy: asset/reservation.php → libraries/solidres/reservation/reservation.php
                   │
                   ├─► ★ installEmailTemplates($app)
                   │       ├─► getDefaultTemplate() - Detect active template from DB
                   │       ├─► Check/Create: templates/[DETECTED-TEMPLATE]/html/layouts/com_solidres/emails/
                   │       └─► For each email template:
                   │           ├─► reservation_complete_customer_html.php
                   │           ├─► reservation_complete_customer_html_inliner.php
                   │           ├─► reservation_complete_owner_html.php
                   │           ├─► reservation_complete_owner_html_inliner.php
                   │           └─► reservation_complete_customer_pdf.php
                   │               ├─► Backup if exists: file.php.backup.YmdHis
                   │               └─► Copy: asset/emails/file.php → destination
                   │
                   └─► appendLanguageStrings($app)
                           └─► Update language files
```

## copyFileWithBackup Method Flow

```
┌─────────────────────────────────────────────┐
│   copyFileWithBackup($src, $dest, $app)      │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
         ┌─────────────────┐
         │ File exists?    │
         └────┬────────┬───┘
              │ Yes    │ No
              ▼        │
    ┌──────────────┐  │
    │ Create Backup│  │
    │ .backup.     │  │
    │  YmdHis      │  │
    └──────┬───────┘  │
           │          │
           └──────┬───┘
                  ▼
         ┌──────────────┐
         │  Copy File   │
         └──────┬───────┘
                │
         ┌──────┴───────┐
         │ Success?     │
         └──┬───────┬───┘
            │       │
     Yes ◄──┘       └──► No
         │               │
         ▼               ▼
    ┌─────────┐    ┌─────────┐
    │ Success │    │ Warning │
    │ Message │    │ Message │
    └─────────┘    └─────────┘
```

## Key Features

### ✅ Automatic Directory Creation
- Creates `libraries/solidres/reservation/` if it doesn't exist
- Creates `templates/greenery/html/layouts/com_solidres/emails/` if it doesn't exist

### ✅ Backup Protection
- All existing files are backed up before overwrite
- Backup format: `filename.php.backup.20260209120000`
- Timestamp ensures unique backup names

### ✅ Error Handling
- Installation continues even if one file fails
- Clear success/failure messages for each operation
- Warnings instead of fatal errors

### ✅ User Feedback
- `✅ [Filename] sikeresen telepítve` - Success
- `⚠️ Nem sikerült a [filename] másolása!` - Failure
- Informative messages throughout the process

## Files Installed

| Plugin  | Source                                      | Destination                                              |
|---------|---------------------------------------------|----------------------------------------------------------|
| Both    | asset/reservation.php                       | libraries/solidres/reservation/reservation.php           |
| Both    | asset/emails/*.php (5 files)                | templates/greenery/html/layouts/com_solidres/emails/     |

## Uninstallation Behavior

⚠️ **Important:** The `uninstall()` method does **NOT** automatically remove:
- reservation.php
- Email template files

This prevents accidental data loss. Files must be manually removed if needed.
