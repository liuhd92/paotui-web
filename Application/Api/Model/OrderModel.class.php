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
    function getInfoByUId($user_id = 0, $order_status = 0) {
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
    
}