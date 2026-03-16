# Raspberry Pi Infoscreen

Ein webbasierter Infoscreen für den Raspberry Pi mit Bild-Slides, Web-Slides und separater Uhr-Seite.

## Funktionen

- Bild-Uploads für den Infoscreen
- Web-Slides
- separate Uhr-Seite
- einstellbare Reihenfolge der Slides
- Fade-/Anzeige-Einstellungen
- Upload für Uhr-Logo
- Admin-Oberfläche im Browser

## Projektstruktur

```text
infoscreen/
├── admin.php
├── clock.php
├── delete.php
├── index.php
├── save_config.php
├── save_order.php
├── save_slides.php
├── slides.json
├── upload.php
├── upload_clock_logo.php
├── uploads/
├── clock_assets/
├── config.json
├── order.json
└── .gitignore
Voraussetzungen

Raspberry Pi

Raspberry Pi OS

Apache2

PHP

Browser im Kiosk-Modus

Netzwerkzugriff auf die Weboberfläche

Installation
1. Repository klonen
cd /var/www/html
sudo git clone https://github.com/Norcane82/raspberry-infoscreen.git infoscreen
2. Besitzer und Rechte setzen
sudo chown -R pi:pi /var/www/html/infoscreen
sudo chown -R www-data:www-data /var/www/html/infoscreen/uploads
sudo chmod -R 775 /var/www/html/infoscreen/uploads

Falls Uhr-Logos per Weboberfläche hochgeladen werden:

sudo chown -R www-data:www-data /var/www/html/infoscreen/clock_assets
sudo chmod -R 775 /var/www/html/infoscreen/clock_assets
3. Apache und PHP installieren
sudo apt update
sudo apt install apache2 php libapache2-mod-php -y
4. Projekt im Browser öffnen
http://IP-DES-RASPBERRY/infoscreen/

Admin-Bereich:

http://IP-DES-RASPBERRY/infoscreen/admin.php
Wichtige Laufzeitdateien

Diese Dateien werden lokal erzeugt oder geändert und sind normalerweise nicht Teil des Repositories:

uploads/

config.json

order.json

Logos in clock_assets/

logo.png

Update des Projekts

Im Projektordner:

cd /var/www/html/infoscreen
git pull
