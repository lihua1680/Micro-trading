#!/bin/bash
declare -a types
types[0]=5min
types[1]=30min
types[2]=1hour
types[3]=1day

for type in ${types[*]}
do
/www/server/php/73/bin/php /www/wwwroot/admin/stock.php Btc528/runLastData/type/${type}
done


#kline.sh 获取k线信息。每分钟执行一次