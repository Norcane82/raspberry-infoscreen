<?php
$configFile = __DIR__ . '/config.json';

$defaultConfig = [
    'bg' => '#ffffff',
    'clockColor' => '#111111',
    'clockLogo' => 'logo.png',
    'clockTitle' => 'Caritas',
    'clockSubtitle' => 'Herzlich Willkommen'
];

$config = $defaultConfig;
if (file_exists($configFile)) {
    $raw = json_decode(file_get_contents($configFile), true);
    if (is_array($raw)) {
        $config = array_merge($config, $raw);
    }
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$logoPath = __DIR__ . '/' . $config['clockLogo'];
$logoWeb  = file_exists($logoPath) ? rawurlencode($config['clockLogo']) : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Infoscreen Uhr</title>
<style>
html, body {
    margin: 0;
    width: 100%;
    height: 100%;
    background: <?= h($config['bg']) ?>;
    overflow: hidden;
    font-family: Arial, Helvetica, sans-serif;
    color: <?= h($config['clockColor']) ?>;
}

.page {
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 28px;
    text-align: center;
    box-sizing: border-box;
    padding: 40px;
}

.logo img {
    max-width: 420px;
    max-height: 140px;
    object-fit: contain;
}

.title {
    font-size: 2.4vw;
    font-weight: 700;
}

.subtitle {
    font-size: 1.3vw;
    opacity: 0.8;
    margin-top: -10px;
}

.time {
    font-size: 6vw;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.04em;
    margin-top: 10px;
}

.date {
    font-size: 2vw;
    opacity: 0.85;
}

@media (max-width: 900px) {
    .title { font-size: 8vw; }
    .subtitle { font-size: 4vw; }
    .time { font-size: 16vw; }
    .date { font-size: 5vw; }
    .logo img {
        max-width: 70vw;
        max-height: 18vh;
    }
}
</style>
</head>
<body>
<div class="page">
    <?php if ($logoWeb !== ''): ?>
    <div class="logo">
        <img src="logo.png" alt="Caritas Logo">
    </div>
    <?php endif; ?>

    <div class="subtitle"><?= h($config['clockSubtitle']) ?></div>

    <div class="time" id="clockTime"></div>
    <div class="date" id="clockDate"></div>
</div>

<script>
function updateClock() {
    const now = new Date();

    const time = now.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    const date = now.toLocaleDateString('de-DE', {
        weekday: 'long',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    document.getElementById('clockTime').textContent = time;
    document.getElementById('clockDate').textContent = date;
}

updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>
