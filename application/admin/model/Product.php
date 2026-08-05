<?php

namespace app\admin\model;

use think\Model;


class Product extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'product';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
        'rtime_text'
    ];
    

    



    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['rtime']) ? $data['rtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function producttype()
    {
        return $this->belongsTo('ProductType', 'cid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
