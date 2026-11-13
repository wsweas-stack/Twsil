<?php
// Temporary cache clear script - DELETE AFTER USE
$viewsPath = __DIR__ . '/../storage/framework/views';
$files = glob($viewsPath . '/*.php');
$count = 0;
foreach ($files as $file) {
    if (unlink($file)) $count++;
}
echo "Cleared $count compiled view files. Refresh your browser now!";
