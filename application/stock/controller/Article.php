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

    /**
     * 采集东兴证券公司新闻
     * 来源: https://www.dxzq.net/main/gydx/gsdt/gsxw/index.shtml?catalogId=1,9,24,31
     */
    public function getArticleListByDxzq()
    {
        // content 字段过短会导致 Data too long，先扩为 MEDIUMTEXT
        $this->ensureArticleContentColumn();

        $maxPage = max(1, intval($this->request->param('page', 1)));
        $baseUrl = 'https://www.dxzq.net';
        $listUrl = $baseUrl . '/main/gydx/gsdt/gsxw/index.shtml?catalogId=1,9,24,31';
        $html = $this->fetchDxzqHtml($listUrl);
        if ($html === false) {
            echo '采集失败：无法获取列表页';
            die();
        }

        $items = $this->parseDxzqList($html);
        // 支持多页采集（可选 page=2..N）
        for ($p = 2; $p <= $maxPage; $p++) {
            // 站点分页走 Ajax，HTML 静态页仅首页；多页时尝试 index_{n}.shtml 常见形态
            $pageUrl = $baseUrl . '/main/gydx/gsdt/gsxw/index_' . $p . '.shtml?catalogId=1,9,24,31';
            $pageHtml = $this->fetchDxzqHtml($pageUrl);
            if ($pageHtml === false) {
                break;
            }
            $more = $this->parseDxzqList($pageHtml);
            if (empty($more)) {
                break;
            }
            $items = array_merge($items, $more);
        }

        $success = 0;
        $fail = 0;
        foreach (array_reverse($items) as $item) {
            $title = $item['title'];
            $check = Db::name('article')->where('title', '=', $title)->find();
            if ($check || empty($title)) {
                continue;
            }
            $detailUrl = $item['url'];
            if (strpos($detailUrl, 'http') !== 0) {
                $detailUrl = $baseUrl . $detailUrl;
            }
            $detail = $this->getDxzqDetail($detailUrl);
            $content = $detail['content'];
            $image = $detail['image'];
            // 补全相对图片路径
            $content = preg_replace('/(src=["\'])(\/upload\/)/i', '$1' . $baseUrl . '$2', $content);
            if (!empty($image) && strpos($image, 'http') !== 0) {
                $image = $baseUrl . $image;
            }
            $row = [
                'title' => $title,
                'summary' => mb_substr(trim(strip_tags($content)), 0, 120, 'UTF-8'),
                'image' => $image ?: '',
                'author' => 'dxzq.net',
                'content' => $content,
                'cteate_time' => time(),
                'pubtime' => $item['pubtime'],
            ];
            try {
                Db::name('article')->insert($row);
                $success++;
            } catch (\Exception $e) {
                $fail++;
                echo '写入失败：' . $title . ' => ' . $e->getMessage() . PHP_EOL;
            }
        }

        if ($success > 0) {
            echo '采集成功，数量：' . $success . ($fail > 0 ? '，失败：' . $fail : '');
            die();
        }
        echo $fail > 0 ? '采集失败，全部写入失败' : '采集完成，无新增数据';
        die();
    }

    /**
     * 确保 article.content 可存长文 HTML
     */
    private function ensureArticleContentColumn()
    {
        try {
            $prefix = config('database.prefix');
            $table = $prefix . 'article';
            $cols = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'content'");
            if (empty($cols)) {
                return;
            }
            $type = strtolower($cols[0]['Type']);
            // varchar / text 都可能不够，统一升到 mediumtext（约 16MB）
            if (strpos($type, 'mediumtext') === false && strpos($type, 'longtext') === false) {
                Db::execute("ALTER TABLE `{$table}` MODIFY COLUMN `content` MEDIUMTEXT NULL COMMENT '正文'");
                echo "已扩容 {$table}.content => MEDIUMTEXT" . PHP_EOL;
            }
        } catch (\Exception $e) {
            echo '扩容 content 字段失败（可手动执行 ALTER）：' . $e->getMessage() . PHP_EOL;
        }
    }

    private function fetchDxzqHtml($url)
    {
        try {
            $client = new Client([
                'timeout' => 30,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.dxzq.net/',
                    'Accept-Language' => 'zh-CN,zh;q=0.9',
                ],
            ]);
            $response = $client->request('GET', $url);
            $body = $response->getBody()->getContents();
            // 官网为 GBK
            if (!mb_check_encoding($body, 'UTF-8')) {
                $body = mb_convert_encoding($body, 'UTF-8', 'GBK');
            }
            return $body;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function parseDxzqList($html)
    {
        $items = [];
        if (preg_match_all('/<li>\s*<span\s+class="time">([^<]+)<\/span>\s*<a\s+href="([^"]+)"[^>]*title="([^"]*)"[^>]*>/isu', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $items[] = [
                    'pubtime' => trim($m[1]),
                    'url' => trim($m[2]),
                    'title' => html_entity_decode(trim($m[3]), ENT_QUOTES, 'UTF-8'),
                ];
            }
        }
        return $items;
    }

    private function getDxzqDetail($url)
    {
        $html = $this->fetchDxzqHtml($url);
        $content = '';
        $image = '';
        if ($html === false) {
            return ['content' => $content, 'image' => $image];
        }
        if (preg_match('/<div\s+class="article_cont"[^>]*>([\s\S]*?)<\/div>/iu', $html, $m)) {
            $content = trim($m[1]);
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/iu', $content, $imgMatch)) {
            $image = $imgMatch[1];
        }
        return ['content' => $content, 'image' => $image];
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