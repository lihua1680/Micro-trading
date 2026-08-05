<?php

namespace app\admin\behavior;

class AdminLog
{
    public function run(&$params)
    {
        $action = request()->action();
        if (in_array($action, array('index', 'add', 'del', 'multi', 'edit', 'score', 'chat', 'report', 'login', 'detail', 'share', 'cache')))
            \app\admin\model\AdminLog::record();
        
    }
}
