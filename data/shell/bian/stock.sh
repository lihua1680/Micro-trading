#!/bin/bash
int=1;
while(( int <= 20 ));
do 
    /www/server/php/72/bin/php /www/wwwroot/gtja.jzhryk.com/stock.php Bian/runStock
    # /www/server/php/72/bin/php /www/wwwroot/wp.bkex.info/stock.php Bian/getUsdt
    let "int++";
    sleep 3;
done