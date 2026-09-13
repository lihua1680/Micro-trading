<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;
use think\Exception;

/**
 * 关于我们
 *
 * @icon fa fa-info-circle
 */
class About extends Backend
{
    public function index()
    {
        $row = Db::name('config')->where('name', 'company_desc')->find();
        if (!$row) {
            try {
                Db::name('config')->insert([
                    'name'  => 'company_desc',
                    'group' => 'basic',
                    'title' => '关于我们',
                    'tip'   => '前端关于我们展示内容，支持HTML',
                    'type'  => 'editor',
                    'value' => '',
                    'content' => '',
                    'rule'  => '',
                    'extend'=> '',
                ]);
            } catch (Exception $e) {
                // ignore duplicate
            }
            $row = Db::name('config')->where('name', 'company_desc')->find();
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $content = isset($params['company_desc']) ? $params['company_desc'] : '';
            try {
                Db::name('config')->where('name', 'company_desc')->update(['value' => $content]);
                $this->refreshSiteFile();
                $this->success('保存成功');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $this->view->assign('row', ['company_desc' => $row ? $row['value'] : '']);
        return $this->view->fetch();
    }

    protected function refreshSiteFile()
    {
        $config = [];
        $list = Db::name('config')->select();
        foreach ($list as $item) {
            $value = $item['value'];
            if (in_array($item['type'], ['selects', 'checkbox', 'images', 'files'])) {
                $value = explode(',', $value);
            }
            if ($item['type'] === 'array') {
                $value = (array)json_decode($item['value'], true);
            }
            $config[$item['name']] = $value;
        }
        file_put_contents(APP_PATH . 'extra' . DS . 'site.php', '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        Config::set('site', $config);
    }
}
