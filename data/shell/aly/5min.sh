#!/bin/bash
cd ../../

declare -a types
types[0]=5min
types[1]=30min
types[2]=1hour
types[3]=1day

while true
do

for type in ${types[*]}
do
 /www/server/php/72/bin/php stock.php Aly/runLastData/type/${type}  > /dev/null 2>&1 &
done

sleep 300s;
done