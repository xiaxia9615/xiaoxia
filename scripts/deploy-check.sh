#!/usr/bin/env bash
set -e
PENDING=$(php artisan migrate:status --pending | grep -c "Pending" || true)
if [ "$PENDING" -gt "0" ]; then
    echo "FAIL: 发现 $PENDING 个未执行迁移，禁止上线"
    php artisan migrate:status --pending
    exit 1
fi
echo "OK: 无未执行迁移"
