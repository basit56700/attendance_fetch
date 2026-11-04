#!/usr/bin/env bash
set -e

if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run this script with sudo: sudo bash $0"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_DIR="$SCRIPT_DIR/deploy"

echo "==> 1. Installing PHP-FPM pool config for attendance_erp..."
cp "$DEPLOY_DIR/attendance-erp.conf" /etc/php/8.4/fpm/pool.d/attendance-erp.conf

echo "==> 2. Reloading PHP 8.4 FPM..."
systemctl reload php8.4-fpm

echo "==> 3. Installing Nginx server block for port 8087..."
cp "$DEPLOY_DIR/attendance-erp-8087.conf" /etc/nginx/conf.d/attendance-erp-8087.conf

echo "==> 4. Testing Nginx configuration..."
nginx -t

echo "==> 5. Reloading Nginx..."
systemctl reload nginx

echo "==> 6. Allowing Port 8087 in firewall..."
if [ -x /usr/local/hestia/bin/v-add-firewall-rule ]; then
    /usr/local/hestia/bin/v-add-firewall-rule ACCEPT 0.0.0.0/0 8087 TCP attendance_erp 2>/dev/null || true
fi

if ! iptables -C INPUT -p tcp --dport 8087 -j ACCEPT 2>/dev/null; then
    iptables -I INPUT 6 -p tcp --dport 8087 -j ACCEPT
fi

echo "==> 7. Verifying listening port 8087..."
ss -lntp | grep ':8087' || true

echo "==> 8. Verifying HTTP response..."
curl -sI http://127.0.0.1:8087 | head -n 5

echo ""
echo "=========================================================="
echo "SUCCESS! attendance_erp is live on port 8087."
echo "URL: http://172.16.50.29:8087 or http://192.168.10.201:8087"
echo "Web Root: /home/shoaib/web/gcs/private/attendance_erp/public"
echo "=========================================================="

