<?php
/**
 * Manual Storage Link tool for Shared Hosting
 * Run this once via: https://yourdomain.com/link_storage.php
 */

$target = __DIR__ . '/../storage/app/public';
$link = __DIR__ . '/storage';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Laravel Storage Link Tool</h2>";

if (file_exists($link)) {
    echo "<p>Found existing storage path. Attempting to clear it...</p>";
    if (is_link($link)) {
        unlink($link);
        echo "<p>Old symlink removed.</p>";
    } else {
        $backupName = $link . '_backup_' . time();
        if (rename($link, $backupName)) {
            echo "<p>Existing folder moved to: $backupName</p>";
        } else {
            die("<p style='color:red'>Error: Could not move existing storage folder. Please delete 'public/storage' manually.</p>");
        }
    }
}

if (symlink($target, $link)) {
    echo "<p style='color:green; font-weight:bold;'>Success! Storage link created successfully.</p>";
    echo "<p>Now your images should appear correctly.</p>";
    echo "<p style='color:orange'>Please DELETE this file (link_storage.php) from your server for security.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>Failed to create symlink.</p>";
    echo "<p>Check if your hosting allows 'symlink' function. If not, you may need to move your storage files manually.</p>";
}
