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
class WeChatController extends Controller { //UserFilterController
    /**
     * error code 说明.
     * <ul>
     *    <li>-41001: encodingAesKey 非法</li>
     *    <li>-41003: aes 解密失败</li>
     *    <li>-41004: 解密后得到的buffer非法</li>
     *    <li>-41005: base64加密失败</li>
     *    <li>-41016: base64解密失败</li>
     * </ul>
     */
    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
    // 小程序
    public static $appid = 'wx3ada1e3ffa9ff8a7';  //小程序appid
    public static $secret = '4d1d1ba3fe249953f6bcfeae76f77234'; //小程序秘钥

    public $sessionKey ='';

    // 获取openId session-key 等
    public function getopenId($value='')
    {

        $code = I('post.code');
        $appid = self::$appid;
        $secret = self::$secret;
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='. $appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        $result = self::httpGet($url);
        $res = json_decode($result);
        $this->ajaxReturn($res);


    }

    // 获取小程序手机号api 接口，对应下面小程序 js
    public function getPhoneNumber($value='')
    {

        $encryptedData = I('post.encryptedData');
        $iv = I('post.iv');
        $openid = I('post.openid');
        $this->sessionKey=I('post.session_key');
        $res = $this->decryptData($encryptedData, $iv);
        $res = objecttoarray($res);
        Log::write(var_export($res, true));
        Log::write($res['phoneNumber']);
        if($res['phoneNumber']){
            
            // $res->phoneNumbe 就是手机号可以 写入数据库或者做其他操作
            $User = D('User');
            
            $userInfo = $User->getInfoByPhone($res['phoneNumber']);
            if ($userInfo) {
                $res['phoneNumber'] = $userInfo['phone'];
                $openid = $userInfo['open_id'];
                $user_id = $userInfo['id'];
            } else {
                $user_id = $User->add(array('phone' => $res['phoneNumber'], 'open_id' => $openid));
                if ($user_id === false) json_error(10107); // 数据库操作失败
            }
            
        }
        $res['user_id'] = $user_id;
        json_success($res);
//         $this->ajaxReturn(['data'=>$res,'status'=>'1']); //把手机号返回

    }

    // 小程序解密
    public function decryptData($encryptedData, $iv)
    {
//        
        Log::write('key : '.$this->sessionKey);
        if (strlen($this->sessionKey) != 24) {
            return self::$IllegalAesKey;
        }
        $aesKey=base64_decode($this->sessionKey);


        if (strlen($iv) != 24) {
            return self::$IllegalIv;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return self::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != self::$appid )
        {
            return self::$IllegalBuffer;
        }

        return  $dataObj;
        // return self::$OK;
    }


    function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }


}
