#!/usr/bin/env bash
FAIL=0

# 1. 禁止外链图片
if grep -rnE "https?://.*\.(png|jpg|jpeg|svg|webp|gif)" --include="*.ts" --include="*.tsx" src/ 2>/dev/null; then
  echo "FAIL: 发现外链图片"; FAIL=1
fi

# 2. 禁止私有 scope 依赖（package.json + lock 都查）
if grep -E '"@(qmuse|alipay|antgroup)/' package.json package-lock.json 2>/dev/null; then
  echo "FAIL: 发现私有 scope 依赖"; FAIL=1
fi

# 3. 禁止外部运行时注入
if grep -rnE "window\.__[A-Z]" --include="*.ts" --include="*.tsx" src/ 2>/dev/null; then
  echo "FAIL: 发现外部运行时注入"; FAIL=1
fi

# 4. 疑似硬编码密钥（排除类型声明和 env 引用）
if grep -rnE "(api[_-]?key|secret|token)\s*[:=]\s*['\"][^'\"]{16,}" --include="*.ts" --include="*.tsx" src/ 2>/dev/null \
   | grep -vE "env\(|:\s*string|interface |type " ; then
  echo "FAIL: 疑似硬编码密钥"; FAIL=1
fi

if [ "$FAIL" -eq 0 ]; then
  echo "OK: 无外部依赖残留"
fi
exit $FAIL
