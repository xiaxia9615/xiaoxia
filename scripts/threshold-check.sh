#!/usr/bin/env bash
#
# 阈值校验脚本 — 统一检查项目质量门禁
# 用法: ./scripts/threshold-check.sh [--coverage]
#   --coverage  同时运行覆盖率检查（较慢）
#
set -euo pipefail

# ── 阈值配置 ──────────────────────────────────────────────
MIN_TESTS=200
MIN_COVERAGE=60          # 百分比
MIN_ASSERTIONS=600
REQUIRED_ARCH_GUARDS=13

# ── 颜色 ──────────────────────────────────────────────────
RED=0; YELLOW=0
ok()   { echo -e "\033[32m[PASS]\033[0m $1"; }
warn() { echo -e "\033[33m[WARN]\033[0m $1"; YELLOW=1; }
fail() { echo -e "\033[31m[FAIL]\033[0m $1"; RED=1; }

echo "========================================"
 echo "  阈值校验 — $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"

# ── 1. 测试数量与通过率 ────────────────────────────────────
echo ""
echo "▶ 1. 测试基线"
TEST_OUTPUT=$(php artisan test 2>&1 || true)

PASSED=$(echo "$TEST_OUTPUT" | grep -oE '[0-9]+ passed' | grep -oE '[0-9]+' || echo "0")
SKIPPED=$(echo "$TEST_OUTPUT" | grep -oE '[0-9]+ skipped' | grep -oE '[0-9]+' || echo "0")
FAILED=$(echo "$TEST_OUTPUT" | grep -oE '[0-9]+ failed' | grep -oE '[0-9]+' || echo "0")
ASSERTIONS=$(echo "$TEST_OUTPUT" | grep -oE '[0-9]+ assertions' | grep -oE '[0-9]+' || echo "0")

echo "  passed: $PASSED | skipped: $SKIPPED | failed: $FAILED | assertions: $ASSERTIONS"

if [ "$FAILED" -gt 0 ]; then
    fail "$FAILED 个测试失败"
else
    ok "无失败测试"
fi

if [ "$PASSED" -lt "$MIN_TESTS" ]; then
    fail "测试数 $PASSED < 阈值 $MIN_TESTS"
else
    ok "测试数 $PASSED >= $MIN_TESTS"
fi

if [ "$ASSERTIONS" -lt "$MIN_ASSERTIONS" ]; then
    fail "断言数 $ASSERTIONS < 阈值 $MIN_ASSERTIONS"
else
    ok "断言数 $ASSERTIONS >= $MIN_ASSERTIONS"
fi

# ── 2. 架构守卫 ────────────────────────────────────────────
echo ""
echo "▶ 2. 架构守卫"
ARCH_OUTPUT=$(php artisan test tests/Architecture 2>&1 || true)
ARCH_PASSED=$(echo "$ARCH_OUTPUT" | grep -oE '[0-9]+ passed' | grep -oE '[0-9]+' || echo "0")
ARCH_FAILED=$(echo "$ARCH_OUTPUT" | grep -oE '[0-9]+ failed' | grep -oE '[0-9]+' || echo "0")

echo "  architecture tests passed: $ARCH_PASSED | failed: $ARCH_FAILED"

if [ "$ARCH_FAILED" -gt 0 ]; then
    fail "架构守卫有 $ARCH_FAILED 项失败"
else
    ok "架构守卫全绿"
fi

if [ "$ARCH_PASSED" -lt "$REQUIRED_ARCH_GUARDS" ]; then
    warn "架构守卫数 $ARCH_PASSED < 预期 $REQUIRED_ARCH_GUARDS（可能新增了守卫未更新阈值）"
else
    ok "架构守卫数 $ARCH_PASSED >= $REQUIRED_ARCH_GUARDS"
fi

# ── 3. 静态分析 ────────────────────────────────────────────
echo ""
echo "▶ 3. 静态分析 (Larastan)"
STAN_OUTPUT=$(vendor/bin/phpstan analyse --no-progress 2>&1 || true)
STAN_ERRORS=$(echo "$STAN_OUTPUT" | grep -oE '[0-9]+ error' | grep -oE '[0-9]+' || echo "0")

if [ "$STAN_ERRORS" -gt 0 ]; then
    fail "PHPStan 报告 $STAN_ERRORS 个错误"
    echo "$STAN_OUTPUT" | tail -10
else
    ok "PHPStan 0 errors"
fi

# ── 4. 代码格式 ────────────────────────────────────────────
echo ""
echo "▶ 4. 代码格式"
if vendor/bin/pint --test 2>&1; then
    ok "Pint 格式检查通过"
else
    fail "Pint 格式检查失败，请运行 vendor/bin/pint 修复"
fi

# ── 5. 调试残留 ────────────────────────────────────────────
echo ""
echo "▶ 5. 调试残留检查"
DEBUG_HITS=$(grep -rnE '\bdd\(|\bvar_dump\(|\bdump\(' app/ routes/ 2>/dev/null || true)
if [ -z "$DEBUG_HITS" ]; then
    ok "无调试残留"
else
    fail "发现调试残留:"
    echo "$DEBUG_HITS"
fi

# ── 6. 裸查检查 ────────────────────────────────────────────
echo ""
echo "▶ 6. Controller 裸查检查"
NAKED_HITS=$(grep -rnE '::find\(|::findOrFail\(' app/Modules/*/Http/Controllers/ 2>/dev/null || true)
if [ -z "$NAKED_HITS" ]; then
    ok "Controller 无裸查"
else
    fail "Controller 存在裸查:"
    echo "$NAKED_HITS"
fi

# ── 7. 迁移状态 ────────────────────────────────────────────
echo ""
echo "▶ 7. 迁移状态"
PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || true)
if [ "$PENDING" -gt 0 ]; then
    fail "发现 $PENDING 个未执行迁移"
else
    ok "无未执行迁移"
fi

# ── 8. 覆盖率（可选） ──────────────────────────────────────
if [ "${1:-}" = "--coverage" ]; then
    echo ""
    echo "▶ 8. 代码覆盖率"
    COV_OUTPUT=$(php -d pcov.directory=/var/www/app/app artisan test --coverage 2>&1 || true)
    COV_PCT=$(echo "$COV_OUTPUT" | grep -oE 'Total: [0-9.]+ %' | grep -oE '[0-9.]+' || echo "0")
    echo "  覆盖率: ${COV_PCT}%"

    COV_INT=$(echo "$COV_PCT" | cut -d. -f1)
    if [ "$COV_INT" -lt "$MIN_COVERAGE" ]; then
        fail "覆盖率 ${COV_PCT}% < 阈值 ${MIN_COVERAGE}%"
    else
        ok "覆盖率 ${COV_PCT}% >= ${MIN_COVERAGE}%"
    fi
fi

# ── 汇总 ──────────────────────────────────────────────────
echo ""
echo "========================================"
if [ $RED -eq 0 ]; then
    echo -e "  \033[32m总判定: 通过\033[0m"
    [ $YELLOW -eq 1 ] && echo -e "  \033[33m（有警告但非阻断）\033[0m"
    exit 0
else
    echo -e "  \033[31m总判定: 有 FAIL，阻断\033[0m"
    exit 1
fi
