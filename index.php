<?php
$baseDir        = __DIR__;
$uploadDir      = $baseDir . '/uploads';
$clockAssetsDir = $baseDir . '/clock_assets';
$configFile     = $baseDir . '/config.json';
$slidesFile     = $baseDir . '/slides.json';

$defaultConfig = [
    'fadeSeconds'      => 1.5,
    'showSeconds'      => 6,
    'fit'              => 'contain',
    'bg'               => '#000000',

    'clockEnabled'     => true,
    'clockMode'        => 'slide',
    'clockSeconds'     => true,
    'clockShowDate'    => true,
    'clockShowWeekday' => true,
    'clockPosition'    => 'top-right',
    'clockBg'          => 'rgba(0,0,0,0.35)',
    'clockColor'       => '#ffffff',
    'clockTitle'       => '',
    'clockDateLocale'  => 'de-AT',
    'clockLogo'        => ''
];

$config = $defaultConfig;
if (file_exists($configFile)) {
    $raw = json_decode(file_get_contents($configFile), true);
    if (is_array($raw)) {
        $config = array_merge($config, $raw);
    }
}

if (!isset($config['clockEnabled']) && isset($config['clockSlideEnabled'])) {
    $config['clockEnabled'] = !empty($config['clockSlideEnabled']);
}

if (empty($config['clockMode']) || !in_array($config['clockMode'], ['slide', 'overlay'], true)) {
    $config['clockMode'] = 'slide';
}

$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

$slides = [];
if (file_exists($slidesFile)) {
    $rawSlides = json_decode(file_get_contents($slidesFile), true);
    if (is_array($rawSlides)) {
        $slides = $rawSlides;
    }
}

$validSlides = [];
foreach ($slides as $slide) {
    if (!is_array($slide)) continue;

    $type  = $slide['type'] ?? '';
    $value = $slide['value'] ?? '';

    if ($type === 'image') {
        $value = basename((string)$value);
        if ($value === '') continue;
        $path = $uploadDir . '/' . $value;
        if (is_file($path)) {
            $validSlides[] = [
                'type'  => 'image',
                'value' => $value
            ];
        }
    } elseif ($type === 'url') {
        $value = trim((string)$value);
        if ($value !== '' && preg_match('~^https?://~i', $value)) {
            $validSlides[] = [
                'type'  => 'url',
                'value' => $value
            ];
        }
    }
}

$slides = $validSlides;

$clockEnabled = !empty($config['clockEnabled']);
$clockMode    = $config['clockMode'];

$clockLogo = basename((string)$config['clockLogo']);
$clockLogoPath = '';
if ($clockLogo !== '') {
    $candidate = $clockAssetsDir . '/' . $clockLogo;
    if (is_file($candidate)) {
        $clockLogoPath = 'clock_assets/' . rawurlencode($clockLogo);
    }
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Infoscreen</title>
<style>
html, body {
    margin: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: <?= h($config['bg']) ?>;
    font-family: Arial, Helvetica, sans-serif;
}

#stage {
    position: relative;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: <?= h($config['bg']) ?>;
}

.slide {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity <?= (float)$config['fadeSeconds'] ?>s ease-in-out;
    pointer-events: none;
    border: 0;
    background: <?= h($config['bg']) ?>;
}

.slide.active {
    opacity: 1;
    pointer-events: auto;
}

img.slide {
    object-fit: <?= h($config['fit']) ?>;
    user-select: none;
    -webkit-user-drag: none;
}

iframe.slide {
    border: 0;
    width: 100%;
    height: 100%;
    background: #fff;
}

.clock-slide-wrap {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: <?= h($config['bg']) ?>;
}
.clock-box {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 16px 22px;
    border-radius: 18px;
    background: <?= h($config['clockBg']) ?>;
    color: <?= h($config['clockColor']) ?>;
    box-sizing: border-box;
    max-width: calc(100vw - 60px);
}

.clock-logo {
    display: block;
    max-height: 72px;
    max-width: 220px;
    width: auto;
    height: auto;
    object-fit: contain;
    flex: 0 0 auto;
}

.clock-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.clock-title {
    font-size: 18px;
    line-height: 1.2;
    font-weight: 700;
    margin-bottom: 8px;
    opacity: 0.96;
    word-break: break-word;
}

.clock-time {
    font-size: clamp(42px, 7vw, 88px);
    line-height: 1;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.clock-date {
    font-size: clamp(18px, 2vw, 28px);
    line-height: 1.2;
    margin-top: 10px;
    opacity: 0.95;
    word-break: break-word;
}

#clockOverlay {
    position: absolute;
    z-index: 30;
    pointer-events: none;
}

#clockOverlay.top-right {
    top: 20px;
    right: 20px;
}
#clockOverlay.top-left {
    top: 20px;
    left: 20px;
}
#clockOverlay.bottom-right {
    bottom: 20px;
    right: 20px;
}
#clockOverlay.bottom-left {
    bottom: 20px;
    left: 20px;
}

#empty {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ddd;
    font-size: 3vw;
    text-align: center;
    padding: 2rem;
}
</style>
</head>
<body>
<div id="stage">
    <?php $hasSlides = count($slides) > 0 || ($clockEnabled && $clockMode === 'slide'); ?>

    <?php if (!$hasSlides && !$clockEnabled): ?>
        <div id="empty">Keine Inhalte vorhanden.</div>
    <?php else: ?>

        <?php foreach ($slides as $i => $slide): ?>
            <?php if ($slide['type'] === 'image'): ?>
                <img
                    class="slide<?= $i === 0 ? ' active' : '' ?>"
                    src="uploads/<?= rawurlencode($slide['value']) ?>"
                    alt="Bild"
                    loading="eager"
                    decoding="async"
                    draggable="false"
                >
            <?php elseif ($slide['type'] === 'url'): ?>
                <iframe
                    class="slide<?= $i === 0 ? ' active' : '' ?>"
                    src="<?= h($slide['value']) ?>"
                    title="Webseite"
                    loading="eager"
                    referrerpolicy="no-referrer"
                ></iframe>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($clockEnabled && $clockMode === 'slide'): ?>
            <div class="slide<?= count($slides) === 0 ? ' active' : '' ?>" data-kind="clock-slide">
                <div class="clock-slide-wrap">
                    <div class="clock-box">
                        <?php if ($clockLogoPath !== ''): ?>
                            <img class="clock-logo" src="<?= h($clockLogoPath) ?>" alt="Logo">
                        <?php endif; ?>
                        <div class="clock-text">
                            <?php if (trim((string)$config['clockTitle']) !== ''): ?>
                                <div class="clock-title"><?= h($config['clockTitle']) ?></div>
                            <?php endif; ?>
                            <div class="clock-time" id="clockTimeSlide">--:--</div>
                            <div class="clock-date" id="clockDateSlide"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($clockEnabled && $clockMode === 'overlay'): ?>
            <div id="clockOverlay" class="<?= h($config['clockPosition']) ?>">
                <div class="clock-box">
                    <?php if ($clockLogoPath !== ''): ?>
                        <img class="clock-logo" src="<?= h($clockLogoPath) ?>" alt="Logo">
                    <?php endif; ?>
                    <div class="clock-text">
                        <?php if (trim((string)$config['clockTitle']) !== ''): ?>
                            <div class="clock-title"><?= h($config['clockTitle']) ?></div>
                        <?php endif; ?>
                        <div class="clock-time" id="clockTimeOverlay">--:--</div>
                        <div class="clock-date" id="clockDateOverlay"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
<script>
const slides = Array.from(document.querySelectorAll('.slide'));
const showMs = <?= max(1, (int)$config['showSeconds']) ?> * 1000;
const showClock = <?= $clockEnabled ? 'true' : 'false' ?>;
const showSeconds = <?= !empty($config['clockSeconds']) ? 'true' : 'false' ?>;
const showDate = <?= !empty($config['clockShowDate']) ? 'true' : 'false' ?>;
const showWeekday = <?= !empty($config['clockShowWeekday']) ? 'true' : 'false' ?>;
const locale = <?= json_encode((string)$config['clockDateLocale'], JSON_UNESCAPED_UNICODE) ?>;

let current = 0;
let slideTimer = null;

function preloadImages() {
    slides.forEach(el => {
        if (el.tagName === 'IMG') {
            const pre = new Image();
            pre.src = el.src;
        }
    });
}

function startSlideshow() {
    if (slides.length <= 1) return;
    if (slideTimer) clearInterval(slideTimer);

    slideTimer = setInterval(() => {
        const prev = slides[current];
        current = (current + 1) % slides.length;
        const next = slides[current];

        prev.classList.remove('active');
        next.classList.add('active');
    }, showMs);
}

function pad(n) {
    return String(n).padStart(2, '0');
}

function renderClock() {
    if (!showClock) return;

    const now = new Date();

    const timeText = showSeconds
        ? `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`
        : `${pad(now.getHours())}:${pad(now.getMinutes())}`;

    let dateText = '';
    if (showDate) {
        const parts = [];
        if (showWeekday) {
            parts.push(now.toLocaleDateString(locale, { weekday: 'long' }));
        }
        parts.push(
            now.toLocaleDateString(locale, {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            })
        );
        dateText = parts.join(' · ');
    }

    const timeEls = [
        document.getElementById('clockTimeSlide'),
        document.getElementById('clockTimeOverlay')
    ].filter(Boolean);

    const dateEls = [
        document.getElementById('clockDateSlide'),
        document.getElementById('clockDateOverlay')
    ].filter(Boolean);

    timeEls.forEach(el => el.textContent = timeText);
    dateEls.forEach(el => {
        el.textContent = dateText;
        el.style.display = showDate ? '' : 'none';
    });
}

function scheduleClock() {
    if (!showClock) return;

    renderClock();

    if (showSeconds) {
        setInterval(renderClock, 1000);
    } else {
        const now = new Date();
        const delay = ((60 - now.getSeconds()) * 1000) - now.getMilliseconds();

        setTimeout(() => {
            renderClock();
            setInterval(renderClock, 60000);
        }, delay);
    }
}

preloadImages();
startSlideshow();
scheduleClock();
</script>
</body>
</html>
