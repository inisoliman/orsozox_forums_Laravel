#!/usr/bin/env bash
set -euo pipefail

# ============================================================================
#  ⚠️  ملاحظة: السكريبت الرسمي المعتمد هو: scripts/hostinger-cache-cleanup.sh
#  هذا الملف يستدعيه فقط للحفاظ على التوافق. استخدم الملف الرسمي في الكرون.
# ============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

exec bash "${SCRIPT_DIR}/hostinger-cache-cleanup.sh"
