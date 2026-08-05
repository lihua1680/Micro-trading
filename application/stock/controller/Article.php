<?php

namespace app\stock\controller;

use GuzzleHttp\Client;
use think\Controller;
use html\simple_html_dom;
use think\Db;


class Article extends Controller
{
    public function getArticleList(){
        $client = new Client();
        $headers=[
            'Referer'=>'https://m.cn.investing.com/news/economy',
            'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'
        ];
        $response = $client->request('GET', 'https://cn.investing.com/news/cryptocurrency-news', [
            'headers' =>$headers
        ]);
        $html=$response->getBody()->getContents();
        $htmlObj = new simple_html_dom();   //工具类对象初始化
        $htmlObj->load($html);  // 从url中加载
        $arts=$htmlObj->find(".largeTitle>.articleItem");
        $data=array();
        foreach ($arts as $art){
            $detailUrl='https://cn.investing.com'.$art->find('a',0)->href;
            $title=$art->find('.textDiv>a',0)->innertext;
            $pubtime=$art->find('.textDiv>.articleDetails>.date',0)->innertext;
            $pubtime=str_replace("&nbsp;-&nbsp;","",$pubtime);
            $check=Db::name('article')->where('title','=',$title)->find();
            if(!$check&&!empty($title)){
                $summary=$art->find('.textDiv>p',0)->innertext;
                try{
                    if($art->find('.lazyload',0)!=null){
                        $image=$art->find('.lazyload',0)->getAttribute('data-src');
                    }
                }catch (\Exception $e){

                }
                $content=$this->getDetail($detailUrl);
                $data[]=[
                    'title'=>$title,
                    'summary'=>$summary,
                    'image'=>$image,
                    'author'=>'Investing.com',
                    'content'=>$content,
                    'cteate_time'=>time(),
                    'pubtime'=>$pubtime
                ];

            }

        }
        Db::name('article')->insertAll(array_reverse($data));
//        $art=$htmlObj->find(".largeTitle>.articleItem",0);
//        $detailUrl='https://cn.investing.com'.$art->find('a',0)->href;
//        //echo $detailUrl;
//        $content=$this->getDetail($detailUrl);
//        echo $content;
        echo '采集成功';
        die();
    }
    private function getDetail($url){
        $client = new Client();
        $headers=[
            'Referer'=>'https://cn.investing.com/news/cryptocurrency-news',
            'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'
        ];
        $response = $client->request('GET', $url, [
            'headers' =>$headers
        ]);
        $html=$response->getBody()->getContents();
        $htmlObj = new simple_html_dom();   //工具类对象初始化
        $htmlObj->load($html);
        $ps=$htmlObj->find('.articlePage>p');
        $content='';
        foreach ($ps as $p){
            if(strpos($p->innertext,"【本文来自英为财情") !== false){
                break;
            }
            $content.=$p->outertext;
        }
        return $content;
    }
    
    
    
    public function getArticleListByEastmoney(){
        $url = 'https://eminfo.eastmoney.com/pc_news/FastNews/GetInfoList?code=106&pageNumber=1&pagesize=20&condition=&r=';
        $response = file_get_contents($url);
        $newsData = json_decode($response,true);
        $dataList = [];
        if(!empty($newsData) && !empty($newsData['items'])){
            $newsItems = $newsData['items'];
            foreach($newsItems as $newsItem){
                $title = $newsItem['title'];
                $check=Db::name('article')->where('title','=',$title)->find();
                if(!$check && !empty($title)){
                    $newsId = $newsItem['code'];
                    $summary = $newsItem['digest'];
                    $image = empty($newsItem['imgUrl'])?'':$newsItem['imgUrl'];
                    
                    $pubtime = date('Y-m-d H:i:s',$newsItem['updateTime']/1000);
                    $detailUrl = "http://eminfo.eastmoney.com/PC_News/Detail/GetDetailContent?id={$newsId}&type=1";
                    $detail_res = file_get_contents($detailUrl);
                    $detail_res = json_decode($detail_res);
                    $detail_res = json_decode($detail_res,true);
                    $content=empty($detail_res['data']['content'])?'':$detail_res['data']['content'];
                    
                    $dataList[] = [
                        'title'=>$title,
                        'summary'=>$summary,
                        'image'=>$image,
                        'author'=>'eastmoney.com',
                        'content'=>$content,
                        'cteate_time'=>time(),
                        'pubtime'=>$pubtime
                    ];
                }
                
            }
        }
        if(!empty($dataList)){
            Db::name('article')->insertAll(array_reverse($dataList));
    //        $art=$htmlObj->find(".largeTitle>.articleItem",0);
    //        $detailUrl='https://cn.investing.com'.$art->find('a',0)->href;
    //        //echo $detailUrl;
    //        $content=$this->getDetail($detailUrl);
    //        echo $content;
            echo '采集成功，数量：'.count($dataList);
            die();
        }
        echo '采集失败，无可采集数据';
        die();
       
    }
    
function unicodeToCN($unicodeStr) {
    $pattern = '/(\\\\u(\\p{XDigit}{4}))/u';

    return preg_replace_callback($pattern, function($matches) {
        $ch = mb_convert_encoding(pack('H*', $matches[2]), 'UTF-8', 'UCS-2BE');
        return $ch;
    }, $unicodeStr);
}

function delHTMLTag($htmlStr) {
    $regEx_script = "<script[^>]*?>[\\s\\S]*?<\\/script>";
    $regEx_style = "<style[^>]*?>[\\s\\S]*?<\\/style>";
    $regEx_html = "<[^>]+>";

    $p_script = "/$regEx_script/"; $htmlStr = preg_replace($p_script, "", $htmlStr);
    $p_style = "/$regEx_style/"; $htmlStr = preg_replace($p_style, "", $htmlStr);
    $p_html = "/$regEx_html/"; $htmlStr = preg_replace($p_html, "", $htmlStr);

    return $htmlStr;
}

}