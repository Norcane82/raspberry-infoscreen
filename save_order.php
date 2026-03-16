<?php
$orderFile = __DIR__ . '/order.json';
$orderRaw = $_POST['order'] ?? '[]';
$order = json_decode($orderRaw, true);

if (!is_array($order)) {
    $order = [];
}

$clean = [];
foreach ($order as $item) {
    $item = basename((string)$item);
    if ($item !== '') {
        $clean[] = $item;
    }
}

file_put_contents($orderFile, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

header('Location: admin.php');
exit;
