#!/bin/bash
set -euo pipefail
export PATH="/usr/sbin:/usr/bin:/usr/local/bin:$PATH"
cd /workspace
if ! command -v php >/dev/null || ! command -v mariadbd >/dev/null; then
  sudo apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php-cli php-mysql php-mbstring php-xml php-curl php-zip mariadb-server curl
fi
mkdir -p .runtime
if [ ! -d .runtime/mysql/mysql ]; then
  mariadb-install-db --datadir=/workspace/.runtime/mysql --auth-root-authentication-method=normal > .runtime/install.log 2>&1
fi
started=0
if ! mariadb-admin --socket=/workspace/.runtime/mysql.sock ping >/dev/null 2>&1; then
  mariadbd --datadir=/workspace/.runtime/mysql --socket=/workspace/.runtime/mysql.sock --pid-file=/workspace/.runtime/mysql.pid --skip-networking --log-error=/workspace/.runtime/mysql.log &
  started=1
fi
cleanup() { if [ "$started" = 1 ]; then mariadb-admin --socket=/workspace/.runtime/mysql.sock -u root shutdown; fi; }
trap cleanup EXIT
for attempt in $(seq 1 60); do
  if mariadb-admin --socket=/workspace/.runtime/mysql.sock ping >/dev/null 2>&1; then break; fi
  sleep 1
done
mariadb --socket=/workspace/.runtime/mysql.sock -u root -e "CREATE DATABASE IF NOT EXISTS taskflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'taskflow'@'localhost' IDENTIFIED BY 'taskflow-local-dev'; GRANT ALL ON taskflow.* TO 'taskflow'@'localhost';"
if [ ! -f wordpress/wp-includes/version.php ]; then
  curl -fsSL https://wordpress.org/latest.tar.gz -o .runtime/wordpress.tar.gz
  tar -xzf .runtime/wordpress.tar.gz
fi
if [ ! -f scripts/wp-cli.phar ]; then
  curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o scripts/wp-cli.phar
fi
if ! php scripts/wp-cli.phar --path=wordpress core is-installed >/dev/null 2>&1; then
  php scripts/wp-cli.phar --path=wordpress core install --url=http://localhost:3000 --title=Taskflow --admin_user=admin --admin_password=Taskflow2026! --admin_email=admin@taskflow.local --skip-email
fi
php scripts/wp-cli.phar --path=wordpress eval-file /workspace/scripts/seed.php
