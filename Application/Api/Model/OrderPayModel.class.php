<?php
// +----------------------------------------------------------------------
// | http://www.paotui.com/ 跑腿
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://paotui.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuhd<liuhd92@163.com>
// +----------------------------------------------------------------------
namespace Api\Model;
/**
 * 支付管理数据层
 * @author liuhd
 * @date 2019/01/08
 */
class OrderPayModel {
    
    /**
     * 添加支付记录
     * @param  array $data
     * @return bool|int
     * @author liuhd
     * @data 2019/01/08
     */
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $OrderPay = M('OrderPay');
        return $OrderPay->add($data);
    }
    
}