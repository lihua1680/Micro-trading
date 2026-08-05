#!/bin/bash
cd ../../
while true
do

#processnum=`ps -ef | grep 'Aly/runLastData/type/1min' | grep -v 'grep' | grep -v 'vim'| wc -l`
#if [ $processnum -lt 1 ]
#then
/www/server/php/72/bin/php stock.php Aly/runLastData/type/1min  > /dev/null 2>&1 &
#fi

sleep 20s;
done