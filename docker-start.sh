#!/bin/sh
set -eu

storage_path=/var/www/html/storage/app
mkdir -p "$storage_path/public" "$storage_path/private"
chown -R www-data:www-data "$storage_path"
find "$storage_path" -type d -exec chmod 775 {} \;
find "$storage_path/private" -type d -exec chmod 700 {} \;
find "$storage_path/private" -type f -exec chmod 600 {} \;

a2dismod mpm_event mpm_worker || true
a2enmod mpm_prefork rewrite

port="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:.*>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
