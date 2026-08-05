#!/bin/bash
# 计划任务中每三十秒执行一次 获取1分钟k线
/www/server/php/72/bin/php /www/wwwroot/gtja.jzhryk.com/stock.php Bian/runLastData/type/1min
# int=1;
# while(( int <= 2 ));
# do 
#     /www/server/php/72/bin/php /www/wwwroot/www.pingan002.xyz/stock.php Bian/runLastData/type/1min
#     let "int++";
#     sleep 30;
# done
