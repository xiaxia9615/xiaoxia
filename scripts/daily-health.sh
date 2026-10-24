#!/bin/bash
set -uo pipefail
cd /var/www/app

LOG=/tmp/health-$(date +%F).log
./healthcheck.sh > "$LOG" 2>&1
EXIT=$?

# 追加到健康日志
{
  echo ""
  echo "## $(date +%F\ %H:%M) 自动体检"
  echo "- exit: $EXIT"
  grep -E "\[红\]|\[黄\]" "$LOG" || echo "- 全绿"
} >> docs/health-log.md

# 红则告警 (独立 SMTP 通道, 不启动 Laravel, 应用挂了也能发; 凭据在 .env 不进 git)
if [ $EXIT -ne 0 ]; then
    RED=$(grep '\[红\]' "$LOG" | head -5 | tr '\n' '|')
    php /var/www/app/scripts/mail-alert.php al@qssxmall.cn 'Health Alert' "[health] $(hostname) 体检红: ${RED}" || true
fi

# 死人开关 ping (阶段4接入, 此处占位)
# curl -fsS -m 10 https://hc-ping.com/<UUID> || true

exit $EXIT
