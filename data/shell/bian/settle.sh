#!/bin/bash
int=1;
while(( int <= 600 ));
do 
    /www/server/php/72/bin/php /www/wwwroot/gtja.jzhryk.com/stock.php settle/run
    let "int++";
    sleep 1;
done
