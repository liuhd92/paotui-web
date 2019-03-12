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
 * 统计数据层
 * @author liuhd
 * @date 2018/11/16
 */
class PromoteModel {
    
    /**
     * 记录摊位推广的用户数据
     * @param  number $booth_id 摊位id
     * @return bool|array
     * @author liuhd
     */
    function add($booth_id = 0, $open_id = '') {
        if (empty($booth_id) || empty($open_id)) {
            return false;
        }
        
        $Booth = M('Booth');
        return $Booth->add(array('booth_id'=>$booth_id, 'open_id'=>$open_id, 'created_time'=>time()));
    }

    /**
     * 记录摊位推广的用户数据
     * @param  number $booth_id 摊位id
     * @return bool|array
     * @author liuhd
     */
    function getInfoByOpenid($open_id = '') {
        if (empty($open_id)) {
            return false;
        }
        
        $Booth = M('Booth');
        return $Booth->where("`open_id` = '$open_id'")->find();
    }

    
    
}