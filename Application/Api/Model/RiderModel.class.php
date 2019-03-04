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
 * 骑手数据层
 * @author liuhd
 * @date 2019/01/08
 */
class RiderModel {
    
    /**
     * 添加打赏记录
     * @param  array $data
     * @return bool|int
     * @author liuhd
     * @data 2019/02/15
     */
    public function add($data = array()) {
        if (empty($data)) {
            return false;
        }
        $Rider = M('Rider');
        return $Rider->add($data);
    }
    
    /**
     * 根据骑手id获取单个骑手信息
     * @param  number $user_id 用户id
     * @return bool|array
     * @author liuhd
     */
    function getInfoById($rider_id = 0) {
        if (empty($rider_id)) {
            return false;
        }
    
        $Rider = M('Rider');
        return $Rider->where("`id` = '$rider_id'")->find();
    }
    
    /**
     * 根据骑手手机号获取单个骑手信息
     * @param  string $mobile 骑手手机号
     * @return bool|array
     * @author liuhd
     */
    function getInfoByMobile($mobile = '') {
        if (empty($mobile)) {
            return false;
        }
    
        $Rider = M('Rider');
        return $Rider->where("`real_phone` = '$mobile'")->find();
    }
    
    /**
     * 根据条件修改骑手信息
     * @param  array $where 修改条件
     * @param  array $data  修改信息
     * @return bool|int
     * @author liuhd
     */
    public function edit($where = array(), $data = array()) {
        if (empty($where) || empty($data)) {
            return false;
        }
        
        $Rider = M('Rider');
        return $Rider->where($where)->data($data)->save();
    }
        
    /**
     * 获取用户token信息（redis）
     * @param 用户id int $id
     * @return boolean
     * @author liukw
     */
    public function getToken($id){
        $data = false;
    
        if($id){
            // 用户进度key
            $key = format_key("user:{$id}:token");
            $redis = new \Redis();
            try{
                $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
    
                /*-----------线上redis服务器需要密码-------------*/
                if ($redis->auth(C("REDIS_PWD")) == false){
                    json_error(10204);// Redis服务器密码错误
                }
                $data = $redis->hGetAll($key);
                $redis->close();
            }catch(\Exception $ex){
                // Redis服务连接异常
                json_error(10203);
            }
        }
    
        return $data;
    }
    
}