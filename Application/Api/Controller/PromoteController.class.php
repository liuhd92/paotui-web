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
 * 推广业务层
 * @author liuhd
 * @date 2018/11/14
 */
class PromoteController extends Controller {
    /*------------------------------------------------------ */
    //--咨询客户修改
    /*------------------------------------------------------ */
    public function booth(){
        /* ----------post/get参数 + 数据校验---------- */
        $boot_id = I('post.booth_id', '');
        $open_id = I('post.open_id', '');
        Log::write('booth_id : '.$boot_id);
        /* ----------查询用户是否已经被推广过---------- */
        $Promote = D('Promote');
        $info = $Promote->getInfoByOpenid($open_id);
        // 已经被推广不做任何操作
        if ($info) {
            json_success(array('msg'=>'ok'));
        }
        $result = $Promote->add($boot_id, $open_id);
        if ($result) {
            json_success(array('msg'=>'ok'));
        } else {
            json_error(10107);
        }
    }
}