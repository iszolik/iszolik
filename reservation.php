<?php
// Suppress Imagick version mismatch warning
// The warning "Imagick was compiled against ImageMagick version X but version Y is loaded"
// occurs during PHP module initialization and cannot be caught by error handlers.
// This solution suppresses warnings while keeping errors and notices visible.
// For production environments, consider configuring error_reporting in php.ini instead.
error_reporting(E_ALL & ~E_WARNING);
?>
Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted): 2026-01-25 16:09:42
Current User's Login: iszolik