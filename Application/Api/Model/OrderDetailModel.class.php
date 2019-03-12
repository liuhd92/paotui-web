<?php
// +----------------------------------------------------------------------
// | http://www.paotui.com/ 跑腿
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://paotui.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuhd<liuhd92@163.com>
// +----------------------------------------------------------------------
namespace Api\Model;
use Think\Log;

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
        if (!$data['from_time']) {
            $data['from_time'] = time();
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
    
    /**
     * 更新订单详情记录
     * @param  array $where 更新条件
     * @param  array $data 更新数据
     * @return bool|int
     * @author liuhd
     * @data 2019/01/08
     */
    public function edit($where = array(), $data = array()) {
        if (empty($where) || empty($data)) {
            return false;
        }
        $OrderDetail = M('OrderDetail');
        return $OrderDetail->where($where)->save($data);
    }
    
    /**
     * 根据骑手id获取骑手接单的订单列表
     * @param  int $rider_id 骑手id
     * @return bool|array
     * @author liuhd
     * @data 2019/02/21
     */
    public function getListByRid($rider_id = 0, $order_status = 0, $rows = 10, $pages = 1) {
        if (empty($rider_id) || empty($order_status)) {
            return false;
        }
        
        
        $OrderDetail = M('OrderDetail');
        return $OrderDetail
                    ->alias("od")
                    ->field("o.*, od.*")
                    ->join("LEFT JOIN pt_order AS o ON od.order_id = o.id")
                    ->where("od.rid = '$rider_id' AND o.record_status = '1' AND o.is_pay = '1' AND o.order_status = '$order_status'")
                    ->page($pages, $rows)
                    ->order("`get_rider_time` asc")
                    ->select();
    }
}