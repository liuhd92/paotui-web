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
 * 物品管理数据层
 * @author liuhd
 * @date 2018/11/29
 */
class GoodsModel {
    
    /**
     * 根据商品类型获取商品列表
     * @param  int $type 商品类型
     * @return bool|array
     * @author liuhd
     */
    function getInfoByType($type = 0) {
        if (empty($type)) {
            return false;
        }
        
        $Goods = M('Goods');
        return $Goods->where("`type` = '$type' AND `status` = '0'")->select();
    }
    
}