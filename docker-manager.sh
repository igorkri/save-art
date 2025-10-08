#!/bin/bash

# Назви сервісів (можеш змінити за потреби)
PROJECT_DIR="$(dirname "$0")"
DOCKER_COMPOSE_FILE="$PROJECT_DIR/docker-compose.yml"

echo " Розгортаємо проєкт з Docker..."
if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
  echo "❌ Файл docker-compose.yml не знайдено в $PROJECT_DIR"
  exit 1
fi

# Перевіряємо, чи встановлений Docker
#if ! command -v docker &> /dev/null; then
#  echo "❌ Docker не встановлено. Будь ласка, встановіть Docker перед запуском цього скрипту."
#  exit 1
#fi
# Перевіряємо, чи встановлений docker-compose
#if ! command -v docker-compose &> /dev/null; then
#  echo "❌ docker-compose не встановлено. Будь ласка, встановіть docker-compose перед запуском цього скрипту."
#  exit 1
#fi
# Перевіряємо, чи встановлений PHP
if ! command -v php &> /dev/null; then
  echo "❌ PHP не встановлено. Будь ласка, встановіть PHP перед запуском цього скрипту."
  exit 1
fi
# Перевіряємо, php 8.4
if ! php -v | grep -q "PHP 8.4"; then
  echo "❌ PHP версія 8.4 не знайдена. Будь ласка, встановіть PHP 8.4 перед запуском цього скрипту."
  echo "~/developer/switch-php.sh 8.4"
  php -v
  exit 1
fi


start() {
  echo "🛑 Зупиняємо всі контейнери..."
  docker stop $(docker ps -q) 2>/dev/null || true

  echo "🚀 Запускаємо docker-compose..."
  docker-compose -f "$DOCKER_COMPOSE_FILE" up -d

# Перевіряємо, чи зупинено nginx та чи запущено Apache на локальному сервері (не Docker)
sudo systemctl stop nginx 2>/dev/null || true
if ! systemctl is-active --quiet apache2; then
  echo "✅ Apache не запущено, запускаємо..."
  sudo systemctl start apache2
else
  echo "🔁 Apache вже запущено."
fi
echo "сайт запущено на http://save-art.local"
#  echo "🧪 Перевіряємо локальний сервер PHP (Apache)..."
#  if ! lsof -i :8000 | grep LISTEN >/dev/null; then
#    echo "✅ Laravel сервер неактивний, запускаємо..."
#    php artisan serve &
#  else
#    echo "🔁 Laravel сервер вже запущено."
#  fi

#echo " Запускаю MCP (Менеджер Керування Проєктами)..."
#sleep 5
#php artisan boost:mcp
#sleep 2
#php artisan queue:work
}

down() {
  echo "🛑 Зупиняємо docker-compose контейнери..."
  docker-compose -f "$DOCKER_COMPOSE_FILE" down

  echo "🛑 Зупиняємо всі інші контейнери..."
  docker stop $(docker ps -q) 2>/dev/null || true
}

case "$1" in
  start)
    start
    ;;
  down)
    down
    ;;
  *)
    echo "❌ Невідомий параметр. Використання:"
    echo "   ./docker-manager.sh start  — запустити все"
    echo "   ./docker-manager.sh down   — зупинити все"
    exit 1
    ;;
esac
