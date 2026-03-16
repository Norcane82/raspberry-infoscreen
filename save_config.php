<?php
$configFile = __DIR__ . '/config.json';

$existing = [];
if (file_exists($configFile)) {
    $raw = json_decode(file_get_contents($configFile), true);
    if (is_array($raw)) {
        $existing = $raw;
    }
}

function post_bool(string $key, bool $default = false): bool {
    if (!isset($_POST[$key])) return $default;
    return $_POST[$key] === '1';
}

function post_string(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function clamp_float($value, float $min, float $max, float $default): float {
    if (!is_numeric($value)) return $default;
    $v = (float)$value;
    if ($v < $min) $v = $min;
    if ($v > $max) $v = $max;
    return $v;
}

function clamp_int($value, int $min, int $max, int $default): int {
    if (!is_numeric($value)) return $default;
    $v = (int)$value;
    if ($v < $min) $v = $min;
    if ($v > $max) $v = $max;
    return $v;
}

$fit = post_string('fit', 'contain');
if (!in_array($fit, ['contain', 'cover', 'fill'], true)) {
    $fit = 'contain';
}

$clockMode = post_string('clockMode', 'slide');
if (!in_array($clockMode, ['slide', 'overlay'], true)) {
    $clockMode = 'slide';
}

$clockPosition = post_string('clockPosition', 'top-right');
if (!in_array($clockPosition, ['top-right', 'top-left', 'bottom-right', 'bottom-left'], true)) {
    $clockPosition = 'top-right';
}

$clockDateLocale = post_string('clockDateLocale', 'de-AT');
if (!in_array($clockDateLocale, ['de-AT', 'de-DE', 'en-GB'], true)) {
    $clockDateLocale = 'de-AT';
}

$config = $existing;
$config['fadeSeconds']      = clamp_float($_POST['fadeSeconds'] ?? 1.5, 0.1, 10, 1.5);
$config['showSeconds']      = clamp_int($_POST['showSeconds'] ?? 6, 1, 300, 6);
$config['fit']              = $fit;
$config['bg']               = post_string('bg', '#000000');

$config['clockEnabled']     = post_bool('clockEnabled', true);
$config['clockMode']        = $clockMode;
$config['clockSeconds']     = post_bool('clockSeconds', true);
$config['clockShowDate']    = post_bool('clockShowDate', true);
$config['clockShowWeekday'] = post_bool('clockShowWeekday', true);
$config['clockPosition']    = $clockPosition;
$config['clockBg']          = post_string('clockBg', 'rgba(0,0,0,0.35)');
$config['clockColor']       = post_string('clockColor', '#ffffff');
$config['clockTitle']       = post_string('clockTitle', '');
$config['clockDateLocale']  = $clockDateLocale;
$config['clockLogo']        = basename(post_string('clockLogo', ''));

/* Kompatibilität */
$config['clockSlideEnabled'] = $config['clockEnabled'];

file_put_contents(
    $configFile,
    json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

header('Location: admin.php');
exit;
