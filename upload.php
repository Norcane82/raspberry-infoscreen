<?php
$baseDir    = __DIR__;
$uploadDir  = $baseDir . '/uploads';
$slidesFile = $baseDir . '/slides.json';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

function load_image_by_type(string $tmp, string $ext) {
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            return @imagecreatefromjpeg($tmp);
        case 'png':
            return @imagecreatefrompng($tmp);
        case 'gif':
            return @imagecreatefromgif($tmp);
        case 'webp':
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false;
        case 'bmp':
            return function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($tmp) : false;
        default:
            return false;
    }
}

function save_as_jpeg($img, string $target, int $quality = 85): bool {
    imageinterlace($img, true);
    return imagejpeg($img, $target, $quality);
}

$slides = [];
if (file_exists($slidesFile)) {
    $raw = json_decode(file_get_contents($slidesFile), true);
    if (is_array($raw)) {
        $slides = $raw;
    }
}

if (!isset($_FILES['images'])) {
    header('Location: admin.php');
    exit;
}

$names = $_FILES['images']['name'] ?? [];
$tmps  = $_FILES['images']['tmp_name'] ?? [];
$errs  = $_FILES['images']['error'] ?? [];

for ($i = 0; $i < count($names); $i++) {
    if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
    if (!is_uploaded_file($tmps[$i])) continue;

    $origName = $names[$i];
    $tmp      = $tmps[$i];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) continue;

    $src = load_image_by_type($tmp, $ext);
    if (!$src) continue;

    $width  = imagesx($src);
    $height = imagesy($src);

    $maxW = 1920;
    $maxH = 1080;

    $scale = min($maxW / max(1, $width), $maxH / max(1, $height), 1);
    $newW = max(1, (int)round($width * $scale));
    $newH = max(1, (int)round($height * $scale));

    $dst = imagecreatetruecolor($newW, $newH);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

    $base = pathinfo($origName, PATHINFO_FILENAME);
    $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
    $base = trim((string)$base, '_');
    if ($base === '') $base = 'bild';

    $targetName = time() . '_' . $base . '.jpg';
    $targetPath = $uploadDir . '/' . $targetName;

    if (save_as_jpeg($dst, $targetPath, 85)) {
        $slides[] = [
            'type' => 'image',
            'value' => $targetName
        ];
    }

    imagedestroy($src);
    imagedestroy($dst);
}

file_put_contents(
    $slidesFile,
    json_encode($slides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

header('Location: admin.php');
exit;
