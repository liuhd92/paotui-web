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
 * 订单评论数据层
 * @author liuhd
 * @date 2018/12/28
 */
class OrderCommentModel {
    
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $OrderComment = M('OrderComment');
        return $OrderComment->add($data);
    }
    
}