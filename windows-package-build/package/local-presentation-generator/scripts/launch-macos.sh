#!/bin/zsh

set -u

SCRIPT_DIR=${0:A:h}
if [[ -d "$SCRIPT_DIR/app" ]]; then
  APP_ROOT="$SCRIPT_DIR/app"
else
  APP_ROOT=${SCRIPT_DIR:h}/Resources/app
fi
ENV_FILE="$APP_ROOT/.env"
LOG_DIR="$HOME/Library/Logs/EnglishPresentationGenerator"
SERVER_LOG="$LOG_DIR/server.log"

mkdir -p "$LOG_DIR" "$APP_ROOT/storage/output" "$APP_ROOT/storage/images" "$APP_ROOT/public/downloads"

if [[ ! -f "$ENV_FILE" && -f "$APP_ROOT/.env.example" ]]; then
  cp "$APP_ROOT/.env.example" "$ENV_FILE"
fi

read_env_value() {
  local key="$1"
  local fallback="$2"
  local value
  value=$(sed -n "s/^[[:space:]]*${key}[[:space:]]*=[[:space:]]*//p" "$ENV_FILE" 2>/dev/null | head -n 1)
  value=${value#\"}
  value=${value%\"}
  value=${value#\'}
  value=${value%\'}
  print -r -- "${value:-$fallback}"
}

find_php() {
  local candidate
  for candidate in \
    "$APP_ROOT/runtime/php/bin/php" \
    "/opt/homebrew/bin/php" \
    "/usr/local/bin/php" \
    "$(command -v php 2>/dev/null)"; do
    if [[ -n "$candidate" && -x "$candidate" ]]; then
      print -r -- "$candidate"
      return 0
    fi
  done
  return 1
}

port_is_open() {
  /usr/bin/nc -z 127.0.0.1 "$1" >/dev/null 2>&1
}

port=$(read_env_value APP_PORT 8010)
if [[ ! "$port" =~ '^[0-9]+$' ]]; then
  port=8010
fi

while port_is_open "$port"; do
  open "http://127.0.0.1:$port/"
  exit 0
done

php_bin=$(find_php) || {
  osascript -e 'display alert "Генератор презентаций" message "PHP не найден. Установите PHP 8+ через Homebrew и повторите запуск." as critical'
  exit 1
}

if ! "$php_bin" -m | grep -qx 'zip'; then
  osascript -e 'display alert "Генератор презентаций" message "В PHP не включено расширение ZIP, необходимое для создания PPTX." as critical'
  exit 1
fi

cd "$APP_ROOT" || exit 1
( sleep 1; open "http://127.0.0.1:$port/" ) &
exec "$php_bin" -S "127.0.0.1:$port" -t public >>"$SERVER_LOG" 2>&1
