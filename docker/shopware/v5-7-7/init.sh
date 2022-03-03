#!/bin/bash

set -e

if [[ -z ${SHOPWARE_HOST}  ]]; then
  echo "SHOPWARE_URL not specified."
  if [[ -n ${NGROK_TOKEN} ]]; then 
    echo "Launching ngrok to get temporary URL"
    SHOPWARE_HOST=$(ngrok.sh ${NGROK_TOKEN})
  else
    echo "No NGROK_TOKEN specified. Using localhost as URL"
    SHOPWARE_HOST=localhost
  fi
fi

echo "DB_USER=${SHOPWARE_DB_USER}" > .env
echo "DB_PASSWORD=${SHOPWARE_DB_PASS}" >> .env
echo "DB_HOST=${SHOPWARE_DB_HOST}" >> .env
echo "DB_NAME=${SHOPWARE_DB_NAME}" >> .env
echo "DB_PORT=3306" >> .env
echo "SW_HOST=${SHOPWARE_HOST}" >> .env
echo "SW_BASE_PATH=" >> .env
echo "ELASTICSEARCH_HOST=elasticsearch" >> .env


make init
unzip -o test_images.zip

cp /tmp/.htaccess .htaccess

cp -a /tmp/plugin/Frontend/QentaCheckoutSeamless /var/www/html/engine/Shopware/Plugins/Community/Frontend

php ./bin/console sw:plugin:install --activate cron
php ./bin/console sw:plugin:refresh
php ./bin/console sw:plugin:install --activate --clear-cache QentaCheckoutSeamless
php ./bin/console sw:plugin:refresh

composer install

chown -R www-data:www-data .
function print_info() {
  echo
  echo '####################################'
  echo
  echo "Shop: https://${SHOPWARE_HOST}"
  echo "Admin Panel: https://${SHOPWARE_HOST}/backend"
  echo "Backend User: demo"
  echo "Backend Password: demo"
  echo
  echo '####################################'
  echo
}

print_info

apache2-foreground "$@"

function print_info() {
  echo
  echo '####################################'
  echo
  echo "Shop: https://${SHOPWARE_HOST}"
  echo "Admin Panel: https://${SHOPWARE_HOST}/backend"
  echo "Backend User: demo"
  echo "Backend Password: demo"
  echo
  echo '####################################'
  echo
}
