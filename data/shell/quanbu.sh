# 仅做记录
nohup sh bian/settle.sh  >/dev/null 2>&1 &
nohup sh bian/stock.sh  >/dev/null 2>&1 &
nohup sh bian/kline.sh  >/dev/null 2>&1 &  
nohup sh bian/1min.sh  >/dev/null 2>&1 &

nohup sh aly/stock.sh  >/dev/null 2>&1 &
nohup sh aly/kline.sh  >/dev/null 2>&1 &
nohup sh aly/5min.sh  >/dev/null 2>&1 &

ps -ef | grep bian | grep -v grep | awk '{print $2}' | xargs kill -9
ps -ef | grep aly | grep -v grep | awk '{print $2}' | xargs kill -9