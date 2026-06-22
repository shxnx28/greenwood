<?php
/**
 * Database credentials — TEMPLATE.
 *
 * 1. Copy this file to ONE directory ABOVE your web root (e.g. the folder that
 *    CONTAINS public_html), NOT inside the public site, and rename it to:
 *        gw-db-config.php
 *    Example final path on Hostinger:  /home/uXXXXXXX/gw-db-config.php
 *
 * 2. Fill in your real values below.
 *
 * 3. Remove the old "SetEnv DB_*" lines from .htaccess.
 *
 * admin/db.php loads this automatically. Never commit the real file to git
 * (the example is fine; the real gw-db-config.php must stay out of the repo).
 */
return [
    'host' => 'localhost',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'name' => 'your_db_name',
];
