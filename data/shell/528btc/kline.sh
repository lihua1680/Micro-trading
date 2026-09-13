#!/bin/bash
declare -a types
types[0]=5min
types[1]=15min
types[2]=30min
types[3]=1hour
types[4]=1day

for type in ${types[*]}
do
/www/server/php/73/bin/php /www/wwwroot/admin/stock.php Btc528/runLastData/type/${type}
done


#kline.sh 获取k线信息。每分钟执行一次
