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
/**
 * 下单业务层
 * @author liuhd
 * @date 2018/11/14
 */
class BuyOrderController extends Controller { //UserFilterController
    
    /**
     * 获取模考列表
     * http://192.168.30.62/api/doku.php?id=gmat2:test_test_list
     * @author by liuhd
     * @date 2018/11/14
     */
    public function buy_order(){
        $this->checkLogin();
        echo '1234';
        
    }
}