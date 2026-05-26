<?php

/**
 * Fallback when document root is the Laravel project root (not public/).
 * Serves the app without relying on root .htaccess rewrite to /public/.
 */
require __DIR__ . '/public/index.php';
