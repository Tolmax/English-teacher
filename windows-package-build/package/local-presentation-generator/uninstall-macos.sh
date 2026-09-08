#!/bin/zsh

set -euo pipefail

APP_NAME="Генератор презентаций.app"
APP_BUNDLE="$HOME/Applications/$APP_NAME"
DESKTOP_LINK="$HOME/Desktop/$APP_NAME"

rm -f "$DESKTOP_LINK"

if [[ -d "$APP_BUNDLE" ]]; then
  read "answer?Удалить приложение вместе с настройками и созданными презентациями? Введите YES: "
  if [[ "$answer" == "YES" ]]; then
    rm -rf "$APP_BUNDLE"
    echo "Приложение удалено."
  else
    echo "Иконка удалена, данные приложения сохранены."
  fi
fi
