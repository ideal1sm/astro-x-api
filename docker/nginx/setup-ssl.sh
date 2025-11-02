#!/bin/bash

DOMAIN="api.astro-x.ru"
WEBROOT="/var/www/certbot"
NGINX_CONF="./docker/nginx/prod.conf"

# Проверяем, есть ли сертификат
if [ ! -f "./docker/certbot/conf/live/$DOMAIN/fullchain.pem" ]; then
  echo "Получаем SSL сертификат для $DOMAIN..."
  docker compose run --rm certbot certonly \
    --webroot -w $WEBROOT \
    -d $DOMAIN \
    --email ardanovinbox@gmail.com --agree-tos --no-eff-email
else
  echo "Сертификат уже существует, пропускаем получение."
fi

# Проверяем, есть ли HTTPS сервер в конфиге
if ! grep -q "listen 443 ssl;" $NGINX_CONF; then
  echo "Добавляем HTTPS сервер в конфиг NGINX..."
  cat >> $NGINX_CONF <<EOL

server {
    listen 443 ssl;
    server_name $DOMAIN;

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    root /var/www/html/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
EOL
fi

# Перезапуск NGINX
echo "Перезапускаем NGINX..."
docker compose exec web nginx -s reload || docker compose restart web
echo "SSL настроен и HTTPS включён ✅"
