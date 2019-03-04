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
    
    public function login() {
        vendor('WxMini.wxBizDataCrypt');
    
        $APPID = C('WX.APPID');
        $AppSecret = C('WX.APPSECRET');
        $code = I('post.code', '');
        $encryptedData = I('post.encryptedData', '');
        $signature = I('post.signature', '');
        $rawdata = I('post.rawData', '');
        $iv = I('post.iv', '');
         
        // 获取信息，对接口进行解密
        if(empty($signature) || empty($encryptedData) || empty($iv)) json_error(10308); // 参数不全
    
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$APPID.'&secret='.$AppSecret.'&js_code='.$code.'&grant_type=authorization_code';
    
        /* 校验数字签名 */
        $arr = self::vegt($url);
        $arr = json_decode($arr,true);
        $session_key = $arr['session_key'];
        if ($signature != sha1(htmlspecialchars_decode($rawdata).$session_key)) json_error(10309); // 数字签名失败
    
        $pc = new \WXBizDataCrypt($APPID,$session_key);
        $errCode = $pc->decryptData($encryptedData,$iv,$data);
        if($errCode != 0) json_error(10310); // 数据解密失败
        $data = json_decode($data,true);
        $save['type']      = 1;
        $save['unique_id'] = $data['openId'];
        $save['nick_name'] = filter_Emoji($data['nickName']);
        $save['gender']     = $data['gender'];
        $save['headimgurl'] = $data['avatarUrl'];
        $save['country'] = $data['country'];
        $save['province'] = $data['province'];
        $save['city'] = $data['city'];
        $save['status'] = 1;
        $save['created_time'] = time();
         
        /* 查询账号是否存在 */
        $UserQuick = D('UserQuick');
        $exist = $UserQuick->getUserInfoByUniqueId($data['openId']);
    
        if ($exist === false) {
            json_error(10202); // 数据库操作失败
        } else if ($exist){
            /*-----查看用户是否绑定手机号----*/
            if($exist['user_id'] > 0){
                $user_data = array();
                $user_data['user_id'] = $exist['user_id'];  //用户ID
                $user_data['nick_name'] = $exist['nick_name'];  //微信昵称
                $user_data['unique_id'] = $exist['unique_id'];  //微信账号唯一标识
                $user_data['headimgurl'] = $exist['headimgurl'];//微信头像
    
                /*--存入缓存--*/
                load_redis('hset', format_key("MINI_USER:".$data['openId']), json_encode($user_data), 'userData');
            }
            json_success($exist);
        }
        /* 账号添加 */
        $new_id = $UserQuick->add($save);
        if ($new_id === false) json_error(10202); // 数据库操作失败
    
    
        /* 生成第三方3rd_session */
        $session3rd  = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;
        for($i=0;$i<16;$i++){
            $session3rd .=$strPol[rand(0,$max)];
        }
    
        $new = array(
            'session3rd' => $session3rd,
            'unique_id' => $data['openId'],
            'session_key' => $session_key
        );
        json_success($new);
    }
    
    /*------------------------------------------------------ */
    //--意见反馈
    /*------------------------------------------------------ */
    public function mini_feedback() {
        /* ----------post/get参数 + 数据校验---------- */
        $user_id = (int)I('post.uid', 0);
        $content = I('post.content', '');
        $phone = I('post.phone', '');
        if (empty($user_id)) json_error(10201); // 用户id不能为空
        if (empty($content)) json_error(10501); // 反馈内容不能为空
        
        /* ----------记录反馈内容---------- */
        // 组合反馈信息
        $data = array();
        $data['uid'] = $user_id;
        $data['content'] = $content;
        $data['phone'] = $phone;
        $data['create_time'] = time();
        $Feedback = D('Feedback');
        $result = $Feedback->add($data);
        if ($result > 0) json_success(array('msg' => '反馈成功！'));
        
        json_error(10107); // 数据库操作失败
    }
}