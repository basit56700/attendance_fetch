#!/usr/bin/env bash
set -e

if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run this script with sudo: sudo bash $0"
  exit 1
fi

APP_DIR="/home/shoaib/web/gcs/private/attendance_erp"

echo "==> 1. Installing php8.4-sqlite3 extension directly..."
apt-get install -y php8.4-sqlite3

echo "==> 2. Restarting PHP 8.4 FPM & Nginx..."
systemctl restart php8.4-fpm
systemctl reload nginx

echo "==> 3. Running migrations and seeders..."
su - shoaib -c "cd $APP_DIR && php artisan migrate --seed --force"

echo "==> 4. Setting permissions on storage, cache, and database..."
chown -R shoaib:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/database"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/database"
find "$APP_DIR/database" -type f -exec chmod 664 {} +

echo "==> 5. Optimizing Laravel for production..."
su - shoaib -c "cd $APP_DIR && php artisan optimize:clear && php artisan optimize"

echo "==> 6. Testing local endpoint on port 8087..."
curl -sI http://127.0.0.1:8087/login | head -n 5

echo ""
echo "=========================================================="
echo "DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "Login URL: http://172.16.50.29:8087/login"
echo "Admin Credentials: admin@local.com / admin123"
echo "=========================================================="
