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
 * 订单详情管理数据层
 * @author liuhd
 * @date 2018/11/16
 */
class OrderDetailModel {
    
    /**
     * 添加订单详细信息
     * @param  array $data 订单详细信息
     * @return bool|int
     * @author liuhd
     * @date 2018/11/16
     */
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $OrderDetail = M('OrderDetail');
        return $OrderDetail->data($data)->add();
    }
    
    /**
     * 根据订单id获取单个订单详细信息
     * @param  number $order_id 订单id
     * @return bool|array
     * @author liuhd
     * @date 2018/11/16
     */
    function getInfoByOId($order_id = 0) {
        if (empty($order_id)) {
            return false;
        }
        
        $OrderDetail = M('OrderDetail');
        return $OrderDetail->where("`order_id` = '$order_id'")->find();
    }
    
    /**
     * 根据订单详情id获取单个订单详细信息
     * @param  number $id 订单详情id
     * @return bool|array
     * @author liuhd
     * @date 2018/11/16
     */
    public function getInfoById($id = 0) {
        if (empty($id)) {
            return false;
        }
        
        $OrderDetail = M('OrderDetail');
        return $OrderDetail->where("`id` = '$id'")->find();
    }
    
}