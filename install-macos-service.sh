#!/bin/zsh

set -euo pipefail

SOURCE_ROOT=${0:A:h}
APP_NAME="Генератор презентаций.app"
INSTALL_DIR="$HOME/Applications"
APP_BUNDLE="$INSTALL_DIR/$APP_NAME"
APP_CONTENTS="$APP_BUNDLE/Contents"
APP_DATA="$APP_CONTENTS/Resources/app"
USER_DATA="$HOME/Library/Application Support/EnglishPresentationGenerator"
DESKTOP_LINK="$HOME/Desktop/$APP_NAME"
SERVICE_ID="local.englishteacher.presentation-generator.server"
LAUNCH_AGENT="$HOME/Library/LaunchAgents/$SERVICE_ID.plist"
LOG_DIR="$HOME/Library/Logs/EnglishPresentationGenerator"
BACKUP_DIR=$(mktemp -d)

cleanup() {
  rm -rf "$BACKUP_DIR"
}
trap cleanup EXIT

mkdir -p "$INSTALL_DIR" "$HOME/Desktop" "$HOME/Library/LaunchAgents" "$LOG_DIR"

if [[ -f "$APP_DATA/.env" ]]; then
  cp "$APP_DATA/.env" "$BACKUP_DIR/.env"
fi
if [[ -d "$APP_DATA/storage" ]]; then
  cp -R "$APP_DATA/storage" "$BACKUP_DIR/storage"
fi

rm -rf "$APP_BUNDLE"
osacompile -o "$APP_BUNDLE" \
  -e 'on run' \
  -e 'set appPath to POSIX path of (path to me)' \
  -e 'do shell script quoted form of (appPath & "Contents/Resources/launch-macos.sh")' \
  -e 'end run'
mkdir -p "$APP_CONTENTS/Resources" "$APP_DATA"

ditto --norsrc "$SOURCE_ROOT" "$APP_DATA"

cp "$SOURCE_ROOT/scripts/launch-macos.sh" "$APP_CONTENTS/Resources/launch-macos.sh"
chmod +x "$APP_CONTENTS/Resources/launch-macos.sh"

if [[ -f "$BACKUP_DIR/.env" ]]; then
  cp "$BACKUP_DIR/.env" "$APP_DATA/.env"
elif [[ ! -f "$APP_DATA/.env" && -f "$APP_DATA/.env.example" ]]; then
  cp "$APP_DATA/.env.example" "$APP_DATA/.env"
fi
if [[ -d "$BACKUP_DIR/storage" ]]; then
  rm -rf "$APP_DATA/storage"
  cp -R "$BACKUP_DIR/storage" "$APP_DATA/storage"
fi

mkdir -p "$APP_DATA/storage/output" "$APP_DATA/storage/images" "$APP_DATA/public/downloads"

mkdir -p "$USER_DATA/storage/output" "$USER_DATA/storage/images" "$USER_DATA/downloads"
if [[ -f "$APP_DATA/.env" && ! -f "$USER_DATA/.env" ]]; then
  cp "$APP_DATA/.env" "$USER_DATA/.env"
fi
if [[ -d "$APP_DATA/storage" ]]; then
  ditto --norsrc "$APP_DATA/storage" "$USER_DATA/storage"
fi
if [[ -d "$APP_DATA/public/downloads" ]]; then
  ditto --norsrc "$APP_DATA/public/downloads" "$USER_DATA/downloads"
fi
rm -rf "$APP_DATA/storage" "$APP_DATA/public/downloads"
rm -f "$APP_DATA/.env"
mkdir -p "$APP_DATA/storage" "$APP_DATA/public/downloads"

plist="$APP_CONTENTS/Info.plist"
plutil -replace CFBundleDisplayName -string 'Генератор презентаций' "$plist"
plutil -replace CFBundleIdentifier -string 'local.englishteacher.presentation-generator' "$plist"
plutil -replace CFBundleName -string 'Генератор презентаций' "$plist"
plutil -replace CFBundleShortVersionString -string '1.0.0' "$plist"
plutil -insert LSMinimumSystemVersion -string '12.0' "$plist" 2>/dev/null || plutil -replace LSMinimumSystemVersion -string '12.0' "$plist"
plutil -insert LSUIElement -bool true "$plist" 2>/dev/null || plutil -replace LSUIElement -bool true "$plist"

rm -f "$DESKTOP_LINK"
ln -s "$APP_BUNDLE" "$DESKTOP_LINK"
xattr -dr com.apple.quarantine "$APP_BUNDLE" 2>/dev/null || true
codesign --force --deep --sign - "$APP_BUNDLE"

php_bin=''
for candidate in '/opt/homebrew/bin/php' '/usr/local/bin/php' "$(command -v php 2>/dev/null)"; do
  if [[ -n "$candidate" && -x "$candidate" ]]; then
    php_bin="$candidate"
    break
  fi
done
if [[ -z "$php_bin" ]]; then
  echo 'PHP 8+ не найден. Установите PHP через Homebrew.' >&2
  exit 1
fi

plutil -create xml1 "$LAUNCH_AGENT"
plutil -insert Label -string "$SERVICE_ID" "$LAUNCH_AGENT"
plutil -insert ProgramArguments -array "$LAUNCH_AGENT"
plutil -insert ProgramArguments.0 -string "$php_bin" "$LAUNCH_AGENT"
plutil -insert ProgramArguments.1 -string '-S' "$LAUNCH_AGENT"
plutil -insert ProgramArguments.2 -string '127.0.0.1:8010' "$LAUNCH_AGENT"
plutil -insert ProgramArguments.3 -string '-t' "$LAUNCH_AGENT"
plutil -insert ProgramArguments.4 -string "$APP_DATA/public" "$LAUNCH_AGENT"
plutil -insert WorkingDirectory -string "$APP_DATA" "$LAUNCH_AGENT"
plutil -insert RunAtLoad -bool true "$LAUNCH_AGENT"
plutil -insert KeepAlive -bool true "$LAUNCH_AGENT"
plutil -insert StandardOutPath -string "$LOG_DIR/server.log" "$LAUNCH_AGENT"
plutil -insert StandardErrorPath -string "$LOG_DIR/server-error.log" "$LAUNCH_AGENT"

launchctl bootout "gui/$(id -u)/$SERVICE_ID" >/dev/null 2>&1 || true
launchctl bootstrap "gui/$(id -u)" "$LAUNCH_AGENT"

echo "Приложение установлено: $APP_BUNDLE"
echo "Иконка создана: $DESKTOP_LINK"
