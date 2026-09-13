<?php

namespace app\admin\model;

use think\Model;

class Article extends Model
{
    protected $connection = 'database';
    protected $name = 'article';
    protected $autoWriteTimestamp = false;
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;
}
