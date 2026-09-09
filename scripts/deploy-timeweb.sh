#!/usr/bin/env bash

set -Eeuo pipefail

: "${TIMEWEB_HOST:?TIMEWEB_HOST is required}"
: "${TIMEWEB_USER:?TIMEWEB_USER is required}"
: "${TIMEWEB_SITE_PATH:?TIMEWEB_SITE_PATH is required}"
: "${TIMEWEB_SITE_URL:?TIMEWEB_SITE_URL is required}"

readonly remote="${TIMEWEB_USER}@${TIMEWEB_HOST}"
readonly source_dir="website/"
readonly backup_root="${TIMEWEB_SITE_PATH%/}-deploy-backups"
readonly backup_dir="${backup_root}/$(date -u +%Y%m%dT%H%M%SZ)-${GITHUB_SHA:-local}"
readonly exclude_args=(
  --exclude=.env
  --exclude='database/*.sqlite*'
  --exclude=storage/
  --exclude=uploads/
  --exclude=.git/
)

if [[ ! -d "$source_dir" ]]; then
  echo "Deployment source is missing: $source_dir" >&2
  exit 1
fi

echo "Creating server backup: $backup_dir"
ssh "$remote" "set -e; mkdir -p '$backup_dir'; rsync -a --exclude='.env' --exclude='database/*.sqlite*' --exclude='storage/' --exclude='uploads/' --exclude='.git/' '${TIMEWEB_SITE_PATH%/}/' '$backup_dir/'"

rollback() {
  echo "Deployment check failed. Restoring code from $backup_dir" >&2
  rsync -az --delete --delay-updates "${exclude_args[@]}" "$remote:$backup_dir/" "$remote:${TIMEWEB_SITE_PATH%/}/"
}
trap rollback ERR

echo "Publishing website code"
rsync -az --delete --delay-updates "${exclude_args[@]}" "$source_dir" "$remote:${TIMEWEB_SITE_PATH%/}/"

echo "Checking PHP syntax on Timeweb"
ssh "$remote" "set -e; find '${TIMEWEB_SITE_PATH%/}' -type f -name '*.php' -not -path '*/storage/*' -print0 | xargs -0 -n1 php -l >/dev/null"

echo "Checking public and teacher-login pages"
curl --fail --silent --show-error --location --retry 3 --retry-delay 2 --max-time 30 "${TIMEWEB_SITE_URL%/}/" >/dev/null
curl --fail --silent --show-error --location --retry 3 --retry-delay 2 --max-time 30 "${TIMEWEB_SITE_URL%/}/admin/login" >/dev/null

trap - ERR
echo "Deployment completed successfully: ${GITHUB_SHA:-local}"
echo "Backup retained at: $backup_dir"
