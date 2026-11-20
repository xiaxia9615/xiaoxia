#!/usr/bin/env bash
# CI 预检脚本：在开真实 PR 前本地复现 CI 各检查项，降低首次 PR 失败概率。
# 本脚本不执行 git push、不开 PR、不触发远端 CI，不修改任何配置或阈值。

set -u  # 不使用 -e：任一检查失败不中断后续

cd "$(dirname "$0")/.."  # 切换到仓库根目录

echo "=========================================="
echo "CI 预检（本地模拟）"
echo "=========================================="

echo ""
echo "[环境信息]"
echo "PHP 版本: $(php -r 'echo PHP_VERSION;')"
echo "Node 版本: $(node -v)"
if php -m | grep -qi pcov; then
  echo "pcov: 已启用"
else
  echo "pcov: 未启用（覆盖率统计将不可用）"
fi
echo ""

NAMES=()
CODES=()

run_check() {
  local name="$1"
  shift
  echo "------------------------------------------"
  echo "[检查] $name"
  echo "命令: $*"
  echo "------------------------------------------"
  "$@"
  local code=$?
  NAMES+=("$name")
  CODES+=("$code")
  echo ""
  echo "[结果] $name 退出码: $code"
  echo ""
}

# 1. secretlint 全仓扫描
SECRET_BIN=""
if command -v secretlint >/dev/null 2>&1; then
  SECRET_BIN="secretlint"
elif [ -x /home/ubuntu/secret-test/node_modules/.bin/secretlint ]; then
  SECRET_BIN="/home/ubuntu/secret-test/node_modules/.bin/secretlint"
  echo "[警告] 主项目未安装 secretlint，降级使用 /home/ubuntu/secret-test 二进制"
fi
if [ -n "$SECRET_BIN" ]; then
  run_check "secretlint 全仓扫描" "$SECRET_BIN" "**/*"
else
  if [ -n "${CI:-}" ]; then
    echo "[失败] secretlint 不可用（未安装且沙箱路径不存在），CI 环境下视为 FAIL"
    NAMES+=("secretlint 全仓扫描")
    CODES+=(1)
  else
    echo "[跳过] secretlint 不可用（未安装且沙箱路径不存在），本地环境跳过"
    NAMES+=("secretlint 全仓扫描")
    CODES+=("SKIP")
  fi
fi

# 2. 后端测试覆盖率（阈值 55%）
run_check "后端测试覆盖率（--parallel --coverage --min=55）" php artisan test --parallel --coverage --min=55

# 3. 前端测试覆盖率
run_check "前端测试覆盖率（npm run test:coverage）" npm run test:coverage

# 4. workflow 语法检查
if command -v actionlint >/dev/null 2>&1; then
  run_check "workflow 语法（actionlint）" actionlint
else
  if [ -n "${CI:-}" ]; then
    echo "[失败] actionlint 未安装，CI 环境下视为 FAIL"
    NAMES+=("workflow 语法（actionlint）")
    CODES+=(1)
  else
    echo "[跳过] actionlint 未安装，本地环境跳过 workflow 语法检查"
    NAMES+=("workflow 语法（actionlint）")
    CODES+=("SKIP")
  fi
fi

# 汇总
echo "=========================================="
echo "汇总"
echo "=========================================="
FAIL=0
for i in "${!NAMES[@]}"; do
  if [ "${CODES[$i]}" = "0" ]; then
    echo "通过  | ${NAMES[$i]}"
  elif [ "${CODES[$i]}" = "SKIP" ]; then
    echo "跳过  | ${NAMES[$i]}"
  else
    echo "失败  | ${NAMES[$i]}（退出码 ${CODES[$i]}）"
    FAIL=1
  fi
done
echo ""
if [ "$FAIL" = "0" ]; then
  echo "结论: 全部检查通过（或跳过）"
else
  echo "结论: 存在失败项，请修复后再开 PR"
fi
exit "$FAIL"
