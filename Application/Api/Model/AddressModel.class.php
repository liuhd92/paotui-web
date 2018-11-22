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
 * 用户地址管理数据层
 * @author liuhd
 * @date 2018/11/19
 */
class AddressModel {
    
    /**
     * 根据地址id获取单个地址信息
     * @param  number $id 地址id
     * @return bool|array
     * @author liuhd
     */
    function getInfoById($id = 0) {
        if (empty($id)) {
            return false;
        }
        
        $Address = M('Address');
        return $Address->where("`id` = '$id'")->find();
    }
    
    /**
     * 根据用户id获取其所有的收货地址
     * @param  number $user_id 用户id
     * @param  number $is_temporary 临时收货地址(0非临时收货地址 | 1临时收货地址 | 2所有地址)
     * @return bool|array
     * @author liuhd
     */
    public function getInfoByUid($user_id = 0, $is_temporary = 0) {
        if (empty($user_id)) {
            return false;
        }

        $where = $is_temporary == 2 ? "`user_id` = '$user_id'" : ($is_temporary == 1 ? "`user_id` = '$user_id' AND `is_temporary` = '1'" : "`user_id` = '$user_id' AND `is_temporary` = '0'"  ) ;
        
        $Address = M('Address');
        return $Address->where($where)->select();
    }
    
    /**
     * 添加地址
     * @param  array $data 用户地址信息
     * @return bool|int
     * @author liuhd
     */
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        
        $Address = M('Address');
        return $Address->data($data)->add();
    }
    
    /**
     * 根据用户id获取默认收货地址
     * @param number $user_id 用户id
     * @return bool|array
     * @author liuhd
     */
    public function getDefaultInfoByUid($user_id = 0) {
        if (empty($user_id)) {
            return false;
        }
        
        $Address = M('Address');
        return $Address->where("`user_id` = '$user_id' AND `is_default` = '1' AND `is_temporary` = '0'")->find();
    }
    
    /**
     * 根据条件修改收货地址信息
     * @param array $data 要修改的收货地址信息
     * @return bool|int
     * @author liuhd
     */
    public function edit($where = array(), $data = array()) {
        if (empty($where) || empty($data)) {
            return false;
        }
        
        $Address = M('Address');
        return $Address->where($where)->data($data)->save();
    }
}