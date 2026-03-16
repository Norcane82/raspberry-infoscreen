<?php
$clockAssetsDir = __DIR__ . '/clock_assets';
$configFile     = __DIR__ . '/config.json';

if (!is_dir($clockAssetsDir)) {
    mkdir($clockAssetsDir, 0775, true);
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

if (
    !isset($_FILES['clockLogoFile']) ||
    !is_uploaded_file($_FILES['clockLogoFile']['tmp_name'])
) {
    header('Location: admin.php');
    exit;
}

$name = $_FILES['clockLogoFile']['name'] ?? '';
$tmp  = $_FILES['clockLogoFile']['tmp_name'] ?? '';
$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed, true)) {
    header('Location: admin.php');
    exit;
}

$targetName = 'clock_logo_' . date('Ymd_His') . '.' . $ext;
$targetPath = $clockAssetsDir . '/' . $targetName;

if (!move_uploaded_file($tmp, $targetPath)) {
    header('Location: admin.php');
    exit;
}

$config = [];
if (file_exists($configFile)) {
    $raw = json_decode(file_get_contents($configFile), true);
    if (is_array($raw)) {
        $config = $raw;
    }
}

$config['clockLogo'] = $targetName;

file_put_contents(
    $configFile,
    json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

header('Location: admin.php');
exit;
