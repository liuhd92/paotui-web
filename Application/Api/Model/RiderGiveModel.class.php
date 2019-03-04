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
 * 打赏骑手数据层
 * @author liuhd
 * @date 2019/01/08
 */
class RiderGiveModel {
    
    /**
     * 添加打赏记录
     * @param  array $data
     * @return bool|int
     * @author liuhd
     * @data 2019/01/08
     */
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $RiderGive = M('RiderGive');
        return $RiderGive->add($data);
    }
    
}