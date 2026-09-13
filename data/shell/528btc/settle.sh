#!/bin/bash
# 配合计划任务：每小时 0/10/20/30/40/50 分各拉起一次
# 本脚本只跑约 9 分半，单次 PHP 最多 8 秒，避免卡死导致下一轮被跳过

PHP="/www/server/php/73/bin/php"
APP="/www/wwwroot/admin/stock.php"
LOCK="/tmp/settle_528btc.lock"

exec 9>"$LOCK"
if ! flock -n 9; then
    echo "settle already running, skip"
    exit 0
fi

end=$((SECONDS + 570))
while [ $SECONDS -lt $end ]; do
    if command -v timeout >/dev/null 2>&1; then
        timeout 8 "$PHP" "$APP" settle/run
    else
        "$PHP" "$APP" settle/run
    fi
    sleep 1
done
