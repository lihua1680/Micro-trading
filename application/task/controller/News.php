<?php
//require 'extend/querylist/vendor/autoload.php';
use think\Controller;
//use QL\QueryList;
class News extends Controller
{
    public function getNewsList(){
        $data = QueryList::get('https://m.cn.investing.com/news/cryptocurrency-news')
            ->rules([
                'image'=>array('article>div>img','src'),
                'a'=>array('article>div>img','href')
            ])
            ->query()->getData();
        print_r($data->all());
        die();
    }
}