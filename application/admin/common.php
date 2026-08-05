<?php

use app\common\model\Category;
use fast\Form;
use fast\Tree;
use think\Db;
use think\Session;

if (!function_exists('build_select')) {

    /**
     * 生成下拉列表
     * @param string $name
     * @param mixed $options
     * @param mixed $selected
     * @param mixed $attr
     * @return string
     */
    function build_select($name, $options, $selected = [], $attr = [])
    {
        $options = is_array($options) ? $options : explode(',', $options);
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        return Form::select($name, $options, $selected, $attr);
    }
}

if (!function_exists('build_radios')) {

    /**
     * 生成单选按钮组
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @return string
     */
    function build_radios($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? key($list) : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::radio($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="radio">' . implode(' ', $html) . '</div>';
    }
}

if (!function_exists('build_checkboxs')) {

    /**
     * 生成复选按钮组
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @return string
     */
    function build_checkboxs($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? [] : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::checkbox($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="checkbox">' . implode(' ', $html) . '</div>';
    }
}


if (!function_exists('build_category_select')) {

    /**
     * 生成分类下拉列表框
     * @param string $name
     * @param string $type
     * @param mixed $selected
     * @param array $attr
     * @param array $header
     * @return string
     */
    function build_category_select($name, $type, $selected = null, $attr = [], $header = [])
    {
        $tree = Tree::instance();
        $tree->init(Category::getCategoryArray($type), 'pid');
        $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = $header ? $header : [];
        foreach ($categorylist as $k => $v) {
            $categorydata[$v['id']] = $v['name'];
        }
        $attr = array_merge(['id' => "c-{$name}", 'class' => 'form-control selectpicker'], $attr);
        return build_select($name, $categorydata, $selected, $attr);
    }
}

if (!function_exists('build_toolbar')) {

    /**
     * 生成表格操作按钮栏
     * @param array $btns 按钮组
     * @param array $attr 按钮属性值
     * @return string
     */
    function build_toolbar($btns = null, $attr = [])
    {
        $auth = \app\admin\library\Auth::instance();
        $controller = str_replace('.', '/', strtolower(think\Request::instance()->controller()));
        $btns = $btns ? $btns : ['refresh', 'add', 'edit', 'del', 'import'];
        $btns = is_array($btns) ? $btns : explode(',', $btns);
        $index = array_search('delete', $btns);
        if ($index !== false) {
            $btns[$index] = 'del';
        }
        $btnAttr = [
            'refresh' => ['javascript:;', 'btn btn-primary btn-refresh', 'fa fa-refresh', '', __('Refresh')],
            'add'     => ['javascript:;', 'btn btn-success btn-add', 'fa fa-plus', __('Add'), __('Add')],
            'edit'    => ['javascript:;', 'btn btn-success btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit'), __('Edit')],
            'del'     => ['javascript:;', 'btn btn-danger btn-del btn-disabled disabled', 'fa fa-trash', __('Delete'), __('Delete')],
            'import'  => ['javascript:;', 'btn btn-info btn-import', 'fa fa-upload', __('Import'), __('Import')],
        ];
        $btnAttr = array_merge($btnAttr, $attr);
        $html = [];
        foreach ($btns as $k => $v) {
            //如果未定义或没有权限
            if (!isset($btnAttr[$v]) || ($v !== 'refresh' && !$auth->check("{$controller}/{$v}"))) {
                continue;
            }
            list($href, $class, $icon, $text, $title) = $btnAttr[$v];
            //$extend = $v == 'import' ? 'id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"' : '';
            //$html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" ' . $extend . '><i class="' . $icon . '"></i> ' . $text . '</a>';
            if ($v == 'import') {
                $template = str_replace('/', '_', $controller);
                $download = '';
                if (file_exists("./template/{$template}.xlsx")) {
                    $download .= "<li><a href=\"/template/{$template}.xlsx\" target=\"_blank\">XLSX模版</a></li>";
                }
                if (file_exists("./template/{$template}.xls")) {
                    $download .= "<li><a href=\"/template/{$template}.xls\" target=\"_blank\">XLS模版</a></li>";
                }
                if (file_exists("./template/{$template}.csv")) {
                    $download .= empty($download) ? '' : "<li class=\"divider\"></li>";
                    $download .= "<li><a href=\"/template/{$template}.csv\" target=\"_blank\">CSV模版</a></li>";
                }
                $download .= empty($download) ? '' : "\n                            ";
                if (!empty($download)) {
                    $html[] = <<<EOT
                        <div class="btn-group">
                            <button type="button" href="{$href}" class="btn btn-info btn-import" title="{$title}" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="{$icon}"></i> {$text}</button>
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" title="下载批量导入模版">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">{$download}</ul>
                        </div>
EOT;
                } else {
                    $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="' . $icon . '"></i> ' . $text . '</a>';
                }
            } else {
                $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $text . '</a>';
            }
        }
        return implode(' ', $html);
    }
}

if (!function_exists('build_heading')) {

    /**
     * 生成页面Heading
     *
     * @param string $path 指定的path
     * @return string
     */
    function build_heading($path = null, $container = true)
    {
        $title = $content = '';
        if (is_null($path)) {
            $action = request()->action();
            $controller = str_replace('.', '/', request()->controller());
            $path = strtolower($controller . ($action && $action != 'index' ? '/' . $action : ''));
        }
        // 根据当前的URI自动匹配父节点的标题和备注
        $data = Db::name('auth_rule')->where('name', $path)->field('title,remark')->find();
        if ($data) {
            $title = __($data['title']);
            $content = __($data['remark']);
        }
        if (!$content) {
            return '';
        }
        $result = '<div class="panel-lead"><em>' . $title . '</em>' . $content . '</div>';
        if ($container) {
            $result = '<div class="panel-heading">' . $result . '</div>';
        }
        return $result;
    }
    
    function getQrstr($str, $leftStr, $rightStr)
    {
        $left = strpos($str, $leftStr);
        $right = strpos($str, $rightStr,$left);
        if($left < 0 or $right < $left) return '';
        return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
    }
    
    
    /**
     * 获取房间号
     * @return string
     */
    function getRoomId() {
        //return Session::get('admin.room_id');
        return 1;
    }
    
    /**
     * 获取游戏tabs
     * @param unknown $tab
     * @param string $url
     * @return string
     */
    function build_game_tabs($tab, $url = '') {
        $gameList = Db::name('product')->where(array('status'=>1))->order('rank desc')->select();
        $result = '<div class="row game_tab_list">';
        if (!$url) {
            $url = think\Request::instance()->controller().'/'.think\Request::instance()->action();
        }
        foreach ($gameList as $key=>$val) {
            $on = ($val["code"] == $tab) ? ' class="on" ':'';
            $result .= '<div class="col-md-1 col-sm-2 col-xs-3 game_tab_btn"><a href="'.url($url, array('type'=>$val['code'])).'"'.$on.'>'.$val["code"].'/'.$val["title"].'</a></div>';
        }
        $result .= '</div><div class="game_tab_list_line"></div>';
        return $result;
    }
    
    
    /**
     * 获取时间tabs
     * @param unknown $tab
     * @param string $url
     * @return string
     */
    function build_time_tabs($tab, $url = '') {
        $gameList = getGameConfig(true, false);
        $timeList = array('today'=>'今天', 'yesterday'=>'昨天', 'week'=>'本周', 'lastweek'=>'上周', 'month'=>'本月', 'lastmonth'=>'上月');
        $result = '<div class="row game_tab_list">';
        if (!$url) {
            $url = think\Request::instance()->controller().'/'.think\Request::instance()->action();
        }
        foreach ($timeList as $key=>$val) {
            $on = ($key == $tab) ? ' class="on" ':'';
            $result .= '<div class="col-md-1 col-sm-2 col-xs-3 game_tab_btn"><a href="'.url($url, array('type'=>$key)).'"'.$on.'>'.$val.'</a></div>';
        }
        $result .= '</div><div class="game_tab_list_line"></div>';
        return $result;
    }
    
    
}
