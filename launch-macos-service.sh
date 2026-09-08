#!/bin/zsh

set -u

SCRIPT_DIR=${0:A:h}
if [[ -d "$SCRIPT_DIR/app" ]]; then
  APP_ROOT="$SCRIPT_DIR/app"
else
  APP_ROOT=${SCRIPT_DIR:h}/Resources/app
fi
LOG_DIR="$HOME/Library/Logs/EnglishPresentationGenerator"
SERVICE_ID="local.englishteacher.presentation-generator.server"

mkdir -p "$LOG_DIR"

port_is_open() {
  /usr/bin/nc -z 127.0.0.1 "$1" >/dev/null 2>&1
}

port=8010

while port_is_open "$port"; do
  open "http://127.0.0.1:$port/"
  exit 0
done

if ! launchctl kickstart -k "gui/$(id -u)/$SERVICE_ID" >/dev/null 2>&1; then
  osascript -e 'display alert "Генератор презентаций" message "Сервис запуска не найден. Переустановите приложение с помощью install-macos.sh." as critical'
  exit 1
fi

for _ in {1..40}; do
  if port_is_open "$port"; then
    open "http://127.0.0.1:$port/"
    exit 0
  fi
  sleep 0.1
done

osascript -e 'display alert "Генератор презентаций" message "Локальный сервер не запустился. Проверьте журнал приложения." as critical'
exit 1
