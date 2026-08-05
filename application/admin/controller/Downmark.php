<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Session;
use think\Db;
/**
 * 上下分管理
 *
 * @icon fa fa-circle-o
 */
class Downmark extends Backend
{
    
    /**
     * Upmark模型对象
     * @var \app\admin\model\Upmark
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Upmark;

    }
    
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            ->with(['user'])->where($where)->where(['type'=>'下分', 'upmark.real'=>1])
            ->order($sort, $order)
            ->count();
            
            $list = $this->model
            ->with(['user'])->where($where)->where(['type'=>'下分', 'upmark.real'=>1])
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
            
            $list = collection($list)->toArray();
            foreach ($list as $key=>$val) {
               $tmp = json_decode($val['pay_info'], true);
               $val['account'] = $tmp['account'];
               $val['user_name'] = $tmp['user_name'];
               $val['bank_name'] = $tmp['bank_name'];
               $val['user_remark'] = $tmp['user_remark'];
               
               $val['isagent'] = get_user_byid($val['user_id'], 'isagent');
               if ($val['pay_type'] == 'usdt-trc20' || $val['pay_type'] == 'usdt-erc20' ){
                   $day_hui_lv = cache('day_hui_lv');
                   if (empty($day_hui_lv)){
                       $day_hui_lv = 7.24;
                   }
                   $val['act_money'] = bcdiv($val['money'],$day_hui_lv,2);
                   $pay_info = json_decode($val['pay_info'],true);
                   $val['account'] = $pay_info['usdt_url'];
                   $val['num'] = $pay_info['num'] ?:'';
               }else{
                   $val['num'] = '';
               }
               if ($val["pay_type"] == "人工取出") {
                   $val["pay_type"] = "网银出金";
               }
               $list[$key] = $val;
            }
            
            $result = array("total" => $total, "rows" => $list);
            
            return json($result);
        }
        $this->model->where(array('view_status'=>0))->update(array('view_status'=>1));
        $this->view->assign('voice', Db::name('config')->where(array('name'=>'withdraw_voice'))->value('value'));
        return $this->view->fetch();
    }

    public function setVoice() {
        $status = Db::name('config')->where(array('name'=>'withdraw_voice'))->value('value');
        Db::name('config')->where(array('name'=>'withdraw_voice'))->setField('value', $status ? 0 : 1);
        $this->success();
    }

    /**
     * 查看转账记录
     */
    public function viewhd()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $ids = $this->request->get("ids", '');
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        $user_id=$row['user_id'];
        $userModal = new \app\admin\model\User;
        //$user = $userModal->find($user_id);
        //$id = $this->request->get("id", '');
        $this->view->assign("check_date",date('Y/m/d', strtotime($row['check_time'])));
        $this->view->assign("pay_info", json_decode($row['pay_info'], true));
        $this->view->assign("mark", $row);
        return $this->view->fetch();
    }
    /**
     * 查看转账记录
     */
    public function viewhd_c()
    {
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
            if ($val[0] == 'user_id') {
                $val[0] = 'user_id|real_name|last_login_ip';
            }
            if ($val[0] == 'status'){
                $val[0] = 'upmark.status';
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
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    
                     
                    $userModal = new \app\admin\model\User;
                    $user = $userModal->find($row['user_id']);
                    $attach_text = $user['attach_text'];
                    $attach_text_arr = json_decode($attach_text,true);
                   
                    $attach_text_arr['bank_card']['account'] = $params['pay_info']['account'];
                    $attach_text_arr['bank_card']['user_name'] = $params['pay_info']['user_name'];
                    $attach_text_arr['bank_card']['bank_name'] = $params['pay_info']['bank_name'];
                    $user->attach_text = json_encode($attach_text_arr);
                    $user->save();
                    
                    $params['pay_info'] = json_encode($params['pay_info']);
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
         
        $row['pay_info'] = json_decode($row['pay_info'], true);
        
        $this->view->assign("row", $row);
        
        return $this->view->fetch();
    }
    
    
    /**
     * 批量更新
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values || $this->auth->isSuperAdmin()) {
                    $adminIds = $this->getDataLimitAdminIds();
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $count = 0;
                    Db::startTrans();
                    try {
                        $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                        foreach ($list as $index => $item) {
                            if ($item->status < 1) {
                                $res = $this->setUpmark($item->id, $values['status']);
                                $values['check_account'] = Session::get('admin.username').'(管理编号:'.Session::get('admin.id').')';
                                $values['check_time'] = date('Y-m-d H:i:s');
                                $count += $item->allowField(true)->isUpdate(true)->save($values);
                            }
                            
                        }
                        Db::commit();
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    if ($count) {
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    
    /**
     * 审核
     */
    public function check($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            
            $params = $this->request->post("row/a");
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
                    
                    if ($params['status'] > 0) {
                        $res = $this->setUpmark($ids, $params['status']);
                        $params['check_account'] = Session::get('admin.username').'(管理编号:'.Session::get('admin.id').')';
                        $params['check_time'] = date('Y-m-d H:i:s');
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
        $row['pay_info'] = json_decode($row['pay_info'], true);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
    public function cx($ids) {
        $result = Db::name('upmark')->where(array('id'=>$ids))->delete();
        if ($result) {
             $this->success();
        } else {
            $this->error("操作失败");
        }
    }
    
    public function change_status($ids) {
        $upmark = Db::name('upmark')->where(array('id'=>$ids))->find();
        if ($upmark['pay_type'] == 'USDT') {
            $field = 'usdt';
        } else {
            $field = 'money';
        }
         
        if ($upmark['status'] == 1) {
            Db::startTrans();
            try{
                Db::name('upmark')->where(array('id'=>$ids))->update(["status" => 2]);
                setBalnce($upmark, 1, $field);
                Db::commit();
                $this->success();
            }catch(Exception $e){
                 Db::rollback();
                 $this->error("操作失败");
            }
        } else {
            Db::startTrans();
            try{
                Db::name('upmark')->where(array('id'=>$ids))->update(["status" => 1]);
                setBalnce($upmark, 0, $field);
                Db::commit();
                $this->success();
            }catch(Exception $e){
                 Db::rollback();
                 $this->error("操作失败");
            }
        }
    }
    
    private function setUpmark($ids, $status) {
        $upmark = Db::name('upmark')->where(array('id'=>$ids))->find();
        if ($upmark['status'] != $status) {
            if ($status == 2) {
                $mark['user_id'] = $upmark['user_id'];
                $mark['room_id'] = $upmark['room_id'];
                $mark['type'] = '退款';
                $mark['money'] = $upmark['money'];
                $mark['real'] = 1;
                if ($upmark['pay_type'] == 'USDT') {
                    $mark['balance_before'] = get_user_byid($upmark['user_id'], 'usdt');
                    $mark['balance_later'] = $mark['balance_before'] + $upmark['money'];
                    $mark['content'] = '下分编号为'.$upmark['id'].'审核未通过，退款USDT数量'.$upmark['money'];
                    $field = 'usdt';
                } else {
                    $mark['balance_before'] = get_user_byid($upmark['user_id'], 'money');
                    $mark['balance_later'] = $mark['balance_before'] + $upmark['money'];
                    $mark['content'] = '下分编号为'.$upmark['id'].'审核未通过，退款金额'.$upmark['money'];
                    $field = 'money';
                }
                $mark['order_sn'] = $upmark["order_sn"];
                addMark($mark);
                setBalnce($upmark, 1, $field);
                return $mark;
            } else {
                // 写消息 消息类型 1 余额宝收益 2下单 3结算 4提现，根据这个和是否已读做图标显示
                Db::name('message')->insert([
                    'money' => $upmark['money'],
                    'type' => 4,
                    'is_read' => 2,
                    'user_id' => $upmark['user_id'],
                    'time' => date('Y-m-d H:i:s', time()),
                    'order_sn' => $upmark["order_sn"]
                ]);
            }
        }
        return false;
        
    }
    
    
    public function close($id){
        if(Db::name('upmark')->where(array('id'=>$id))->update(['statuss'=>1])){
            $this->success();
        }
        $this->error();
    }

}
