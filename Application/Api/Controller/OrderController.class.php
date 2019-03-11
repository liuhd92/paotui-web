<?php
// +----------------------------------------------------------------------
// | http://www.paotui.com/ 跑腿
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://paotui.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuhd<liuhd92@163.com>
// +----------------------------------------------------------------------
namespace Api\Controller;
use Think\Controller;
use Think\Log;
/**
 * 订单业务层
 * @author liuhd
 * @date 2018/12/19
 */
class OrderController extends Controller {
    /*------------------------------------------------------ */
    //--咨询客户修改
    /*------------------------------------------------------ */
    public function order_detail(){
        Log::write(var_export($_POST, true));
        /* ----------post/get参数 + 数据校验---------- */
        $order_id = (int)I('post.id', 0); // 订单id
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        
        /* ----------订单基本信息---------- */
        $Order = D('Order');
        $detail_info = $Order->getInfoById($order_id);
        Log::write(var_export($detail_info, true));
        if ($detail_info == null) {
            json_error(10319); // 暂无当前订单
        } else if ($detail_info === false){
            json_error(10107); // 数据库操作失败
        }
        
        // 格式化订单数据
        $detail_info = self::filter_order($detail_info);
        
        if ($detail_info === false) {
            json_error(10107) ; // 数据库操作失败
        }
        Log::write(var_export($detail_info, true));
        /************输出************/
        json_success($detail_info);
    }
    
    /*------------------------------------------------------ */
    //--修改订单状态
    /*------------------------------------------------------ */
    public function update_status(){
        /* ----------post/get参数 + 数据校验---------- */
        $order_id = (int)I('post.id', 0);
        $order_status = (int)I('post.order_status', 0);
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        if (empty($order_status)) json_error(10313); // 订单状态不能为空
    
        /* ----------订单基本信息---------- */
        $Order = D('Order');
        $detail_info = $Order->getInfoById($order_id);
        if ($detail_info == null) {
            json_error(10319); // 暂无当前订单
        } else if ($detail_info === false){
            json_error(10107); // 数据库操作失败
        }
        
        /* ----------修改订单状态---------- */
        // 更新条件
        $where = array();
        $where['id'] = $order_id;
        // 待更新的数据
        $order_data = array();
        $order_data['order_status'] = $order_status;
        $order_data['update_time'] = time();
        // 修改订单状态
        $result = $Order->edit($where, $order_data);
        if ($result >= 0) {
            json_success(array('msg'=>'修改成功！'));
        }
        
        json_error(10320); // 订单修改失败
    }
    
    /*------------------------------------------------------ */
    //--评价订单
    /*------------------------------------------------------ */
    public function order_comment() {
        /* ----------post/get参数 + 数据校验---------- */
        $user_id = (int)I('post.uid', 0);
        $order_id = (int)I('post.oid', 0);
        $stars = (int)I('post.stars', 5);
        $type = (int)I('post.type', 0); // 评价类型：1好评|2中评|3差评
        $tags = I('post.tags', ''); // 标签
        $content = I('post.content', '');
        if (empty($user_id)) json_error(10201);
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        
        /* ----------订单基本信息---------- */
        $Order = D('Order');
        $detail_info = $Order->getInfoById($order_id);
        if ($detail_info == null) {
            json_error(10319); // 暂无当前订单
        } else if ($detail_info === false){
            json_error(10107); // 数据库操作失败
        }
        
        /* ----------订单添加评价---------- */
        // 组合订单评价信息
        $comment_data = array();
        $comment_data['user_id'] = $user_id;
        $comment_data['order_id'] = $order_id;
        $comment_data['star_level'] = $stars;
        $comment_data['content'] = $content;
        $comment_data['type'] = $type;
        $comment_data['tag'] = htmlspecialchars_decode($tags);
        $comment_data['created_time'] = time();
        // 添加订单评论
        $OrderCommet = D('OrderComment');
        $result = $OrderCommet->add($comment_data);
        if ($result > 0) {
            json_success(array('msg' => '评价成功！'));
        }
        
        json_error(10321); // 评价失败
    }
    
    /*------------------------------------------------------ */
    //--私有方法 -- 格式化订单详情
    /*------------------------------------------------------ */
    private function filter_order($order_info = array()){
        if (empty($order_info)) return false;
    
        // 查询订单详情
        $OrderDetail = D('OrderDetail');
        $order_info['create_time_date'] = date("Y-m-d H:i:s",$order_info['create_time']);
        $order_detail_info = $OrderDetail->getInfoByOId($order_info['id']);
        Log::write(var_export($order_detail_info, true));
        if ($order_detail_info === false){
            return false;
        }
        
        
        $goods_info = $order_detail_info['detail_info'];
        $goods_info = explode("、", $goods_info);
        $goods_info[1] = explode("公斤", $goods_info[1])[0];
        // 寄件人和收件人的名称处理emoji表情
        $order_detail_info['from_user'] = to_emoji($order_detail_info['from_user']);
        $order_detail_info['to_user'] = to_emoji($order_detail_info['to_user']);
        $order_detail_info['goods'] = $goods_info;
        $order_detail_info['from_time_hi'] = date('H:i', $order_detail_info['from_time']);
        
        // 骑手信息
        $Rider = D('Rider');
        $rider_info = $Rider->getInfoById($order_detail_info['rid']);
        Log::write(var_export($rider_info, true));
        if($rider_info === false) json_error(10107); // 数据库查询失败
        $order_detail_info['rider'] = $rider_info;
        // 组合订单详情信息
        $order_info['detail'] = $order_detail_info;
        return $order_info;
    }

    /*------------------------------------------------------ */
    //--后台未派单
    /*------------------------------------------------------ */
    public function admin_order_list() {
        /* ----------筛选数据---------- */
        $shaixuan = I('get.shaixuan', '');
        Log::write('shaixuan : '.$shaixuan);
        
        
        /* ----------获取未分配的订单列表数据---------- */
        $where = array();
        $where['o.is_pay'] = 1;
        $where['o.order_status'] = 2;
        if ($shaixuan) {
            $where['_string'] = "od.from_address like '%$shaixuan%' or od.to_address  like '%$shaixuan%'";
        }
        
        $Order = D('Order');
        $order_list = $Order->getList($where);
        
        // 获取订单详细信息并组合
        $OrderDetail = D('OrderDetail');
        foreach ($order_list as $k=>$v) {
            $order_list[$k]['detail'] = $OrderDetail->getInfoByOId($v['id']);
        }
        
        $Rider = D('Rider');
        $rider_list = $Rider->getList();
        $this->assign('shaixuan', $shaixuan);
        $this->assign('order_list', $order_list);
        $this->assign('rider_list', $rider_list);
        
        $this->display();
    }
    
    /*------------------------------------------------------ */
    //--后台已派单
    /*------------------------------------------------------ */
    public function admin_order_list2() {
        $pages = (int)I('get.pages', 1);
        $rows = (int)I('get.rows', 10000);
        /* ----------获取未分配的订单列表数据---------- */
        
        $where = array();
        $where['o.order_status'] = 1;
        $where['o.is_pay'] = 1;
        $Order = D('Order');
        $order_list = $Order->getList($where, 'od.get_rider_time');
        
        // 获取订单详细信息并组合
        $Rider = D('Rider');
        $OrderDetail = D('OrderDetail');
        foreach ($order_list as $k=>$v) {
            $order_list[$k]['detail'] = $OrderDetail->getInfoByOId($v['id']);
            $order_list[$k]['detail']['rider_info'] = $Rider->getInfoById($order_list[$k]['detail']['rid']);
        }
        
        $this->assign('order_list', $order_list);
        $this->display();
    }
    
    public function get_order() {
        /* ----------post参数 + 数据校验---------- */
        $order_id  = I('post.order_ids', '');
        $rider_id  = (int)I('post.rider_id', 0);
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        $order = explode(",", $order_id);
        
        $Order = D('Order');
        $OrderDetail = D('OrderDetail');
        /* ----------查询订单信息是否有效---------- */
        foreach ($order as $k=>$v){
            // 获取订单基础信息
            $order_info = $Order->getInfoById($order_id);
            if ($order_info == null) {
                json_error(10606); // 暂无订单
            } else if ($order_info === false) {
                json_error(10107); // 数据库操作失败
            }
            // 获取订单详细信息
            
            $order_detail_info = $OrderDetail->getInfoByOId($order_id);
            if ($order_detail_info == null) {
                json_error(10606); // 暂无订单
            } else if ($order_detail_info === false) {
                json_error(10107); // 数据库操作失败
            }
            
            /* ----------派单---------- */
            $result = $OrderDetail->edit(array('order_id'=>$v), array('rid'=>$rider_id, 'get_rider_time'=>time()));
            if ($result === false) {
                json_error(10107); // 数据库操作失败
            } else if ($result == 0) {
                json_error(10608); // 抢单失败
            }
            
            /* ----------修改订单状态为 进行中（已接单）---------- */
            $status_result = $Order->edit(array('id'=>$v), array('order_status'=>1));
            if ($status_result === false) {
                json_error(10107); // 数据库操作失败
            }
            if (!$status_result) json_error(10609); // 数据库操作失败
        }
        
        // 组合订单信息
        // 抢单成功
        json_success(array('msg'=>'派单成功'));
    }
}