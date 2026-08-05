#!/bin/bash
int=1;
while(( int <= 600 ));
do 
    /www/server/php/73/bin/php /www/wwwroot/admin/stock.php settle/run
    let "int++";
    sleep 1;
done
