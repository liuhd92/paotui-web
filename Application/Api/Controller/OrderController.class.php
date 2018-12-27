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
        /* ----------post/get参数 + 数据校验---------- */
        $order_id = (int)I('post.id', 0);
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        
        /* ----------订单基本信息---------- */
        $Order = D('Order');
        $detail_info = $Order->getInfoById($order_id);
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
    //--私有方法 -- 格式化订单详情
    /*------------------------------------------------------ */
    private function filter_order($order_info = array()){
        if (empty($order_info)) return false;
    
        // 查询订单详情
        $OrderDetail = D('OrderDetail');
        $order_info['create_time_date'] = date("Y-m-d H:i:s",$order_info['create_time']);
        $order_detail_info = $OrderDetail->getInfoByOId($order_info['id']);
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
        // 组合订单详情信息
        $order_info['detail'] = $order_detail_info;
    
        return $order_info;
    }
}