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

echo "
const:
  DB_USER: '${SHOPWARE_DB_USER}'
  DB_PASSWORD: '${SHOPWARE_DB_PASS}'
  DB_HOST: '${SHOPWARE_DB_HOST}'
  DB_NAME: '${SHOPWARE_DB_NAME}'
  DB_PORT: '3306'
  SW_HOST: '${SHOPWARE_HOST}'
  SW_BASE_PATH: ''
  PHP_VERSION: '7.2'
  MYSQL_VERSION: '5.7'
  ELASTICSEARCH_IMAGE: 'docker.elastic.co/elasticsearch/elasticsearc'
  ELASTICSEARCH_VERSION: '6.7.2'
  CONTAINER_SUFFIX: '-dev'" >> .psh.yaml

cp /tmp/.htaccess .htaccess

composer install
./psh.phar init

unzip -o test_images.zip

cp -a /tmp/plugin/Frontend/QentaCheckoutSeamless /var/www/html/engine/Shopware/Plugins/Community/Frontend

php ./bin/console sw:plugin:install --activate cron
php ./bin/console sw:plugin:refresh
php ./bin/console sw:plugin:install --activate QentaCheckoutSeamless
php ./bin/console sw:plugin:refresh

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
