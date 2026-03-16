#!/bin/bash

set -e

PROJECT_DIR="/var/www/html/infoscreen"
WEB_USER="www-data"
APP_USER="pi"

echo "==> Paketliste aktualisieren"
sudo apt update

echo "==> Apache und PHP installieren"
sudo apt install -y apache2 php libapache2-mod-php git

echo "==> Apache aktivieren"
sudo systemctl enable apache2
sudo systemctl restart apache2

echo "==> Projektordner prüfen"
if [ ! -d "$PROJECT_DIR" ]; then
  echo "Projektordner $PROJECT_DIR existiert nicht."
  echo "Bitte Repository zuerst nach $PROJECT_DIR klonen."
  exit 1
fi

echo "==> Ordner anlegen"
mkdir -p "$PROJECT_DIR/uploads"
mkdir -p "$PROJECT_DIR/clock_assets"

echo "==> Besitzer setzen"
sudo chown -R "$APP_USER":"$APP_USER" "$PROJECT_DIR"
sudo chown -R "$WEB_USER":"$WEB_USER" "$PROJECT_DIR/uploads"
sudo chown -R "$WEB_USER":"$WEB_USER" "$PROJECT_DIR/clock_assets"

echo "==> Rechte setzen"
sudo chmod -R 775 "$PROJECT_DIR/uploads"
sudo chmod -R 775 "$PROJECT_DIR/clock_assets"

echo "==> Schreibrechte für Laufzeitdateien vorbereiten"
if [ ! -f "$PROJECT_DIR/config.json" ]; then
  echo '{}' | sudo tee "$PROJECT_DIR/config.json" > /dev/null
fi

if [ ! -f "$PROJECT_DIR/order.json" ]; then
  echo '[]' | sudo tee "$PROJECT_DIR/order.json" > /dev/null
fi

sudo chown "$WEB_USER":"$WEB_USER" "$PROJECT_DIR/config.json"
sudo chown "$WEB_USER":"$WEB_USER" "$PROJECT_DIR/order.json"
sudo chmod 664 "$PROJECT_DIR/config.json"
sudo chmod 664 "$PROJECT_DIR/order.json"

echo "==> Installation abgeschlossen"
echo "Aufruf:"
echo "  Frontend: http://<RASPBERRY-IP>/infoscreen/"
echo "  Admin:    http://<RASPBERRY-IP>/infoscreen/admin.php"
