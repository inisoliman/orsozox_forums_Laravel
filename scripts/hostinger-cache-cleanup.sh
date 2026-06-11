#!/usr/bin/env bash
set -euo pipefail

# Safe cache cleanup for Hostinger/shared hosting.
# Put this script under the Laravel project root in scripts/
# and run it from cron. It deletes old cache/session/log files only.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

CACHE_DIR="${PROJECT_ROOT}/storage/framework/cache/data"
SESSIONS_DIR="${PROJECT_ROOT}/storage/framework/sessions"
VIEWS_DIR="${PROJECT_ROOT}/storage/framework/views"
LOGS_DIR="${PROJECT_ROOT}/storage/logs"

CACHE_MAX_AGE_MINUTES="${CACHE_MAX_AGE_MINUTES:-360}"
SESSION_MAX_AGE_MINUTES="${SESSION_MAX_AGE_MINUTES:-1440}"
VIEW_MAX_AGE_MINUTES="${VIEW_MAX_AGE_MINUTES:-1440}"
LOG_MAX_AGE_MINUTES="${LOG_MAX_AGE_MINUTES:-10080}"

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1"
}

is_under_project() {
    case "$1" in
        "${PROJECT_ROOT}"/*) return 0 ;;
        *) return 1 ;;
    esac
}

clean_old_files() {
    local dir="$1"
    local age_minutes="$2"
    local label="$3"

    if [ ! -d "$dir" ]; then
        log "skip ${label}: directory not found"
        return 0
    fi

    if ! is_under_project "$dir"; then
        log "refuse ${label}: path is outside project root: ${dir}"
        return 1
    fi

    local deleted
    deleted="$(
        find "$dir" -type f ! -name '.gitignore' -mmin "+${age_minutes}" -print -delete 2>/dev/null | wc -l
    )"

    find "$dir" -mindepth 1 -type d -empty -delete 2>/dev/null || true
    log "cleaned ${label}: ${deleted} file(s) older than ${age_minutes} minutes"
}

log "cleanup started for ${PROJECT_ROOT}"
clean_old_files "$CACHE_DIR" "$CACHE_MAX_AGE_MINUTES" "file cache"
clean_old_files "$SESSIONS_DIR" "$SESSION_MAX_AGE_MINUTES" "sessions"
clean_old_files "$VIEWS_DIR" "$VIEW_MAX_AGE_MINUTES" "compiled views"
clean_old_files "$LOGS_DIR" "$LOG_MAX_AGE_MINUTES" "logs"
log "cleanup finished"
