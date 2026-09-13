#!/bin/bash
declare -a types
types[0]=5min
types[1]=15min
types[2]=30min
types[3]=1hour
types[4]=1day

for type in ${types[*]}
do
/www/server/php/72/bin/php /www/wwwroot/gtja.jzhryk.com/stock.php Bian/runLastData/type/${type}
done

#kline.sh 获取k线信息。每分钟执行一次
