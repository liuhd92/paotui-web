<?php
// +----------------------------------------------------------------------
// | http://www.paotui.com/ 跑腿
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://paotui.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuhd<liuhd92@163.com>
// +----------------------------------------------------------------------
namespace Api\Controller;
use Think\Controller;
use Think\Log;
/**
 * 下单业务层
 * @author liuhd
 * @date 2018/11/14
 */
class RiderController extends UserFilterController {
    
    /*------------------------------------------------------ */
    //--骑手账号分配
    /*------------------------------------------------------ */
    public function reg_rider() {
        /* ----------post参数 + 数据校验---------- */
        $mobile = I('post.real_phone', '');
        $idcard = I('post.idcard', '');
        $name = I('post.real_name', '');
        $name = urldecode($name);
        $duty = I('post.duty', '');
        $gender = (int)I('post.gender', 0); // 0未知|1男|2女
        $password = strlen($idcard)==15 ? ('19' . substr($idcard, 6, 6)) : substr($idcard, 6, 8); // 身份证提取出生年月日作为密码
        if (empty($mobile)) json_error(10601); // 手机号不能为空
        if (empty($password)) json_error(10602); // 密码不能为空
        if (empty($idcard)) json_error(10603); // 身份证号码不能为空
        
        /* ----------生成骑手账号---------- */
        $rider_data = array();
        $rider_data['nick_name'] = $name;
        $rider_data['wx_phone'] = $mobile;
        $rider_data['real_name'] = $name;
        $rider_data['duty'] = $duty;
        $rider_data['real_phone'] = $mobile;
        $rider_data['gender'] = $gender;
        $rider_data['idcard'] = $idcard;
        $rider_data['salt'] = randcode(6, 3);
        $rider_data['password'] = md5(md5($password).$rider_data['salt']);
        $rider_data['noencryption_passwprd'] = $password;
        $rider_data['created_time'] = time();
        // 添加
        $Rider = D('Rider');
        $id = $Rider->add($rider_data);
        if ($id) {
            // 查询用户信息
            $rider_info = $Rider->getInfoById($id);
            Log::write(json_success($rider_info));
            json_success($rider_info);
        }
        
        json_error(10107); // 数据库操作失败
    }
    
    /*------------------------------------------------------ */
    //--骑手登录
    /*------------------------------------------------------ */
    public function login() {
        /* ----------post参数 + 数据校验---------- */
        $mobile = I('post.real_phone', '');
        $password = I('post.password', '');
        $equipment = I('post.equipment', ''); // 登录设备
         Log::write(var_export($_POST, true));
        if (empty($mobile)) json_error(10601); // 手机号不能为空
        if (empty($password)) json_error(10602); // 密码不能为空
//         if (empty($equipment)) json_error(10121); // 无法验证您的登录设备，请联系管理员
        
        /* ----------登录---------- */
        // ①：查询骑手信息是否存在
        $Rider = D('Rider');
        $rider_info = $Rider->getInfoByMobile($mobile);
        if ($rider_info == null) {
            json_error(10322); // 暂无该骑手信息或已离职
        } else if ($rider_info === false) {
            json_error(10107); // 数据库操作失败
        }
        // ②：验证密码是否正确
        if (md5(md5($password).$rider_info['salt']) != $rider_info['password']) json_error(10605); // 登录密码错误
        // ③：如果是第一次登陆，则记录设备信息；第二次不可更换设备登陆
        if (empty($rider_info['token'])) {
            $result = $Rider->edit(array('id'=>$rider_info['id']), array('equipment'=>$equipment));
            if ($result === false) json_error(10707); // 数据库操作失败
        }

        // 标记登录状态
        $token = guid();
        /* ----------本地系统数据更新---------- */
        //存入redis
        $result = $Rider->edit(array('id'=>$rider_info['id']), array('token'=>$token));
        if($result === false) json_error(10122);// Token生成失败
    
        /* ----------查询用户信息以供返回---------- */
        $rider_info = array();
        $rider_info = $Rider->getInfoByMobile($mobile);
        if ($rider_info == null) {
            json_error(10322); // 暂无该骑手信息或已离职
        } else if ($rider_info === false) {
            json_error(10107); // 数据库操作失败
        }
        json_success($rider_info);
    }
    
    /*------------------------------------------------------ */
    //--已发布的订单列表
    /*------------------------------------------------------ */
    public function order_list() {
        /* ----------校验登录状态---------- */
         $this->checkLogin();
        
        /* ----------post参数 + 数据校验---------- */
        $rows  = (int)I('post.rows', 10);
        $pages = (int)I('post.pages', 1); 
        
        /* ----------获取订单信息---------- */
        $Order = D('Order');
        // 获取待接单的订单（基本信息）
        $order_list = $Order->getListByStatus($pages, $rows, 1); //0全部|1进行中|2待接单|3已取消|4待支付|5已完成
        if ($order_list == null) {
            json_error(10606); // 暂无订单
        } else if ($order_list === false) {
            json_error(10107); // 数据库操作失败
        }
        // 获取订单详细信息并组合
        $OrderDetail = D('OrderDetail');
        foreach ($order_list as $k=>$v) {
            $order_list[$k]['detail'] = $OrderDetail->getInfoByOId($v['id']);
        }
        
        json_success($order_list);
    }
    
    /*------------------------------------------------------ */
    //--抢单
    /*------------------------------------------------------ */
    public function get_order() {
        /* ----------校验登录状态---------- */
        $this->checkLogin();
        /* ----------post参数 + 数据校验---------- */
        $order_id  = (int)I('post.order_id', 0);
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        
        /* ----------查询订单信息是否有效---------- */
        // 获取订单基础信息
        $Order = D('Order');
        $order_info = $Order->getInfoById($order_id);
        if ($order_info == null) {
            json_error(10606); // 暂无订单
        } else if ($order_info === false) {
            json_error(10107); // 数据库操作失败
        }
        // 获取订单详细信息
        $OrderDetail = D('OrderDetail');
        $order_detail_info = $OrderDetail->getInfoByOId($order_id);
        if ($order_detail_info == null) {
            json_error(10606); // 暂无订单
        } else if ($order_detail_info === false) {
            json_error(10107); // 数据库操作失败
        } else if ($order_detail_info['rid']) {
            json_error(10607); // 当前订单已被抢
        }
        
        /* ----------抢单---------- */
        $result = $OrderDetail->edit(array('order_id'=>$order_id), array('rid'=>$this->uid, 'get_rider_time'=>time()));
        if ($result === false) {
            json_error(10107); // 数据库操作失败
        } else if ($result == 0) {
            json_error(10608); // 抢单失败
        }

        /* ----------修改订单状态为 进行中（已接单）---------- */
        $status_result = $Order->edit(array('id'=>$order_id), array('order_status'=>1));
        if ($status_result === false) {
            json_error(10107); // 数据库操作失败
        }
        P($status_result);
        if (!$status_result) json_error(10609); // 数据库操作失败
        
        // 组合订单信息
        $order_info['detail'] = $order_detail_info;
        // 抢单成功
        json_success($order_info);
    }

    /*------------------------------------------------------ */
    //--骑手已接单的订单列表
    /*------------------------------------------------------ */
    public function rider_order_list() {
        /* ----------校验登录状态---------- */
        $this->checkLogin();
        $rider_id = $this->uid;
        $order_status = (int)I('post.order_status', 1); // 订单状态：1进行中 | 5已完成
        $order_status_arr = array('1', '5');
        if (!in_array($order_status, $order_status_arr)) json_error(10610); // 订单状态错误
        $rows  = (int)I('post.rows', 10);
        $pages = (int)I('post.pages', 1);
        
        
        /* ----------获取骑手的接单列表---------- */
        $OrderDetail = D('OrderDetail');
        $order_list = $OrderDetail->getListByRid($rider_id, $order_status, $rows, $pages);
        if ($order_list == null) {
            json_error(10606); // 暂无订单信息
        } else if ($order_list === false) {
            json_error(10107); // 数据库操作失败
        }
        
        // 获取订单详细信息并组合
        $OrderDetail = D('OrderDetail');
        foreach ($order_list as $k=>$v) {
            $order_list[$k]['detail'] = $OrderDetail->getInfoByOId($v['id']);
        }
        
        // 返回订单列表
        json_success($order_list);
    }
    
    /*------------------------------------------------------ */
    //--完成订单
    /*------------------------------------------------------ */
    public function finish_order() {
        /* ----------校验登录状态---------- */
        $this->checkLogin();
        $rider_id = $this->uid;
        
        /* ----------post参数 + 数据校验---------- */
        $order_id  = (int)I('post.order_id', 0);
        $tracking_number = I('post.tracking_number', ''); // 快递公司生成的订单号
        if (empty($order_id)) json_error(10318); // 订单id不能为空
        
        /* ----------查询订单信息是否有效---------- */
        // 获取订单基础信息
        $Order = D('Order');
        $order_info = $Order->getInfoById($order_id);
        if ($order_info == null) {
            json_error(10606); // 暂无订单
        } else if ($order_info === false) {
            json_error(10107); // 数据库操作失败
        }
        // 获取订单详细信息
        $OrderDetail = D('OrderDetail');
        $order_detail_info = $OrderDetail->getInfoByOId($order_id);
        if ($order_detail_info['rid'] != $rider_id) json_error(10611); // 请先接单
        if ($order_detail_info == null) {
            json_error(10606); // 暂无订单
        } else if ($order_detail_info === false) {
            json_error(10107); // 数据库操作失败
        }
        
        /* ----------订单完成---------- */
        if (empty($tracking_number)) {
            // 修改订单状态：如果有快递公司生成的订单号， 则填写订单号
            $result = $Order->edit(array('id'=>$order_id), array('order_status'=>'5'));
            if ($result === false) json_error(10107); // 数据库操作失败
        } else {
            $data['order_status'] = 5;
            $result = $Order->edit(array('id'=>$order_id), array('order_status'=>'5'));
            if ($result === false) json_error(10107); // 数据库操作失败
            
            $data['tracking_number'] = $tracking_number;
            $result = $OrderDetail->edit(array('id'=>$order_id), array('tracking_number'=>$tracking_number, 'finish_time'=>time()));
            if ($result === false) json_error(10107); // 数据库操作失败
        }
        
        json_success(array('msg'=>'订单状态修改成功'));
    }
}