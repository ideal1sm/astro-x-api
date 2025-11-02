#!/bin/bash

DOMAIN="api.astro-x.ru"
WEBROOT="/var/www/certbot"

# Получаем сертификат
docker compose run --rm certbot certonly \
  --webroot -w $WEBROOT \
  -d $DOMAIN \
  --email ardanovinbox@gmail.com --agree-tos --no-eff-email

# Создаём конфиг HTTPS для NGINX
cat > ./docker/certbot/conf/live/$DOMAIN/ssl.conf <<EOL
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

# Перезапускаем NGINX
docker compose exec web nginx -s reload
