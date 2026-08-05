#!/bin/bash
cd ../../
while true
do


#processnum=`ps -ef | grep 'Zb/runStock' | grep -v 'grep' | grep -v 'vim'| wc -l`
#if [ $processnum -lt 1 ]
#then
/www/server/php/72/bin/php stock.php Aly/runStock  > /dev/null 2>&1 &
#fi

sleep 1s;
done