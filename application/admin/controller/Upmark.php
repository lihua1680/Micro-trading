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
class Upmark extends Backend
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
            ->with(['user'])->where($where)->where(['type'=>'上分', 'upmark.real'=>1])
            ->order($sort, $order)
            ->count();
            
            $list = $this->model
            ->with(['user'])->where($where)->where(['type'=>'上分', 'upmark.real'=>1])
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
            
            $list = collection($list)->toArray();

            foreach ($list as $key=>&$val) {
                $user = get_user_byid($val['user_id']);
                $val['uremark'] = $user['remark'];
                $val['real_name'] = $user['real_name'];
                if ($val['pay_type'] == 'USDT') {
                    $val['pay_type'] = $val['pay_type'].'('.$val['address_type'].')';
                }
                
                if ((strtotime($val['time']) + (10*60)) > time()){
                    $val['is_show'] = 0;
                }else{
                    $val['is_show'] = 1;
                }
                
                if ($val["pay_type"] == "人工存入") {
                    $val["pay_type"] = "网银入金";
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->model->where(array('view_status'=>0))->update(array('view_status'=>1));
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
                    
                    if ($params['status'] > 0) {
                        if ($params['status'] == 1) {
                            $res = $this->setUpmark($ids, $params['status']);
                           if ($res) {
                               $params['balance_before'] = $res['balance_before'];
                               $params['balance_later'] = $res['balance_later'];
                           }
                        }
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
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
                    
                    if ($params['status'] > 0) {
                        if ($params['status'] == 1) {
                            $res = $this->setUpmark($ids, $params['status']);
                            if ($res) {
                                $params['balance_before'] = $res['balance_before'];
                                $params['balance_later'] = $res['balance_later'];
                            }
                        }
                        $params['check_account'] = Session::get('admin.username').'(管理编号:'.Session::get('admin.id').')';
                        $params['check_time'] = date('Y-m-d H:i:s');
                    }
                    
                    if ($params['withdraw'] > 0){
                        $order = Db::name('upmark')->where('id',$params['ids'])->find();
                        if (!(strtotime($order['time']) + (10*60)) > time()){
                            $this->error('超出时间！');
                        }
                        $user_money = Db::name('user')->where('id',$order['user_id'])->value('money');
                        if ($user_money < $order['money']){
                            $this->error('余额不足撤回！');
                        }
                        // 删掉订单
                        Db::name('upmark')->where('id',$params['ids'])->delete();
                        // 扣除金额
                        setBalnce(array('user_id'=>$order['user_id'], 'money'=>$order['money']));
                        // 删除消息
                        Db::name('message')->where('user_id',$order['user_id'])->where('order_sn',$order['order_sn'])->delete();
                        // 删除资金记录
                        Db::name('mark')->where('user_id',$order['user_id'])->where('order_sn',$order['order_sn'])->delete();

                        $result = true;
                    }else{
                        $result = $row->allowField(true)->save($params);
                    }
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
                               if ($res) {
                                   $values['balance_before'] = $res['balance_before'];
                                   $values['balance_later'] = $res['balance_later'];
                               }
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
    
    
    private function setUpmark($ids, $status) {
        $upmark = Db::name('upmark')->where(array('id'=>$ids))->find();
        if ($upmark['status'] != $status) {
           if ($status == 1) {
               
               $field = 'money';
               
               if ($upmark['pay_way'] != 'cny' && $upmark['pay_way'] != 'usdt')
                   $field = $upmark['pay_way'];
               $mark['user_id'] = $upmark['user_id'];
               $mark['room_id'] = $upmark['room_id'];
               $mark['type'] = '上分';
               $mark['money'] = $upmark['money'];
               $mark['balance_before'] = get_user_byid($upmark['user_id'], $field);
               $mark['balance_later'] = $mark['balance_before'] + $upmark['money'];
               $mark['real'] = 1;
               $mark['content'] = $upmark['pay_type'].'上分金额';
               addMark($mark);
               setBalnce($upmark, 1, $field);
               return $mark;
           }
        }
        return false;
        
    }

}
