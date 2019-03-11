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
 * 用户管理数据层
 * @author liuhd
 * @date 2018/11/16
 */
class UserModel {
    
    /**
     * 根据用户id获取单个用户信息
     * @param  number $user_id 用户id
     * @return bool|array
     * @author liuhd
     */
    function getInfoById($user_id = 0) {
        if (empty($user_id)) {
            return false;
        }
        
        $User = M('User');
        return $User->where("`id` = '$user_id'")->find();
    }
    
    /**
     * 根据用户手机号获取单个用户信息
     * @param  number $user_id 用户id
     * @return bool|array
     * @author liuhd
     */
    function getInfoByPhone($phone = '') {
        if (empty($phone)) {
            return false;
        }
        
        $User = M('User');
        return $User->where("`phone` = '$phone'")->find();
    }
    
    /**
     * 添加用户信息
     * @param  array $user 用户数据
     * @return bool|int
     * @author liuhd
     */
    function add($user = array()) {
        if (empty($user)) {
            return false;
        }
        
        $User = M('User');
        return $User->data($user)->add();
    }
    
}