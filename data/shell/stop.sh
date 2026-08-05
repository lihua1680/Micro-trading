#!/bin/bash
#!/bin/bash
ps -ef | grep bian | grep -v grep | awk '{print $2}' | xargs kill -9
ps -ef | grep aly | grep -v grep | awk '{print $2}' | xargs kill -9

