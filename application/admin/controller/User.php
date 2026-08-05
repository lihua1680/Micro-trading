<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Session;
use think\Db;
use fast\Random;
use think\Config;
use think\Cache;
/**
 * 会员列管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    
    /**
     * User模型对象
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }
    
    /**
     * 分数
     */
    public function credit_score($ids)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            
            if ($params) {
                if($params['value'] <= 0){
                    $this->error('数值操作必须大于0');
                }
                $info = $row->toArray();
                $result = false;
                if (in_array($params['optype'], array('增加信誉分', '扣除信誉分'))) {
                    if ($params['optype'] == '增加信誉分') {
                        if (($info['credit_score'] + $params['value']) > 100){
                            $maxAdd = 100 - $info['credit_score'];
                            $this->error("信誉分最多100，最多可增加[ {$maxAdd} ]点信誉分");
                        }
                       $op = 1;
                    } else {
                       if (($info['credit_score'] - $params['value']) < 0){
                           $maxDel = $info['credit_score'];
                           $this->error("信誉分最少0，最多可扣除[ {$maxDel} ]点信誉分");
                       }
                          
                       $op = 0;
                    }
                   
                    
                    setCreditScore(array('user_id'=>$info['id'], 'credit_score'=>$params['value']), $op);
                    $result = true;
                }
                
                
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
                
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 实名认证
     */
    public function auth($ids)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
           
            if ($params) {
                
                $info = $row->toArray();
                
                $result = Db::name('user')->where(array('id'=>$info['id']))->update($params);

                if ($result) {
                    $this->success();
                } else {
                    $this->error('未作任何修改');
                }
                
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 分数
     */
    public function freeze_funds($ids)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            
            if ($params) {
                if($params['value'] <= 0){
                    $this->error('金额必须大于0');
                }
                $info = $row->toArray();
                $result = false;
                if (in_array($params['optype'], array('冻结', '取消冻结'))) {
                    if ($params['optype'] == '取消冻结') {
                       $op = 1;
                      
                    } else {
                       if ($info['money'] < $params['value'])
                          $this->error('该用户余额不足');
                       $op = 0;
                    }
                    
                    setBalnceWithFreezeFunds(array('user_id'=>$info['id'], 'money'=>$params['value']), $op);
                    $result = true;
                }
                
                
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
                
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
    
     /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $active = 1;
        $params = $this->request->param();
        
        
        $condition['real'] = 1; 
        if (isset($params['real'])) {
            $active = 2;
            $condition['real'] = $params['real'];
        }
        if (isset($params['saylimit'])) {
            $active = 3;
            $condition['say_limit'] = $params['saylimit'];
        }
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)->where($condition)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)->where($condition)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>&$val) {
                $login_place = Cache::get($val['last_login_ip']);
                if(empty($login_place)){
                    $url = 'https://apis.map.qq.com/ws/location/v1/ip?key=JF3BZ-QBBK7-EKQXU-HO2KP-GONCQ-3VB2F&ip='.$val['last_login_ip'];
	                $ch = curl_init();//初始化curl
	                curl_setopt($ch, CURLOPT_URL, $url);//设置请求地址
	                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回结果为字符串
	                curl_setopt($ch, CURLOPT_HEADER, 0);//设置请求头部不输出
	                $result = curl_exec($ch);//执行curl请求
	                
	                curl_close($ch);//关闭curl
	                $result = json_decode($result,true);
	                $login_place = '未知地址';
	                if(!empty($result) && $result['status'] == 0){
	                    $ad_info = $result['result']['ad_info'];
	                    $login_place = $ad_info['nation'].$ad_info['province'].$ad_info['city'];
	                    Cache::set($val['last_login_ip'],$login_place,0);
	                }
                }
                $val['last_login_ip'] .= "<p>".$login_place."</p>";
                
                $val['is_online'] = (time() - $val['rtime']) < 60 ? 1 : 0;
                if ($val['agent'])
                    $val['agent'] = $val['agent'].'('.get_user_byid($val['agent'], 'username').')';
                $attach_text = $val['attach_text'];
                
                if ($attach_text) {
                    $pay = json_decode($attach_text, true);
 
                    if ($pay['bank_card']['account'] && $pay['bank_card']['user_name'] && $pay['bank_card']['bank_name']) {
                        $val['attach_text'] = 1;
                    }
                } else {
                    $val['attach_text'] = 0;
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign('active', $active);
        return $this->view->fetch();
    }
    
    
    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed   $searchfields   快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);
        if (isset($filter['btime'])) {
            unset($filter['btime']);
            unset($op['btime']);
        }
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch) {
            if (!empty($this->model)) {
                $name = \think\Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
                $tableName = $name . '.';
            }
            $sortArr = explode(',', $sort);
            foreach ($sortArr as $index => & $item) {
                $item = stripos($item, ".") === false ? $tableName . trim($item) : $item;
            }
            unset($item);
            $sort = implode(',', $sortArr);
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$tableName . $this->dataLimitField, 'in', $adminIds];
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        
        foreach ($filter as $k => $v) {
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $tableName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            
            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $where[] = "FIND_IN_SET('{$v}', " . ($relationSearch ? $k : '`' . str_replace('.', '`.`', $k) . '`') . ")";
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        
        //id|real_name|ip
        foreach ($where as $key=>&$val) {
            if ($val[0] == 'account') {
                $val[0] = 'account|id|real_name|last_login_ip';
            }
        }
        
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }
    
    
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $ucount = $this->model->where('account',$params['account'])->count();
                if($ucount != 0 ){
                    $this->error('该用户已存在');
                    return ;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    if ($params['passwd']) {
                        $params['passwd'] = fh_encoding($params['passwd']);
                    }
                    $params['username'] = $params['account'];
                    $params['phone'] = $params['account'];
                    $result = $this->model->allowField(true)->save($params);
                    
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('userLevel', getUserLevel());
        return $this->view->fetch();
    }
    
    /**
     * 分数
     */
    public function score($ids)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $info = $row->toArray();
                $result = false;
                if (in_array($params['optype'], array('网银入金','系统存入', '网银出金', '赠送积分'))) {
                    
                    if ($info['real']) {
                        $mark['user_id']        = $info['id'];
                        $mark['type']           = $params['optype'];
                        $mark['money']          = $params['value'];
                        $mark['balance_before'] = get_user_byid($info['id'], 'money');
                        
                        if (in_array($params['optype'], array('网银入金', '赠送积分','系统存入'))) {
                           $op = 1;
                           $type = '上分';
                           $mark['balance_later'] = $mark['balance_before'] + $params['value'];
                        } else {
                           if ($info['money'] < $params['value'])
                              $this->error('该用户余额不足');
                           $mark['balance_later'] = $mark['balance_before'] - $params['value'];
                           $op = 0;
                           $type = '下分';
                        }
                        
                        
                        $mark['real'] = 1;
                        $mark['content'] = Session::get('admin.username').'(管理编号:'.Session::get('admin.id').')'.$params['optype'].$params['value'] . ($params['remark']?',备注:'.$params['remark']:'');
                        $mark["order_sn"] = "CRX" . generateRandomString();
                        
                        $upmark['username'] = $info['username'];
                        $upmark["order_sn"] = $mark["order_sn"];
                       
                        $upmark['user_id'] = $info['id'];
                        $upmark['agent'] = $info['agent'];
                        $upmark['type'] = $type;
                        $upmark['status'] = 1;
                        $upmark['pay_type'] = $params['optype'];
                        $upmark['money'] = $params['value'];
                        $upmark['balance_before'] = $mark['balance_before'];
                        $upmark['balance_later'] = $mark['balance_later'];
                        $upmark['remark'] = $mark['content'];
                        $upmark['display'] = 0;
                        $upmark['real'] = $info['real'];
                        $upmark['check_account'] = Session::get('admin.username').'(管理编号:'.Session::get('admin.id').')';
                        $upmark['check_time'] = date('Y-m-d H:i:s');
                        if (in_array($params['optype'], array('网银入金', '网银出金','系统存入')))
                            addUpmark($upmark);
                        addMark($mark);

                        if (in_array($params['optype'], array('网银入金', '赠送积分','系统存入'))) {
                            Db::name('message')->insert([
                                'money' => $upmark['money'],
                                'type' => 5,
                                'is_read' => 2,
                                'user_id' => $info['id'],
                                'time' => date('Y-m-d H:i:s', time()),
                                'order_sn' => $upmark["order_sn"]
                            ]);
                        }
                    }
                    setBalnce(array('user_id'=>$info['id'], 'money'=>$params['value']), $op);
                    $result = true;
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if ($params['passwd']) {
                    $params['passwd'] = fh_encoding($params['passwd']);
                } else {
                    unset($params['passwd']);
                }
                if ($params['mpasswd']) {
                   $params['mpasswd'] = fh_encoding($params['mpasswd']);
                } else {
                    unset($params['mpasswd']);
                }
                if (isset($params['attach_text']['bank_card'])) {
                    
                    // 个人修改银行卡联动修改提现记录的银行卡
                    $pay_info = [
                        "user_name"=>$params['attach_text']['bank_card']['user_name'],
                        "account"=>$params['attach_text']['bank_card']['account'],
                        "bank_name"=>$params['attach_text']['bank_card']['bank_name'],
                    ];
                
                    $res = (new \app\admin\model\Upmark)->where('user_id',$row['id'])->update([
                        'pay_info'=>json_encode($pay_info)
                    ]);
                    
                    
                        
                        
                    $params['attach_text'] = json_encode($params['attach_text']);
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['attach_text'] = json_decode($row['attach_text'], true);
        $this->view->assign("row", $row);
        $this->assign('userLevel', getUserLevel());
        return $this->view->fetch();
    }
    
    
    /**
     * 详情
     */
    public function detail($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        $row = $row->toArray();
        $row['attach_text'] = json_decode($row['attach_text'], true);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
    /**
     * 详情
     */
    public function chat($ids)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $content = $params['content'];
            if ($content) {
                $message['user_id'] = 'system';
                $message['username'] = '客服';
                $message['headimg'] = Config::get('site.robot_img');
                $message['to_id'] = $ids;
                $message['type'] = 'S1';
                $message['content'] = $content;
                if (addMessage($message))
                    $this->success('私信发送成功');
                else $this->error('私信发送失败');
            }
            
        }
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }
    
    
    /**
     * 设置真假人
     */
    public function check($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($row) {
            $params = $this->request->param();
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 上传文件
     */
    public function uploadimg()
    {
        
        $file = $this->request->file('file');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }
        
        //判断是否已经存在附件
        $sha1 = $file->hash();
        
        $upload = Config::get('upload');
        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';
        
        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);
        
        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
                )
            ) {
                $this->error(__('Uploaded file format is limited'));
            }
            $replaceArr = [
                '{year}'     => date("Y"),
                '{mon}'      => date("m"),
                '{day}'      => date("d"),
                '{hour}'     => date("H"),
                '{min}'      => date("i"),
                '{sec}'      => date("s"),
                '{random}'   => Random::alnum(16),
                '{random32}' => Random::alnum(32),
                '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
                '{suffix}'   => $suffix,
                '{.suffix}'  => $suffix ? '.' . $suffix : '',
                '{filemd5}'  => time().mt_rand(10000,99999),
            ];
            $savekey = $upload['savekey'];
            $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);
            
            $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
            $fileName = substr($savekey, strripos($savekey, '/') + 1);
            //
            $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
            if ($splInfo) {
                $imagewidth = $imageheight = 0;
                if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                    $imgInfo = getimagesize($splInfo->getPathname());
                    $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                    $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
                }
               
                
                $content = $uploadDir . $splInfo->getSaveName();
                $message['type'] = 'U2';
                $message['to_id'] = 'system';
                $message['content'] = $content;
                $message['addtime'] = date('Y-m-d H:i:s');
                $message['ctype'] = 1;
                $message['view_status'] = 0;
                
                // Db::name('message')->insert($message);
                
                return json(array('code'=> 0,'data'=>$message));
                
            } else {
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
            
    }
}
