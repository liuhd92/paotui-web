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
class IndexController extends Controller {
    /*------------------------------------------------------ */
    //--咨询客户修改
    /*------------------------------------------------------ */
    public function index(){
        /* ----------post/get参数 + 数据校验---------- */
        $username = I('post.username', '');
        $passwd = I('post.password', '');
        Log::write(var_export($_POST, true));
        Log::write('usernmae : '.$username);
        Log::write('passwd : '.$passwd);
        
        if (($username == 'syy' && $passwd=='shaoyaoyao') || ($username == 'lyb' && $passwd=='liuyuanbo') ||( $username == 'lhd' && $passwd=='liuhuandong')) {
            $this->redirect('order/admin_order_list');
        } else {
            if ($username && $passwd) {
                echo "<script>alert('账号/密码错误')</script>";
            }
            
            $this->display();
        }
        
    }
}