<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;
use think\Request;
/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    protected $noNeedRight = ['ajax'];
    
    /**
     * 查看
     */
    public function index()
    {
        $now = date('Y-m-d').' 00:00:00 - '.date('Y-m-d').' 23:59:59';
        if ($this->request->isAjax()) {
           $time = $this->request->post('time_text');
           $data = $this->getData($time ? $time : $now);
           $this->success('查询成功','', $data);
        }

        $this->view->assign('total_user', Db::name('user')->where(array('status'=>1))->count());
        $this->view->assign('total_money', Db::name('user')->where(array('status'=>1))->sum('money'));
        $this->view->assign('data', $this->getData($now));
        $this->view->assign('time', $now)->assign('data', $this->getData($now));
        $this->view->assign('kefu', Config::get('site.kefu_script'));
        
        
        $now = date('Y-m-d');
        $list = array();
        $timeList = array();
        $mark = array();
        $order = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-'.$i.' days'));
            $timeList[] = $date;
            $condition['status'] = 1;
            $condition['type'] = '上分';
            $condition['pay_type'] = '银行卡';
            $condition['time'] = array('between', array($date.' 00:00:00', $date.' 23:59:59'));
            $mark['up'][] = Db::name('upmark')->where($condition)->sum('money');
            $condition['pay_type'] = 'USDT';
            $mark['usdtup'][] = Db::name('upmark')->where($condition)->sum('money');
            
            $condition['type'] = '下分';
            $condition['pay_type'] = '银行卡';
            $mark['down'][] = Db::name('upmark')->where($condition)->sum('money'); 
            $condition['pay_type'] = 'USDT';
            $mark['usdtdown'][] = Db::name('upmark')->where($condition)->sum('money');
            
            $trade['status'] = 3;
            $trade['buy_time'] = array('between', array($date.' 00:00:00', $date.' 23:59:59'));
            $trade['o_style'] = 1;
            $order['buy'][] =  Db::name('order')->where($trade)->sum('buy_money'); 
            $trade['o_style'] = 2;
            $order['sell'][] =  Db::name('order')->where($trade)->sum('buy_money'); 
        }
        $result = array(
           'timeList' => $timeList,
            'mark' => $mark,
            'order' => $order
        );
        $this->view->assign('chatdata', json_encode($result));
        return $this->view->fetch();
    }
    
    
    private function getData($time ='') {
        $where = '';
        if ($time) {
            $btime = explode(' - ', $time);
            $where =  array('between', array($btime['0'], $btime['1']));
        }
        
        $data = array(
            'online'        => 0,
            't_order'       => 0,
            't_people'       => 0,
            't_profit' => 0,
            't_recharge'   => 0,
            't_withdraw' => 0,
            't_recharge_usdt'=>0,
            't_withdraw_usdt' => 0,
        );
        
        $data['online'] = Db::name('user')->where(array('rtime'=>array('gt', time() - 60)))->count();
        
        $order = array();
        if ($where)
           $order['buy_time'] = $where;
        
        $data['t_order'] = Db::name('order')->where($order)->count();
        
        $user = array();
        if ($where)
            $user['reg_time'] = $where;
        
        $data['t_people'] = Db::name('user')->where($user)->count();
           
        
  
        $bet = array();
        if ($where)
            $bet['buy_time'] = $where;
        
        $bet['status'] = 3;
        $t_profit = Db::name('order')->where($bet)->sum('end_profit');
        
        $data['t_profit'] = round($t_profit, 2);

        $mark = array('status'=>1, 'type'=>'上分', 'pay_type'=>'银行卡');
        if ($where)
            $mark['time'] = $where;
        $upmark = Db::name('upmark')->where($mark)->sum('money');
        $data['t_recharge'] = $upmark ? $upmark : 0;
        
        $mark['pay_type'] = 'USDT';
        $upmark_usdt = Db::name('upmark')->where($mark)->sum('money');
        $data['t_recharge_usdt'] = $upmark_usdt ? $upmark_usdt : 0;
        
        $mark['pay_type'] = '银行卡';
        $mark['type'] = '下分';
        $downmark = Db::name('upmark')->where($mark)->sum('money');
        $data['t_withdraw'] = $downmark ? $downmark : 0;
        
        
        $mark['pay_type'] = 'USDT';
        $downmark_usdt = Db::name('upmark')->where($mark)->sum('money');
        $data['t_withdraw_usdt'] = $downmark_usdt ? $downmark_usdt : 0;
        
        
        return $data;
    }
    
    
    public function ajax() {
        
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $result = array('success'=> 0);
            $request = $param['request'];
            switch ($request) {
                case 'chat':
                    $type = $param['type'];
                    $condition['room_id'] = getAroomid();
                    if ($type == 'first') {
                        $chats = $this->getChat($condition, 'id desc', 30);
                        return json($chats);
                    } elseif ($type == 'update') {
                        $condition['id'] = array('gt', $param['id']);
                        $chats = $this->getChat($condition, 'id desc');
                        return json($chats);
                    }
                    break;
                case 'send':
                    $say['type'] = 'all';
                    $say['vip'] = 'all';
                    $say['room_id'] = getAroomid();
                    
                    if (robotSay($param['content'], $say))
                        $result['success'] = 1;
                    else {
                        $result['msg'] = '发送失败';
                    }
                 break;
            }
            return json($result);
        }
    }
    
    private function getChat($condition, $order='', $limit = '') {
        return Db::name('chat')->where($condition)->order($order)->limit($limit)->select();
    }

}
