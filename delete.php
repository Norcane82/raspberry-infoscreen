<?php
$baseDir    = __DIR__;
$uploadDir  = $baseDir . '/uploads';
$orderFile  = $baseDir . '/order.json';

$file = $_GET['file'] ?? '';
$file = basename($file);

if ($file !== '') {
    $path = $uploadDir . '/' . $file;
    if (is_file($path)) {
        unlink($path);
    }

    if (file_exists($orderFile)) {
        $order = json_decode(file_get_contents($orderFile), true);
        if (is_array($order)) {
            $order = array_values(array_filter($order, function ($item) use ($file) {
                return $item !== $file;
            }));
            file_put_contents($orderFile, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}

header('Location: admin.php');
exit;
