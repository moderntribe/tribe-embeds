#!/usr/bin/env bash

##################################################
# Automatically downloads and installs WordPress
# inside the Lando PHP container.
##################################################

shopt -s expand_aliases

# LANDO_WEBROOT=/app/dev/public
WP_PATH=${LANDO_WEBROOT}
alias wp="/usr/local/bin/wp --path=${WP_PATH}"

function download_wp() {
  echo "* Downloading WordPress..."

  wp core download \
    --version=${WP_VERSION:-latest} \
    --force

  wp config create \
    --dbname=${DB_NAME:-wordpress} \
    --dbuser=${DB_USER:-wordpress} \
    --dbpass=${DB_PASSWORD:-wordpress} \
    --dbhost=${DB_HOST:-database}
}

function install_wp() {
  echo "* Installing WordPress..."

  # Wait for the database service to actually be ready...
  sleep 5

  wp core install \
    --url=${WP_HOME:-https://tribe-embed.lndo.site}/ \
    --title="Tribe Embed dev site" \
    --admin_user=admin \
    --admin_password=password \
    --admin_email=admin@tribe-embed.lndo.site \
    --skip-email
}

if [ ! -f "${WP_PATH}/wp-config.php" ]; then
  download_wp
  install_wp
else
  echo "* WordPress directory found at ${WP_PATH}. Skipping installation..."
fi
