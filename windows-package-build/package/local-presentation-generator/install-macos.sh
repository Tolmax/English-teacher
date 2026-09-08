#!/bin/zsh

set -euo pipefail

SOURCE_ROOT=${0:A:h}
APP_NAME="Генератор презентаций.app"
INSTALL_DIR="$HOME/Applications"
APP_BUNDLE="$INSTALL_DIR/$APP_NAME"
APP_CONTENTS="$APP_BUNDLE/Contents"
APP_DATA="$APP_CONTENTS/Resources/app"
DESKTOP_LINK="$HOME/Desktop/$APP_NAME"
BACKUP_DIR=$(mktemp -d)

cleanup() {
  rm -rf "$BACKUP_DIR"
}
trap cleanup EXIT

mkdir -p "$INSTALL_DIR" "$HOME/Desktop"

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

echo "Приложение установлено: $APP_BUNDLE"
echo "Иконка создана: $DESKTOP_LINK"
