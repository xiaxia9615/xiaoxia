#!/bin/bash
RED=0; YELLOW=0
ok(){   echo -e "\033[32m[绿] $1\033[0m"; }
warn(){ echo -e "\033[33m[黄] $1\033[0m"; YELLOW=1; }
fail(){ echo -e "\033[31m[红] $1\033[0m"; RED=1; }

echo "=== 1.测试 ==="
OUT=$(php artisan test 2>&1 | tail -3)
echo "$OUT" | grep -q "failed" && fail "测试有失败" || ok "测试通过"
CNT=$(echo "$OUT" | grep -oE "[0-9]+ passed" | grep -oE "[0-9]+")
[ -z "$CNT" ] && fail "无法解析测试数"
[ -n "$CNT" ] && { [ "$CNT" -lt 66 ] && fail "测试数 $CNT < 66" || ok "测试数 $CNT >= 66"; }

echo "=== 2.依赖漏洞 ==="
composer audit 2>&1 | grep -qE "high|critical" && fail "依赖有高危" || ok "无高危"

echo "=== 3.越权查询 ==="
HIT=$(grep -rnE "::find\(|::findOrFail\(" app/Modules/*/Http/Controllers/ 2>/dev/null)
[ -z "$HIT" ] && ok "Controller无裸查" || fail "Controller裸查: $HIT"

echo "=== 4.无超时HTTP ==="
HIT=""
for f in $(grep -rlE "Http::" app/ 2>/dev/null); do
  grep -qE "timeout|connectTimeout" "$f" || HIT="$HIT $f"
done
[ -z "$HIT" ] && ok "HTTP全带超时" || fail "无超时:$HIT"

echo "=== 5.调试残留 ==="
HIT=$(grep -rnE "\bdd\(|\bvar_dump\(|\bdump\(" app/ routes/ 2>/dev/null)
[ -z "$HIT" ] && ok "无调试残留" || fail "残留: $HIT"

echo "=== 6.工作区 ==="
[ -z "$(git status --short)" ] && ok "工作区干净" || warn "有未提交改动"

echo "=== 7.commit诚实度(人工) ==="
git log --oneline -1 --stat
warn "人工核对 message 与 diff 一致性"

echo ""
[ $RED -eq 0 ] && echo -e "\033[32m总判定: 通过\033[0m" || echo -e "\033[31m总判定: 有红, 阻断\033[0m"
exit $RED
