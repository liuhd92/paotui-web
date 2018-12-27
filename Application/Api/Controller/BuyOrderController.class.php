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
class BuyOrderController extends Controller { //UserFilterController

    /*------------------------------------------------------ */
    //--下单
    /*------------------------------------------------------ */
    public function buy_order(){
        // $this->checkLogin();
        /************post/get参数 + 数据校验************/
        $user_id            = (int)I('post.uid', 0);
        $type               = (int)I('post.type', 0); // 订单类型：1取快递|2送快递|3取餐|4衣物送洗|5送洗衣物代取，以后拓展往后顺接
        $detail_info        = I('post.detail_info', ''); // 订单详情
        $base_price         = (int)I('post.base_price', 0);
        $refer_price        = (int)I('post.refer_price', 0); // 用户估价
        $tip_price          = (int)I('post.tip_price', 0); // 感谢费
        $is_specified       = (int)I('post.is_specified', 0); // 是否指定地址
        $specified_address  = I('post.specified_address', ''); // 指定的地址
        $specified_distance = (int)I('post.specified_distance', 0); // 指定地址与配送人员之间的距离
        $specified_price    = I('post.specified_price', 0.00); // 指定地址所增加的金额
        $discount_id        = (int)I('post.discount_id', 0);
        $discount_price     = (int)I('post.discount_price', 0); // 优惠券金额
        $weight_price       = (int)I('post.weight_price', 0); // 重量金额
        $distance_price     = (int)I('post.distance_price', 0); // 距离金额
        $is_pay             = (int)I('post.is_pay', 0); // 是否支付
        $pay_time           = (int)I('post.pay_time', 0); // 支付时间
        $create_time        = (int)I('post.create_time', time()); // 订单创建时间
        $from_address       = I('post.from_address', ''); // 送货地址
        $from_user          = I('post.from_user', ''); // 下单人 
        $from_time          = I('post.from_time', ''); // 取件时间（只有取送件模块有）
        $from_latitude      = I('post.from_latitude', ''); // 送件地址维度
        $from_longitude     = I('post.from_longitude', ''); // 送件地址经度
        $to_address         = I('post.to_address', ''); // 取货地址（只有取送件模块有）
        $to_user            = I('post.to_user', ''); // 收货人（只有取送件模块有）
        $to_latitude        = I('post.to_latitude', ''); // 取件地址维度
        $to_longitude       = I('post.to_longitude', ''); // 取件地址经度
        $order_status       = (int)I('post.order_status', 4); // 订单支付状态：0全部|1进行中|2待接单|3已取消|4待支付|5已完成
        $remark             = I('post.remark', ''); // 订单备注信息
        if (empty($user_id)) json_error(10201);
        if (empty($detail_info)) json_error(10301); // 订单详情不能为空
        if (empty($from_address)) json_error(10302); // 收货地址不能为空
        if (empty($from_user)) json_error(10307); // 收货地址不能为空

        /************查询用户信息************/
        $User = D('User');
        $user_info = $User->getInfoById($user_id);
        if ($user_info == null) {
            json_error(10202); // 暂无当前用户信息
        } else if ($user_info === false) {
            json_error(10107); // 数据库操作失败
        }
        
        /************生成订单************/
        // ①构建订单基础数据(order)
        $order_data = array();
        $order_data['order_number'] = create_order_num($type); // 生成订单号
        $order_data['uid']          = $user_id;
        $order_data['type']         = $type;
        $order_data['total_price']  = $base_price + $tip_price + $specified_price + $weight_price + $distance_price;
        $order_data['is_pay']       = $is_pay;
        $order_data['pay_price']    = $discount_id ? $order_data['total_price'] - $discount_price + $weight_price + $distance_price : $order_data['total_price'] + $weight_price + $distance_price; // -优惠金额
        $order_data['pay_time']     = $pay_time;
        $order_data['create_time']  = $create_time;
        $order_data['update_time']  = time();
        $order_data['order_status'] = $order_status;
        // 添加订单基础数据
        $Order = D('Order');
        $order_id = $Order->add($order_data);
        if ($order_id === false) json_error(10107); // 数据库操作失败
        
        // 构建订单详细数据(order_detail)
        $from_user = explode("  ", $from_user);
        $to_user = explode("  ", $to_user); 
        
        $order_detail_data = array();
        $order_detail_data['order_id'] = $order_id;
        $order_detail_data['from_address'] = $from_address;
        $order_detail_data['from_user'] = from_emoji($from_user[0]);
        $order_detail_data['from_phone'] = $from_user[1];
        $order_detail_data['from_time'] = strtotime(date("Y-m-d").' '.$from_time);
        $order_detail_data['from_latitude'] = $from_latitude;
        $order_detail_data['from_longitude'] = $from_longitude;
        $order_detail_data['to_address'] = $to_address;
        $order_detail_data['to_user'] = from_emoji($to_user[0]);
        $order_detail_data['to_phone'] = $to_user[1];
        $order_detail_data['to_latitude'] = $to_latitude;
        $order_detail_data['to_longitude'] = $to_longitude;
        $order_detail_data['base_price'] = $base_price;
        $order_detail_data['discount_id'] = $discount_id;
        $order_detail_data['discount_price'] = $discount_price;
        $order_detail_data['tip_price'] = $tip_price;
        $order_detail_data['weight_price'] = $weight_price;
        $order_detail_data['distance_price'] = $distance_price;
        $order_detail_data['detail_info'] = $detail_info;
        $order_detail_data['is_specified'] = $is_specified;
        $order_detail_data['specified_address'] = $specified_address;
        $order_detail_data['specified_price'] = $specified_price;
        $order_detail_data['specified_distance'] = $specified_distance;
        $order_detail_data['refer_price'] = $refer_price;
        $order_detail_data['remark'] = $remark;
        // 添加订单详细数据
        $orderDetail = D('OrderDetail');
        $order_detail_id = $orderDetail->add($order_detail_data);
        if ($order_id === false) json_error(10107); // 数据库操作失败
        
        /************输出************/
        if ($order_id && $order_detail_id) json_success(array('msg'=>'订单生成成功！'));
        
        json_error(10303); // 订单生成失败
    }
    
    /*------------------------------------------------------ */
    //--订单列表
    /*------------------------------------------------------ */
    public function order_list() {
//         $this->checkLogin();
        /************post/get参数 + 数据校验************/
        $user_id = (int)I('post.uid', 0);
        $order_status = I('post.order_status', 0);
        if (empty($user_id)) json_error(10201);
        
        /************查询用户信息是否存在************/
        $User = D('User');
        $user_info = $User->getInfoById($user_id);
        if ($user_info == null) {
            json_error(10202); // 暂无当前用户信息
        } else if ($user_info === false) {
            json_error(10107); // 数据库操作失败
        }
        
        /************查询订单列表************/
        $Order = D('Order');
        $order_info = $Order->getInfoByUId($user_id, $order_status);
        if ($order_info == null) {
            json_error(10314); // 暂无订单信息
        } else if ($order_info === false){
            json_error(10107); // 数据库操作失败
        }
        // 格式化订单数据
        $order_info = self::filter_order($order_info) != false ? self::filter_order($order_info) : json_error(10312) ; // 订单列表查询失败
        
        /************输出************/
        json_success($order_info);
    }
    
    /*------------------------------------------------------ */
    //--收货地址列表
    /*------------------------------------------------------ */
    public function address_list() {
//         $this->checkLogin();
        /************post/get参数 + 数据校验************/
        $user_id = (int)I('post.uid', 0);
        $is_temporary = I('post.is_temporary', 0);
        if (empty($user_id)) json_error(10201);
      
        /************查询用户信息是否存在************/
        $User = D('User');
        $user_info = $User->getInfoById($user_id);
        if ($user_info == null) {
            json_error(10202); // 暂无当前用户信息
        } else if ($user_info === false) {
            json_error(10107); // 数据库操作失败
        }
        
        /************查询正常状态的收货地址列表************/
        $Address = D('Address');
        $address_info = $Address->getInfoByUid($user_id, $is_temporary);
        if ($address_info == null){
            json_error(10315); // 暂无收货地址
        } else if ($address_info === false){
            json_error(10107); // 数据库操作失败
        }
        
        /************输出************/
        json_success($address_info);
    }
    
    
    /*------------------------------------------------------ */
    //--添加收货地址
    /*------------------------------------------------------ */
    public function add_address() {
        // $this->checkLogin();
        /************post/get参数 + 数据校验************/
        $user_id       = (int)I('post.uid', 0);
        $country_name  = I('post.country_name', '');
        $province_name = I('post.province_name', '');
        $city_name     = I('post.city_name', '');
        $detail_info   = I('post.detail_info', '');
        $national_code = I('post.national_code', '');
        $postal_code   = I('post.postal_code', '');
        $tel_number    = I('post.tel_number', '');
        $user_name     = I('post.user_name', '');
        $is_temporary  = (int)I('post.is_temporary', 0); // 是否是临时地址：0否 | 1是
        if (empty($user_id)) json_error(10201);
        if (empty($country_name)) json_error(10304); // 收货地址国家不能为空
        if (empty($province_name)) json_error(10305); // 收货地址城市不能为空
        if (empty($city_name)) json_error(10306); // 收货地址地区不能为空'
        if (empty($detail_info)) json_error(10307); // 详细收货地址信息不能为空
        if (empty($tel_number)) json_error(10308); // 收货人手机号码不能为空
        if (empty($user_name)) json_error(10309); // 收货人姓名不能为空
        
        /************添加收货地址************/
        // 构建用户地址数据(address)
        $address_data = array();
        $address_data['user_id'] = $user_id;
        $address_data['username'] = $user_name;
        $address_data['postal_code'] = $postal_code;
        $address_data['is_temporary'] = $is_temporary;
        $address_data['national_code'] = $national_code;
        $address_data['city_name'] = $city_name;
        $address_data['province_name'] = $province_name;
        $address_data['country_name'] = $country_name;
        $address_data['detail_info'] = $detail_info;
        $address_data['tel_number'] = $tel_number;
        $address_data['create_time'] = time();
        // 添加用户地址信息
        $Address = D('Address');
        $address_id = $Address->add($address_data);
        if ($address_id>0) {
            json_success(array('msg'=>'地址添加成功！'));
        }
        
        /************输出************/
        json_error(10311); // 收货地址添加失败
    }
    
    /*------------------------------------------------------ */
    //--修改收货地址
    /*------------------------------------------------------ */
    public function set_default_address() {
        //         $this->checkLogin();
        /************post/get参数 + 数据校验************/
        $user_id = (int)I('post.uid', 0);
        $address_id = (int)I('post.address_id', 0);
        if (empty($user_id)) json_error(10201);
        if (empty($address_id)) json_error(10316); // 收货地址id不能为空
        
        /************查询用户信息是否存在************/
        $User = D('User');
        $user_info = $User->getInfoById($user_id);
        if ($user_info == null) {
            json_error(10202); // 暂无当前用户信息
        } else if ($user_info === false) {
            json_error(10107); // 数据库操作失败
        }
        
        /************查询当前默认收货地址************/
        $Address = D('Address');
        $default_info = $Address->getDefaultInfoByUid($user_id);
        if ($default_info === false) {
            json_error(10107); // 数据库操作失败
        } else if ($default_info){
            // 取消原默认收货地址
            $result1 = $Address->edit(array('id'=>$default_info['id'], 'user_id'=>$default_info['user_id']), array('is_default'=>0));
            if ($result1 === false) json_error(10107); // 数据库操作失败
        }

        /************设置默认收货地址************/
        // 修改默认地址
        $result2 = $Address->edit(array('id'=>$address_id, 'user_id'=>$user_id), array('is_default'=>1));
        if ($result2 === false) json_error(10107); // 数据库操作失败
        
        /************输出************/
        json_success(array('msg'=>'修改成功！'));
    }
    
    /*------------------------------------------------------ */
    //--私有方法
    /*------------------------------------------------------ */
    private function filter_order($order_info = array()){
        if (empty($order_info)) return false;
        
        $OrderDetail = D('OrderDetail');
        $Address = D('Address');
        foreach ($order_info as $k=>$v){
//             // 查询订单详情
            $order_info[$k]['create_time_date'] = date("Y-m-d H:i:s",$v['create_time']);
            $order_detail_info = $OrderDetail->getInfoByOId($v['id']);
            $order_detail_info['from_user'] = to_emoji($order_detail_info['from_user']);
            $order_detail_info['to_user'] = to_emoji($order_detail_info['to_user']);
//             if ($order_detail_info) {
//                 // 查询收货地址信息
//                 $address_info = $Address->getInfoById($order_detail_info['address']);
//                 $order_detail_info['address'] = !empty($address_info) ? $address_info : array() ;
//             } else {
//                 return false;
//             }
            
            // 组合订单详情信息(包含收货地址)
            $order_info[$k]['detail'] = $order_detail_info;
        }
        
        return $order_info;
    }
}