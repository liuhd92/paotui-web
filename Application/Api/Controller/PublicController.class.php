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
 * 公共业务层
 * @author liuhd
 * @date 2018/11/16
 */
class PublicController extends Controller {
    public function index(){
        $this->display();
    }
}