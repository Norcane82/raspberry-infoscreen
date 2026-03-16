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

$images = [];
if (is_dir($uploadDir)) {
    foreach (scandir($uploadDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $uploadDir . '/' . $file;
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExt, true)) {
            $images[] = $file;
        }
    }
}

$slides = [];
if (file_exists($slidesFile)) {
    $rawSlides = json_decode(file_get_contents($slidesFile), true);
    if (is_array($rawSlides)) {
        $slides = $rawSlides;
    }
}

/* Alte Bilder automatisch in slides.json übernehmen, falls noch leer */
if (empty($slides) && !empty($images)) {
    foreach ($images as $img) {
        $slides[] = [
            'type'  => 'image',
            'value' => $img
        ];
    }
}

$validSlides = [];
$usedImages = [];

foreach ($slides as $slide) {
    if (!is_array($slide)) continue;

    $type = $slide['type'] ?? '';
    $value = $slide['value'] ?? '';

    if ($type === 'image') {
        $value = basename((string)$value);
        if ($value !== '' && in_array($value, $images, true)) {
            $validSlides[] = ['type' => 'image', 'value' => $value];
            $usedImages[] = $value;
        }
    } elseif ($type === 'url') {
        $value = trim((string)$value);
        if ($value !== '') {
            $validSlides[] = ['type' => 'url', 'value' => $value];
        }
    }
}

/* Nicht einsortierte Bilder hinten anhängen */
foreach ($images as $img) {
    if (!in_array($img, $usedImages, true)) {
        $validSlides[] = ['type' => 'image', 'value' => $img];
    }
}

$slides = $validSlides;

$clockLogoOptions = [];
if (is_dir($clockAssetsDir)) {
    foreach (scandir($clockAssetsDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $clockAssetsDir . '/' . $file;
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExt, true)) {
            $clockLogoOptions[] = $file;
        }
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
<title>Infoscreen Verwaltung V2</title>
<style>
body {
    margin: 0;
    background: #f4f6f8;
    color: #222;
    font-family: Arial, Helvetica, sans-serif;
}
.wrap {
    max-width: 1280px;
    margin: 0 auto;
    padding: 24px;
}
h1, h2, h3 {
    margin-top: 0;
}
.card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 22px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.field {
    flex: 1 1 220px;
}
label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}
input[type="number"],
input[type="text"],
input[type="url"],
select,
input[type="color"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccd3db;
    border-radius: 10px;
    box-sizing: border-box;
}
input[type="file"] {
    width: 100%;
}
button {
    border: 0;
    border-radius: 10px;
    padding: 10px 16px;
    cursor: pointer;
    font-weight: bold;
}
.btn-primary { background: #2563eb; color: #fff; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-secondary { background: #374151; color: #fff; }
.btn-light { background: #e5e7eb; color: #111827; }

.toplinks {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.toplinks a {
    text-decoration: none;
    color: #2563eb;
    font-weight: bold;
}
.small {
    font-size: 13px;
    color: #555;
}
.slides-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.slide-item {
    border: 2px solid #dde4ea;
    border-radius: 16px;
    padding: 14px;
    background: #fff;
}
.slide-item.dragging {
    opacity: 0.5;
}
.slide-top {
    display: flex;
    gap: 16px;
    align-items: center;
}
.slide-thumb {
    width: 180px;
    height: 110px;
    object-fit: contain;
    background: #111;
    border-radius: 10px;
    flex: 0 0 auto;
}
.slide-meta {
    flex: 1 1 auto;
    min-width: 0;
}
.slide-type {
    display: inline-block;
    font-size: 12px;
    font-weight: bold;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 999px;
    padding: 4px 10px;
    margin-bottom: 8px;
}
.slide-value {
    font-size: 15px;
    word-break: break-word;
}
.slide-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
}
.preview-box {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 14px;
    background: <?= h($config['clockBg']) ?>;
    color: <?= h($config['clockColor']) ?>;
    margin-top: 12px;
}
.preview-logo {
    max-height: 56px;
    max-width: 180px;
    object-fit: contain;
}
.preview-time {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
}
.preview-date {
    margin-top: 6px;
    font-size: 16px;
}
.codebox {
    background: #111827;
    color: #f9fafb;
    border-radius: 12px;
    padding: 12px;
    font-family: monospace;
    font-size: 13px;
    overflow-x: auto;
}
</style>
</head>
<body>
<div class="wrap">
    <div class="toplinks">
        <a href="/infoscreen/" target="_blank">Infoscreen öffnen</a>
        <a href="/infoscreen/admin.php">Verwaltung neu laden</a>
    </div>

    <h1>Infoscreen Verwaltung V2</h1>

    <div class="card">
        <h2>Bilder hochladen</h2>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <input type="file" name="images[]" accept="image/*" multiple required>
            <div style="margin-top:12px;">
                <button class="btn-primary" type="submit">Bilder hochladen und optimieren</button>
            </div>
        </form>
        <div class="small" style="margin-top:10px;">
            Bilder werden beim Upload automatisch verkleinert, damit der Pi weniger RAM und CPU braucht.
        </div>
    </div>

    <div class="card">
        <h2>Webseite als Slide hinzufügen</h2>
        <form action="save_slides.php" method="post" onsubmit="return addUrlBeforeSubmit();">
            <div class="row">
                <div class="field" style="flex: 1 1 500px;">
                    <label for="newUrl">HTTP-Adresse</label>
                    <input type="url" id="newUrl" placeholder="https://example.org">
                </div>
            </div>

            <input type="hidden" name="slides" id="slidesJsonUrl" value='<?= h(json_encode($slides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>

            <div style="margin-top:12px;">
                <button class="btn-primary" type="submit">Webseite hinzufügen</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Uhr</h2>
        <form action="upload_clock_logo.php" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="field">
                    <label for="clockLogoFile">Uhr-Logo hochladen</label>
                    <input type="file" id="clockLogoFile" name="clockLogoFile" accept="image/*" required>
                </div>
            </div>
            <div style="margin-top:12px;">
                <button class="btn-secondary" type="submit">Uhr-Logo hochladen</button>
            </div>
        </form>

        <hr style="margin:24px 0;border:none;border-top:1px solid #e5e7eb;">

        <form action="save_config.php" method="post">
            <div class="row">
                <div class="field">
                    <label for="fadeSeconds">Fade-Dauer (Sekunden)</label>
                    <input type="number" step="0.1" min="0.1" max="10" id="fadeSeconds" name="fadeSeconds" value="<?= h($config['fadeSeconds']) ?>">
                </div>

                <div class="field">
                    <label for="showSeconds">Anzeigedauer pro Bild (Sekunden)</label>
                    <input type="number" step="1" min="1" max="300" id="showSeconds" name="showSeconds" value="<?= h($config['showSeconds']) ?>">
                </div>

                <div class="field">
                    <label for="fit">Bild-Anpassung</label>
                    <select id="fit" name="fit">
                        <option value="contain" <?= $config['fit'] === 'contain' ? 'selected' : '' ?>>contain</option>
                        <option value="cover" <?= $config['fit'] === 'cover' ? 'selected' : '' ?>>cover</option>
                        <option value="fill" <?= $config['fit'] === 'fill' ? 'selected' : '' ?>>fill</option>
                    </select>
                </div>

                <div class="field">
                    <label for="bg">Hintergrundfarbe</label>
                    <input type="color" id="bg" name="bg" value="<?= h($config['bg']) ?>">
                </div>
            </div>

            <div class="row" style="margin-top:16px;">
                <div class="field">
                    <label for="clockEnabled">Uhr anzeigen</label>
                    <select id="clockEnabled" name="clockEnabled">
                        <option value="1" <?= !empty($config['clockEnabled']) ? 'selected' : '' ?>>Ja</option>
                        <option value="0" <?= empty($config['clockEnabled']) ? 'selected' : '' ?>>Nein</option>
                    </select>
                </div>

                <div class="field">
                    <label for="clockMode">Uhr-Modus</label>
                    <select id="clockMode" name="clockMode">
                        <option value="slide" <?= $config['clockMode'] === 'slide' ? 'selected' : '' ?>>Als Folie</option>
                        <option value="overlay" <?= $config['clockMode'] === 'overlay' ? 'selected' : '' ?>>Als Overlay</option>
                    </select>
                </div>

                <div class="field">
                    <label for="clockSeconds">Sekunden anzeigen</label>
                    <select id="clockSeconds" name="clockSeconds">
                        <option value="1" <?= !empty($config['clockSeconds']) ? 'selected' : '' ?>>Ja</option>
                        <option value="0" <?= empty($config['clockSeconds']) ? 'selected' : '' ?>>Nein</option>
                    </select>
                </div>

                <div class="field">
                    <label for="clockPosition">Position im Overlay-Modus</label>
                    <select id="clockPosition" name="clockPosition">
                        <option value="top-right" <?= $config['clockPosition'] === 'top-right' ? 'selected' : '' ?>>Oben rechts</option>
                        <option value="top-left" <?= $config['clockPosition'] === 'top-left' ? 'selected' : '' ?>>Oben links</option>
                        <option value="bottom-right" <?= $config['clockPosition'] === 'bottom-right' ? 'selected' : '' ?>>Unten rechts</option>
                        <option value="bottom-left" <?= $config['clockPosition'] === 'bottom-left' ? 'selected' : '' ?>>Unten links</option>
                    </select>
                </div>
            </div>
            <div class="row" style="margin-top:16px;">
                <div class="field">
                    <label for="clockShowDate">Datum anzeigen</label>
                    <select id="clockShowDate" name="clockShowDate">
                        <option value="1" <?= !empty($config['clockShowDate']) ? 'selected' : '' ?>>Ja</option>
                        <option value="0" <?= empty($config['clockShowDate']) ? 'selected' : '' ?>>Nein</option>
                    </select>
                </div>

                <div class="field">
                    <label for="clockShowWeekday">Wochentag anzeigen</label>
                    <select id="clockShowWeekday" name="clockShowWeekday">
                        <option value="1" <?= !empty($config['clockShowWeekday']) ? 'selected' : '' ?>>Ja</option>
                        <option value="0" <?= empty($config['clockShowWeekday']) ? 'selected' : '' ?>>Nein</option>
                    </select>
                </div>

                <div class="field">
                    <label for="clockColor">Uhrfarbe</label>
                    <input type="color" id="clockColor" name="clockColor" value="<?= h($config['clockColor']) ?>">
                </div>

                <div class="field">
                    <label for="clockDateLocale">Sprachraum</label>
                    <select id="clockDateLocale" name="clockDateLocale">
                        <option value="de-AT" <?= $config['clockDateLocale'] === 'de-AT' ? 'selected' : '' ?>>Deutsch (AT)</option>
                        <option value="de-DE" <?= $config['clockDateLocale'] === 'de-DE' ? 'selected' : '' ?>>Deutsch (DE)</option>
                        <option value="en-GB" <?= $config['clockDateLocale'] === 'en-GB' ? 'selected' : '' ?>>English (UK)</option>
                    </select>
                </div>
            </div>

            <div class="row" style="margin-top:16px;">
                <div class="field">
                    <label for="clockBg">Uhr-Hintergrund (CSS)</label>
                    <input type="text" id="clockBg" name="clockBg" value="<?= h($config['clockBg']) ?>">
                </div>

                <div class="field">
                    <label for="clockTitle">Zusatztext über der Uhr</label>
                    <input type="text" id="clockTitle" name="clockTitle" value="<?= h($config['clockTitle']) ?>" placeholder="z. B. Caritas St. Pölten">
                </div>

                <div class="field">
                    <label for="clockLogo">Uhr-Logo</label>
                    <select id="clockLogo" name="clockLogo">
                        <option value="">Kein Logo</option>
                        <?php foreach ($clockLogoOptions as $file): ?>
                            <option value="<?= h($file) ?>" <?= $config['clockLogo'] === $file ? 'selected' : '' ?>><?= h($file) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-top:16px;">
                <button class="btn-primary" type="submit">Einstellungen speichern</button>
            </div>
        </form>

        <div class="preview-box">
            <?php if (!empty($config['clockLogo'])): ?>
                <img class="preview-logo" src="clock_assets/<?= rawurlencode($config['clockLogo']) ?>" alt="Logo">
            <?php endif; ?>
            <div>
                <?php if (trim((string)$config['clockTitle']) !== ''): ?>
                    <div style="font-size:15px;font-weight:700;opacity:0.95;margin-bottom:6px;"><?= h($config['clockTitle']) ?></div>
                <?php endif; ?>
                <div class="preview-time">12:34<?= !empty($config['clockSeconds']) ? ':56' : '' ?></div>
                <?php if (!empty($config['clockShowDate'])): ?>
                    <div class="preview-date">
                        <?= !empty($config['clockShowWeekday']) ? 'Freitag · ' : '' ?>13.03.2026
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Slides verwalten</h2>
        <p class="small">Bilder und Webseiten lassen sich gemeinsam sortieren. Zusätzlich gibt es Hoch/Runter-Buttons für stabile Bedienung.</p>

        <form id="slidesForm" action="save_slides.php" method="post">
            <input type="hidden" name="slides" id="slidesJson" value='<?= h(json_encode($slides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>

            <div id="slidesList" class="slides-list"></div>

            <div style="margin-top:18px;">
                <button class="btn-primary" type="submit">Slides speichern</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Format von Webseiten</h3>
        <div class="codebox">https://example.org</div>
    </div>
</div>

<script>
let slides = <?= json_encode($slides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderSlides() {
    const list = document.getElementById('slidesList');
    const hidden = document.getElementById('slidesJson');
    hidden.value = JSON.stringify(slides);

    if (!list) return;
    list.innerHTML = '';

    slides.forEach((slide, index) => {
        const item = document.createElement('div');
        item.className = 'slide-item';
        item.draggable = true;
        item.dataset.index = index;

        let preview = '';
        let typeLabel = '';
        let valueText = '';

        if (slide.type === 'image') {
            preview = `<img class="slide-thumb" src="uploads/${encodeURIComponent(slide.value)}" alt="">`;
            typeLabel = 'Bild';
            valueText = escapeHtml(slide.value);
        } else {
            preview = `<div class="slide-thumb" style="display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;">URL</div>`;
            typeLabel = 'Webseite';
            valueText = escapeHtml(slide.value);
        }

        item.innerHTML = `
            <div class="slide-top">
                ${preview}
                <div class="slide-meta">
                    <div class="slide-type">${typeLabel}</div>
                    <div class="slide-value">${valueText}</div>
                    <div class="slide-actions">
                        <button type="button" class="btn-light" onclick="moveSlide(${index}, -1)">Hoch</button>
                        <button type="button" class="btn-light" onclick="moveSlide(${index}, 1)">Runter</button>
                        <button type="button" class="btn-danger" onclick="removeSlide(${index})">Entfernen</button>
                    </div>
                </div>
            </div>
        `;

        item.addEventListener('dragstart', () => {
            item.classList.add('dragging');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
        });

        item.addEventListener('dragover', e => {
            e.preventDefault();
            const from = Number(document.querySelector('.slide-item.dragging')?.dataset.index);
            const to = index;
            if (Number.isNaN(from) || from === to) return;
        });

        list.appendChild(item);
    });

    enableDnD();
}

function enableDnD() {
    const items = Array.from(document.querySelectorAll('.slide-item'));
    let draggedIndex = null;

    items.forEach(item => {
        item.addEventListener('dragstart', () => {
            draggedIndex = Number(item.dataset.index);
        });

        item.addEventListener('dragover', e => {
            e.preventDefault();
        });

        item.addEventListener('drop', e => {
            e.preventDefault();
            const targetIndex = Number(item.dataset.index);
            if (draggedIndex === null || draggedIndex === targetIndex) return;

            const moved = slides.splice(draggedIndex, 1)[0];
            slides.splice(targetIndex, 0, moved);
            renderSlides();
        });
    });
}

function moveSlide(index, delta) {
    const target = index + delta;
    if (target < 0 || target >= slides.length) return;
    const tmp = slides[index];
    slides[index] = slides[target];
    slides[target] = tmp;
    renderSlides();
}

function removeSlide(index) {
    slides.splice(index, 1);
    renderSlides();
}

function addUrlBeforeSubmit() {
    const input = document.getElementById('newUrl');
    const hidden = document.getElementById('slidesJsonUrl');
    const url = input.value.trim();

    if (!url) return false;
    if (!/^https?:\/\//i.test(url)) {
        alert('Bitte eine vollständige http- oder https-Adresse eingeben.');
        return false;
    }

    const current = hidden.value ? JSON.parse(hidden.value) : [];
    current.push({ type: 'url', value: url });
    hidden.value = JSON.stringify(current);
    return true;
}

renderSlides();
</script>
</body>
</html>
