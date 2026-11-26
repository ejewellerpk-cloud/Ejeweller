<?php
/**
 * DISABLED — this public storage-link utility was an unauthenticated filesystem risk.
 * Create the storage link via SSH instead:
 *   php artisan storage:link
 */
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "This tool has been disabled. Use: php artisan storage:link";
exit;
