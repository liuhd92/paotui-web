<?php
// +----------------------------------------------------------------------
// | http://www.paotui.com/ 跑腿
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://paotui.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuhd<liuhd92@163.com>
// +----------------------------------------------------------------------
namespace Api\Model;
/**
 * 订单管理数据层
 * @author liuhd
 * @date 2018/11/16
 */
class OrderModel {
    
    /**
     * 添加订单基础信息
     * @param  array $data 订单基础信息
     * @return bool|int
     * @author liuhd
     * @date 2018/11/16
     */
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $Order = M('Order');
        return $Order->data($data)->add();
    }
    
    /**
     * 根据用户id和订单类型获取订单列表
     * @param  number $user_id 用户id
     * @param  number $order_status 订单类型(0为所有所有)
     * @return bool|array
     * @author liuhd
     * @date 2018/11/16
     */
    function getInfoByUId($user_id = 0, $order_status = '') {
        if (empty($user_id)) {
            return false;
        }
        
        $where = $order_status == 0 ? "`uid` = '$user_id'" : "`uid` = '$user_id' AND `order_status` = '$order_status'";
        
        $Order = M('Order');
        return $Order->where($where)->order("`create_time` desc")->select();
    }
    
    /**
     * 根据订单id获取单个订单基础信息
     * @param  number $id 订单id
     * @return bool|array
     * @author liuhd
     * @date 2018/11/16
     */
    public function getInfoById($id = 0) {
        if (empty($id)) {
            return false;
        }
        
        $Order = M('Order');
        return $Order->where("`id` = '$id'")->find();
    }
    
    /**
     * 根据订单编号获取单个订单基础信息
     * @param  string $order_number 订单编号
     * @return bool|array
     * @author liuhd
     * @date 2019/01/08
     */
    public function getInfoByNum($order_number = '') {
        if (empty($order_number)) {
            return false;
        }
        
        $Order = M('Order');
        return $Order->where("`order_number` = '$order_number'")->find();
    }
    /**
     * 修改订单
     * @param array $where 条件
     * @param array $data 修改的数据
     * @author liuhd
     * @return bool|int
     * @date 2019/01/08
     */
    public function edit($where = array(), $data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $Order = M('Order');
        return $Order->where($where)->data($data)->save();
    }
    
    /**
     * 根据订单状态获取订单列表    0全部|1进行中|2待接单|3已取消|4待支付|5已完成
     * @param number $status
     */
    public function getListByStatus($pages = 1, $rows = 10, $status = 0) {
        $Order = M('Order');
        return $Order->where("`order_status` = '$status' and `is_pay` = '1'")->page($pages, $rows)->order('pay_time desc')->select();
    }
}