<?php
$slidesFile = __DIR__ . '/slides.json';

$raw = $_POST['slides'] ?? '[]';
$data = json_decode($raw, true);

if (!is_array($data)) {
    $data = [];
}

$clean = [];

foreach ($data as $item) {
    if (!is_array($item)) continue;

    $type = $item['type'] ?? '';
    $value = $item['value'] ?? '';

    if ($type === 'image') {
        $value = basename((string)$value);
        if ($value !== '') {
            $clean[] = [
                'type' => 'image',
                'value' => $value
            ];
        }
    } elseif ($type === 'url') {
        $value = trim((string)$value);
        if ($value !== '' && preg_match('~^https?://~i', $value)) {
            $clean[] = [
                'type' => 'url',
                'value' => $value
            ];
        }
    }
}

file_put_contents(
    $slidesFile,
    json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

header('Location: admin.php');
exit;
