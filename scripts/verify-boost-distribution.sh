#!/usr/bin/env bash
set -euo pipefail

revision="${1:-HEAD}"
archive="$(mktemp)"
trap 'rm -f "$archive"' EXIT
git archive --format=tar "$revision" > "$archive"

for path in \
    resources/boost/guidelines/core.blade.php \
    resources/boost/skills/filament-acl/SKILL.md \
    resources/boost/skills/filament-acl-upgrade/SKILL.md \
    resources/boost/skills/filament-acl-upgrade/references/migration.md; do
    tar -tf "$archive" | grep -Fx "$path" > /dev/null
done

if tar -tf "$archive" | grep -E '^(docs/|UPGRADING.md$)' > /dev/null; then
    echo 'Development-only upgrade/docs files unexpectedly included' >&2
    exit 1
fi
cmp <(tar -xOf "$archive" resources/boost/skills/filament-acl-upgrade/references/migration.md) <(git show "$revision:UPGRADING.md")
echo 'Both Boost skills and the migration reference are included in the distribution.'
